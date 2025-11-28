<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\NotificationController as NotificationController;
use App\Http\Controllers\Admin\CreatorController as AdminCreatorController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\CustomerNotificationController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\FooterLinkController;
use App\Http\Controllers\Admin\ProductAnalyticsController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
//stripe webhook
Route::post('/handleStripeWebhook', [OrderController::class, 'handleStripeWebhook']);
//product analytics
Route::post('/products/{product}/view', [ProductAnalyticsController::class, 'incrementView']);
//active banners 
Route::get('/banners/active', [BannerController::class, 'active']);
// Testimonials
Route::get('/testimonials', [TestimonialController::class, 'index']);
// Footer Links
Route::get('/footer-links', [FooterLinkController::class, 'index']);
// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/app-settings', [AppSettingController::class, 'index'])
    ->withoutMiddleware(['throttle:api'])
    ->middleware('throttle:public-settings');
Route::post('/admin/auth/login', [AuthController::class, 'adminLogin']);
Route::post('/admin/auth/register', [AuthController::class, 'adminRegister']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']); // Move this BEFORE the {id} route
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/creators', [App\Http\Controllers\CreatorController::class, 'index']);
Route::get('/creators/{id}', [App\Http\Controllers\CreatorController::class, 'show']);
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe']);
Route::post('/newsletter/unsubscribe', [App\Http\Controllers\NewsletterController::class, 'unsubscribe']);

// Recommendation routes
Route::post('/find-products-by-images', [RecommendationController::class, 'findProductsByImages']);
Route::post('/process-recommendations', [RecommendationController::class, 'processRecommendations']);
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    //notifications routes 
    Route::get('/my-notifications', [CustomerNotificationController::class, 'myNotifications']);
    Route::post('/customer-notifications/{notification}/mark-as-read', [CustomerNotificationController::class, 'markAsRead']);
    Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);});
    // Auth routes
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::put('/cart', [CartController::class, 'update']);
    Route::delete('/cart/{productId}', [CartController::class, 'delete']);
    Route::delete('/cart', [CartController::class, 'clear']);
    
    // Wishlist routes
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::put('/wishlist', [WishlistController::class, 'update']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'delete']);
    Route::delete('/wishlist', [WishlistController::class, 'clear']);
    
    // Order routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/initiatePayment', [OrderController::class, 'initiatePayment']);
    
    
    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        //promotion update route
        Route::patch('/products/{product}/promotion', [ProductController::class, 'updatePromotion']);
        //product analytics admin routes
        Route::get('/total-views', [ProductAnalyticsController::class, 'totalViews']);
        Route::get('/top-products/{limit?}', [ProductAnalyticsController::class, 'topProducts']);
        Route::get('/daily-views', [ProductAnalyticsController::class, 'dailyViews']);
        Route::get('/products/{product}/views-by-user', [ProductAnalyticsController::class, 'viewsByUser']);
        Route::get('/overview', [ProductAnalyticsController::class, 'totalViewsOverview']);
        // Banner routes
        Route::get('/banners', [BannerController::class, 'index']);
        Route::post('/banners', [BannerController::class, 'store']);
        Route::put('/banners/{banner}', [BannerController::class, 'update']);
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy']);

        // Testimonials routes
        Route::get('/testimonials', [TestimonialController::class, 'all']);
        Route::post('/testimonials', [TestimonialController::class, 'store']);
        Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update']);
        Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy']);

        // Footer Links routes
        Route::get('/footer-links', [FooterLinkController::class, 'all']);
        Route::post('/footer-links', [FooterLinkController::class, 'store']);
        Route::put('/footer-links/{footerLink}', [FooterLinkController::class, 'update']);
        Route::delete('/footer-links/{footerLink}', [FooterLinkController::class, 'destroy']);

        Route::get('/customer-notifications', [CustomerNotificationController::class, 'index']);
        Route::post('/customer-notifications', [CustomerNotificationController::class, 'store']);
        Route::get('/customer-notifications/{notification}', [CustomerNotificationController::class, 'show']);
        Route::put('/customer-notifications/{notification}', [CustomerNotificationController::class, 'update']);
        Route::delete('/customer-notifications/{notification}', [CustomerNotificationController::class, 'destroy']);

        Route::get('/profile', [AuthController::class, 'adminProfile']);
        Route::put('/profile', [AuthController::class, 'updateAdminProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        
        Route::get('notifs', [CustomerNotificationController::class, 'show']);
        Route::post('/app-settings', [AppSettingController::class, 'update']);  
        
        Route::prefix('dashboard')->group(function () {
            Route::get('/overview', [AdminDashboardController::class, 'overview']);
            Route::get('/revenue-analytics', [AdminDashboardController::class, 'revenueAnalytics']);
            Route::get('/sales-analytics', [AdminDashboardController::class, 'salesAnalytics']);
            Route::get('/customer-analytics', [AdminDashboardController::class, 'customerAnalytics']);
            Route::get('/product-analytics', [AdminDashboardController::class, 'productAnalytics']);
            Route::get('/real-time-stats', [AdminDashboardController::class, 'realTimeStats']);
        });
        
        Route::prefix('products')->group(function () {
            Route::get('/', [AdminProductController::class, 'index']);
            Route::post('/', [AdminProductController::class, 'store']);
            Route::get('/stats', [AdminProductController::class, 'getStats']);
            Route::get('/{id}', [AdminProductController::class, 'show']);
            Route::put('/{id}', [AdminProductController::class, 'update']);
            Route::delete('/{id}', [AdminProductController::class, 'destroy']);
            Route::put('/{id}/stock', [AdminProductController::class, 'updateStock']);
        });
        
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index']);
            Route::post('/', [AdminCategoryController::class, 'store']);
            Route::get('/stats', [AdminCategoryController::class, 'getStats']);
            Route::post('/reorder', [AdminCategoryController::class, 'reorder']);
            Route::get('/{id}', [AdminCategoryController::class, 'show']);
            Route::put('/{id}', [AdminCategoryController::class, 'update']);
            Route::delete('/{id}', [AdminCategoryController::class, 'destroy']);
            Route::post('/{id}/bottom-banner', [AdminCategoryController::class, 'updateBottomBanner']);
        });
        
        Route::prefix('creators')->group(function () {
            Route::get('/', [AdminCreatorController::class, 'index']);
            Route::post('/', [AdminCreatorController::class, 'store']);
            Route::put('/{id}', [AdminCreatorController::class, 'update']);
            Route::delete('/{id}', [AdminCreatorController::class, 'destroy']);
        });
        
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::post('/', [AdminUserController::class, 'store']);
            Route::get('/stats', [AdminUserController::class, 'getStats']);
            Route::get('/{id}', [AdminUserController::class, 'show']);
            Route::put('/{id}', [AdminUserController::class, 'update']);
            Route::delete('/{id}', [AdminUserController::class, 'destroy']);
            Route::post('/{id}/verify-email', [AdminUserController::class, 'verifyEmail']);
            Route::post('/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
            Route::post('/{id}/impersonate', [AdminUserController::class, 'impersonate']);
        });
        
        // Admin order routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index']);
            Route::get('/stats', [AdminOrderController::class, 'getStats']);
            Route::get('/revenue-chart', [AdminOrderController::class, 'getRevenueChart']);
            Route::post('/export', [AdminOrderController::class, 'export']);
            Route::get('/{id}', [AdminOrderController::class, 'show']);
            Route::put('/{id}/status', [AdminOrderController::class, 'updateStatus']);
            Route::put('/{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);
            Route::post('/{id}/notes', [AdminOrderController::class, 'addNotes']);
        });


        Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::patch('{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']); 
        Route::delete('/', [NotificationController::class, 'destroyAll']);             
});

        Route::get('/newsletter/subscribers', [App\Http\Controllers\NewsletterController::class, 'index']);
        Route::delete('/newsletter/subscribers/{id}', [App\Http\Controllers\NewsletterController::class, 'destroy']);
    });
});
