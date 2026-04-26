# ⚙️ ShopPro — Backend API

> **Production-ready RESTful API** for the ShopPro multi-vendor e-commerce platform. Built with Laravel 11, MySQL, and Laravel Sanctum. Features 314+ routes, 6 role-based panels, complete order lifecycle, payment processing, delivery management, warehouse tracking, and SEO sitemap generation.

---

## 🌟 Complete API Features

### 👥 User Roles & Access Control
- 5 roles: **Admin, Seller, Customer, Support, Rider** (via Spatie Permission)
- Role-based middleware for every route group
- Email domain enforcement per role
- Admin credentials hardcoded & protected
- Block / Unblock users with reason logging
- Per-device token tracking

### 🔐 Authentication & Security
- Laravel Sanctum token-based auth
- Google OAuth via Laravel Socialite
- Forgot password → 6-digit OTP (10 min expiry) → Reset
- Rate limiting: 5 failed attempts = 15 min lockout
- Login attempts tracked in `login_attempts` table
- **Device tracking** on every login (device type, IP, user agent)
- **Per-device token revocation** — logout specific devices
- Logout all other devices endpoint
- Bcrypt password hashing
- Blocked user check on every login attempt

### 🏪 Product Management
- Full CRUD (Admin & Seller)
- Categories & subcategories (parent-child)
- Multiple image upload — Intervention Image resize
- Product variants (Size, Color, Material) with SKU
- Auto SKU generation on create
- Stock quantity with configurable threshold
- **Draft / Published / Archived** status with toggle endpoint
- Bulk CSV import (PhpSpreadsheet)
- Low stock scheduled alerts
- Soft delete

### 🛒 Cart & Wishlist
- Database-backed cart per user
- Guest cart via session
- Cart total: subtotal, tax, shipping, discount
- Wishlist CRUD per user
- Shared wishlist token

### 💳 Order Management
- Full order lifecycle: Pending → Processing → Shipped → Delivered
- **24-hour cancellation window** with stock restore
- Stock deduction on order placement
- Return / Refund request handling
- Order history per user & per seller

### 💰 Payment System
- **Cash on Delivery (COD)** — fully implemented
- **Bank Transfer** — with reference number tracking & bank details
- **Stripe placeholder** — structure ready for integration
- Payment status: Pending / Paid / Failed / Refunded
- Admin mark-as-paid endpoint
- Admin refund processing endpoint
- PaymentService class (clean separation)

### 🚚 Shipping & Delivery
- ShippingAddress CRUD per user (multiple with default)
- **Delivery charges by city/region** (ShippingZone model)
  - 10 Pakistan cities pre-seeded
  - Karachi/Lahore: Rs. 150 | Islamabad: Rs. 200 | Other: Rs. 350
- Shipping charge calculation endpoint
- Admin manage shipping zones CRUD
- **Delivery Rider assignment** to orders
- Rider status updates: Assigned → Picked Up → Delivered

### 📦 Inventory & Warehouse
- Real-time stock deduction on order
- Low stock threshold alerts
- Daily scheduled `check:low-stock` command
- **Warehouse model** — multiple warehouse locations
- **Product-Warehouse pivot** — stock & reserved quantity per warehouse
- Warehouse stock update endpoint

### 📊 Admin Dashboard
- Real-time KPIs: orders, revenue, users, products
- Month-over-month growth calculation
- Recent orders with customer info
- Low stock products list
- Dynamic — no hardcoded values

### 🎯 Discount & Coupon System
- Coupon validation at checkout
- Percentage & fixed types
- Expiry date, usage limit, minimum order amount
- Per-user usage tracking

### ⭐ Reviews & Ratings
- Star rating 1–5
- **Verified Purchase** auto-check from order history
- Admin approve / reject moderation
- Average rating per product
- One review per user per product enforced

### 🔍 Search System
- Product search with filters
- **SearchLog model** — every query logged
- Search analytics API (top keywords, zero-result queries)
- Filter: category, price, brand, rating

### 🚴 Delivery Rider System
- RiderMiddleware + rider route group
- RiderAssignment model (order → rider)
- Admin assign rider to order
- Rider dashboard stats API
- Rider update delivery status endpoint

### 🏭 Warehouse Management
- Warehouse CRUD (admin)
- Product stock per warehouse
- Reserved quantity tracking
- Stock update per product per warehouse

### 📝 Blog Management
- Blog posts CRUD with slug
- Blog categories
- Draft / Published status
- Author attribution

### 📧 Email Campaigns
- EmailCampaign model with stats
- Sent count, open count, click count, revenue tracking
- Segments & Newsletter management
- Email templates

### 👤 Profile Management (All Roles)
- Name update (email locked — read only)
- Avatar upload to storage
- Password change with current password verify
- Separate profile endpoints per role

### 📋 System Logs
- **ActivityLog model** — append-only log table
- Logs: login, logout, product created/deleted, order updates, user blocked
- Admin filter by action, role, date range, search
- IP address & user agent tracking

### 🗺️ SEO & Sitemap
- **Sitemap generator** at `/sitemap.xml` (spatie/laravel-sitemap)
- Includes: static pages, published products, categories, blog posts
- Product API returns full SEO data:
  - Meta title & description
  - Open Graph image
  - Canonical URL
  - Schema.org JSON-LD (Product, AggregateRating, Offer)
- Scheduled daily sitemap regeneration

### 📄 Invoice & Reports
- PDF invoice generation (DomPDF)
- Invoice download endpoint
- Monthly sales report (ReportService)
- Tax calculation support
- GenerateDailyReports scheduled job

---

## 🛠️ Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| 🐘 PHP | 8.2 | Server Language |
| 🚀 Laravel | 11 | API Framework |
| 🐬 MySQL | 8.0 | Database |
| 🔑 Laravel Sanctum | v4 | Token Authentication |
| 🛡️ Spatie Permission | v6 | Role Management |
| 🖼️ Intervention Image | v3 | Image Resize & Processing |
| 🔗 Laravel Socialite | v5 | Google OAuth |
| 📊 PhpSpreadsheet | v2 | CSV/Excel Bulk Import |
| 📄 Laravel DomPDF | Latest | PDF Invoice Generation |
| 🗺️ Spatie Sitemap | Latest | XML Sitemap Generator |
| 📧 Laravel Mail | Built-in | Email System (Gmail SMTP) |
| ⏰ Laravel Scheduler | Built-in | Cron Jobs |
| 🗄️ Laravel Eloquent | Built-in | ORM |
| 🔒 Laravel Queue | Built-in | Async Jobs |

---

## 📁 Project Structure

```
shoppro-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ProductController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── CartController.php
│   │   │   ├── WishlistController.php
│   │   │   ├── OrderController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ShippingController.php
│   │   │   ├── ReviewController.php
│   │   │   ├── SearchController.php
│   │   │   ├── DeviceController.php
│   │   │   ├── ComparisonController.php
│   │   │   ├── SitemapController.php
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── CampaignController.php
│   │   │   │   ├── BlogController.php
│   │   │   │   ├── RiderController.php
│   │   │   │   ├── WarehouseController.php
│   │   │   │   ├── ActivityLogController.php
│   │   │   │   └── SearchAnalyticsController.php
│   │   │   ├── Seller/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── SellerOrderController.php
│   │   │   │   ├── SellerAnalyticsController.php
│   │   │   │   └── SellerProfileController.php
│   │   │   └── Rider/
│   │   │       └── RiderDashboardController.php
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php
│   │       ├── SellerMiddleware.php
│   │       ├── CustomerMiddleware.php
│   │       ├── SupportMiddleware.php
│   │       ├── RiderMiddleware.php
│   │       └── AdminOrSellerMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── ProductImage.php
│   │   ├── ProductVariant.php
│   │   ├── Category.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── Wishlist.php
│   │   ├── WishlistItem.php
│   │   ├── ShippingAddress.php
│   │   ├── ShippingZone.php
│   │   ├── RiderAssignment.php
│   │   ├── Warehouse.php
│   │   ├── Coupon.php
│   │   ├── Review.php
│   │   ├── LoginAttempt.php
│   │   ├── PasswordResetOtp.php
│   │   ├── SearchLog.php
│   │   ├── ActivityLog.php
│   │   ├── EmailCampaign.php
│   │   └── BlogPost.php
│   ├── Services/
│   │   ├── PaymentService.php
│   │   ├── OrderService.php
│   │   ├── InvoiceService.php
│   │   ├── ReportService.php
│   │   └── SearchService.php
│   ├── Mail/
│   │   ├── OtpMail.php
│   │   └── LowStockMail.php
│   └── Console/Commands/
│       └── CheckLowStock.php
├── database/
│   ├── migrations/          # 25+ migration files
│   └── seeders/
│       ├── RoleSeeder.php
│       ├── AdminSeeder.php
│       └── ShippingZoneSeeder.php
├── routes/
│   ├── api.php              # 314+ API routes
│   ├── web.php              # Sitemap route
│   └── console.php          # Scheduled commands
└── .env
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- XAMPP (recommended for local)
- Git

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/YOUR_USERNAME/shoppro-backend.git

# 2. Navigate to project
cd shoppro-backend

# 3. Install PHP dependencies
composer install

# 4. Copy environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Configure .env (see below)

# 7. Run migrations
php artisan migrate

# 8. Seed database (roles + admin + shipping zones)
php artisan db:seed

# 9. Create storage symlink
php artisan storage:link

# 10. Start server
php artisan serve
```

### Server runs at
```
http://localhost:8000
```

---

## ⚙️ Environment Configuration

```env
APP_NAME=ShopPro
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shoppro_db
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173

# Gmail SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_16_digit_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@shoppro.com"
MAIL_FROM_NAME="ShopPro"

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

JWT_SECRET=your_jwt_secret
```

---

## 🔑 Default Credentials (Auto-seeded)

```
👑 Admin Email:    malik.umerkhan97@gmail.com
👑 Admin Password: malikawan97
```

> Run `php artisan db:seed` to create admin + roles + shipping zones automatically

---

## 📡 Key API Endpoints

### Auth
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
POST   /api/auth/forgot-password
POST   /api/auth/verify-otp
POST   /api/auth/reset-password
GET    /api/auth/google/redirect
GET    /api/auth/google/callback
```

### Products
```
GET    /api/products
GET    /api/products/{id}
POST   /api/products
PUT    /api/products/{id}
DELETE /api/products/{id}
POST   /api/products/{id}/images
PATCH  /api/products/{id}/status
POST   /api/products/bulk-upload
GET    /api/products/{id}/variants
POST   /api/products/{id}/variants
```

### Orders & Payment
```
POST   /api/checkout
GET    /api/orders/{id}
POST   /api/orders/{id}/cancel
GET    /api/orders/{id}/can-cancel
POST   /api/orders/{id}/payment
GET    /api/orders/{id}/payment/status
```

### Shipping
```
GET    /api/shipping/zones
POST   /api/shipping/calculate
```

### Devices
```
GET    /api/devices
DELETE /api/devices/{id}
POST   /api/devices/logout-all-others
POST   /api/devices/logout-all
```

### Admin
```
GET    /api/admin/dashboard/stats
GET    /api/admin/users
POST   /api/admin/users/{id}/block
POST   /api/admin/users/{id}/unblock
GET    /api/admin/reviews
POST   /api/admin/reviews/{id}/approve
DELETE /api/admin/reviews/{id}/reject
GET    /api/admin/riders
POST   /api/admin/orders/{id}/assign-rider
GET    /api/admin/warehouses
POST   /api/admin/warehouses
GET    /api/admin/warehouses/{id}/stock
GET    /api/admin/activity-logs
GET    /api/admin/search-analytics
POST   /api/admin/orders/{id}/payment/mark-paid
POST   /api/admin/orders/{id}/payment/refund
GET    /api/admin/shipping/zones
POST   /api/admin/shipping/zones
```

### Seller
```
GET    /api/seller/dashboard
GET    /api/seller/orders
GET    /api/seller/analytics
GET    /api/seller/profile
PUT    /api/seller/profile
POST   /api/seller/profile/avatar
```

### Rider
```
GET    /api/rider/dashboard
PATCH  /api/rider/assignments/{id}/status
```

### SEO
```
GET    /sitemap.xml
```

---

## 🗄️ Database Tables (25+)

| Table | Purpose |
|---|---|
| users | All users with roles |
| roles / permissions | Spatie role management |
| personal_access_tokens | Sanctum tokens + device info |
| login_attempts | Rate limiting tracker |
| password_reset_otps | OTP for password reset |
| products | Product listings |
| product_images | Product gallery |
| product_variants | Size/Color/Material |
| categories | Nested categories |
| orders | Customer orders |
| order_items | Items per order |
| carts / cart_items | Shopping cart |
| wishlists / wishlist_items | Saved products |
| shipping_addresses | User addresses |
| shipping_zones | City delivery charges |
| rider_assignments | Order → Rider mapping |
| warehouses | Warehouse locations |
| product_warehouse | Stock per warehouse |
| coupons | Discount codes |
| reviews | Product reviews |
| search_logs | Search query analytics |
| activity_logs | System activity tracking |
| email_campaigns | Marketing campaigns |
| blog_posts | Blog articles |
| invoices | Order invoices |

---

## ⏰ Scheduled Commands

```bash
# Check low stock daily → email admin & seller
php artisan check:low-stock

# Regenerate sitemap daily
php artisan sitemap:generate

# Run Laravel scheduler (add to server cron)
php artisan schedule:run
```

---

## 📜 Useful Artisan Commands

```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Drop all + re-migrate
php artisan db:seed              # Seed roles + admin + shipping zones
php artisan migrate:fresh --seed # Fresh start with all seeds
php artisan storage:link         # Link public storage
php artisan route:list           # View all routes (314+)
php artisan config:clear         # Clear config cache
php artisan cache:clear          # Clear app cache
php artisan route:clear          # Clear route cache
php artisan serve                # Start dev server (port 8000)
```

---

## 🔗 Frontend Repository

👉 **[ShopPro Frontend Repository](https://github.com/YOUR_USERNAME/shoppro-frontend)**

Frontend runs at `http://localhost:5173`

---

## 📄 License

[MIT License](LICENSE)

---

## 👨‍💻 Developer

**Malik Umer Khan**
- 📧 malik.umerkhan97@gmail.com


---

> 💡 **Portfolio Project** — Demonstrates professional Laravel 11 API development with multi-role auth, complete e-commerce business logic, payment processing, delivery management, warehouse tracking, SEO optimization, and clean RESTful API design with 314+ endpoints.
