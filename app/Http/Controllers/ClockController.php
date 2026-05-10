<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ClockController extends Controller
{
    /**
     * Punch via username + password — no unit required.
     * Used by the Ponto tab on the login page (unauthenticated, AJAX).
     */
    public function credentialPunch(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->where('active', true)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Usuário ou senha inválidos.'], 422);
        }

        $last = TimeEntry::where('user_id', $user->id)
            ->whereDate('recorded_at', Carbon::today())
            ->where('type', '!=', 'correction')
            ->orderByDesc('recorded_at')
            ->first();

        $type = (! $last || $last->type === 'clock_out') ? 'clock_in' : 'clock_out';

        $entry = TimeEntry::create([
            'company_id'  => $user->company_id,
            'user_id'     => $user->id,
            'unit_id'     => null,
            'type'        => $type,
            'recorded_at' => now(),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        $time  = $entry->recorded_at->format('H:i');
        $label = $type === 'clock_in' ? 'Entrada' : 'Saída';

        $response = [
            'type'    => $type,
            'time'    => $time,
            'message' => "{$label} registrada às {$time}",
            'name'    => $user->name,
        ];

        if ($type === 'clock_out') {
            $todayEntries = TimeEntry::where('user_id', $user->id)
                ->whereDate('recorded_at', Carbon::today())
                ->where('type', '!=', 'correction')
                ->orderBy('recorded_at')
                ->get();

            $minutes = 0;
            $ins     = $todayEntries->where('type', 'clock_in')->values();
            $outs    = $todayEntries->where('type', 'clock_out')->values();

            foreach ($ins as $i => $in) {
                $out = $outs->get($i);
                if ($out) {
                    $minutes += Carbon::parse($in->recorded_at)->diffInMinutes(Carbon::parse($out->recorded_at));
                }
            }

            $h = intdiv($minutes, 60);
            $m = $minutes % 60;

            $lastIn = $ins->last();

            $response['clock_in_time'] = $lastIn ? Carbon::parse($lastIn->recorded_at)->format('H:i') : null;
            $response['worked']        = "{$h}h" . ($m > 0 ? " {$m}min" : '');
        }

        return response()->json($response);
    }
}
