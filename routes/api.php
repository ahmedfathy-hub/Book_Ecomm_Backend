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

///////// Routes with auth //////////////////////
    Route::middleware('auth:sanctum')->group(function () 
        {
            Route::post('/logout', [AuthController::class, 'logout']);

        /// to get the profile info
            Route::post('/profile', [AuthController::class, 'profile']);

        /// to update into to profile ///
            Route::put('/profile', [AuthController::class, 'updateProfile']);
        

            Route::post('/products/{product}/images', [ProductController::class, 'storeImages']);
            Route::put('/product-images/{image}/set-main', [ProductImageController::class, 'setMainImage']);
            Route::delete('/product-images/{image}', [ProductImageController::class, 'destroy']);

            Route::get('/carts/{cart}', [CartController::class, 'show']);
            Route::post('/carts/{cart}/items', [CartController::class, 'addItem']);
            Route::delete('/carts/{cart}/items/{item}', [CartController::class, 'removeItem']);
            
            // Order routes
            Route::apiResource('orders', OrderController::class)->except(['update', 'destroy']);
            
            // Payment routes
            Route::post('/orders/{order}/payment', [PaymentController::class, 'store']);

        });


/////////// Admin routes////////////
        Route::prefix('admin')->group(function () {   
            // POST /admin/categories (store)
                Route::post('/categories', [CategoryController::class, 'store'])->middleware(['auth:sanctum', RoleMiddleware::class.':admin']);
                
            // PUT/PATCH /admin/categories/{id} (update)
                Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware(['auth:sanctum', RoleMiddleware::class.':admin']);
                
            // DELETE /admin/categories/{id} (destroy)
                Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware(['auth:sanctum', RoleMiddleware::class.':admin']);
        });


/////////// Admin and seller routes////////////
        Route::middleware(['auth:sanctum', RoleMiddleware::class.':seller,admin'])->group(function () {
            Route::post('/products', [ProductController::class, 'store']);  // POST (create) -- (seller/admin)
            Route::put('/products/{product}', [ProductController::class, 'update']);  // PUT (update) -- (seller/admin)
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);  // DELETE (destroy) -- (seller/admin)
            
            // Product Images routes
            Route::post('/products/{product}/images', [ProductController::class, 'storeImages']);
            Route::put('/product-images/{image}/set-main', [ProductImageController::class, 'setMainImage']);
            Route::delete('/product-images/{image}', [ProductImageController::class, 'destroy']);
        });

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
