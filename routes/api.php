<?php

use App\Http\Controllers\Api\CategoryController;

Route::get('/categories', [CategoryController::class, 'index']);

// TODO: Create these API controllers before uncommenting
// use App\Http\Controllers\Api\AuthController;
// use App\Http\Controllers\Api\ProductController;
// use App\Http\Controllers\Api\OrderController;
//
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);
//
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/products', [ProductController::class, 'index']);
//     Route::get('/products/{id}', [ProductController::class, 'show']);
//     Route::post('/cart', [OrderController::class, 'addToCart']);
//     Route::post('/checkout', [OrderController::class, 'checkout']);
//     Route::get('/orders', [OrderController::class, 'myOrders']);
// });
