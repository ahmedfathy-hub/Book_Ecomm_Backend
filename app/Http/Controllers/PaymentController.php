<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'transaction_id' => 'required|string',
            'payment_method' => 'required|string',
            'amount' => 'required|numeric'
        ]);

        $payment = $order->payment()->create([
            'transaction_id' => $request->transaction_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => 'completed'
        ]);

        $order->update(['status' => 'processing']);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
