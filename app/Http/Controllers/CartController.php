<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;


class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show($id)
    {
        $cart = Cart::with('items.product')->findOrFail($id);
        return response()->json($cart);
    }

    public function addItem(Request $request, $cartId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = CartItem::updateOrCreate(
            ['cart_id' => $cartId, 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        return response()->json($cartItem, 201);
    }

    public function removeItem($cartId, $itemId)
    {
        CartItem::where('cart_id', $cartId)->where('id', $itemId)->delete();

        return response()->json(null, 204);
    }
}
