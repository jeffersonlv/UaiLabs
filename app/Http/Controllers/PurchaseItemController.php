<?php

namespace App\Http\Controllers;

use App\Models\PurchaseItem;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PurchaseItemController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $filter = $request->input('filter', 'all');
        $today  = Carbon::today();
        $start  = $today->copy()->subDays(6);

        $recent = PurchaseItem::with('unit')
            ->whereBetween('requested_at', [$start, $today])
            ->when($filter === 'pending', fn($q) => $q->where('status', 'pending'))
            ->when($filter === 'done',    fn($q) => $q->where('status', 'done'))
            ->orderBy('id')
            ->get()
            ->groupBy(fn($i) => $i->requested_at->toDateString());

        $oldPending = PurchaseItem::with('unit')
            ->where('status', 'pending')
            ->where('requested_at', '<', $start)
            ->orderBy('requested_at')
            ->get();

        $days = collect();
        for ($d = $start->copy(); $d->lte($today); $d->addDay()) {
            $days->push($d->copy());
        }

        $units = Unit::where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('purchase-items.index', compact('recent', 'oldPending', 'days', 'units', 'filter'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $user = auth()->user();

        PurchaseItem::create([
            'company_id'   => $user->company_id,
            'unit_id'      => $request->unit_id ?: null,
            'created_by'   => $user->id,
            'name'         => $request->name,
            'status'       => 'pending',
            'requested_at' => Carbon::today(),
        ]);

        return back()->with('success', 'Item solicitado.');
    }

    public function toggle(PurchaseItem $purchaseItem)
    {
        $user = auth()->user();

        if ($purchaseItem->status === 'pending') {
            $purchaseItem->update([
                'status'  => 'done',
                'done_at' => now(),
                'done_by' => $user->id,
            ]);
        } else {
            $purchaseItem->update([
                'status'  => 'pending',
                'done_at' => null,
                'done_by' => null,
            ]);
        }

        return back();
    }
}
