<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['items.product', 'payments'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'payments'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'shipping_address' => 'required|string',
            'billing_address' => 'required|string',
        ]);

        $cart = Cart::with('items.product')->where('user_id', Auth::id())->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $total = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $order = Order::create([
            'user_id' => Auth::id(),
            'order_number' => (new Order)->generateOrderNumber(),
            'status' => Order::STATUS_PENDING,
            'total' => $total,
            'payment_method' => $request->payment_method,
            'shipping_address' => $request->shipping_address,
            'billing_address' => $request->billing_address,
        ]);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ]);
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_id' => (new Payment)->generateTransactionId(),
            'amount' => $total,
            'payment_method' => $request->payment_method,
            'status' => Payment::STATUS_PENDING,
        ]);

        $cart->items()->delete();

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load(['items.product', 'payments']),
        ], 201);
    }

    public function cancel($id)
    {
        $order = Order::where('user_id', Auth::id())
            ->where('status', Order::STATUS_PENDING)
            ->findOrFail($id);

        $order->update(['status' => Order::STATUS_CANCELLED]);

        $order->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->update(['status' => Payment::STATUS_FAILED]);

        return response()->json(['message' => 'Order cancelled']);
    }

    public function sellerIndex()
    {
        $sellerId = Auth::id();
        
        // Debug products
        $products = Product::where('seller_id', $sellerId)->get();
        logger('Seller Products:', $products->toArray());
        
        // Debug orders
        $orders = Order::with(['items.product'])
            ->whereHas('items.product', function($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->get();
        
        logger('Seller Orders Query:', [$orders->toArray()]);
        
        return response()->json([
            'success' => true,
            'data' => $orders,
            'debug' => [
                'seller_id' => $sellerId,
                'product_count' => $products->count()
            ]
        ]);
    }

    public function sellerShow($id)
    {
        $sellerId = Auth::id();
        
        $order = Order::with(['items.product', 'user', 'payments'])
            ->whereHas('items.product', function($query) use ($sellerId) {
                $query->where('user_id', $sellerId);
            })
            ->find($id);

        if (!$order) {
            $exists = Order::where('id', $id)->exists();
            
            if ($exists) {
                // Get the actual product IDs in the order that don't belong to seller
                $foreignProducts = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('order_items.order_id', $id)
                    ->where('products.seller_id', '!=', $sellerId)
                    ->pluck('products.id');
                    
                return response()->json([
                    'success' => false,
                    'message' => 'Order contains products from other sellers',
                    'foreign_products' => $foreignProducts
                ], 403);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function sellerUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:processing,completed,shipped'
        ]);

        $order = Order::whereHas('items.product', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->findOrFail($id);

        // Prevent updating cancelled or completed orders
        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update status of a cancelled order'
            ], 400);
        }

        if ($order->status === Order::STATUS_COMPLETED && $request->status !== Order::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change status from completed'
            ], 400);
        }

        $order->update(['status' => $request->status]);

        // // Optionally send notification to customer
        // event(new OrderStatusUpdated($order));

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Order status updated successfully',
        //     'data' => $order
        // ]);
    }
}
