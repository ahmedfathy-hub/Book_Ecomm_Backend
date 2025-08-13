<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index(Request $request)
    {
        $orders = Order::with('items.product')->where('user_id', $request->user()->id) ->get();
        
        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'payment_method' => 'required|string',
            'shipping_address' => 'required|array',
            'billing_address' => 'required|array'
        ]);

        $cart = Cart::with('items.product')->find($request->cart_id);

        // Calculate total
        $total = $cart->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        // Create order
        $order = Order::create([
            'user_id' => $request->user()->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'total' => $total,
            'payment_method' => $request->payment_method,
            'shipping_address' => $request->shipping_address,
            'billing_address' => $request->billing_address
        ]);

        // Create order items
        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price
            ]);
        }

        // Clear cart
        $cart->items()->delete();

        return response()->json($order->load('items.product'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);
        return response()->json($order->load('items.product', 'payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
