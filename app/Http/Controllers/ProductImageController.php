<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function setMainImage($id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Reset all main images for this product
        ProductImage::where('product_id', $image->product_id)
            ->update(['is_main' => false]);
            
        // Set this one as main
        $image->update(['is_main' => true]);
        
        return response()->json(['message' => 'Main image updated successfully']);
    }

    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Don't allow deleting if it's the only image
        if ($image->is_main && $image->product->images()->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the only image of a product'
            ], 422);
        }
        
        // Delete from storage
        Storage::disk('public')->delete($image->image_path);
        
        $image->delete();
        
        // If we deleted the main image, set a new one
        if ($image->is_main) {
            $newMain = $image->product->images()->first();
            if ($newMain) {
                $newMain->update(['is_main' => true]);
            }
        }
        
        return response()->json(['message' => 'Image deleted successfully']);
    }
}
