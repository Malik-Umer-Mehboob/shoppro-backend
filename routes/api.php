<?php

use App\Http\Controllers\Api\AuthController;
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
use App\Http\Controllers\Api\Admin\UserSegmentController;
use App\Http\Controllers\Api\Admin\NewsletterController;
use App\Http\Controllers\Api\Admin\EmailTemplateController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\SupportAgentController;
use App\Http\Controllers\Api\ComparisonController;

use App\Http\Controllers\Api\LiveChatController;
use App\Http\Controllers\Api\GiftCardController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\Admin\SearchAnalyticsController;
use App\Http\Controllers\Api\Admin\BlogController;
use App\Http\Controllers\Api\Admin\ProfileController;


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

// Knowledge Base routes (public)
Route::get('/kb', [KnowledgeBaseController::class, 'index']);
Route::get('/kb/{slug}', [KnowledgeBaseController::class, 'show']);
Route::post('/kb/{id}/vote', [KnowledgeBaseController::class, 'vote']);

// Public support tools
Route::post('/support/order-lookup', [SupportAgentController::class, 'orderLookup']);

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

    // User: addresses
    Route::get('/user/addresses', [UserAddressController::class, 'index']);
    Route::post('/user/addresses', [UserAddressController::class, 'store']);
    Route::put('/user/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/user/addresses/{id}', [UserAddressController::class, 'destroy']);

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

    // Admin-only
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/products/low-stock', [ProductController::class, 'getLowStockProducts']);
        Route::apiResource('categories', CategoryController::class)->except(['index']);
        Route::apiResource('discounts', DiscountController::class);

        // Admin refund processing
        Route::post('/orders/{id}/refund', [OrderController::class, 'refund']);

        // Search analytics
        Route::get('/search/top', [SearchController::class, 'topSearches']);

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
    });

    // Admin or Seller
    Route::middleware(['admin_or_seller'])->group(function () {
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::post('/products/{id}/images', [ProductController::class, 'uploadImages']);
        Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
        Route::patch('/products/{id}/status', [ProductController::class, 'updateStatus']);

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
    Route::post('/newsletter/unsubscribe', [EmailPreferenceController::class, 'unsubscribe']);

    // Admin Email Marketing
    Route::middleware(['admin_or_support'])->prefix('admin')->group(function () {
        // Campaigns
        Route::get('/email-campaigns', [EmailCampaignController::class, 'index']);
        Route::post('/email-campaigns', [EmailCampaignController::class, 'store']);
        Route::get('/email-campaigns/{id}', [EmailCampaignController::class, 'show']);
        Route::put('/email-campaigns/{id}', [EmailCampaignController::class, 'update']);
        Route::post('/email-campaigns/{id}/send', [EmailCampaignController::class, 'send']);

        // Segments
        Route::get('/user-segments', [UserSegmentController::class, 'index']);
        Route::post('/user-segments', [UserSegmentController::class, 'store']);
        Route::get('/user-segments/{id}', [UserSegmentController::class, 'show']);
        Route::put('/user-segments/{id}', [UserSegmentController::class, 'update']);
        Route::delete('/user-segments/{id}', [UserSegmentController::class, 'destroy']);

        // Newsletters
        Route::get('/newsletters', [NewsletterController::class, 'index']);
        Route::post('/newsletters', [NewsletterController::class, 'store']);
        Route::post('/newsletters/{id}/send', [NewsletterController::class, 'send']);
        Route::get('/newsletter-subscribers', [NewsletterController::class, 'subscribers']);

        // Templates
        Route::get('/email-templates', [EmailTemplateController::class, 'index']);
        Route::post('/email-templates', [EmailTemplateController::class, 'store']);
        Route::put('/email-templates/{id}', [EmailTemplateController::class, 'update']);
        Route::delete('/email-templates/{id}', [EmailTemplateController::class, 'destroy']);

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
    Route::patch('/comments/{comment}/moderate', [\App\Http\Controllers\Api\Blog\CommentController::class, 'moderate']);
});

// Localization (Public)
Route::get('/localization/languages', [\App\Http\Controllers\Api\Admin\LanguageController::class, 'index']);
Route::get('/localization/strings/{lang}', [\App\Http\Controllers\Api\Admin\TranslationController::class, 'getStrings']);

// Analytics tracking (Public)
Route::get('/analytics/open/{campaignId}/{userId}', [EmailAnalyticsController::class, 'trackOpen']);
Route::get('/analytics/click/{campaignId}/{userId}', [EmailAnalyticsController::class, 'trackClick']);

