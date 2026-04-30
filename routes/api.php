<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\GlobalSearchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\BulkUploadController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserOrderController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\EmailPreferenceController;
use App\Http\Controllers\Api\EmailAnalyticsController;
use App\Http\Controllers\Api\Admin\EmailCampaignController;
use App\Http\Controllers\Api\Admin\CampaignController;
use App\Http\Controllers\Api\Admin\SegmentController;
use App\Http\Controllers\Api\Admin\UserSegmentController;
use App\Http\Controllers\Api\Admin\NewsletterController;
use App\Http\Controllers\Api\Admin\EmailTemplateController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\SupportAgentController;
use App\Http\Controllers\Api\ComparisonController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\Admin\RiderController;
use App\Http\Controllers\Api\Rider\RiderDashboardController;
use App\Http\Controllers\Api\Admin\WarehouseController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\NewsletterController as PublicNewsletterController;
use App\Http\Controllers\Api\Customer\ProfileController as CustomerProfileController;

use App\Http\Controllers\Api\LiveChatController;
use App\Http\Controllers\Api\GiftCardController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\Admin\SearchAnalyticsController;
use App\Http\Controllers\Api\Admin\BlogController;
use App\Http\Controllers\Api\Admin\ProfileController;
use App\Http\Controllers\Api\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Api\Seller\SellerOrderController;
use App\Http\Controllers\Api\Seller\SellerAnalyticsController;
use App\Http\Controllers\Api\Seller\SellerProfileController;
use App\Http\Controllers\Api\Rider\RiderProfileController;
use App\Http\Controllers\Api\Support\SupportProfileController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/wishlist/shared/{token}', [WishlistController::class, 'showPublic']);
Route::get('/products/{id}/variants', [ProductVariantController::class, 'index']);
Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
Route::middleware('auth:sanctum')->post('/products/{id}/reviews', [ReviewController::class, 'store']);

// Cart routes (Guest & Auth)
Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart', [CartController::class, 'store']);
Route::put('/cart/{itemId}', [CartController::class, 'update']);
Route::delete('/cart/{itemId}', [CartController::class, 'destroy']);
Route::delete('/cart', [CartController::class, 'clear']);
Route::post('/cart/coupon', [CartController::class, 'applyCoupon']);

// Search routes (public)
Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete']);

// Newsletter routes (public)
Route::get('/newsletter/unsubscribe/{token}', [PublicNewsletterController::class, 'unsubscribeByToken']);
Route::get('/newsletter/resubscribe/{token}', [PublicNewsletterController::class, 'resubscribeByToken']);

// Knowledge Base routes (public)
Route::get('/kb', [KnowledgeBaseController::class, 'index']);
Route::get('/kb/{slug}', [KnowledgeBaseController::class, 'show']);
Route::post('/kb/{id}/vote', [KnowledgeBaseController::class, 'vote']);

// Public support tools
Route::post('/support/order-lookup', [SupportAgentController::class, 'orderLookup']);

// Public shipping routes
Route::get('/shipping/zones', [ShippingController::class, 'zones']);
Route::post('/shipping/calculate', [ShippingController::class, 'calculate']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'show']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::patch('/wishlist/privacy', [WishlistController::class, 'updatePrivacy']);
    Route::delete('/wishlist/{itemId}', [WishlistController::class, 'destroy']);

    // Product Comparison
    Route::get('/comparison', [ComparisonController::class, 'index']);
    Route::post('/comparison', [ComparisonController::class, 'store']);
    Route::delete('/comparison/{productId}', [ComparisonController::class, 'destroy']);
    Route::delete('/comparison', [ComparisonController::class, 'clear']);

    // Social Auth
    Route::get('/auth/social/{provider}/redirect', [\App\Http\Controllers\Api\SocialAuthController::class, 'redirectToProvider']);
    Route::get('/auth/social/{provider}/callback', [\App\Http\Controllers\Api\SocialAuthController::class, 'handleProviderCallback']);
    Route::post('/auth/social/{provider}/disconnect', [\App\Http\Controllers\Api\SocialAuthController::class, 'disconnect']);

    // Gift Cards
    Route::get('/gift-cards', [GiftCardController::class, 'index']);
    Route::post('/gift-cards', [GiftCardController::class, 'store']);
    Route::post('/gift-cards/check', [GiftCardController::class, 'checkBalance']);
    Route::post('/gift-cards/redeem', [GiftCardController::class, 'redeem']);

    // Loyalty Program
    Route::get('/loyalty/status', [LoyaltyController::class, 'getStatus']);
    Route::get('/loyalty/rewards', [LoyaltyController::class, 'getRewards']);
    Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeemReward']);

    // User: my orders
    Route::get('/user/orders', [UserOrderController::class, 'index']);
    Route::get('/user/orders/{id}', [UserOrderController::class, 'show']);
    Route::post('/user/orders/{id}/refund-request', [UserOrderController::class, 'requestRefund']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{id}/can-cancel', [OrderController::class, 'canCancel']);

    // Payment routes
    Route::post('/orders/{id}/payment', [PaymentController::class, 'process']);
    Route::get('/orders/{id}/payment/status', [PaymentController::class, 'status']);

    // User: addresses
    Route::get('/user/addresses', [UserAddressController::class, 'index']);
    Route::post('/user/addresses', [UserAddressController::class, 'store']);
    Route::put('/user/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/user/addresses/{id}', [UserAddressController::class, 'destroy']);

    // Customer Returns
    Route::get('/returns', [ReturnController::class, 'index']);
    Route::post('/returns', [ReturnController::class, 'store']);

    // Customer Profile
    Route::prefix('customer')->group(function () {
        Route::get('/profile', [CustomerProfileController::class, 'show']);
        Route::put('/profile', [CustomerProfileController::class, 'update']);
        Route::post('/profile/avatar', [CustomerProfileController::class, 'uploadAvatar']);
        Route::post('/profile/change-password', [CustomerProfileController::class, 'changePassword']);
        Route::post('/profile/newsletter', [CustomerProfileController::class, 'updateNewsletterPreference']);
    });

    // Checkout & Shipping
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::post('/coupons/validate', [CouponController::class, 'validate']);
    Route::get('/shipping/zones', [ShippingController::class, 'zones']);
    Route::post('/shipping/calculate', [ShippingController::class, 'calculate']);

    // Invoice (accessible by owner, admin, seller — controller enforces auth)
    Route::get('/orders/{id}/invoice', [InvoiceController::class, 'show']);
    Route::get('/orders/{id}/invoice/download', [InvoiceController::class, 'download']);

    // Notifications
    Route::get('/user/notifications', [NotificationController::class, 'index']);
    Route::get('/user/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/user/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::put('/user/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('/user/notifications/{id}', [NotificationController::class, 'destroy']);

    // Notification Preferences
    Route::get('/user/notification-preferences', [NotificationPreferenceController::class, 'show']);
    Route::put('/user/notification-preferences', [NotificationPreferenceController::class, 'update']);

    // Device Management
    Route::prefix('devices')->group(function () {
        Route::get('/', [DeviceController::class, 'index']);
        Route::delete('/{tokenId}', [DeviceController::class, 'destroy']);
        Route::post('/logout-all-others', [DeviceController::class, 'logoutAllOthers']);
        Route::post('/logout-all', [DeviceController::class, 'logoutAll']);
    });

    // Rider routes
    Route::middleware(['rider'])->prefix('rider')->group(function () {
        Route::get('/dashboard', [RiderDashboardController::class, 'stats']);
        Route::patch('/assignments/{id}/status', [RiderDashboardController::class, 'updateStatus']);
    });

    // Admin-only
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/global-search', [GlobalSearchController::class, 'search']);
        // Admin profile
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
        Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

        Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/low-stock', [ProductController::class, 'getLowStockProducts']);
        Route::apiResource('categories', CategoryController::class)->except(['index']);
        Route::apiResource('discounts', DiscountController::class);

        // Admin refund processing
        Route::post('/orders/{id}/refund', [OrderController::class, 'refund']);

        // Admin payment management
        Route::post('/orders/{id}/payment/mark-paid', [PaymentController::class, 'markPaid']);
        Route::post('/orders/{id}/payment/refund', [PaymentController::class, 'refund']);

        // Admin returns
        Route::get('/returns', [ReturnController::class, 'adminIndex']);
        Route::post('/returns/{id}/approve', [ReturnController::class, 'approve']);
        Route::post('/returns/{id}/reject', [ReturnController::class, 'reject']);
        Route::post('/returns/{id}/refunded', [ReturnController::class, 'markRefunded']);

        // Admin shipping management
        Route::get('/shipping/zones', [ShippingController::class, 'index']);
        Route::post('/shipping/zones', [ShippingController::class, 'store']);
        Route::put('/shipping/zones/{id}', [ShippingController::class, 'update']);
        Route::delete('/shipping/zones/{id}', [ShippingController::class, 'destroy']);

        // Search analytics
        Route::get('/search/top', [SearchController::class, 'topSearches']);

        // Coupons
        Route::apiResource('coupons', CouponController::class);

        // Reports
        Route::get('/reports/sales', [ReportController::class, 'sales']);
        Route::get('/reports/inventory', [ReportController::class, 'inventory']);
        Route::get('/reports/customers', [ReportController::class, 'customers']);
        Route::get('/reports/taxes', [ReportController::class, 'taxes']);
        Route::get('/reports/shipping', [ReportController::class, 'shipping']);
        Route::get('/reports/refunds', [ReportController::class, 'refunds']);
        Route::get('/reports/coupons', [ReportController::class, 'coupons']);
        Route::get('/reports/export', [ReportController::class, 'export']);

        // Search Analytics
        Route::get('/search-analytics', [SearchAnalyticsController::class, 'index']);

        // Blog Management (Admin)
        Route::get('/blog/categories', [BlogController::class, 'getCategories']);
        Route::post('/blog/posts', [BlogController::class, 'store']);
        Route::get('/blog/posts', [BlogController::class, 'index']);
        Route::delete('/blog/posts/{id}', [BlogController::class, 'destroy']);

        // User Management (Admin)
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::post('/users/{id}/block', [AdminUserController::class, 'block']);
        Route::post('/users/{id}/unblock', [AdminUserController::class, 'unblock']);

        // Review Moderation (Admin)
        Route::get('/reviews', [ReviewController::class, 'adminIndex']);
        Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve']);
        Route::delete('/reviews/{id}/reject', [ReviewController::class, 'reject']);

        // Rider Management (Admin)
        Route::get('/riders', [RiderController::class, 'getRiders']);
        Route::post('/orders/{id}/assign-rider', [RiderController::class, 'assignRider']);
        Route::get('/rider-assignments', [RiderController::class, 'assignments']);

        // Activity Logs
        Route::get('/activity-logs', [\App\Http\Controllers\Api\Admin\ActivityLogController::class, 'index']);
        Route::get('/activity-logs/stats', [\App\Http\Controllers\Api\Admin\ActivityLogController::class, 'stats']);

        // Warehouse Management (Admin)
        Route::apiResource('warehouses', WarehouseController::class);
        Route::get('/warehouses/{id}/stock', [WarehouseController::class, 'stock']);
        Route::patch('/warehouses/{warehouseId}/stock/{productId}', [WarehouseController::class, 'updateStock']);
        Route::delete('/warehouses/{warehouseId}/products/{productId}', [WarehouseController::class, 'removeProduct']);
        Route::get('/warehouses/{id}/available-products', [WarehouseController::class, 'availableProducts']);
    });

    // Admin or Seller
    Route::middleware(['admin_or_seller'])->group(function () {
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::post('/products/{id}/images', [ProductController::class, 'uploadImages']);
        Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
        Route::patch('/products/{id}/status', [ProductController::class, 'updateStatus']);
        Route::patch('/products/{id}/restock', [ProductController::class, 'restock']);

        // Variants
        Route::post('/products/{id}/variants', [ProductVariantController::class, 'store']);
        Route::put('/products/{id}/variants/{variantId}', [ProductVariantController::class, 'update']);
        Route::delete('/products/{id}/variants/{variantId}', [ProductVariantController::class, 'destroy']);

        // Bulk Upload
        Route::post('/products/bulk-upload', [BulkUploadController::class, 'upload']);

        // Orders (admin sees all, seller sees own via controller logic)
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::put('/orders/{id}', [OrderController::class, 'update']);
    });

    // Seller
    Route::middleware('seller')->prefix('seller')->group(function () {
        Route::get('/dashboard', [SellerDashboardController::class, 'stats']);
        Route::get('/orders', [SellerOrderController::class, 'index']);
        Route::get('/analytics', [SellerAnalyticsController::class, 'index']);
        Route::get('/profile', [SellerProfileController::class, 'show']);
        Route::put('/profile', [SellerProfileController::class, 'update']);
        Route::post('/profile/avatar', [SellerProfileController::class, 'uploadAvatar']);
        Route::post('/profile/change-password', [SellerProfileController::class, 'changePassword']);
        Route::get('/reports/sales', [ReportController::class, 'seller']);
    });

    // Support & Tickets
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::post('/tickets/{id}/messages', [TicketController::class, 'addMessage']);
    Route::put('/tickets/{id}/status', [TicketController::class, 'updateStatus']);
    Route::post('/tickets/{id}/survey', [TicketController::class, 'submitSurvey']);

    // Live Chat
    Route::post('/chat/start', [LiveChatController::class, 'startChat']);
    Route::post('/chat/assign/{sessionId}', [LiveChatController::class, 'assignChat']);
    Route::post('/chat/end/{sessionId}', [LiveChatController::class, 'endChat']);

    // Support Panel (Specific to agents/admins)
    Route::middleware(['admin_or_support'])->group(function () {
        Route::get('/support/metrics', [SupportAgentController::class, 'dashboardMetrics']);
        Route::get('/support/agents', [SupportAgentController::class, 'agentsList']);
        
        // KB Management
        Route::post('/kb', [KnowledgeBaseController::class, 'store']);
        Route::put('/kb/{id}', [KnowledgeBaseController::class, 'update']);
        Route::delete('/kb/{id}', [KnowledgeBaseController::class, 'destroy']);
    });

    // Email Preferences
    Route::get('/email-preferences', [EmailPreferenceController::class, 'show']);
    Route::put('/email-preferences', [EmailPreferenceController::class, 'update']);
    Route::post('/email-preferences/unsubscribe', [EmailPreferenceController::class, 'unsubscribe']);

    // Newsletter Subscriptions
    Route::post('/newsletter/subscribe', [PublicNewsletterController::class, 'subscribe']);
    Route::post('/newsletter/unsubscribe', [PublicNewsletterController::class, 'unsubscribe']);

    // Admin Email Marketing
    Route::middleware(['admin_or_support'])->prefix('admin')->group(function () {
        // Campaigns
        Route::get('/campaigns/segments', [CampaignController::class, 'getSegments']);
        Route::get('/email-campaigns', [CampaignController::class, 'index']);
        Route::post('/email-campaigns', [CampaignController::class, 'store']);
        Route::get('/email-campaigns/{id}', [CampaignController::class, 'show']);
        Route::put('/email-campaigns/{id}', [CampaignController::class, 'update']);
        Route::delete('/email-campaigns/{id}', [CampaignController::class, 'destroy']);
        Route::post('/email-campaigns/{id}/send', [CampaignController::class, 'send']);

        // Segments
        Route::get('/segments', [SegmentController::class, 'index']);
        Route::post('/segments', [SegmentController::class, 'store']);
        Route::put('/segments/{id}', [SegmentController::class, 'update']);
        Route::delete('/segments/{id}', [SegmentController::class, 'destroy']);
        Route::get('/segments/{id}/users', [SegmentController::class, 'users']);

        Route::get('/user-segments', [UserSegmentController::class, 'index']);
        Route::post('/user-segments', [UserSegmentController::class, 'store']);
        Route::get('/user-segments/{id}', [UserSegmentController::class, 'show']);
        Route::put('/user-segments/{id}', [UserSegmentController::class, 'update']);
        Route::delete('/user-segments/{id}', [UserSegmentController::class, 'destroy']);

        // Newsletters
        Route::get('/newsletters', [NewsletterController::class, 'index']);
        Route::post('/newsletters', [NewsletterController::class, 'store']);
        Route::delete('/newsletters/{id}', [NewsletterController::class, 'destroy']);
        Route::post('/newsletters/{id}/send', [NewsletterController::class, 'send']);
        Route::get('/newsletters/{id}/report', [NewsletterController::class, 'report']);
        Route::get('/newsletters/subscribers', [NewsletterController::class, 'subscribers']);

        // Templates
        Route::get('/email-templates', [EmailTemplateController::class, 'index']);
        Route::post('/email-templates', [EmailTemplateController::class, 'store']);
        Route::get('/email-templates/{id}', [EmailTemplateController::class, 'show']);
        Route::put('/email-templates/{id}', [EmailTemplateController::class, 'update']);
        Route::delete('/email-templates/{id}', [EmailTemplateController::class, 'destroy']);
        Route::patch('/email-templates/{id}/toggle', [EmailTemplateController::class, 'toggleActive']);

        // Multilingual Support
        Route::apiResource('languages', \App\Http\Controllers\Api\Admin\LanguageController::class);
        Route::apiResource('translations', \App\Http\Controllers\Api\Admin\TranslationController::class);

        // Affiliate & Referral Management (Admin)
        Route::prefix('admin')->group(function () {
            Route::get('/affiliates', [\App\Http\Controllers\Api\Admin\AffiliateManagementController::class, 'index']);
            Route::patch('/affiliates/{affiliate}/status', [\App\Http\Controllers\Api\Admin\AffiliateManagementController::class, 'updateStatus']);
            Route::get('/affiliate-orders', [\App\Http\Controllers\Api\Admin\AffiliateManagementController::class, 'orders']);
            Route::patch('/affiliate-orders/{affiliateOrder}', [\App\Http\Controllers\Api\Admin\AffiliateManagementController::class, 'updateOrder']);
        });
    });
});

// Affiliate & Referral (User)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/affiliate/register', [\App\Http\Controllers\Api\AffiliateController::class, 'register']);
    Route::get('/affiliate/dashboard', [\App\Http\Controllers\Api\AffiliateController::class, 'dashboard']);
    Route::get('/affiliate/orders', [\App\Http\Controllers\Api\AffiliateController::class, 'orders']);
    
    Route::get('/referrals', [\App\Http\Controllers\Api\ReferralController::class, 'index']);
    Route::post('/referrals', [\App\Http\Controllers\Api\ReferralController::class, 'store']);

    // Blog (User/Protected)
    Route::post('/blog/comments', [\App\Http\Controllers\Api\Blog\CommentController::class, 'store']);
});

// Blog (Public)
Route::prefix('blog')->group(function () {
    Route::get('/posts', [\App\Http\Controllers\Api\Blog\PostController::class, 'index']);
    Route::get('/posts/{slug}', [\App\Http\Controllers\Api\Blog\PostController::class, 'show']);
    Route::get('/categories', [\App\Http\Controllers\Api\Blog\CategoryController::class, 'index']);
    Route::get('/rss', [\App\Http\Controllers\Api\Blog\PostController::class, 'rss']);
});

// Blog (Admin)
Route::middleware(['auth:sanctum', 'can:manage-blog'])->prefix('admin/blog')->group(function () {
    Route::get('/posts', [\App\Http\Controllers\Api\Blog\PostController::class, 'adminIndex']);
    Route::post('/posts', [\App\Http\Controllers\Api\Blog\PostController::class, 'store']);
    Route::patch('/posts/{post}', [\App\Http\Controllers\Api\Blog\PostController::class, 'update']);
    Route::post('/categories', [\App\Http\Controllers\Api\Blog\CategoryController::class, 'store']);
    Route::get('/comments', [\App\Http\Controllers\Api\Blog\CommentController::class, 'adminIndex']);
    Route::patch('/comments/{comment}/moderate', [\App\Http\Controllers\Api\Blog\CommentController::class, 'moderate']);
});

// Localization (Public)
Route::get('/localization/languages', [\App\Http\Controllers\Api\Admin\LanguageController::class, 'index']);
Route::get('/localization/strings/{lang}', [\App\Http\Controllers\Api\Admin\TranslationController::class, 'getStrings']);

// Analytics tracking (Public)
Route::get('/analytics/open/{campaignId}/{userId}', [EmailAnalyticsController::class, 'trackOpen']);
Route::get('/analytics/click/{campaignId}/{userId}', [EmailAnalyticsController::class, 'trackClick']);

// Rider profile routes
Route::middleware(['auth:sanctum', 'rider'])
    ->prefix('rider')->group(function () {
    Route::get('/profile', [RiderProfileController::class, 'show']);
    Route::put('/profile', [RiderProfileController::class, 'update']);
    Route::post('/profile/avatar', [RiderProfileController::class, 'uploadAvatar']);
    Route::post('/profile/change-password', [RiderProfileController::class, 'changePassword']);
});

// Support profile routes
Route::middleware(['auth:sanctum', 'support'])
    ->prefix('support')->group(function () {
    Route::get('/profile', [SupportProfileController::class, 'show']);
    Route::put('/profile', [SupportProfileController::class, 'update']);
    Route::post('/profile/avatar', [SupportProfileController::class, 'uploadAvatar']);
    Route::post('/profile/change-password', [SupportProfileController::class, 'changePassword']);
});
