# Symfony DDD E-commerce

A Symfony project for marketplace order management and PDF invoices, built with PHP 8.4, Symfony 7.3, Doctrine, PostgreSQL, RabbitMQ, and FrankenPHP.

The project started as a PHP adaptation of a [marketplace coding challenge](docs/challenge.md). That brief explains the initial business scope and simplifications. The original assignment asks for Node.js, TypeScript, a NoSQL database, and two microservices. This implementation uses Symfony and PostgreSQL; Order and Invoice currently share one application and deployment, with separate databases and asynchronous events.

## Current workflow

An order references one product and contains its price, quantity, customer ID, seller ID, and status. Sellers can create, list, inspect, and update orders, and upload a PDF invoice for an order.

An invoice is marked as sent when it exists and its order is `SHIPPED`, regardless of whether shipment or upload happens first. The challenge defines sending as storing a timestamp. Repeated messages preserve the first timestamp. Products and customers are represented by IDs, and authentication and user roles are optional extensions in the original brief.

Orders follow `CREATED → ACCEPTED → SHIPPING_IN_PROGRESS → SHIPPED`. Rejection is allowed from `CREATED` or `ACCEPTED`. `SHIPPED` and `REJECTED` are terminal; repeating the current status has no effect. A future backoffice correction would need its own operation and business rules.

## Architecture

The code is organized into Order and Invoice contexts, each with three layers:

- **Domain:** aggregates, value objects, repository interfaces, and business rules.
- **Application:** commands, queries, and their handlers.
- **Infrastructure:** HTTP controllers, Doctrine persistence, and message consumers.

Shared code contains identifiers, event and bus contracts, and infrastructure adapters. Doctrine XML mappings keep persistence configuration outside the domain entities. Application handlers depend on interfaces implemented by Doctrine, Symfony Messenger, and file storage adapters.

Each context records outgoing events in its database alongside the aggregate change, in one transaction. Outbox workers publish those events through RabbitMQ with publisher confirmations. Invoice uses creation events to maintain its local order projection and checks the sending prerequisites on both shipment and upload. Consumers tolerate repeated delivery.

```text
src/
├── Order/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
├── Invoice/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
└── Shared/
    ├── Domain/
    └── Infrastructure/
```

## Local setup

Docker Compose and Make are required. PHP and Composer run inside the application container.

```bash
git clone https://github.com/sergioalmela/symfony-ddd-ecommerce-microservices.git
cd symfony-ddd-ecommerce-microservices
test -f .env || cp .env.dist .env

make build
docker compose up -d --wait

make sf c="doctrine:database:create --connection=order --if-not-exists"
make sf c="doctrine:database:create --connection=invoice --if-not-exists"
make order-migrate
make invoice-migrate
```

The database initialization script creates `app_order` and `app_invoice` on a fresh PostgreSQL volume. The database creation commands also cover existing volumes where those databases are missing. The container installs Composer dependencies if `vendor/` is empty.

Start each of these workers in a separate terminal:

```bash
make sf c="app:outbox:publish order --watch"
make sf c="app:outbox:publish invoice --watch"
make sf c="messenger:consume async -vv"
```

Order creation and status changes remain available while RabbitMQ is down. Pending events stay in the outboxes. If an outbox worker exits after a broker error, restart it after RabbitMQ recovers. Omit `--watch` to publish the current backlog once. Run the migrations above before starting these workers.

Messages that exhaust their retries go to the `failed` RabbitMQ queue. Inspect that queue in the RabbitMQ management UI and retry interactively with `make sf c="messenger:failed:retry"`. The AMQP transport does not support `messenger:failed:show` or listing messages by ID.

| Service | Address |
| --- | --- |
| API | https://localhost |
| OpenAPI documentation | https://localhost/api/doc |
| RabbitMQ management | http://localhost:15673 |

The default RabbitMQ management credentials are `app` / `!ChangeMe!`. See [.env.dist](.env.dist) for application defaults and [compose.yaml](compose.yaml) for container configuration.

For an offline demo, build the images and install dependencies before disconnecting. Start the existing environment with `make up`; `make build` pulls images and requires network access.

## API

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/orders` | Create an order. |
| GET | `/orders?sellerId={sellerId}` | List a seller's orders. |
| GET | `/orders/{id}?sellerId={sellerId}` | Get an order's details. |
| PATCH | `/orders/{id}/status` | Update status using `sellerId` and `status` in the JSON body. |
| POST | `/invoices/{orderId}/upload` | Upload a PDF using multipart fields `sellerId` and `file`. |

This implementation uses UUID v4 identifiers. Seller IDs supplied in requests are filters; the application does not authenticate users. Invoice uploads must match the seller recorded in the local order projection. `GET /orders` returns an array.

Malformed JSON, missing fields, incorrect field types, and invalid values return `400`. Missing orders or another seller's order return `404`. Duplicate orders, duplicate invoices, and invalid status transitions return `409`.

Create an order:

```bash
curl -k https://localhost/orders \
  -H 'Content-Type: application/json' \
  -d '{
    "id": "80ebe6dd-fc51-41d5-ad53-59386d3ee6aa",
    "productId": "70ebe6dd-fc51-41d5-ad53-59386d3ee6bb",
    "quantity": 2,
    "price": 29.99,
    "customerId": "60ebe6dd-fc51-41d5-ad53-59386d3ee6cc",
    "sellerId": "50ebe6dd-fc51-41d5-ad53-59386d3ee6dd"
  }'
```

Upload an invoice after the creation event has been consumed:

```bash
curl -k https://localhost/invoices/80ebe6dd-fc51-41d5-ad53-59386d3ee6aa/upload \
  -F 'sellerId=50ebe6dd-fc51-41d5-ad53-59386d3ee6dd' \
  -F 'file=@invoice.pdf'
```

Uploads accept PDF bytes up to 10 MiB. Files are stored under `public/uploads`, served at `/uploads/...`, and retained in the Compose `invoice_uploads` volume. Invoice sending is triggered by events; there is no public invoice-send endpoint.

## Development

```bash
make test           # Run PHPUnit tests.
make coverage       # Generate HTML coverage with Xdebug.
make cs-check       # Check code style.
make cs-fix         # Apply code style fixes.
make sf c="cache:warmup --env=dev"
make phpstan        # Run static analysis.
make rector         # Preview automated refactoring.
make qa             # Run style checks, static analysis, and tests.
make down           # Stop the environment.
```

Tests cover application handlers, lifecycle transitions, request validation, and storage. Database integration tests additionally cover HTTP responses, multipart uploads, repeated delivery, event arrival order, transaction rollback, and outbox recovery. Additional Make targets are listed by `make help`.

Database integration tests are opt-in. Point both `DATABASE_ORDER_URL` and `DATABASE_INVOICE_URL` at dedicated test databases, create them, and apply the corresponding migrations with `APP_ENV=test`. The current multi-connection configuration does not automatically add `_test` to these URLs. Then run:

```bash
docker compose exec -e APP_ENV=test -e RUN_DATABASE_TESTS=1 -e XDEBUG_MODE=coverage php bin/phpunit
```

Set the database URLs in that container's environment before running the setup commands and tests. The integration tests wrap their database changes in transactions and roll them back.

## Current limitations

Order and Invoice share a deployment, and events use PHP serialization. Independent deployments and an explicit integration message format remain future improvements. Authentication is not implemented. File writes and database transactions are separate operations, so a database failure after a file write can leave an unused file.

The [architecture review](docs/architecture-review.md) preserves the original findings and records the completed fixes for items 1–6. CI reproducibility remains a separate follow-up.

## Project background

The [challenge summary](docs/challenge.md) preserves the initial requirements and explains the differences in this implementation. It provides context for the current scope and future changes.

The Docker environment is based on [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker). Its setup documentation is retained under [docs](docs/).
