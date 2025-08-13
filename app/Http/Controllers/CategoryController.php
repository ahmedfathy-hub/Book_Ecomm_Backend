<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
       $this->middleware('auth:sanctum')->except(['index', 'show']);
        
        $this->middleware(function ($request, $next) {
            if ($request->user()->role->name !== 'admin') {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        })->except(['index', 'show']); // Apply to all methods except these
    }
    
    public function index()
    {
        $categories = Category::with('parent', 'children')->get();
        return response()->json($categories);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'parent_id' => $request->parent_id
        ]);

        return response()->json($category, 201);
    }

    
    public function show(string $id)
    {
        $category = Category::with('parent', 'children', 'products')->findOrFail($id);
        return response()->json($category);
    }

    
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category->update([
            'name' => $request->name ?? $category->name,
            'slug' => $request->name ? Str::slug($request->name) : $category->slug,
            'description' => $request->description ?? $category->description,
            'parent_id' => $request->parent_id ?? $category->parent_id
        ]);

        return response()->json($category);
    }

    
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        
        if ($category->products()->count() > 0 || $category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with products or subcategories'
            ], 422);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
