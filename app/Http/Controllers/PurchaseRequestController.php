<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequestRequest;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $active = PurchaseRequest::with('user', 'unit')
            ->whereIn('status', ['requested', 'ordered'])
            ->when($unitIds !== null, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->orderByDesc('created_at')
            ->get();

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        return view('purchase-requests.index', compact('active', 'units', 'user'));
    }

    public function store(StorePurchaseRequestRequest $request)
    {
        $user = auth()->user();

        $pr = PurchaseRequest::create($request->validated() + [
            'company_id' => $user->company_id,
            'user_id'    => $user->id,
            'status'     => 'requested',
        ]);

        AuditLogger::crud('purchase_request.created', 'purchase_request', $pr->id, $pr->product_name);
        return back()->with('success', 'Solicitação registrada.');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $this->authorizeAccess($purchaseRequest);
        return view('purchase-requests.show', compact('purchaseRequest'));
    }

    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        $this->authorizeAccess($purchaseRequest);

        $newStatus = $request->input('status');

        // Only admin/manager can change status (except requester cancelling own)
        if ($newStatus === 'cancelled') {
            abort_unless($purchaseRequest->canBeCancelledBy($user), 403);
        } else {
            abort_unless($user->isManagerOrAbove(), 403);
            abort_unless(in_array($newStatus, ['ordered', 'purchased']), 422);
        }

        $old = $purchaseRequest->status;
        $purchaseRequest->update([
            'status'            => $newStatus,
            'status_changed_by' => $user->id,
            'status_changed_at' => now(),
        ]);

        AuditLogger::crud("purchase_request.{$newStatus}", 'purchase_request', $purchaseRequest->id, $purchaseRequest->product_name, [
            'de' => $old, 'para' => $newStatus,
        ]);

        return back()->with('success', 'Status atualizado.');
    }

    public function history()
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $history = PurchaseRequest::with('user', 'unit', 'statusChangedBy')
            ->whereIn('status', ['purchased', 'cancelled'])
            ->when($unitIds !== null, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->orderByDesc('status_changed_at')
            ->paginate(30);

        return view('purchase-requests.history', compact('history', 'user'));
    }

    private function authorizeAccess(PurchaseRequest $pr): void
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();
        if ($unitIds !== null) {
            abort_unless(in_array($pr->unit_id, $unitIds), 403);
        } else {
            abort_if($pr->company_id !== $user->company_id, 403);
        }
    }
}