<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::with('items.product')
            ->where('user_id', Auth::id())
            ->firstOrCreate(['user_id' => Auth::id()]);

        return response()->json($cart);
    }

     public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::firstOrCreate(
            ['user_id' => Auth::id()],
            ['session_id' => session()->getId()]
        );

        $cartItem = CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        return response()->json([
            'message' => 'Product added to cart',
            'cart_item' => $cartItem->load('product'),
        ], 201);
    }

     public function removeItem($id)
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartItem = $cart->items()->where('id', $id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in cart'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    public function clearCart()
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cart->items()->delete();

        return response()->json(['message' => 'Cart cleared']);
    }
}
