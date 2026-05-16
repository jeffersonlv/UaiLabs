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

        $stats = $this->buildStats($user->company_id, $today);

        return view('purchase-items.index', compact('recent', 'oldPending', 'days', 'units', 'filter', 'stats'));
    }

    public function suggestions(Request $request)
    {
        $q    = $request->input('q', '');
        $user = auth()->user();

        $names = PurchaseItem::where('company_id', $user->company_id)
            ->when($q, fn($query) => $query->where('name', 'like', $q . '%'))
            ->orderBy('name')
            ->distinct()
            ->pluck('name')
            ->take(20);

        return response()->json($names);
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

    private function buildStats(int $companyId, Carbon $today): array
    {
        $weekAgo     = $today->copy()->subDays(6);
        $monthAgo    = $today->copy()->subDays(29);
        $quarterAgo  = $today->copy()->subDays(89);

        // All items in the last 90 days to compute counts and intervals
        $all = PurchaseItem::where('company_id', $companyId)
            ->where('requested_at', '>=', $quarterAgo)
            ->orderBy('name')
            ->orderBy('requested_at')
            ->get(['name', 'requested_at']);

        $grouped = $all->groupBy('name');

        $rows = [];
        foreach ($grouped as $name => $items) {
            $dates = $items->pluck('requested_at')->map(fn($d) => Carbon::parse($d)->toDateString())->unique()->sort()->values();

            $weekCount    = $items->filter(fn($i) => Carbon::parse($i->requested_at)->gte($weekAgo))->count();
            $monthCount   = $items->filter(fn($i) => Carbon::parse($i->requested_at)->gte($monthAgo))->count();
            $quarterCount = $items->count();

            // Average interval between consecutive dates
            $avgDays = null;
            $nextDate = null;
            if ($dates->count() >= 2) {
                $intervals = [];
                for ($i = 1; $i < $dates->count(); $i++) {
                    $intervals[] = Carbon::parse($dates[$i - 1])->diffInDays(Carbon::parse($dates[$i]));
                }
                $avgDays = round(array_sum($intervals) / count($intervals));

                $lastDate = Carbon::parse($dates->last());
                $nextDate = $lastDate->copy()->addDays($avgDays);
            }

            $rows[] = [
                'name'          => $name,
                'week'          => $weekCount,
                'month'         => $monthCount,
                'quarter'       => $quarterCount,
                'avg_days'      => $avgDays,
                'next_date'     => $nextDate,
                'days_until'    => $nextDate ? (int) $today->diffInDays($nextDate, false) : null,
            ];
        }

        // Sort by quarter count desc
        usort($rows, fn($a, $b) => $b['quarter'] <=> $a['quarter']);

        return $rows;
    }
}
