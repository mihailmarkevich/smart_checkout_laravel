# SmartCheckout – Laravel E-commerce API Demo

SmartCheckout is a small but **production-style E-commerce API** built on **Laravel 10** to demonstrate usage of:

- Laravel’s **Eloquent ORM**, Service Container & Service Providers  
- **RESTful API design** with DTOs, Resources, Form Requests & custom validation handling  
- **JWT authentication** (stateless API)  
- **Queues & workers** (Redis + jobs)  
- **Scheduling (cron)** for background tasks  
- **CSV reporting** using Query Builder  
- **API documentation** with Swagger (L5-Swagger)  
- Full **Dockerized environment** (PHP-FPM + Nginx + MySQL + Redis)

The goal of this project is to look and feel like a real-world backend of a small shop, not just a “todo list”.

---

## Features Overview

### Business features

- **Authentication (JWT)**
  - `POST /api/auth/login` – login with email/password, returns JWT
  - `GET /api/auth/me` – get current authenticated user

- **Catalog & Inventory**
  - `GET /api/products` – paginated list of products with stock info
  - `POST /api/products` – create product + stock (validated via FormRequest)

- **Customers & Orders**
  - `GET /api/customers` – paginated customers
  - `GET /api/customers/{id}/orders` – customer’s orders with items
  - `GET /api/orders` – filterable orders (status, customer email)
  - `GET /api/orders/{id}` – order details
  - `POST /api/orders/checkout` – checkout endpoint:
    - validates payload (FormRequest)
    - creates/updates customer
    - reserves stock
    - creates order + order items in a transaction

- **Order lifecycle & WMS integration**
  - `POST /api/orders/{id}/cancel` – cancel order with domain rules
  - `POST /api/orders/{id}/resync-wms` – dispatches job to (re)sync order to WMS via queue  
    (`SyncOrderToWms` job on Redis queue)

- **Reports**
  - `GET /api/reports/sales.csv?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD`  
    Streams **CSV sales report**, built with Query Builder:
    - product SKU / name
    - orders count
    - total quantity
    - total revenue

- **Health check**
  - `GET /api/health` – checks DB & Redis connectivity, returns `ok` / `degraded`

---

## Laravel Features Demonstrated

### 1. Eloquent ORM & Relationships

- Models: `Product`, `StockItem`, `Customer`, `Order`, `OrderItem`, `User`, etc.
- Relations:
  - `Order` → `customer` (belongsTo)
  - `Order` → `items` (hasMany)
  - `OrderItem` → `product` (belongsTo)
  - `Product` → `stockItem` (hasOne)
- Eager loading & N+1 avoidance with `with()`, `load()`
- Pagination via `paginate()` and API Resources

### 2. Form Requests & Validation

- Dedicated FormRequest classes:
  - `CheckoutRequest`
  - `Auth\LoginRequest`
  - `Product\StoreProductRequest`
- **Global JSON validation error format** via overridden `Handler::invalidJson()`:
  - unified structure:

    ```json
    {
      "status": false,
      "message": "First validation error message",
      "errors": {
        "field": ["error1", "error2"]
      }
    }
    ```

### 3. Service Container & Service Providers

- Business logic in **services**:
  - `OrderService` encapsulates checkout/cancel rules
  - `JwtService` encapsulates token generation/verification
  - `WmsClientInterface` + `HttpWmsClient` simulate WMS integration
- Explicit bindings in `App\Providers\AppServiceProvider` (and/or dedicated providers), e.g.:

  ```php
  public function register(): void
  {
      $this->app->bind(
          \App\Services\Contracts\OrderServiceInterface::class,
          \App\Services\OrderService::class
      );

      $this->app->singleton(
          \App\Services\Contracts\WmsClientInterface::class,
          \App\Services\HttpWmsClient::class
      );
  }
  ```

- Constructor DI in controllers/services:

  ```php
  public function __construct(
      private readonly OrderService $orderService
  ) {}
  ```

### 4. JWT Authentication

- Stateless auth with custom `JwtService`
- Middleware `JwtAuthMiddleware`:
  - reads `Authorization: Bearer <token>`
  - validates token
  - resolves `User` and attaches it to the request
- Guards/routes configured so that API endpoints require JWT except login/health/etc.

### 5. Queues & Jobs (Redis)

- Docker `redis` service used as queue backend.
- `queue-worker` container runs:

  ```bash
  php artisan queue:work redis --queue=default,wms --sleep=3 --tries=3
  ```

- Jobs:
  - `SyncOrderToWms` – simulates async order sync to WMS
- Queue configuration via `.env`:

  ```env
  QUEUE_CONNECTION=redis
  REDIS_HOST=redis
  REDIS_PORT=6379
  ```

### 6. Scheduling (cron)

- Custom console command (e.g. daily report/export logic) in `app/Console/Commands`.
- Registered in `app/Console/Kernel.php` `schedule()` method to run daily at a specific time.
- Manually trigger schedule:

  ```bash
  docker-compose exec app php artisan schedule:run
  ```

In production, this would be executed via system cron every minute.

### 7. API Resources (Transformers)

- `OrderResource`, `OrderItemResource`, `CustomerResource`, `ProductResource`:
  - decouple DB schema from API response format
  - hide internal fields
  - control embedding of relations
  - format money/dates

Example:

```php
class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'number'   => $this->order_number,
            'status'   => $this->status,
            'total'    => (float) $this->total_amount,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items'    => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
```

### 8. Swagger / OpenAPI

- **L5-Swagger** integration:
  - annotation-based docs on controllers (`@OA\Get`, `@OA\Post`, `@OA\Schema`, etc.)
- Generate docs:

  ```bash
  docker-compose exec app php artisan l5-swagger:generate
  ```

- Swagger UI available at:

  - `http://localhost:8080/api/documentation`

### 9. Testing

- **Feature tests** for:
  - auth (login)
  - checkout
  - orders listing / filters
  - reports
- **Unit tests** for:
  - `OrderService` and domain logic
- Run tests:

  ```bash
  docker-compose exec app php artisan test
  ```

---

## Tech Stack

- **PHP** 8.3 (PHP-FPM)
- **Laravel** 10
- **MySQL** 8
- **Redis** 7 (queue, cache)
- **Nginx** (reverse proxy, PHP-FPM backend)
- **Docker / docker-compose**
- **Swagger (OpenAPI)** via `darkaonline/l5-swagger`

---

## Getting Started

### Prerequisites

- Docker & docker-compose installed
- Ports **8080**, **3307**, **6379** are free on your host machine

### 1. Clone the repository

```bash
git clone https://github.com/your-org/smartcheckout-api.git
cd smartcheckout-api
```

### 2. Environment configuration

Inside `./`:

```bash
cp .env.example .env
```

Update your `.env` to match Docker services:

> Note: `DB_HOST=db` and `REDIS_HOST=redis` must match the service names in `docker-compose.yml`.

### 3. Build & start containers

From the repository root:

```bash
docker-compose up -d --build
```

This will start the following services:

- `smartcheckout_app` – PHP-FPM (Laravel application)
- `smartcheckout_queue` – queue worker (Redis)
- `smartcheckout_scheduler` - scheduler 
- `smartcheckout_nginx` – Nginx (exposed on port `8080`)
- `smartcheckout_db` – MySQL 8 (exposed on port `3307`)
- `smartcheckout_redis` – Redis 7 (exposed on port `6379`)

### 4. Install PHP dependencies

```bash
docker-compose exec app composer install
```

### 5. Generate application key

```bash
docker-compose exec app php artisan key:generate
```

### 6. Run migrations & seeders

```bash
docker-compose exec app php artisan migrate --seed
```

This will:

- create the database schema
- seed:
  - default admin user
  - ~100 customers
  - ~1000 products (+ stock)
  - ~200 orders with items

### 7. Generate Swagger docs (optional but recommended)

```bash
docker-compose exec app php artisan l5-swagger:generate
```

### 8. Access the application

- API base URL: `http://localhost:8080/api`
- Swagger UI: `http://localhost:8080/api/documentation`
- Health check: `http://localhost:8080/api/health`

---

## Usage

### Authentication

1. **Login**

   ```http
   POST /api/auth/login
   Content-Type: application/json

   {
     "email": "admin@example.com",
     "password": "password"
   }
   ```

2. **Response**

   ```json
   {
     "access_token": "eyJ0eXAiOiJKV1QiLCJh...",
     "token_type": "Bearer",
     "expires_in": 3600
   }
   ```

3. **Authenticated request**

   ```http
   GET /api/orders
   Authorization: Bearer <access_token>
   ```

### Example endpoints

- `GET /api/products` – list products with stock
- `POST /api/products` – create product + stock
- `GET /api/customers` – list customers
- `GET /api/customers/{id}/orders` – customer’s orders
- `GET /api/orders` – list/filter orders
- `GET /api/orders/{id}` – order details
- `POST /api/orders/checkout` – place an order
- `POST /api/orders/{id}/cancel` – cancel order
- `POST /api/orders/{id}/resync-wms` – enqueue WMS sync
- `GET /api/reports/sales.csv?date_from=2025-01-01&date_to=2025-12-31` – CSV sales report

---

## Running Tests

From the project root:

```bash
docker-compose exec app php artisan test
```

Run specific suites or tests:

```bash
docker-compose exec app php artisan test tests/Feature
docker-compose exec app php artisan test --filter=CheckoutTest
```

---

## Queues & Scheduler

### Queues

Queue worker is started by the `queue-worker` service:

```yaml
command: php artisan queue:work redis --queue=default,wms --sleep=3 --tries=3
```

To test queue processing:

1. Ensure Redis and `queue-worker` are running (`docker-compose ps`).
2. Call:

   ```http
   POST /api/orders/{id}/resync-wms
   Authorization: Bearer <token>
   ```

3. Watch worker logs:

   ```bash
   docker-compose logs -f queue-worker
   ```

### Scheduler

A scheduled command is registered in `app/Console/Kernel.php` to run daily (e.g. generate daily stats/report).

Run manually:

```bash
docker-compose exec app php artisan schedule:run
```

In real production, configure a cron job (on host or in a scheduler container):

```cron
* * * * * php /var/www/html/artisan schedule:run >> /var/log/cron.log 2>&1
```
