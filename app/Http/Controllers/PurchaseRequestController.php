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
            ->whereIn('status', PurchaseRequest::ACTIVE_STATUSES)
            ->when($unitIds !== null, fn($q) => $q->where(fn($q2) =>
                $q2->whereIn('unit_id', $unitIds)->orWhereNull('unit_id')
            ))
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
        $user      = auth()->user();
        $this->authorizeAccess($purchaseRequest);
        $newStatus = $request->input('status');

        abort_unless($purchaseRequest->isActive(), 422);
        abort_unless(in_array($newStatus, ['ordered', 'purchased', 'received', 'cancelled']), 422);

        if ($newStatus === 'cancelled') {
            $request->validate([
                'cancel_reason'        => 'required|in:ja_comprado,nao_necessario,personalizado',
                'cancel_reason_custom' => 'required_if:cancel_reason,personalizado|nullable|string|max:200',
            ]);
            $reason = $request->cancel_reason === 'personalizado'
                ? $request->cancel_reason_custom
                : \App\Models\PurchaseRequest::CANCEL_REASONS[$request->cancel_reason];
        } else {
            $reason = null;
        }

        $old = $purchaseRequest->status;
        $purchaseRequest->update([
            'status'               => $newStatus,
            'status_changed_by'    => $user->id,
            'status_changed_at'    => now(),
            'cancellation_reason'  => $reason,
        ]);

        AuditLogger::crud("purchase_request.{$newStatus}", 'purchase_request', $purchaseRequest->id, $purchaseRequest->product_name, [
            'de' => $old, 'para' => $newStatus, 'motivo' => $reason,
        ]);

        return back()->with('success', 'Status atualizado.');
    }

    public function history()
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $history = PurchaseRequest::with('user', 'unit', 'statusChangedBy')
            ->whereIn('status', PurchaseRequest::DONE_STATUSES)
            ->when($unitIds !== null, fn($q) => $q->where(fn($q2) =>
                $q2->whereIn('unit_id', $unitIds)->orWhereNull('unit_id')
            ))
            ->orderByDesc('status_changed_at')
            ->paginate(30);

        return view('purchase-requests.history', compact('history', 'user'));
    }

    private function authorizeAccess(PurchaseRequest $pr): void
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();
        if ($unitIds !== null) {
            abort_unless($pr->unit_id === null || in_array($pr->unit_id, $unitIds), 403);
        } else {
            abort_if($pr->company_id !== $user->company_id, 403);
        }
    }
}