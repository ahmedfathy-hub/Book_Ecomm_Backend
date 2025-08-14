<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['order'])
            ->whereHas('order', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest()
            ->get();

        return response()->json($payments);
    }

    public function show($id)
    {
        $payment = Payment::with(['order'])
            ->whereHas('order', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->findOrFail($id);

        return response()->json($payment);
    }

      public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,success,failed,refunded',
        ]);

        $payment = Payment::whereHas('order', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->findOrFail($id);

        $payment->update(['status' => $request->status]);

        if ($request->status == Payment::STATUS_SUCCESS) {
            $payment->order()->update(['status' => Order::STATUS_PROCESSING]);
        }

        return response()->json(['message' => 'Payment status updated']);
    }
}
