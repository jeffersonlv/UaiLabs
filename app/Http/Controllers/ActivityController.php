<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Company;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    private function companyId() { return auth()->user()->company_id; }

    public function index(Request $request)
    {
        $user              = auth()->user();
        $search            = $request->input('search');
        $companies         = null;
        $selectedCompanyId = null;

        if ($user->isSuperAdmin()) {
            $companies         = Company::orderBy('name')->get();
            $selectedCompanyId = $request->input('company_id');

            $activities = Activity::withoutGlobalScopes()
                ->with('category', 'subcategory', 'units')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->when($search, fn($q) => $q->where('title', 'like', "%$search%")
                    ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%$search%")))
                ->orderBy('title')
                ->paginate(15)
                ->withQueryString();
        } else {
            $activities = Activity::with('category', 'subcategory', 'units')
                ->when($search, fn($q) => $q->where('title', 'like', "%$search%")
                    ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%$search%")))
                ->orderBy('title')
                ->paginate(15)
                ->withQueryString();
        }

        $units = $user->isSuperAdmin() ? collect() : Unit::where('company_id', $this->companyId())->where('active', true)->orderBy('name')->get();

        return view('activities.index', compact('activities', 'search', 'companies', 'selectedCompanyId', 'units'));
    }

    public function create()
    {
        $categories    = Category::where('active', true)->orderBy('name')->get();
        $subcategories = Subcategory::orderBy('name')->get();
        $units         = Unit::where('company_id', $this->companyId())->where('active', true)->orderBy('name')->get();
        return view('activities.form', [
            'activity'      => new Activity,
            'categories'    => $categories,
            'subcategories' => $subcategories,
            'units'         => $units,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:150',
            'category_id'    => 'required|exists:categories,id',
            'periodicity'    => 'required',
            'unit_ids'       => 'nullable|array',
            'unit_ids.*'     => 'exists:units,id',
        ]);

        $act = Activity::create($request->only('title', 'description', 'category_id', 'subcategory_id', 'periodicity', 'sequence_order') + [
            'company_id'        => $this->companyId(),
            'sequence_required' => $request->boolean('sequence_required'),
            'active'            => true,
            'created_by'        => auth()->id(),
        ]);

        $this->syncUnits($act, $request);

        AuditLogger::crud('activity.created', 'activity', $act->id, $act->title);
        return redirect()->route('activities.index')->with('success', 'Atividade criada.');
    }

    public function edit(Activity $activity)
    {
        abort_if($activity->company_id !== $this->companyId(), 403);
        $categories    = Category::where('active', true)->orderBy('name')->get();
        $subcategories = Subcategory::orderBy('name')->get();
        $units         = Unit::where('company_id', $this->companyId())->where('active', true)->orderBy('name')->get();
        return view('activities.form', compact('activity', 'categories', 'subcategories', 'units'));
    }

    public function update(Request $request, Activity $activity)
    {
        abort_if($activity->company_id !== $this->companyId(), 403);
        $request->validate([
            'title'       => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
            'periodicity' => 'required',
            'unit_ids'    => 'nullable|array',
            'unit_ids.*'  => 'exists:units,id',
        ]);

        $activity->update($request->only('title', 'description', 'category_id', 'subcategory_id', 'periodicity', 'sequence_order') + [
            'sequence_required' => $request->boolean('sequence_required'),
            'active'            => $request->boolean('active'),
        ]);

        $this->syncUnits($activity, $request);

        AuditLogger::crud('activity.updated', 'activity', $activity->id, $activity->title);
        return redirect()->route('activities.index')->with('success', 'Atividade atualizada.');
    }

    public function destroy(Activity $activity)
    {
        abort_if($activity->company_id !== $this->companyId(), 403);
        $activity->update(['active' => false]);
        AuditLogger::crud('activity.disabled', 'activity', $activity->id, $activity->title);
        return redirect()->route('activities.index')->with('success', 'Atividade desativada.');
    }

    /** Spreadsheet listing view. */
    public function spreadsheet()
    {
        $categories = Category::where('active', true)->orderBy('name')->get();
        $units      = Unit::where('company_id', $this->companyId())->where('active', true)->orderBy('name')->get();
        $activities = Activity::with('category', 'units')->orderBy('title')->get();
        return view('activities.spreadsheet', compact('activities', 'categories', 'units'));
    }

    /** Bulk save from spreadsheet. */
    public function bulkSave(Request $request)
    {
        $request->validate(['rows' => 'required|array']);
        $errors   = [];
        $saved    = 0;
        $companyId = $this->companyId();

        foreach ($request->rows as $idx => $row) {
            $validator = \Illuminate\Support\Facades\Validator::make($row, [
                'title'       => 'required|string|max:150',
                'category_id' => 'required|exists:categories,id',
                'periodicity' => 'required',
            ]);

            if ($validator->fails()) {
                $errors[$idx] = $validator->errors()->all();
                continue;
            }

            if (! empty($row['id'])) {
                $act = Activity::find($row['id']);
                if ($act && $act->company_id === $companyId) {
                    $act->update(array_intersect_key($row, array_flip(['title', 'description', 'category_id', 'periodicity', 'sequence_order', 'active'])));
                }
            } else {
                Activity::create(array_intersect_key($row, array_flip(['title', 'description', 'category_id', 'periodicity', 'sequence_order'])) + [
                    'company_id'        => $companyId,
                    'sequence_required' => false,
                    'active'            => true,
                    'created_by'        => auth()->id(),
                ]);
            }
            $saved++;
        }

        return response()->json(['saved' => $saved, 'errors' => $errors]);
    }

    public function assignUnits(Request $request, Activity $activity)
    {
        abort_if($activity->company_id !== $this->companyId(), 403);
        $request->validate(['unit_ids' => 'nullable|array', 'unit_ids.*' => 'exists:units,id']);

        $activity->units()->sync($request->input('unit_ids', []));
        AuditLogger::crud('activity.units_updated', 'activity', $activity->id, $activity->title);

        return response()->json(['ok' => true, 'units' => $activity->units()->pluck('name')]);
    }

    private function syncUnits(Activity $activity, Request $request): void
    {
        $allUnits = $request->boolean('all_units');
        if ($allUnits) {
            $unitIds = Unit::where('company_id', $activity->company_id)->where('active', true)->pluck('id')->toArray();
        } else {
            $unitIds = $request->input('unit_ids', []);
        }
        $activity->units()->sync($unitIds);
    }
}