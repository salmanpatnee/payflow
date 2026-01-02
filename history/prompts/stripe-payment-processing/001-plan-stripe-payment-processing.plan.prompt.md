---
id: 001
title: plan-stripe-payment-processing
stage: plan
date: 2025-01-07
surface: agent
model: Qwen
feature: stripe-payment-processing
branch: 003-stripe-payment-processing
user: salmanabdul.ghani
command: /sp.plan
labels: ["plan", "payment-processing", "stripe", "architecture"]
links:
  spec: /specs/003-stripe-payment-processing/spec.md
  ticket: null
  adr: null
  pr: null
files:
  - /specs/003-stripe-payment-processing/plan.md
  - /specs/003-stripe-payment-processing/research.md
  - /specs/003-stripe-payment-processing/data-model.md
  - /specs/003-stripe-payment-processing/quickstart.md
  - /specs/003-stripe-payment-processing/contracts/payment-processing-api.yaml
tests:
  - none
---

## Prompt

$ARGUMENTS

## Response snapshot

# Implementation Plan: Payment Processing

**Branch**: `003-stripe-payment-processing` | **Date**: 2025-01-07 | **Spec**: [link to spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-stripe-payment-processing/spec.md`

**Note**: This template is filled in by the `/sp.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Implementation of secure payment processing using Stripe. The system will allow clients to make payments through a secure form, handle successful and failed payment responses, and update payment status in the application. The implementation will follow PCI DSS compliance by using Stripe Elements for secure card input and avoiding storage of sensitive payment data. The user experience will include real-time status updates and appropriate error handling for failed payments.

## Technical Context

**Language/Version**: PHP 8.3, TypeScript 5.3
**Primary Dependencies**: Laravel 12, Inertia.js v2, Vue 3, Stripe PHP SDK, Tailwind CSS v4
**Storage**: PostgreSQL database with payment_collections, payment_items, and payment_transactions tables
**Testing**: Pest v4 with feature, unit, and browser tests
**Target Platform**: Web application with responsive design
**Project Type**: Web application (Laravel backend with Inertia.js/Vue 3 frontend)
**Performance Goals**: Payment processing completes within 10 seconds for 90% of transactions
**Constraints**: Must comply with PCI DSS requirements, no sensitive payment data stored locally, secure HTTPS connection required
**Scale/Scope**: Support multiple concurrent payment collections with individual payment items

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Based on the PayFlow Constitution:
- ✅ Payment Security First: Using Stripe Elements for secure card input, server-side validation, no card data storage
- ✅ Data Integrity & Auditability: Storing complete Stripe responses, audit trails for all transactions
- ✅ User Experience Excellence: Real-time status updates, mobile-responsive design, clear progress indicators
- ✅ Simplicity & Maintainability: Using Laravel conventions, Inertia.js, shadcn-vue components
- ✅ Test-First Development: Feature tests for all CRUD operations, Stripe integration tests
- ✅ Performance & Scalability: Database indexing, eager loading to prevent N+1 queries

## Project Structure

### Documentation (this feature)

```text
specs/003-stripe-payment-processing/
├── plan.md              # This file (/sp.plan command output)
├── research.md          # Phase 0 output (/sp.plan command)
├── data-model.md        # Phase 1 output (/sp.plan command)
├── quickstart.md        # Phase 1 output (/sp.plan command)
├── contracts/           # Phase 1 output (/sp.plan command)
└── tasks.md             # Phase 2 output (/sp.tasks command - NOT created by /sp.plan)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Payment/
│   │       ├── PaymentCollectionController.php
│   │       ├── PaymentItemController.php
│   │       └── PaymentWebhookController.php
│   ├── Requests/
│   │   └── Payment/
│   │       └── ProcessPaymentRequest.php
│   └── Middleware/
├── Models/
│   ├── PaymentCollection.php
│   ├── PaymentItem.php
│   └── PaymentTransaction.php
├── Services/
│   └── StripePaymentService.php
├── Observers/
│   └── PaymentItemObserver.php
└── Providers/
    └── AppServiceProvider.php

resources/
├── js/
│   ├── Pages/
│   │   └── Payment/
│   │       ├── PaymentPage.vue
│   │       └── ThankYouPage.vue
│   └── Components/
│       └── Payment/
│           └── StripePaymentForm.vue
├── css/
└── views/
    └── app.blade.php

routes/
├── web.php
└── api.php

database/
├── migrations/
│   ├── create_payment_collections_table.php
│   ├── create_payment_items_table.php
│   └── create_payment_transactions_table.php
├── seeders/
└── factories/
    ├── PaymentCollectionFactory.php
    └── PaymentItemFactory.php

tests/
├── Feature/
│   └── Payment/
│       ├── ProcessPaymentTest.php
│       └── PaymentWebhookTest.php
├── Unit/
│   └── Services/
│       └── StripePaymentServiceTest.php
└── Browser/
    └── Payment/
        └── PaymentFlowTest.php

config/
└── stripe.php

public/
└── vendor/
    └── [shadcn-vue assets]
```

**Structure Decision**: Web application with Laravel backend and Vue 3 frontend using Inertia.js for seamless integration. The payment processing feature will be implemented with dedicated controllers, models, and services following Laravel conventions and the PayFlow Constitution.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |

# Research: Payment Processing Implementation

## Decision: Stripe Integration Approach
**Rationale**: Using Stripe Elements for secure payment form input to ensure PCI DSS compliance. This approach keeps sensitive card data from touching our servers, as card information is sent directly to Stripe and returns a payment method token.

**Alternatives considered**:
- Raw Stripe API integration without Elements: Requires higher PCI compliance level
- Other payment processors (PayPal, Square): Stripe has better developer experience and documentation for our use case

## Decision: Payment Flow Architecture
**Rationale**: Implementing a PaymentIntent-based flow where each payment item gets its own intent. This allows for individual payment tracking and status updates as specified in the feature requirements.

**Alternatives considered**:
- Single PaymentIntent for entire collection: Would make it harder to track individual payment items
- PaymentElement vs Elements: Elements provides more granular control over form components

## Decision: Frontend Implementation
**Rationale**: Using Vue 3 component with Inertia.js for the payment page to maintain consistency with existing application architecture. The component will handle Stripe Elements integration and real-time status updates.

**Alternatives considered**:
- Pure JavaScript implementation: Would break consistency with existing Vue components
- React component: Would require additional dependencies and break consistency

## Decision: Webhook Handling
**Rationale**: Implementing webhook endpoints to receive payment status updates from Stripe. This ensures payment status is accurately reflected even if the client disconnects during payment processing.

**Alternatives considered**:
- Polling for status updates: Less efficient and real-time than webhooks
- Client-side status updates only: Could miss status changes that happen when client is disconnected

## Decision: Error Handling Strategy
**Rationale**: Implementing comprehensive error handling for both client-side and server-side errors. This includes network timeouts, declined payments, and Stripe API errors as specified in the feature requirements.

**Alternatives considered**:
- Simplified error handling: Would not meet the comprehensive error handling requirements
- Server-side only error handling: Would not provide immediate feedback to users

## Best Practices: PCI DSS Compliance
**Rationale**: Following Stripe's recommended approach for PCI compliance by using Stripe Elements and not storing sensitive card data. This minimizes our PCI scope significantly.

**Best practices identified**:
- Never send card data to your server
- Use HTTPS for all payment-related pages
- Implement proper authentication for admin functions
- Log payment attempts for audit purposes without storing sensitive data

## Best Practices: Security Implementation
**Rationale**: Implementing security measures beyond PCI compliance to protect against fraud and ensure data integrity.

**Best practices identified**:
- Validate payment amounts on the server-side (never trust client-side values)
- Use idempotency keys to prevent duplicate charges
- Verify webhook signatures to ensure authenticity
- Implement rate limiting on payment endpoints
- Store complete Stripe responses for audit trails

## Best Practices: User Experience
**Rationale**: Creating a smooth payment experience that meets the success criteria defined in the specification.

**Best practices identified**:
- Real-time status updates after each payment
- Clear progress indicators showing completion status
- Helpful error messages with actionable next steps
- Mobile-responsive design for all payment forms
- Unlimited retry attempts for failed payments

# Data Model: Payment Processing

## Entities

### PaymentCollection
Represents a collection of payment items that can be paid together

**Fields**:
- `id`: integer, primary key, auto-increment
- `uuid`: string, unique identifier for shareable links, indexed
- `title`: string, display name for the collection
- `description`: text, optional description of the collection
- `status`: enum, values: 'active', 'completed', 'expired', default: 'active', indexed
- `expires_at`: timestamp, optional expiration date
- `admin_user_id`: integer, foreign key to users table
- `created_at`: timestamp
- `updated_at`: timestamp
- `deleted_at`: timestamp, for soft deletes

**Relationships**:
- `hasMany`: PaymentItem (one-to-many with payment_items table)
- `belongsTo`: User (admin user who created the collection)

**Validation Rules**:
- `title`: required, max:255
- `description`: optional, max:1000
- `status`: required, in: active, completed, expired
- `expires_at`: optional, date format
- `uuid`: required, unique

### PaymentItem
Represents an individual payment amount within a collection

**Fields**:
- `id`: integer, primary key, auto-increment
- `payment_collection_id`: integer, foreign key to payment_collections table, indexed
- `description`: string, description of the payment item
- `amount`: decimal, payment amount (with 2 decimal places)
- `currency`: string, currency code (e.g., USD), default: 'usd'
- `status`: enum, values: 'pending', 'processing', 'completed', 'failed', default: 'pending', indexed
- `stripe_payment_intent_id`: string, unique identifier from Stripe
- `paid_at`: timestamp, when payment was completed
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**:
- `belongsTo`: PaymentCollection (many-to-one with payment_collections table)
- `hasMany`: PaymentTransaction (one-to-many with payment_transactions table)

**Validation Rules**:
- `description`: required, max:255
- `amount`: required, numeric, min:0.50 (minimum 50 cents)
- `currency`: required, in: usd, eur, gbp (or other supported currencies)
- `status`: required, in: pending, processing, completed, failed
- `stripe_payment_intent_id`: unique, nullable

### PaymentTransaction
Records the complete transaction details from Stripe

**Fields**:
- `id`: integer, primary key, auto-increment
- `payment_item_id`: integer, foreign key to payment_items table, indexed
- `stripe_response`: json, complete response from Stripe API
- `status`: string, status from Stripe (e.g., succeeded, failed, requires_payment_method)
- `amount`: decimal, amount charged
- `currency`: string, currency code
- `payment_method`: string, payment method type (e.g., card, bank_transfer)
- `failure_code`: string, failure code if payment failed
- `failure_message`: text, failure message if payment failed
- `created_at`: timestamp

**Relationships**:
- `belongsTo`: PaymentItem (many-to-one with payment_items table)

**Validation Rules**:
- `stripe_response`: required, json format
- `status`: required, string
- `amount`: required, numeric
- `currency`: required, string

## State Transitions

### PaymentItem Status Transitions
- `pending` → `processing`: When Stripe PaymentIntent is created
- `processing` → `completed`: When Stripe confirms successful payment
- `processing` → `failed`: When Stripe confirms payment failure
- `failed` → `processing`: When user retries payment
- `completed` → (no further transitions): Completed payments are immutable

### PaymentCollection Status Transitions
- `active` → `completed`: When all associated PaymentItems are completed
- `active` → `expired`: When expires_at date is reached

## Indexes
- `payment_collections.uuid`: For fast lookup of shareable links
- `payment_collections.status`: For filtering collections by status
- `payment_items.payment_collection_id`: For efficient relationship queries
- `payment_items.status`: For filtering payment items by status
- `payment_transactions.payment_item_id`: For efficient relationship queries

## Constraints
- PaymentItem.amount must be positive (minimum 50 cents)
- PaymentCollection.uuid must be unique
- PaymentItem.stripe_payment_intent_id must be unique
- Once PaymentItem.status is 'completed', it cannot be changed
- PaymentCollection status becomes 'completed' only when all PaymentItems are 'completed'

# API Contracts: Payment Processing

## Payment Collection Endpoints

### GET /api/payment-collections/{uuid}
**Description**: Retrieve payment collection details by UUID

**Request**:
- Method: GET
- Path: `/api/payment-collections/{uuid}`
- Headers: 
  - Accept: application/json
- Parameters:
  - uuid (path): Unique identifier for the payment collection

**Response**:
- Success (200):
```json
{
  "id": 1,
  "uuid": "abc123def456",
  "title": "Invoice Collection",
  "description": "Payment for services rendered",
  "status": "active",
  "expires_at": "2024-12-31T23:59:59Z",
  "items": [
    {
      "id": 1,
      "description": "Service Fee",
      "amount": 150.00,
      "currency": "usd",
      "status": "pending"
    }
  ]
}
```

- Not Found (404): Collection not found or expired
- Unprocessable Entity (422): Invalid UUID format

## Payment Processing Endpoints

### POST /api/payment-collections/{uuid}/payment-intent
**Description**: Create a Stripe PaymentIntent for a specific payment item

**Request**:
- Method: POST
- Path: `/api/payment-collections/{uuid}/payment-intent`
- Headers:
  - Accept: application/json
  - Content-Type: application/json
- Parameters:
  - uuid (path): Unique identifier for the payment collection
- Body:
```json
{
  "payment_item_id": 1,
  "payment_method_types": ["card"]
}
}
```

- Unauthorized (401): Invalid or expired collection
- Unprocessable Entity (422): Invalid request data
- Conflict (409): Payment already in progress

### POST /api/payment-collections/{uuid}/confirm-payment
**Description**: Confirm a payment after successful processing

**Request**:
- Method: POST
- Path: `/api/payment-collections/{uuid}/confirm-payment`
- Headers:
  - Accept: application/json
  - Content-Type: application/json
- Parameters:
  - uuid (path): Unique identifier for the payment collection
- Body:
```json
{
  "payment_intent_id": "pi_abc123",
  "payment_item_id": 1
}
```

**Response**:
- Success (200):
```json
{
  "status": "completed",
  "message": "Payment confirmed successfully"
}
```

- Unauthorized (401): Invalid collection or payment item
- Unprocessable Entity (422): Invalid request data
- Failed Dependency (424): Payment failed at Stripe

## Webhook Endpoint

### POST /webhooks/stripe
**Description**: Handle Stripe webhook events for payment status updates

**Request**:
- Method: POST
- Path: `/webhooks/stripe`
- Headers:
  - Content-Type: application/json
  - Stripe-Signature: t=123456789,v1=signature1,v0=signature0
- Body: Stripe webhook event payload

**Response**:
- Success (200): Webhook processed successfully
- Bad Request (400): Invalid webhook signature

## Public Payment Page Endpoints

### GET /pay/{uuid}
**Description**: Display the payment page for a collection

**Request**:
- Method: GET
- Path: `/pay/{uuid}`
- Headers:
  - Accept: text/html

**Response**:
- Success (200): Payment page with collection details
- Not Found (404): Collection not found or expired
- Forbidden (403): Collection is completed or expired

### GET /pay/{uuid}/thank-you
**Description**: Display thank you page after all payments are completed

**Request**:
- Method: GET
- Path: `/pay/{uuid}/thank-you`
- Headers:
  - Accept: text/html

**Response**:
- Success (200): Thank you page
- Not Found (404): Collection not found or not completed

# Quickstart: Payment Processing Implementation

## Setup

### 1. Environment Configuration
```bash
# Copy environment variables
cp .env.example .env

# Add Stripe keys to .env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### 2. Database Migrations
```bash
# Run migrations to create payment tables
php artisan migrate
```

### 3. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install
```

## Key Implementation Steps

### 1. Create Payment Models
```bash
# Generate models with factories and migrations
php artisan make:model PaymentCollection -mfr
php artisan make:model PaymentItem -mfr
php artisan make:model PaymentTransaction -mfr
```

### 2. Implement Stripe Service
Create `app/Services/StripePaymentService.php` to handle all Stripe interactions:
- PaymentIntent creation
- Payment confirmation
- Webhook processing

### 3. Create Payment Controllers
```bash
# Generate controllers
php artisan make:controller Payment/PaymentCollectionController
php artisan make:controller Payment/PaymentItemController
php artisan make:controller Payment/PaymentWebhookController
```

### 4. Frontend Components
Create Vue components in `resources/js/Pages/Payment/`:
- `PaymentPage.vue` - Main payment page
- `ThankYouPage.vue` - Success page
- `StripePaymentForm.vue` - Payment form component

### 5. Routes
Add payment routes to `routes/web.php`:
- `/pay/{uuid}` - Public payment page
- `/pay/{uuid}/thank-you` - Thank you page
- Webhook endpoint

## Testing

### Unit Tests
```bash
# Test Stripe service
php artisan test --filter="StripePaymentServiceTest"

# Test models
php artisan test --filter="PaymentItemTest"
```

### Feature Tests
```bash
# Test payment processing
php artisan test --filter="ProcessPaymentTest"

# Test webhook handling
php artisan test --filter="PaymentWebhookTest"
```

### Browser Tests
```bash
# Test complete payment flow
php artisan test --filter="PaymentFlowTest"
```

## Configuration

### Stripe Webhooks
1. Go to Stripe Dashboard > Developers > Webhooks
2. Add endpoint: `https://yourdomain.com/webhooks/stripe`
3. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`

### Environment Variables
```env
# Stripe keys
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Optional: Stripe API version
STRIPE_API_VERSION=2023-10-16
```

## Deployment Notes

### Production Setup
1. Switch to production Stripe keys (sk_live_, pk_live_)
2. Ensure HTTPS is enabled (required by Stripe)
3. Configure webhook endpoints in Stripe Dashboard
4. Set up queue worker for processing webhooks

### Security
- Never expose STRIPE_SECRET_KEY in frontend
- Verify webhook signatures with STRIPE_WEBHOOK_SECRET
- Validate payment amounts match database records
- Use idempotency keys for all Stripe API calls

## Outcome

- ✅ Impact: Created comprehensive implementation plan for payment processing feature with research, data model, API contracts, and quickstart guide
- 🧪 Tests: none
- 📁 Files: /specs/003-stripe-payment-processing/plan.md, /specs/003-stripe-payment-processing/research.md, /specs/003-stripe-payment-processing/data-model.md, /specs/003-stripe-payment-processing/quickstart.md, /specs/003-stripe-payment-processing/contracts/payment-processing-api.yaml
- 🔁 Next prompts: /sp.tasks to break the plan into implementable tasks
- 🧠 Reflection: The implementation plan is complete with all necessary components for development, including technical architecture, data models, API contracts, and setup instructions.

## Evaluation notes (flywheel)

- Failure modes observed: none
- Graders run and results (PASS/FAIL): PASS
- Prompt variant (if applicable): none
- Next experiment (smallest change to try): none