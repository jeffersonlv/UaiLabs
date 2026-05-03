<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    private function companyId() { return auth()->user()->company_id; }

    public function index(Request $request)
    {
        $user      = auth()->user();
        $search    = $request->input('search');
        $companies = null;
        $selectedCompanyId = null;

        if ($user->isSuperAdmin()) {
            $companies         = Company::orderBy('name')->get();
            $selectedCompanyId = $request->input('company_id');

            $activities = Activity::withoutGlobalScopes()
                ->with('category', 'unit')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->when($search, fn($q) => $q->where('title', 'like', "%$search%")
                    ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%$search%")))
                ->orderBy('title')
                ->paginate(15)
                ->withQueryString();
        } else {
            $activities = Activity::with('category', 'unit')
                ->when($search, fn($q) => $q->where('title', 'like', "%$search%")
                    ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%$search%")))
                ->orderBy('title')
                ->paginate(15)
                ->withQueryString();
        }

        return view('activities.index', compact('activities', 'search', 'companies', 'selectedCompanyId'));
    }

    public function create()
    {
        $categories = Category::where('active', true)->orderBy('name')->get();
        $units      = Unit::where('company_id', $this->companyId())->where('active', true)->orderBy('name')->get();
        return view('activities.form', ['activity' => new Activity, 'categories' => $categories, 'units' => $units]);
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:150', 'category_id' => 'required|exists:categories,id', 'periodicity' => 'required']);
        Activity::create($request->only('title','description','category_id','unit_id','periodicity','sequence_order') + [
            'company_id'        => $this->companyId(),
            'sequence_required' => $request->boolean('sequence_required'),
            'active'            => true,
            'created_by'        => auth()->id(),
        ]);
        return redirect()->route('activities.index')->with('success', 'Atividade criada.');
    }

    public function edit(Activity $activity)
    {
        abort_if($activity->company_id !== $this->companyId(), 403);
        $categories = Category::where('active', true)->orderBy('name')->get();
        $units      = Unit::where('company_id', $this->companyId())->where('active', true)->orderBy('name')->get();
        return view('activities.form', compact('activity', 'categories', 'units'));
    }

    public function update(Request $request, Activity $activity)
    {
        abort_if($activity->company_id !== $this->companyId(), 403);
        $request->validate(['title' => 'required|string|max:150', 'category_id' => 'required|exists:categories,id', 'periodicity' => 'required']);
        $activity->update($request->only('title','description','category_id','unit_id','periodicity','sequence_order') + [
            'sequence_required' => $request->boolean('sequence_required'),
            'active'            => $request->boolean('active'),
        ]);
        return redirect()->route('activities.index')->with('success', 'Atividade atualizada.');
    }

    public function destroy(Activity $activity)
    {
        abort_if($activity->company_id !== $this->companyId(), 403);
        $activity->update(['active' => false]);
        return redirect()->route('activities.index')->with('success', 'Atividade desativada.');
    }
}
