# Research: Stripe Webhooks, Transactions & Receipts

**Feature**: 004-stripe-webhooks-transactions-receipts
**Created**: 2025-01-01

## Research Findings

### 1. Webhook Event Types

**Decision**: Handle payment_intent.succeeded, payment_intent.payment_failed, and checkout.session.completed events

**Rationale**: These are the primary events that indicate payment status changes that need to be processed by our system.

**Alternatives considered**:
- payment_intent.processing: Not needed as we only care about final states
- charge.succeeded: Not needed as we're using Payment Intents API
- invoice.payment_succeeded: Not needed as we're not using Stripe Billing

**Details**:
- `payment_intent.succeeded`: Triggered when a payment is successfully completed
- `payment_intent.payment_failed`: Triggered when a payment attempt fails
- `checkout.session.completed`: Triggered when a checkout session is completed

### 2. Signature Verification

**Decision**: Use Stripe's built-in webhook signature verification with Laravel's request handling

**Rationale**: Stripe provides a secure and tested method for verifying webhook signatures that prevents unauthorized access.

**Alternatives considered**:
- Custom signature verification: Risky and unnecessary when Stripe provides a tested solution
- No signature verification: Insecure and violates PCI compliance

**Implementation approach**:
- Use `Stripe\Webhook::constructEvent()` method
- Verify against webhook signing secret from environment
- Return 400 error for invalid signatures

### 3. Idempotency Handling

**Decision**: Implement idempotency using Stripe event ID with database uniqueness constraint

**Rationale**: Stripe event IDs are guaranteed to be unique, preventing duplicate processing of the same event.

**Alternatives considered**:
- In-memory cache: Not persistent across deployments
- Redis cache: Adds infrastructure complexity
- Application-level tracking: More complex than database approach

**Implementation approach**:
- Create unique constraint on `stripe_event_id` in `payment_transactions` table
- Catch unique constraint violations as duplicate processing prevention
- Log duplicate attempts for monitoring

### 4. Receipt Format

**Decision**: Generate PDF receipts with HTML template containing transaction details

**Rationale**: PDF format is standard for receipts, provides professional appearance, and is easily downloadable by customers.

**Alternatives considered**:
- Plain text emails: Less professional appearance
- HTML-only emails: May not render consistently across email clients
- Image receipts: Larger file size, less accessible

**Receipt content**:
- Business information (name, address, contact)
- Customer information
- Transaction details (amount, date, payment method)
- Transaction ID and receipt number
- Payment confirmation details

### 5. Queue Configuration

**Decision**: Use Laravel's database queue driver with configurable retry attempts

**Rationale**: Database queues are reliable, persistent, and don't require additional infrastructure like Redis.

**Alternatives considered**:
- Redis queue: Requires additional infrastructure
- Beanstalkd: Requires additional infrastructure
- Sync processing: Would block webhook processing

**Configuration**:
- Retry attempts: 3
- Retry delay: Exponential backoff (1s, 5s, 25s)
- Queue name: `receipts` for receipt processing jobs

### 6. PDF Generation Library

**Decision**: Use Laravel Snappy (wkhtmltopdf wrapper) for PDF generation

**Rationale**: Well-maintained Laravel package that integrates easily with existing codebase.

**Alternatives considered**:
- DomPDF: Pure PHP solution but slower rendering
- TCPDF: More complex to use
- Third-party service: Additional dependency and cost

### 7. Email Delivery Service

**Decision**: Use Laravel's built-in mail system with configurable mail driver

**Rationale**: Leverages existing Laravel mail infrastructure and allows flexibility in mail provider choice.

**Alternatives considered**:
- Third-party email APIs directly: More complex implementation
- Queue-specific email handling: Unnecessary complexity

## Technical Decisions Summary

1. **Webhook Events**: Handle payment_intent.succeeded, payment_intent.payment_failed, and checkout.session.completed
2. **Signature Verification**: Use Stripe's built-in verification method
3. **Idempotency**: Database uniqueness constraint on Stripe event ID
4. **Receipt Format**: PDF with HTML template
5. **Queue System**: Laravel database queue with retry logic
6. **PDF Generation**: Laravel Snappy with wkhtmltopdf
7. **Email Delivery**: Laravel's built-in mail system