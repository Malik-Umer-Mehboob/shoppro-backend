# ⚙️ ShopPro — Backend API

> **Production-ready RESTful API** for the ShopPro multi-vendor e-commerce platform. Built with Laravel 11, MySQL, and Laravel Sanctum for secure token-based authentication.

---

## 🌟 API Features

### 👥 User Roles & Access Control
- 4 roles: **Admin, Seller, Customer, Support**
- Spatie Laravel Permission for role management
- Role-based middleware for every route group
- Email domain enforcement per role:
  - Customer → @gmail.com only
  - Seller → @yahoo.com only
  - Support → @hotmail.com only
  - Admin → any email (hardcoded credentials)

### 🔐 Authentication & Security
- Laravel Sanctum token-based authentication
- Google OAuth via Laravel Socialite
- Forgot password with 6-digit OTP (10 min expiry)
- OTP verify → Reset password flow
- Rate limiting — 5 failed attempts = 15 min lockout
- Login attempts tracked in database
- Bcrypt password hashing
- Tokens revoked on logout

### 🏪 Product Management
- Full CRUD for products (Admin & Seller)
- Categories & subcategories with parent-child relation
- Multiple image upload with Intervention Image resize
- Product variants (Size, Color, Material) with separate SKU
- Auto SKU generation on product create
- Stock quantity with low stock threshold
- Draft / Published / Archived status
- Bulk import via CSV (PhpSpreadsheet)
- Low stock alerts via scheduled command
- Soft delete for products

### 🛒 Cart & Wishlist
- Database-backed cart per user
- Guest cart via session
- Cart items with product & variant tracking
- Wishlist CRUD per user
- Cart total calculation (subtotal, tax, shipping, discount)

### 💳 Order Management
- Full order lifecycle: Pending → Processing → Shipped → Delivered
- Order items with product snapshot (price at time of order)
- Stock deduction on order placement
- Order cancellation logic
- Return / Refund request handling
- Payment status tracking: Pending / Paid / Failed / Refunded

### 💰 Payment System
- Cash on Delivery (COD)
- Bank Transfer
- Stripe-ready structure
- Payment status management
- Refund processing

### 🚚 Shipping
- Shipping address CRUD per user
- Multiple addresses with default flag
- Delivery charges per city/region

### 📦 Inventory Management
- Real-time stock deduction on order
- Low stock threshold alerts
- Scheduled daily check:low-stock command
- Low stock email to admin & seller

### 📊 Admin Dashboard API
- Real-time stats: total orders, revenue, users, products
- Month-over-month growth calculation
- Recent orders with customer info
- Low stock products list

### 🎯 Discount & Coupon System
- Coupon code validation
- Percentage & fixed amount types
- Expiry date enforcement
- Usage limit per user
- Minimum order amount condition

### ⭐ Reviews & Ratings
- Product reviews with star rating (1–5)
- Verified purchase check
- Admin approve/reject moderation
- Average rating calculation per product

### 🔍 Search System
- Product search with filters
- Search query logging (SearchLog model)
- Search analytics API for admin
- Filter by: category, price range, brand, rating

### 📩 Email Notifications
- OTP emails via Gmail SMTP
- Order confirmation emails
- Low stock alert emails
- Laravel Queue for async email sending

### 📧 Email Campaigns
- Campaign CRUD with stats tracking
- Sent count, open count, click count, revenue
- User segments management
- Newsletter management
- Email templates

### 📝 Blog Management
- Blog posts CRUD with slug generation
- Blog categories
- Author attribution
- Draft / Published status
- Tags support

### 🧑‍💼 Admin APIs
- User management
- Product moderation
- Order management
- Coupon management
- Dashboard stats
- Search analytics
- Campaign management

### 👤 Profile Management
- Profile update (name only — email locked)
- Avatar upload with storage
- Password change with current password verify
- Separate profile endpoints per role (admin/seller)

---

## 🛠️ Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| 🐘 PHP | 8.2 | Server Language |
| 🚀 Laravel | 11 | API Framework |
| 🐬 MySQL | 8.0 | Database |
| 🔑 Laravel Sanctum | v4 | API Token Auth |
| 🛡️ Spatie Permission | v6 | Role Management |
| 🖼️ Intervention Image | v3 | Image Processing |
| 🔗 Laravel Socialite | v5 | Google OAuth |
| 📊 PhpSpreadsheet | v2 | CSV/Excel Import |
| 📄 Laravel DomPDF | Latest | PDF Generation |
| 📧 Laravel Mail | Built-in | Email System |
| ⏰ Laravel Scheduler | Built-in | Cron Jobs |
| 🗄️ Laravel Eloquent | Built-in | ORM |

---

## 📁 Project Structure

```
shoppro-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── CartController.php
│   │   │   │   ├── WishlistController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── ComparisonController.php
│   │   │   │   ├── SearchController.php
│   │   │   │   ├── ReviewController.php
│   │   │   │   ├── Admin/
│   │   │   │   │   ├── DashboardController.php
│   │   │   │   │   ├── ProfileController.php
│   │   │   │   │   ├── CampaignController.php
│   │   │   │   │   ├── BlogController.php
│   │   │   │   │   └── SearchAnalyticsController.php
│   │   │   │   └── Seller/
│   │   │   │       ├── DashboardController.php
│   │   │   │       ├── SellerOrderController.php
│   │   │   │       ├── SellerAnalyticsController.php
│   │   │   │       └── SellerProfileController.php
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php
│   │       ├── SellerMiddleware.php
│   │       ├── CustomerMiddleware.php
│   │       ├── SupportMiddleware.php
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
│   │   ├── Coupon.php
│   │   ├── Review.php
│   │   ├── LoginAttempt.php
│   │   ├── PasswordResetOtp.php
│   │   ├── SearchLog.php
│   │   ├── EmailCampaign.php
│   │   └── BlogPost.php
│   ├── Mail/
│   │   ├── OtpMail.php
│   │   └── LowStockMail.php
│   └── Console/
│       └── Commands/
│           └── CheckLowStock.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── RoleSeeder.php
│       └── AdminSeeder.php
├── routes/
│   ├── api.php
│   └── console.php
├── storage/
│   └── app/public/     # Product images, avatars
└── .env
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- XAMPP (or any local server with MySQL)
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

# 6. Configure .env file (see below)

# 7. Run database migrations
php artisan migrate

# 8. Seed the database (creates roles + admin user)
php artisan db:seed

# 9. Create storage symlink
php artisan storage:link

# 10. Start the server
php artisan serve
```

### Server runs at
```
http://localhost:8000
```

---

## ⚙️ Environment Configuration

Update your `.env` file with these values:

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

# Gmail SMTP (for OTP & notification emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@shoppro.com"
MAIL_FROM_NAME="ShopPro"

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

# JWT (if used)
JWT_SECRET=your_jwt_secret
```

---

## 🔑 Default Admin Credentials

```
Email:    malik.umerkhan97@gmail.com
Password: malikawan97
```

> ⚠️ These are seeded automatically via `php artisan db:seed`

---

## 📡 API Endpoints

### Auth Routes
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

### Product Routes
```
GET    /api/products
GET    /api/products/{id}
POST   /api/products              (admin/seller)
PUT    /api/products/{id}         (admin/seller)
DELETE /api/products/{id}         (admin/seller)
POST   /api/products/{id}/images  (admin/seller)
PATCH  /api/products/{id}/status  (admin/seller)
POST   /api/products/bulk-upload  (admin/seller)
```

### Admin Routes (auth + admin middleware)
```
GET    /api/admin/dashboard/stats
GET    /api/admin/products/low-stock
GET    /api/admin/search-analytics
GET    /api/admin/campaigns
POST   /api/admin/campaigns
GET    /api/admin/blog/posts
POST   /api/admin/blog/posts
GET    /api/admin/profile
PUT    /api/admin/profile
POST   /api/admin/profile/avatar
POST   /api/admin/profile/change-password
```

### Seller Routes (auth + seller middleware)
```
GET    /api/seller/dashboard
GET    /api/seller/orders
GET    /api/seller/analytics
GET    /api/seller/profile
PUT    /api/seller/profile
POST   /api/seller/profile/avatar
POST   /api/seller/profile/change-password
```

### Cart & Wishlist
```
GET    /api/cart
POST   /api/cart
PUT    /api/cart/{itemId}
DELETE /api/cart/{itemId}
POST   /api/cart/coupon
GET    /api/wishlist
POST   /api/wishlist
DELETE /api/wishlist/{itemId}
```

---

## 🗄️ Database Tables

| Table | Purpose |
|---|---|
| users | All users with roles |
| roles / permissions | Spatie role tables |
| products | Product listings |
| product_images | Product image gallery |
| product_variants | Size/Color/Material variants |
| categories | Product categories (nested) |
| orders | Customer orders |
| order_items | Items within each order |
| carts | User shopping carts |
| cart_items | Items in cart |
| wishlists | User wishlists |
| wishlist_items | Items in wishlist |
| coupons | Discount coupons |
| reviews | Product reviews |
| login_attempts | Rate limiting tracker |
| password_reset_otps | OTP for password reset |
| search_logs | Search query analytics |
| email_campaigns | Marketing campaigns |
| blog_posts | Blog articles |
| personal_access_tokens | Sanctum tokens |

---

## ⏰ Scheduled Commands

```bash
# Check low stock products daily and email admin
php artisan check:low-stock

# Run scheduler (add to cron in production)
php artisan schedule:run
```

---

## 📜 Useful Artisan Commands

```bash
php artisan migrate              # Run all migrations
php artisan migrate:fresh        # Drop all + re-migrate
php artisan db:seed              # Seed roles + admin user
php artisan migrate:fresh --seed # Fresh start with seed
php artisan storage:link         # Link public storage
php artisan route:list           # View all API routes
php artisan config:clear         # Clear config cache
php artisan cache:clear          # Clear application cache
php artisan serve                # Start development server
```

---

## 🔗 Frontend Repository

This backend powers the ShopPro React frontend.

👉 **[ShopPro Frontend Repository](https://github.com/YOUR_USERNAME/shoppro-frontend)**

Frontend runs at `http://localhost:5173`

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

> 💡 **Portfolio Project** — Built to demonstrate professional Laravel API development, multi-role authentication, e-commerce business logic, and RESTful API design patterns.
