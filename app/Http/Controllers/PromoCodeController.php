<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PromoCode;

class PromoCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }
    public function index()
    {
        $promoCodes = PromoCode::latest()->paginate(10);
        return response()->json($promoCodes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:promo_codes',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        $promoCode = PromoCode::create($request->all());
        return response()->json($promoCode, 201);
    }


    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0'
        ]);

        $promoCode = PromoCode::where('code', $request->code)->first();

        if (!$promoCode || !$promoCode->isValid()) {
            return response()->json([
                'message' => 'Invalid or expired promo code'
            ], 422);
        }

        $discount = $promoCode->calculateDiscount($request->subtotal);

        return response()->json([
            'discount' => $discount,
            'promo_code' => $promoCode
        ]);
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
