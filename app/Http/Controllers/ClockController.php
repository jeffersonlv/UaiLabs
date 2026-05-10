<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ClockController extends Controller
{
    public function show(Request $request)
    {
        $authUser = auth()->user();
        $units    = null;

        if ($authUser) {
            $unitIds = $authUser->visibleUnitIds();
            $units = $unitIds !== null
                ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
                : Unit::where('company_id', $authUser->company_id)->where('active', true)->orderBy('name')->get();
        }

        return view('clock', compact('authUser', 'units'));
    }

    public function punch(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            // Unauthenticated: require username + pin
            $request->validate([
                'username' => 'required|string',
                'pin'      => 'required|string',
                'unit_id'  => 'required|exists:units,id',
            ]);
            $user = User::where('username', $request->username)->where('active', true)->first();
            if (! $user || ! Hash::check($request->pin, (string) $user->pin)) {
                return back()->withErrors(['pin' => 'Usuário ou PIN inválido.']);
            }
        } else {
            // Authenticated: require pin only
            $request->validate([
                'pin'     => 'required|string',
                'unit_id' => 'required|exists:units,id',
            ]);
            if (! Hash::check($request->pin, (string) $user->pin)) {
                return back()->withErrors(['pin' => 'PIN inválido.']);
            }
        }

        $unitId = (int) $request->unit_id;

        // Determine type based on last entry
        $last = TimeEntry::where('user_id', $user->id)
            ->whereDate('recorded_at', Carbon::today())
            ->where('type', '!=', 'correction')
            ->orderByDesc('recorded_at')
            ->first();

        $type = (! $last || $last->type === 'clock_out') ? 'clock_in' : 'clock_out';

        $entry = TimeEntry::create([
            'company_id'  => $user->company_id,
            'user_id'     => $user->id,
            'unit_id'     => $unitId,
            'type'        => $type,
            'recorded_at' => now(),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        $label   = $type === 'clock_in' ? 'Entrada' : 'Saída';
        $time    = $entry->recorded_at->format('H:i');
        $message = "{$label} registrada às {$time}";

        if ($type === 'clock_out') {
            // Calculate today's worked time
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
            $message .= ". Total hoje: {$h}h" . ($m > 0 ? " {$m}min" : '');
        }

        return back()->with('clock_message', $message)->with('clock_type', $type);
    }

    /**
     * Credential-based punch for the Ponto tab on the login page.
     * Authenticates with username + password (no PIN), then registers clock_in or clock_out.
     * Returns JSON so the frontend can show the result without a page reload.
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

        $unitId = $request->unit_id;

        if (! $unitId) {
            // Admins/superadmins see all company units; staff see only assigned units
            $unitIds = $user->visibleUnitIds();
            $units   = $unitIds !== null
                ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get(['id', 'name'])
                : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get(['id', 'name']);

            if ($units->isEmpty()) {
                return response()->json(['error' => 'Nenhuma unidade ativa encontrada para este usuário.'], 422);
            }

            if ($units->count() > 1) {
                return response()->json([
                    'needs_unit' => true,
                    'units'      => $units,
                    'user_name'  => $user->name,
                ]);
            }

            $unitId = $units->first()->id;
        }

        if (! Unit::where('id', $unitId)->where('active', true)->exists()) {
            return response()->json(['error' => 'Unidade inválida.'], 422);
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
            'unit_id'     => $unitId,
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

    /** Returns units for a username (public endpoint for login page clock form). */
    public function userUnits(Request $request)
    {
        $user = User::where('username', $request->username)->where('active', true)->first();
        if (! $user) {
            return response()->json([]);
        }
        $units = $user->units()->where('active', true)->orderBy('name')->get(['id', 'name']);
        return response()->json($units);
    }

    public function pinEdit()
    {
        return view('profile.pin-edit');
    }

    public function pinUpdate(Request $request)
    {
        $request->validate([
            'pin'              => ['required', 'string', 'min:4', 'max:6', 'regex:/^\d+$/', 'confirmed'],
            'pin_confirmation' => 'required',
        ]);

        $user = auth()->user();

        // Unique PIN per company (excluding self)
        $exists = User::where('company_id', $user->company_id)
            ->where('id', '!=', $user->id)
            ->get()
            ->first(fn($u) => $u->pin && Hash::check($request->pin, $u->pin));

        if ($exists) {
            return back()->withErrors(['pin' => 'Este PIN já está em uso por outro usuário. Escolha outro.']);
        }

        $user->update([
            'pin'                => Hash::make($request->pin),
            'pin_reset_required' => false,
        ]);

        return back()->with('success', 'PIN atualizado com sucesso.');
    }
}