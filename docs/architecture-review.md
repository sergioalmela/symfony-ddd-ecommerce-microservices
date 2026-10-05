# Architecture review

Reviewed on 2026-10-04 against committed `main` at `1d3333c7f3eefdf6e75b346c5f359221e01e19f2`.

The review covered the domain, application handlers, controllers, persistence, Messenger configuration, tests, and CI. The local checkout had uncommitted changes, so verification used an isolated copy of the committed version. Those local changes were excluded from the review and left untouched.

Challenge context was added on 2026-10-05 after reading the original brief. The original review results are retained below; the follow-up records the implementation and subsequent verification.

## Implementation follow-up — 2026-10-05

Items 1–6 have been implemented. The original findings below describe the reviewed commit, so they remain as historical evidence.

| Item | Result |
| --- | --- |
| 1. Repair the broken paths | Seller-scoped Doctrine lookup uses `orderId`; details serialize the wrapped order; Invoice events are routed asynchronously, with an upload handler and support for events without subscribers. The order list retains its array response. |
| 2. Enforce invoice ownership | Upload compares the supplied seller with the local order projection before writing a file or invoice. UUIDs use a canonical lowercase representation so equivalent uppercase input still matches. Authentication remains outside this change. |
| 3. Recoverable publication | Aggregate changes and outgoing events share a transaction. Each database has an outbox; `app:outbox:publish` relays events with stable IDs and RabbitMQ publisher confirmations. Exhausted consumer retries go to an AMQP failure queue. |
| 4. Repeated and reordered events | Projection and shipment inserts are idempotent. Shipment is remembered even before order creation is consumed. Shipment and upload each check the sending prerequisites; per-order locking and a domain guard preserve the first send timestamp. |
| 5. Lifecycle rules | Enforced `CREATED → ACCEPTED → SHIPPING_IN_PROGRESS → SHIPPED`, with rejection from Created or Accepted and terminal Shipped/Rejected states. Repeating the current state emits no event. Status updates lock the order while checking and changing its state. |
| 6. Storage and HTTP boundary | Preserve PDF bytes, check actual MIME and a 10 MiB byte limit, store under the served uploads directory with a Compose volume, and propagate write failures. Validate JSON structure and field types; expected input and conflict errors return 400/404/409. |

Regression coverage was added alongside these fixes. The old invoice test dates now use relative dates, and update fixtures clear creation events to represent persisted aggregates. CI dependency resolution from item 7 remains unchanged.

Verification used an isolated checkout of the working changes, the current lock file, PHP 8.4.16, PostgreSQL 16, and RabbitMQ 4.1. Equivalent repository-documented commands were run non-interactively inside the isolated PHP container; shared development databases were not used.

| Check | Result |
| --- | --- |
| Full PHPUnit suite, database checks enabled | 109 tests, 273 assertions; passed. Xdebug covered 309/334 executable statements (92.5%) within the configured coverage scope. |
| PHPStan | Passed at the repository's configured level. |
| Full PHP-CS-Fixer check | Passed without formatting unrelated files. |
| Rector | Passed after applying its required changes to the new and modified code. |
| Symfony container and YAML lint | Passed. |
| Migrations | All three migrations for each context completed on fresh databases. |
| Real broker recovery | With RabbitMQ stopped, HTTP order creation and shipment committed and retained both outgoing events. Failed relay kept them; restarting RabbitMQ allowed publication and consumption. |
| Invoice workflow with real RabbitMQ | Multipart HTTP upload after shipment preserved PDF bytes; consuming its event stored the send timestamp. Duplicate shipment preserved that timestamp; InvoiceSent was consumed successfully and both outboxes drained. |
| Failure transport | An invalid event exhausted three retries and was retained in the RabbitMQ `failed` queue. AMQP does not support Symfony's individual failed-message listing command; use the management UI and interactive retry command. |

The [README](../README.md) documents the workers, migrations, lifecycle, upload contract, and opt-in database tests. No staging or production deployment was performed.

## Challenge context

The [original marketplace challenge](challenge.md) defines a deliberately simplified application. Sending an invoice requires only storing a timestamp; email delivery is outside the required scope. Seller/customer access rules are a bonus, and basic testing is required. Outbox infrastructure and complete integration coverage are further reliability improvements.

The brief requires Node.js, TypeScript, a NoSQL database, and two microservices. This repository uses PHP, Symfony, PostgreSQL, and one application deployment. It should be presented as an adaptation of the scenario. If assessed literally against the original requirements, these technology and deployment differences are gaps.

The strongest workflow findings remain the reproduced lookup and response failures, missing Invoice-event handlers, invoice ownership check, PDF byte corruption, and incomplete handling of shipment/upload arrival order. The absence of email delivery is appropriate for the assignment.

## Assessment

There is real hexagonal architecture and DDD work here. The dependency separation is meaningful: business code can run independently of Symfony, Doctrine, HTTP, and RabbitMQ. The areas needing the most improvement are business invariants, integration boundaries, and reliable event processing.

| Architecture | Assessment |
| --- | --- |
| Hexagonal | Yes. Repository, bus, and storage interfaces are ports; Doctrine, Messenger, HTTP, and local storage are adapters. |
| Clean architecture | Broadly yes. Dependencies point inward, although query responses could define a clearer boundary. |
| DDD | Yes, particularly tactical DDD: aggregates, value objects, repositories, and domain events. Business rules need more depth and enforcement. |
| CQRS / CQS | Command/query separation is implemented. Order queries still use the write aggregate and repository, so model segregation is limited. |
| Event-driven architecture | Yes. Order events drive Invoice behavior asynchronously. Recovery, idempotency, and workflow coordination are incomplete. |
| Microservices | The current deployment is a modular monolith with asynchronous messaging and separate databases. Both contexts share one Symfony kernel, application container, and deployment. |

Independent deployment is a central microservice characteristic; separate databases and folders alone do not provide it. The current structure can still be a sensible choice for this application. See [Lewis and Fowler's description of microservices](https://martinfowler.com/articles/microservices.html).

## What to keep

External Doctrine XML mappings are a strong choice here. The entities contain no Doctrine annotations or attributes, and neither Domain nor Application imports Symfony or Doctrine. That makes the separation substantive.

The application handlers have appropriate responsibilities: create value objects, load aggregates, invoke behavior, save, and publish events. Repository interfaces describe application needs without exposing query builders or entity managers.

Invoice's local `OrderProjection` is another good decision. It gives Invoice the information it needs without querying Order's database. Keep that approach and improve how the projection handles repeated messages and delays.

The handler tests demonstrate that the ports work: use cases can be exercised through fakes and spies without starting the infrastructure. That ability is central to [Cockburn's original ports-and-adapters architecture](https://alistair.cockburn.us/hexagonal-architecture).

## Priority improvements

### 1. Fix failures at the integration boundaries

Three concrete problems were reproduced:

- [DoctrineOrderRepository](../src/Order/Infrastructure/Persistence/Doctrine/Repository/DoctrineOrderRepository.php) queries `'id'` in `findByIdAndSeller()`, but the mapped entity property is `orderId`. Doctrine raises `UnrecognizedField`. This affects order details and status updates.
- [GetOrderDetailsController](../src/Order/Infrastructure/Http/Controller/GetOrderDetailsController.php) calls `toPrimitives()` on `GetOrderDetailsResponse`, which only contains an `order` property. Even after fixing the repository, the successful lookup path still fails.
- `InvoiceUploadedEvent` and `InvoiceSentEvent` have neither handlers nor transport routing. With the current [Messenger configuration](../config/packages/messenger.yaml), publishing them raises `NoHandlerForMessageException`.

The third problem is especially consequential: **invoice upload saves the invoice and then reports failure**. A database probe confirmed that the row remains in PostgreSQL after event publication throws. Retrying the upload then encounters an existing invoice.

Correct the mapped property, make the controller and response agree, and explicitly decide how Invoice events should be handled. If events may legitimately have zero subscribers, configure that behavior on the event bus.

### 2. Enforce invoice ownership using the order projection

[UploadInvoiceCommandHandler](../src/Invoice/Application/Command/UploadInvoice/UploadInvoiceCommandHandler.php) checks that an order projection exists, but never compares its seller with the seller uploading the invoice.

A probe created a projection owned by seller A, submitted an upload as seller B, and the handler accepted it.

The projection already contains the required information. Validate ownership before storing the file or creating the invoice.

Authentication and separate seller/customer access rules are a bonus in the original brief. If implementing that extension, derive seller identity from the authenticated caller. Currently, supplying a `sellerId` filters records; it does not establish who the caller is. Comparing the invoice seller with the order projection remains relevant to the core ownership requirement.

### 3. Make database changes and event publication recoverable

The challenge requires asynchronous messaging but does not prescribe a transactional outbox. The recommendations in this section strengthen reliability beyond the basic assignment.

Repository `save()` flushes immediately, then the handler publishes events. These operations can fail independently:

```text
Order commits → RabbitMQ publication fails → Invoice never learns about it
```

This is an architectural reliability gap. Retrying order creation encounters the existing order, which does not repair the missing event.

A transactional outbox would address it: persist the aggregate and outgoing event in the same database transaction, then publish the event through a retryable worker. This provides durable publication intent; consumers still need to tolerate duplicates.

Also configure a failure transport. The current configuration declares `failed`, but the setting selecting it is commented out. Symfony documents that messages are discarded after retries when no failure transport is selected. See [Messenger failure handling](https://symfony.com/doc/7.3/messenger.html#saving-retrying-failed-messages).

### 4. Handle repeated messages and either order of workflow events

[CreateOrderProjectionEventHandler](../src/Invoice/Infrastructure/Listener/CreateOrderProjectionEventHandler.php) always inserts a new projection. Replaying the same event against PostgreSQL produced a primary-key violation.

Make projection creation idempotent, with database enforcement that also handles concurrent deliveries. An event identifier would help support consumer deduplication.

The shipping workflow also assumes an invoice already exists when `OrderShippedEvent` arrives. If shipment happens first, sending throws; uploading an invoice later does not resume that workflow.

Define the prerequisites for delivery and re-evaluate them when either shipment or invoice upload occurs. Persist enough state to recover when messages arrive late.

Finally, `Invoice.send()` overwrites `sentAt` and emits another event on every call. Define whether repeated delivery commands should do nothing or represent an explicit resend.

### 5. Define lifecycle rules deliberately

[Order.updateStatus()](../src/Order/Domain/Entity/Order.php) validates whether a status exists, but allows any transition between valid statuses.

A probe successfully ran:

```text
SHIPPED → REJECTED → CREATED → SHIPPED
```

That produced two shipment events.

The brief lists five statuses but does not prescribe a complete transition graph. Document the supported transitions according to the intended business rules and enforce them in `Order`. Operations such as `accept()`, `reject()`, and `ship()` may express the intent more clearly than a general status setter. The probe demonstrates current behavior; a specific allowed transition sequence is an implementation decision.

This is where the DDD model can become substantially stronger. Value objects protect individual values; aggregates should protect valid changes over time.

[SendInvoiceCommandHandler](../src/Invoice/Application/Command/SendInvoice/SendInvoiceCommandHandler.php) sets a timestamp and publishes an event. Storing the timestamp fulfills the challenge's definition of sending. The earlier review incorrectly treated the absence of actual customer delivery as a defect. The relevant improvements are ensuring the invoice exists, its order is Shipped, and repeated messages do not unintentionally overwrite the timestamp.

### 6. Correct the storage contract and HTTP input handling

[UploadInvoiceController](../src/Invoice/Infrastructure/Http/Controller/UploadInvoiceController.php) base64-encodes the file, while [LocalStorageService](../src/Shared/Infrastructure/Service/LocalStorageService.php) writes that string unchanged. A probe confirmed that the resulting `.pdf` contains base64 text rather than PDF bytes.

The adapter also returns `/uploads/...` while writing under `/tmp/uploads`; the supplied web configuration serves `/app/public`. That URL has no corresponding serving path, and the upload directory has no persistent volume.

Define whether storage accepts raw bytes or an encoded representation, preserve the actual PDF bytes, and make the returned location usable and durable.

At the HTTP boundary, malformed JSON, a missing seller, and an invalid status all returned **500** in probes. Parse and validate request structure and types before constructing commands, while retaining domain validation for business rules. Expected input errors should receive consistent client-error responses.

### 7. Expand tests across the adapters and make CI reproducible

The existing suite has useful handler coverage, but no HTTP or persistence integration tests. Basic testing meets the type of testing requested by the challenge; complete coverage is a bonus. A few focused integration tests would catch the reproduced failures that the fake repositories miss.

Add focused tests for:

- Order creation, details, and status updates through HTTP and real persistence.
- Invoice ownership rejection before side effects.
- PDF upload preserving the original bytes.
- Repeated event delivery and shipment/upload arrival order.
- Failed publication and subsequent recovery.

There are also two existing test issues worth fixing:

- [SendInvoiceCommandHandlerTest](../tests/Invoice/Application/Command/SendInvoice/SendInvoiceCommandHandlerTest.php) uses January 2025 dates while `SentAt` compares against the real current date. Four tests fail at the time of this review. Inject a controllable clock where time determines validity.
- [Order update tests](../tests/Order/Application/Command/UpdateOrderStatus/UpdateOrderStatusCommandHandlerTest.php) load objects built with `Order::create()`, leaving their creation events recorded. They consequently expect `OrderCreatedEvent` during an update, which does not represent an aggregate loaded from persistence.

In [CI](../.github/workflows/ci.yml), the test job deletes `composer.lock` and runs `composer require`. It therefore tests a newly resolved dependency set. Install the committed lock file in the normal verification job; dependency-update testing can be a separate job.

## Later architectural refinements

### Reduce coupling through Shared

[Shared\Domain\OrderCreatedEvent](../src/Shared/Domain/Event/OrderCreatedEvent.php) imports Order's `Price` and `Quantity`, so its consumers depend on Order's domain representation.

For independently deployed services, use an explicit integration contract with primitive fields, an event ID, and a version that the serializer actually uses. Messenger currently uses native PHP serialization, so `toArray()` is not the wire format. See [Messenger serialization](https://symfony.com/doc/7.3/messenger.html#serializing-messages).

### Give queries explicit response data

Give queries explicit response data instead of returning mutable aggregates inside response wrappers. That makes the API contract clearer and allows reads to evolve independently.

Add pagination to `findBySeller()` when the dataset warrants it. Separate read databases are not necessary merely to claim CQRS; its useful distinction is between read and write models. See [Fowler on CQRS](https://martinfowler.com/bliki/CQRS.html).

## Verification results

Verification used the committed Composer lock file, PHP 8.4.16, and an isolated PostgreSQL 16 instance. Repository-native PHPUnit and PHPStan commands were run in Docker against the isolated source copy.

| Check | Result |
| --- | --- |
| PHPStan | Passed with the repository configuration. |
| PHPUnit | 45 tests executed; four date-related errors. The run also reported a coverage-mode warning because Xdebug coverage was not enabled. |
| Order migrations | Both migrations completed successfully. |
| Invoice migrations | Both migrations completed successfully. |
| Order persistence | A real save/reload preserved the order data. |
| Seller-scoped Doctrine lookup | Reproduced `UnrecognizedField` for `Order::$id`. |
| Order details controller | Reproduced undefined `GetOrderDetailsResponse::toPrimitives()` call. |
| Invoice ownership | Upload accepted a seller different from the order projection's seller. |
| PDF storage | Stored bytes were base64 text rather than the original PDF bytes. |
| HTTP input handling | Malformed JSON, missing seller, and invalid status each returned 500. |
| Invoice event publication | Both Invoice event types raised `NoHandlerForMessageException`. |
| Invoice upload persistence | Invoice remained saved after event publication failed. |
| Projection replay | Replaying `OrderCreatedEvent` raised a primary-key violation. |
| Order transitions | Reversal and repeated shipment were accepted, producing two shipment events. |

A full RabbitMQ deployment test was not run. Runtime probes used the configured Symfony container where applicable, real PostgreSQL for persistence checks, and isolated fakes for focused domain and ownership checks. The temporary database container and network were removed afterward.

## Recommended direction

Keep the existing layering and module boundaries, fix the required workflows, and add focused integration coverage. Present the current project as a Symfony/PostgreSQL adaptation of the challenge. Separate deployments matter if the objective is to fulfill the original two-microservice requirement; authentication, outbox infrastructure, and broader coverage can be treated as extensions. The challenge can remain the project's starting point if its scope expands later. The code demonstrates architectural understanding; reliable behavior across those boundaries is the next step.
