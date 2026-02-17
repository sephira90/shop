<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\Webhook\PaymentWebhookController;
use App\Http\Controllers\Api\V1\Webhook\ShippingWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:6,1');
        Route::post('reset-password', [PasswordController::class, 'reset'])->middleware('throttle:6,1');
        Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('email/verification-notification', [VerificationController::class, 'resend'])
                ->middleware(['throttle:6,1'])
                ->name('verification.send');
        });
    });

    Route::prefix('catalog')->middleware('throttle:search')->group(function (): void {
        Route::get('products', [CatalogController::class, 'index']);
        Route::get('products/{slug}', [CatalogController::class, 'show']);
        Route::get('categories', [CatalogController::class, 'categories']);
    });

    Route::prefix('cart')->group(function (): void {
        Route::get('/', [CartController::class, 'show']);
        Route::post('items', [CartController::class, 'upsertItem']);
        Route::delete('items/{variantId}', [CartController::class, 'removeItem']);
    });

    Route::post('checkout/place-order', [CheckoutController::class, 'placeOrder'])->middleware('throttle:checkout');

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::post('checkout/orders/{order}/pay', [CheckoutController::class, 'pay'])->middleware('throttle:checkout');
        Route::get('orders/me', [CheckoutController::class, 'myOrders']);
    });

    Route::prefix('webhooks')->middleware('throttle:webhook')->group(function (): void {
        Route::post('payment', PaymentWebhookController::class);
        Route::post('shipping', ShippingWebhookController::class);
    });

    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'verified', 'role:admin,manager'])
        ->group(function (): void {
            Route::apiResource('products', AdminProductController::class);
            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::get('orders/{order}', [AdminOrderController::class, 'show']);
            Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
            Route::get('promotions', [AdminPromotionController::class, 'index']);
            Route::post('promotions', [AdminPromotionController::class, 'store']);
        });
});
