<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        AuditLog::record('category_created', $category, null, $category->toArray());

        return redirect()->route('categories.index')->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => "required|string|max:100|unique:categories,name,{$id}",
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $category->toArray();
        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::record('category_updated', $category, $oldValues, $category->toArray());

        return redirect()->route('categories.index')->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $category = Category::withCount('products')->findOrFail($id);

        if ($category->products_count > 0) {
            return back()->with('error', "Kategori '{$category->name}' tidak dapat dihapus karena masih memiliki {$category->products_count} produk terdaftar.");
        }

        $oldValues = $category->toArray();
        $category->delete();

        AuditLog::record('category_deleted', null, $oldValues, null);

        return redirect()->route('categories.index')->with('success', "Kategori berhasil dihapus.");
    }
}
