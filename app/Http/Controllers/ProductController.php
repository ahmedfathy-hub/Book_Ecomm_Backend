<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
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
    public function index(Request $request)
    {
        $query = Product::with(['category', 'seller', 'images'])
            ->where('status', 'active');

        // Search
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort, $direction);

        $products = $query->paginate(10);

        return response()->json($products);
    }

    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::create([
            'seller_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'status' => 'active'
        ]);

        // Handle image upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('productimages', 'public');
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_main' => false
                ]);
            }
            
            // Set first image as main if none exists
            if ($product->images()->count() > 0 && !$product->images()->where('is_main', true)->exists()) {
                $product->images()->first()->update(['is_main' => true]);
            }
        }

        return response()->json($product->load('category', 'seller', 'images'), 201);

        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with(['category', 'seller', 'images'])->findOrFail($id);
        return response()->json($product);
    }

   
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        // Authorization - only seller or admin can update
        if ($request->user()->role_id != 1 && $product->seller_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'status' => 'sometimes|in:active,inactive',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product->update([
            'name' => $request->name ?? $product->name,
            'slug' => $request->name ? Str::slug($request->name) : $product->slug,
            'description' => $request->description ?? $product->description,
            'price' => $request->price ?? $product->price,
            'stock' => $request->stock ?? $product->stock,
            'category_id' => $request->category_id ?? $product->category_id,
            'status' => $request->status ?? $product->status
        ]);

        // Handle new image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('productimages', 'public');
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_main' => false
                ]);
            }
        }

        return response()->json($product->fresh()->load('category', 'seller', 'images'));
    }

    
    public function destroy(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        // Authorization - only seller or admin can delete
        if ($request->user()->role_id != 1 && $product->seller_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
