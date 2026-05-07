<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $subcategories = Subcategory::with('category')
            ->orderBy('category_id')
            ->orderBy('order')
            ->paginate(20)
            ->withQueryString();

        return view('subcategories.index', compact('subcategories'));
    }

    public function create()
    {
        $categories = Category::where('active', true)->orderBy('name')->get();
        return view('subcategories.form', ['subcategory' => new Subcategory, 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:100',
            'order'       => 'nullable|integer|min:0',
        ]);

        $sub = Subcategory::create($request->only('category_id', 'name', 'order') + [
            'company_id' => auth()->user()->company_id,
        ]);

        AuditLogger::crud('subcategory.created', 'subcategory', $sub->id, $sub->name);
        return redirect()->route('subcategories.index')->with('success', 'Subcategoria criada.');
    }

    public function edit(Subcategory $subcategory)
    {
        abort_if($subcategory->company_id !== auth()->user()->company_id, 403);
        $categories = Category::where('active', true)->orderBy('name')->get();
        return view('subcategories.form', compact('subcategory', 'categories'));
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        abort_if($subcategory->company_id !== auth()->user()->company_id, 403);
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:100',
            'order'       => 'nullable|integer|min:0',
        ]);

        $subcategory->update($request->only('category_id', 'name', 'order'));
        AuditLogger::crud('subcategory.updated', 'subcategory', $subcategory->id, $subcategory->name);
        return redirect()->route('subcategories.index')->with('success', 'Subcategoria atualizada.');
    }

    public function destroy(Subcategory $subcategory)
    {
        abort_if($subcategory->company_id !== auth()->user()->company_id, 403);
        AuditLogger::crud('subcategory.deleted', 'subcategory', $subcategory->id, $subcategory->name);
        $subcategory->delete();
        return redirect()->route('subcategories.index')->with('success', 'Subcategoria removida.');
    }
}