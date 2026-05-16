<?php

namespace App\Http\Controllers;

use App\Models\BoardAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BoardAllocationController extends Controller
{
    public function index(Request $request)
    {
        $user      = auth()->user();
        $weekParam = $request->input('week', Carbon::today()->format('o-\WW'));
        $weekStart = Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $unitId    = $request->input('unit_id') ? (int) $request->input('unit_id') : null;

        $allocations = BoardAllocation::with(['user'])
            ->where('company_id', $user->company_id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->get();

        $data = [];
        foreach ($allocations as $alloc) {
            $data[$alloc->period][$alloc->station_id][$alloc->date->toDateString()][] = [
                'id'      => $alloc->id,
                'name'    => $alloc->user->name,
                'user_id' => $alloc->user_id,
            ];
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);

        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'station_id' => 'required|exists:stations,id',
            'date'       => 'required|date',
            'period'     => 'required|in:manha,tarde,noite',
            'unit_id'    => 'nullable|exists:units,id',
        ]);

        $user = auth()->user();

        $alloc = BoardAllocation::firstOrCreate(
            [
                'company_id' => $user->company_id,
                'user_id'    => $data['user_id'],
                'station_id' => $data['station_id'],
                'date'       => $data['date'],
                'period'     => $data['period'],
            ],
            [
                'unit_id'    => $data['unit_id'] ?? null,
                'created_by' => $user->id,
            ]
        );

        return response()->json(['ok' => true, 'id' => $alloc->id, 'name' => $alloc->user->name]);
    }

    public function destroy(BoardAllocation $boardAllocation)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        abort_unless($boardAllocation->company_id === auth()->user()->company_id, 403);

        $boardAllocation->delete();
        return response()->json(['ok' => true]);
    }
}
