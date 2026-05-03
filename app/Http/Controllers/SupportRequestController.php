<?php
namespace App\Http\Controllers;

use App\Models\SupportRequest;
use Illuminate\Http\Request;

class SupportRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        abort_unless($user->isManagerOrAbove() && $user->company_id, 403);

        $requests = SupportRequest::where('company_id', $user->company_id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('support-requests.index', compact('requests'));
    }

    public function show(SupportRequest $supportRequest)
    {
        $user = auth()->user();
        abort_unless($user->isManagerOrAbove() && $supportRequest->company_id === $user->company_id, 403);

        $supportRequest->load(['notes.user', 'closedBy', 'user']);
        return view('support-requests.show', compact('supportRequest'));
    }

    public function create()
    {
        $user = auth()->user();
        abort_unless($user->isManagerOrAbove() && $user->company_id, 403);

        return view('support-requests.create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isManagerOrAbove() && $user->company_id, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'body'  => 'required|string|min:10',
        ]);

        SupportRequest::create([
            'company_id' => $user->company_id,
            'user_id'    => $user->id,
            'title'      => $validated['title'],
            'body'       => $validated['body'],
        ]);

        return redirect()->route('support-requests.index')
            ->with('success', 'Solicitação enviada com sucesso.');
    }

    public function addNote(Request $request, SupportRequest $supportRequest)
    {
        $user = auth()->user();
        abort_unless($user->isManagerOrAbove() && $supportRequest->company_id === $user->company_id, 403);
        abort_if($supportRequest->isClosed(), 403, 'Solicitação encerrada.');

        $validated = $request->validate([
            'body'      => 'required|string|min:2',
            'intensity' => 'nullable|integer|between:1,5',
        ]);

        $supportRequest->notes()->create([
            'user_id'   => $user->id,
            'body'      => $validated['body'],
            'intensity' => $validated['intensity'] ?? null,
        ]);

        return back()->with('success', 'Nota adicionada.');
    }

    public function close(Request $request, SupportRequest $supportRequest)
    {
        $user = auth()->user();
        abort_unless($user->isManagerOrAbove() && $supportRequest->company_id === $user->company_id, 403);
        abort_if($supportRequest->isClosed(), 403);

        $request->validate(['feedback' => 'nullable|integer|between:1,5']);

        $supportRequest->update([
            'status'    => 'feito',
            'closed_at' => now(),
            'closed_by' => $user->id,
            'feedback'  => $request->feedback ?: null,
        ]);

        return back()->with('success', 'Solicitação marcada como concluída.');
    }
}
