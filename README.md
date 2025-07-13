# 🛒 Symfony E-commerce Microservices

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat&logo=php)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-7.3-000000.svg?style=flat&logo=symfony)](https://symfony.com)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-11-366AC3.svg?style=flat&logo=php)](https://phpunit.de)

[![DDD](https://img.shields.io/badge/Architecture-Domain%20Driven%20Design-blue.svg?style=flat)](https://en.wikipedia.org/wiki/Domain-driven_design)
[![Hexagonal](https://img.shields.io/badge/Architecture-Hexagonal-green.svg?style=flat)](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software))
[![CQRS](https://img.shields.io/badge/Pattern-CQRS-purple.svg?style=flat)](https://martinfowler.com/bliki/CQRS.html)
[![Event Driven](https://img.shields.io/badge/Architecture-Event%20Driven-red.svg?style=flat)](https://en.wikipedia.org/wiki/Event-driven_architecture)
[![Microservices](https://img.shields.io/badge/Architecture-Microservices-yellow.svg?style=flat)](https://microservices.io/)

A **production-ready e-commerce microservices architecture** built with **Symfony**, **FrankenPHP**, and **Docker**. This project demonstrates enterprise-grade software architecture patterns including **Domain-Driven Design (DDD)**, **CQRS**, **Event-Driven Architecture**, and **Hexagonal Architecture**.

## ✨ Features

- 🏗️ **Microservices Architecture** - Order and Invoice services with clear bounded contexts
- 🧠 **Domain-Driven Design** - Clean separation of Domain, Application, and Infrastructure layers
- ⚡ **CQRS Pattern** - Separate command and query buses using Symfony Messenger
- 🔄 **Event-Driven Architecture** - Asynchronous inter-service communication via RabbitMQ
- 🚀 **FrankenPHP Performance** - Blazing-fast PHP application server with worker mode
- 🐳 **Docker Environment** - Complete containerized development and production setup
- 🗄️ **Multi-Database Setup** - PostgreSQL with separate databases per microservice
- 🧪 **Comprehensive Testing** - Tests with high coverage
- 📚 **OpenAPI Documentation** - Auto-generated API documentation with Swagger UI

## 🏗️ Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Order Service │    │ Invoice Service │    │  Shared Domain  │
│                 │    │                 │    │                 │
│ • Order Creation│    │ • Invoice Gen   │    │ • Value Objects │
│ • Status Updates│    │ • File Upload   │    │ • Events        │
│ • Order Queries │    │ • Email Sending │    │ • Exceptions    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   Message Bus   │
                    │   (RabbitMQ)    │
                    └─────────────────┘
```

### 🎯 Microservices

#### Order Service
- **Domain**: Order lifecycle management
- **Database**: `app_order` (PostgreSQL)
- **Responsibilities**: Order creation, status updates, order queries
- **Events**: `OrderCreatedEvent`, `OrderShippedEvent`

#### Invoice Service  
- **Domain**: Invoice generation and management
- **Database**: `app_invoice` (PostgreSQL)
- **Responsibilities**: Invoice file uploads, invoice distribution
- **Events**: `InvoiceUploadedEvent`, `InvoiceSentEvent`

## 🚀 Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (v20.10+)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/sergioalmela/symfony-ddd-ecommerce-microservices.git
   cd symfony-ddd-ecommerce-microservices
   ```

2. **Build and start the environment**
   ```bash
   make build
   make start
   ```

3. **Set up databases and run migrations**
   ```bash
   make sf c="doctrine:database:create --connection=order"
   make sf c="doctrine:database:create --connection=invoice"
   make sf c="doctrine:migrations:migrate --no-interaction --configuration=config/migrations_order.yaml"
   make sf c="doctrine:migrations:migrate --no-interaction --configuration=config/migrations_invoice.yaml"
   ```

4. **Access the application**
   - **API Info**: https://localhost
   - **Health Check**: https://localhost/health
   - **API Documentation**: https://localhost/api/doc
   - **RabbitMQ Management**: http://localhost:15673 (admin/!ChangeMe!)

## 📁 Project Structure

```
src/
├── Order/                          # Order Microservice
│   ├── Application/               # Use cases & handlers
│   │   ├── Command/              # Write operations
│   │   └── Query/                # Read operations
│   ├── Domain/                   # Business logic
│   │   ├── Entity/              # Aggregates
│   │   ├── ValueObject/         # Value objects
│   │   ├── Event/               # Domain events
│   │   └── Repository/          # Domain interfaces
│   └── Infrastructure/          # External adapters
│       ├── Http/Controller/     # REST controllers
│       ├── Persistence/         # Database implementation
│       └── Listener/            # Event listeners
├── Invoice/                       # Invoice Microservice
│   └── [same structure as Order]
└── Shared/                        # Shared Domain
    └── Domain/
        ├── ValueObject/          # Common value objects
        ├── Event/               # Event infrastructure
        └── Exception/           # Domain exceptions
```

## 🛠️ Development

### Available Commands

```bash
# Docker Environment
make build              # Build Docker images
make start              # Start all services
make down               # Stop all services
make logs               # View container logs

# Development
make bash               # Access PHP container
make cc                 # Clear Symfony cache

# Database Operations
make order-migrate      # Run Order migrations
make invoice-migrate    # Run Invoice migrations
make order-migrate-diff # Generate Order migration diff

# Testing & Quality
make test               # Run PHPUnit tests
make cs-fix             # Fix code style (PHP-CS-Fixer)
make phpstan            # Run static analysis
make rector             # Run automated refactoring
make qa                 # Run full quality assurance suite

# Messaging
make sf c="messenger:consume async"  # Start message consumer
```

### Code Quality Standards

This project maintains high code quality through:

- **PHPStan Level 6**: Static analysis with strict type checking
- **PHP-CS-Fixer**: Modern PHP code style (PSR-12 compatible)
- **Rector**: Automated refactoring and PHP version upgrades
- **PHPUnit**: Comprehensive test coverage
- **Symfony Insight**: Code quality analysis

## 🧪 Testing

### Running Tests

```bash
# Run all tests
make test

# Run specific test groups
make test c="--group=order"
make test c="--group=invoice"
make test c="--group=e2e"

# Run with coverage
make test c="--coverage-html coverage/"
```

### Test Architecture

The project follows testing best practices with:

- **Unit Tests**: To test the use cases and domain logic
- **Test Doubles**: Fakes, spies, and mocks for isolation
- **Builder Pattern**: Fluent test data creation

```php
// Example test builder usage
$order = OrderBuilder::anOrder()
    ->withId(OrderId::generate())
    ->withPrice(Price::of(29.99))
    ->withStatus(OrderStatus::PENDING)
    ->build();
```

## 🔧 Configuration

### Environment Variables

```bash
# Database
DATABASE_ORDER_URL=postgresql://app:!ChangeMe!@database:5432/app_order
DATABASE_INVOICE_URL=postgresql://app:!ChangeMe!@database:5432/app_invoice

# Messaging
MESSENGER_TRANSPORT_DSN=amqp://app:!ChangeMe!@rabbitmq:5672/%2f/messages

# Application
APP_ENV=prod
APP_SECRET=your-secret-key
```

### Key Configuration Files

- `config/packages/messenger.yaml` - Message bus configuration
- `config/packages/doctrine.yaml` - Multi-database setup
- `config/migrations_order.yaml` - Order service migrations
- `config/migrations_invoice.yaml` - Invoice service migrations

## 📊 API Documentation

The project includes interactive API documentation powered by OpenAPI/Swagger. Access the documentation at:

🔗 **https://localhost/api/doc**

This interface allows you to:
- View all available endpoints
- Test API endpoints directly from the browser
- See request/response schemas
- Understand authentication requirements

### Order Endpoints

```http
POST   /orders                    # Create new order
GET    /orders                    # List orders
GET    /orders/{orderId}          # Get order details
PATCH  /orders/{orderId}/status   # Update order status
```

### Invoice Endpoints

```http
POST   /invoices/{orderId}/upload # Upload invoice file
POST   /invoices/{invoiceId}/send # Send invoice to customer
```

### Example API Usage

```bash
# Get API information
curl -X GET https://localhost/

# Check API health
curl -X GET https://localhost/health

# Create an order
curl -X POST https://localhost/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "01234567-89ab-cdef-0123-456789abcdef",
    "sellerId": "01234567-89ab-cdef-0123-456789abcdef",
    "productId": "01234567-89ab-cdef-0123-456789abcdef",
    "quantity": 2,
    "price": 29.99
  }'

# Upload invoice (PDF only)
curl -X POST https://localhost/invoices/{orderId}/upload \
  -F "sellerId=01234567-89ab-cdef-0123-456789abcdef" \
  -F "file=@invoice.pdf"
```

## 🏭 Production Deployment

### Docker Production Build

```bash
# Build production images
docker compose -f compose.yaml -f compose.prod.yaml build

# Deploy to production
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### Performance Optimizations

- **FrankenPHP Worker Mode**: Application warm-up for faster response times
- **OPcache**: Bytecode caching enabled in production
- **Database Indexing**: Optimized database queries
- **Message Queue**: Asynchronous processing for heavy operations

### Coding Standards

- Follow PSR-12 coding standards
- Write comprehensive tests
- Use type hints and return types
- Follow SOLID principles
- Maintain high PHPStan level compliance

## 📈 Architecture Decisions

### Why This Architecture?

- **Microservices**: Enables independent scaling and deployment of business domains
- **DDD**: Aligns software structure with business domains for better maintainability
- **CQRS**: Separates read and write operations for optimal performance
- **Event-Driven**: Enables loose coupling and eventual consistency between services
- **Hexagonal**: Keeps business logic independent of infrastructure concerns

## 🙏 Acknowledgments

- Built on top of [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker)
- Inspired by Domain-Driven Design principles
- Uses modern PHP and Symfony best practices

