<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\Request;

class SupportRequestAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $query = SupportRequest::with(['user', 'company'])
            ->orderByDesc('important')
            ->orderByRaw('CASE WHEN priority IS NULL THEN 999 ELSE priority END')
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15)->withQueryString();

        $counts = SupportRequest::selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalAll       = SupportRequest::count();
        $totalImportant = SupportRequest::where('important', true)->count();

        return view('admin.support-requests.index', compact(
            'requests', 'status', 'counts', 'totalAll', 'totalImportant'
        ));
    }

    public function show(SupportRequest $supportRequest)
    {
        $supportRequest->load(['user', 'company', 'notes.user', 'closedBy']);
        return view('admin.support-requests.show', compact('supportRequest'));
    }

    public function update(Request $request, SupportRequest $supportRequest)
    {
        $validated = $request->validate([
            'status'          => 'sometimes|required|in:avaliar,fazer,perguntar,feito',
            'priority'        => 'sometimes|nullable|in:1,2,3',
            'superadmin_note' => 'sometimes|nullable|string|max:2000',
        ]);

        if (isset($validated['priority']) && $validated['priority'] === '') {
            $validated['priority'] = null;
        }

        $supportRequest->update($validated);

        return back()->with('success', 'Solicitação atualizada.');
    }

    public function toggleImportant(SupportRequest $supportRequest)
    {
        $supportRequest->update(['important' => ! $supportRequest->important]);
        return back();
    }

    public function addNote(Request $request, SupportRequest $supportRequest)
    {
        abort_if($supportRequest->isClosed(), 403, 'Solicitação encerrada.');

        $validated = $request->validate([
            'body'      => 'required|string|min:2',
            'intensity' => 'nullable|integer|between:1,5',
        ]);

        $supportRequest->notes()->create([
            'user_id'   => auth()->id(),
            'body'      => $validated['body'],
            'intensity' => $validated['intensity'] ?? null,
        ]);

        return back()->with('success', 'Nota adicionada.');
    }

    public function close(Request $request, SupportRequest $supportRequest)
    {
        abort_if($supportRequest->isClosed(), 403);

        $request->validate(['feedback' => 'nullable|integer|between:1,5']);

        $supportRequest->update([
            'status'    => 'feito',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
            'feedback'  => $request->feedback ?: null,
        ]);

        return back()->with('success', 'Solicitação concluída.');
    }
}
