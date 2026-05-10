<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
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

            $categories = Category::withoutGlobalScopes()
                ->withCount('activities')
                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                ->when($search, fn($q) => $q->where('name', 'like', "%$search%"))
                ->orderBy('order')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        } else {
            $categories = Category::withCount('activities')
                ->when($search, fn($q) => $q->where('name', 'like', "%$search%"))
                ->orderBy('order')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        }

        return view('categories.index', compact('categories', 'search', 'companies', 'selectedCompanyId'));
    }

    public function create() { return view('categories.form', ['category' => new Category]); }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100',
                Rule::unique('categories')->where('company_id', $this->companyId())],
        ], ['name.unique' => 'Já existe uma categoria com este nome.']);
        $cat = Category::create(['name' => $request->name, 'description' => $request->description, 'company_id' => $this->companyId(), 'active' => true]);
        AuditLogger::crud('category.created', 'category', $cat->id, $cat->name);
        return redirect()->route('categories.index')->with('success', 'Categoria criada.');
    }

    public function edit(Category $category)
    {
        abort_if($category->company_id !== $this->companyId(), 403);
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        abort_if($category->company_id !== $this->companyId(), 403);
        $request->validate([
            'name' => ['required', 'string', 'max:100',
                Rule::unique('categories')->where('company_id', $this->companyId())->ignore($category)],
        ], ['name.unique' => 'Já existe uma categoria com este nome.']);
        $category->update(['name' => $request->name, 'description' => $request->description, 'active' => $request->boolean('active')]);
        AuditLogger::crud('category.updated', 'category', $category->id, $category->name);
        return redirect()->route('categories.index')->with('success', 'Categoria atualizada.');
    }

    public function destroy(Category $category)
    {
        abort_if($category->company_id !== $this->companyId(), 403);
        $category->update(['active' => false]);
        AuditLogger::crud('category.disabled', 'category', $category->id, $category->name);
        return redirect()->route('categories.index')->with('success', 'Categoria desativada.');
    }

    /** Sort screen. */
    public function sort()
    {
        $categories = Category::with('subcategories')->where('active', true)->orderBy('order')->orderBy('name')->get();
        return view('categories.sort', compact('categories'));
    }

    /** Batch reorder (called via AJAX). */
    public function reorder(Request $request)
    {
        $request->validate(['items' => 'required|array']);
        $companyId = $this->companyId();

        foreach ($request->items as $item) {
            Category::where('id', $item['id'])->where('company_id', $companyId)->update(['order' => $item['order']]);

            if (! empty($item['subcategories'])) {
                foreach ($item['subcategories'] as $sub) {
                    \App\Models\Subcategory::where('id', $sub['id'])->where('company_id', $companyId)->update(['order' => $sub['order']]);
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}