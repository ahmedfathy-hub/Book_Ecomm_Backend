<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Middleware\RoleMiddleware;



    

/////Public Routes///
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/products', [ProductController::class, 'index']);  // GET (index - list all) -- public
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);  // GET (index - list all) -- public
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () 
        {
            Route::post('/logout', [AuthController::class, 'logout']);
        });

////Admin Routes /////
    Route::prefix('admin')->middleware(['auth:sanctum', RoleMiddleware::class.':admin'])->group(function () {
            
        /// Product Management
            Route::get('/products', [ProductController::class, 'index']);
            Route::get('/products/{product}', [ProductController::class, 'show']);
            Route::post('/products', [ProductController::class, 'store']);  
            Route::put('/products/{product}', [ProductController::class, 'update']);  
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);  
        
        /// Category Management
            Route::get('/categories', [CategoryController::class, 'index']);
            Route::get('/categories/{category}', [CategoryController::class, 'show']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        /// Add images to products
            Route::post('/products/{product}/images', [ProductController::class, 'storeImages']);
            Route::put('/product-images/{image}/set-main', [ProductImageController::class, 'setMainImage']);
            Route::delete('/product-images/{image}', [ProductImageController::class, 'destroy']);
        
        /// Cart Management
            Route::get('/Carts', [CartController::class, 'index']);

        /// Order Management
            Route::get('/orders', [OrderController::class, 'index']);
            Route::get('/order/{id}', [OrderController::class, 'show']);
            //Route::post('/orders', [OrderController::class, 'store']);
            //Route::put('/order/{id}/cancel', [OrderController::class, 'cancel']);
            
        /// Payment management
            Route::get('/payments', [PaymentController::class, 'index']);
            Route::get('/payment/{id}', [PaymentController::class, 'show']);
            Route::put('/payment/{id}/status', [PaymentController::class, 'updatePaymentStatus']);
        });


////Seller Routes /////
    Route::prefix('seller')->middleware(['auth:sanctum', RoleMiddleware::class.':seller'])->group(function () {
        
        //// Profile Management
            Route::get('/profile', [AuthController::class, 'profile']);
            Route::put('/updateprofile', [AuthController::class, 'updateProfile']);

        //// Product Management
            Route::get('/products', [ProductController::class, 'index']);
            Route::post('/products', [ProductController::class, 'store']);  
            Route::get('/products/{product}', [ProductController::class, 'show']);
            Route::put('/products/{product}', [ProductController::class, 'update']);  
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);  

        /// Product Images routes
            Route::post('/products/{product}/images', [ProductController::class, 'storeImages']);
            Route::put('/product-images/{image}/set-main', [ProductImageController::class, 'setMainImage']);
            Route::delete('/product-images/{image}', [ProductImageController::class, 'destroy']);

        // Order Management
            Route::get('/orders', [OrderController::class, 'sellerIndex']);
            Route::get('/orders/{id}', [OrderController::class, 'sellerShow']);
            Route::put('/order/{id}/status', [OrderController::class, 'sellerUpdateStatus']);
        
        /// payment Management 
            Route::get('/payment', [PaymentController::class, 'index']);
            Route::get('/paymnet/{id}', [PaymentController::class, 'show']);
        });


////Customer Routes /////
    Route::prefix('customer')->middleware(['auth:sanctum', RoleMiddleware::class.':customer'])->group(function () {
        
        //// Profile Management
            Route::get('/profile', [AuthController::class, 'profile']);
            Route::put('/updateprofile', [AuthController::class, 'updateProfile']);
        
        //// Product Management
            Route::get('/products', [ProductController::class, 'index']);
            Route::get('/products/{product}', [ProductController::class, 'show']);
            
        
        //// Order Management
            Route::get('/orders', [OrderController::class, 'index']);
            Route::get('/order/{id}', [OrderController::class, 'show']);
            Route::post('/orders', [OrderController::class, 'store']);
            Route::put('/order/{id}/cancel', [OrderController::class, 'cancel']);

        //// Cart Management
            Route::get('/carts', [CartController::class, 'index']);
            Route::post('/cart/add-item', [CartController::class, 'addItem']);
            Route::delete('/cart/remove-item/{id}', [CartController::class, 'removeItem']);
            Route::delete('/cart/clear', [CartController::class, 'clearCart']);
    
            // Payment routes
            Route::get('/payments', [PaymentController::class, 'index']);
            Route::get('/payment/{id}', [PaymentController::class, 'show']);
            Route::put('/{id}/status', [PaymentController::class, 'updatePaymentStatus']);
        });