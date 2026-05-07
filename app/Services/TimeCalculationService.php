<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimeCalculationService
{
    /**
     * Calculate worked, scheduled, regular, and overtime minutes for a user in a period.
     *
     * @return array{
     *   worked_minutes: int,
     *   scheduled_minutes: int,
     *   regular_minutes: int,
     *   overtime_minutes: int,
     *   breakdown: array<string, array{worked_minutes: int, scheduled_minutes: int, regular_minutes: int, overtime_minutes: int, has_open_pair: bool}>
     * }
     */
    public function calculateForPeriod(User $user, $startDate, $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('recorded_at', [$start, $end])
            ->where('type', '!=', 'correction')
            ->orderBy('recorded_at')
            ->get();

        $shifts = Shift::where('user_id', $user->id)
            ->where('type', 'work')
            ->where(fn($q) => $q->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('end_at', [$start, $end]))
            ->get();

        $breakdown = [];
        $period = Carbon::parse($startDate)->startOfDay();

        while ($period->lte($end)) {
            $dateKey = $period->toDateString();
            $dayEntries = $entries->filter(fn($e) => Carbon::parse($e->recorded_at)->toDateString() === $dateKey);
            $dayShifts  = $shifts->filter(fn($s) =>
                Carbon::parse($s->start_at)->toDateString() === $dateKey
                || Carbon::parse($s->end_at)->toDateString() === $dateKey
            );

            $breakdown[$dateKey] = $this->calculateDay($dayEntries, $dayShifts);
            $period->addDay();
        }

        $totals = array_reduce($breakdown, fn($carry, $day) => [
            'worked_minutes'    => $carry['worked_minutes']    + $day['worked_minutes'],
            'scheduled_minutes' => $carry['scheduled_minutes'] + $day['scheduled_minutes'],
            'regular_minutes'   => $carry['regular_minutes']   + $day['regular_minutes'],
            'overtime_minutes'  => $carry['overtime_minutes']  + $day['overtime_minutes'],
        ], ['worked_minutes' => 0, 'scheduled_minutes' => 0, 'regular_minutes' => 0, 'overtime_minutes' => 0]);

        return array_merge($totals, ['breakdown' => $breakdown]);
    }

    private function calculateDay(Collection $entries, Collection $shifts): array
    {
        $scheduledMinutes = $shifts->sum(fn($s) => Carbon::parse($s->start_at)->diffInMinutes(Carbon::parse($s->end_at)));

        // Pair clock_in / clock_out
        $workedMinutes = 0;
        $hasOpenPair   = false;
        $clockIns      = $entries->where('type', 'clock_in')->values();
        $clockOuts     = $entries->where('type', 'clock_out')->values();

        foreach ($clockIns as $i => $clockIn) {
            $clockOut = $clockOuts->first(fn($o) => Carbon::parse($o->recorded_at)->gt(Carbon::parse($clockIn->recorded_at)));
            if ($clockOut) {
                $workedMinutes += Carbon::parse($clockIn->recorded_at)->diffInMinutes(Carbon::parse($clockOut->recorded_at));
                $clockOuts = $clockOuts->reject(fn($o) => $o->id === $clockOut->id)->values();
            } else {
                $hasOpenPair = true;
            }
        }

        $regularMinutes  = min($workedMinutes, $scheduledMinutes);
        $overtimeMinutes = max(0, $workedMinutes - $scheduledMinutes);

        return compact('workedMinutes', 'scheduledMinutes', 'regularMinutes', 'overtimeMinutes', 'hasOpenPair') +
            ['worked_minutes' => $workedMinutes, 'scheduled_minutes' => $scheduledMinutes,
             'regular_minutes' => $regularMinutes, 'overtime_minutes' => $overtimeMinutes,
             'has_open_pair' => $hasOpenPair];
    }

    public static function formatMinutes(int $minutes): string
    {
        $h = intdiv(abs($minutes), 60);
        $m = abs($minutes) % 60;
        $sign = $minutes < 0 ? '-' : '';
        return "{$sign}{$h}h" . ($m > 0 ? " {$m}min" : '');
    }
}