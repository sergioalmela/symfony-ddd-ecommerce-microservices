# Marketplace coding challenge

This document summarizes the original MediaMarktSaturn brief, titled *Coding Challenge: Marketplace*, supplied as `Marketplace Coding Challenge 1.pdf`. It preserves the initial requirements and their scope as background for the project.

## Goal

Build a simplified marketplace application for order management and invoicing. The system should contain two microservices, using asynchronous communication where appropriate, and support a live technical-interview demonstration.

## Order requirements

- An order references exactly one product.
- Its properties include order ID, price, quantity, product ID, customer ID, seller ID, and status.
- The five statuses are Created, Accepted, Rejected, Shipping in progress, and Shipped.
- A REST endpoint creates orders.
- Sellers can list orders, inspect an order, and update an order.
- Products and customers are outside the assignment's scope. Their IDs may be any IDs.

The brief names the statuses but does not prescribe a complete transition graph. Supported transitions are an implementation decision that should be documented and tested.

## Invoice requirements

- Sellers can upload PDF invoices for their orders.
- An invoice includes an invoice ID, order ID, and a timestamp recording when it was sent.
- An invoice is sent when it exists and the associated order is Shipped.
- Storing the timestamp is sufficient to represent sending. Email delivery is not required.

The send condition depends on both invoice availability and order status. The implementation needs to account for an invoice uploaded before shipment and an invoice uploaded after shipment.

## Required technology and delivery

- Node.js 16 or later with TypeScript 4.5 or later.
- A NoSQL database, with MongoDB given as an example.
- Microservice architecture.
- RESTful APIs.
- Asynchronous communication through a broker or queue, such as Kafka, RabbitMQ, or Pub/Sub.
- A local development setup.
- Basic testing.

## Optional extensions

- A production Dockerfile for each microservice.
- A CI pipeline with its configuration included in the project.
- Seller and customer user types with different access rules for each service.
- Tests for every important aspect of the application.

Authentication and role-based access belong to this optional scope. The core requirement that sellers upload invoices for their own orders still informs ownership checks.

## Evaluation and demonstration

Evaluation considers requirement fulfillment, code structure and readability, architecture, framework and tooling choices, and testing strategy.

The brief favors a correct, understandable partial implementation and an explanation of how the problem was approached. The application must work offline on the developer's machine for the live demonstration.

## This repository's adaptation

| Original brief | This repository |
| --- | --- |
| Node.js and TypeScript | PHP 8.4 and Symfony 7.3. |
| NoSQL database | PostgreSQL, accessed through Doctrine. |
| Two microservices | Order and Invoice contexts with separate databases, sharing one Symfony application and deployment. |
| Asynchronous messaging | Transactional outboxes relay domain events through Symfony Messenger and RabbitMQ. |
| Order and invoice workflow | Commands, queries, aggregates, value objects, and an Invoice-side order projection. |
| Sending represented by a timestamp | `Invoice.send()` stores `sentAt`; no email adapter is needed for the assignment. |
| Basic testing | Unit tests plus opt-in HTTP and persistence integration tests. |
| Local and offline demonstration | Docker Compose setup; images and dependencies must be prepared while online. |

This repository adapts the business scenario to Symfony and PostgreSQL. It does not satisfy the original technology requirements or provide two independently deployable services. The source brief does not establish that these deviations were approved.

The [architecture review](architecture-review.md) distinguishes defects affecting the required workflow from optional access-control features and further production reliability improvements. The challenge provides historical context; any future feature scope can be documented separately as it is decided.
