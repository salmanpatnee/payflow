# PayFlow Qwen Context - Stripe Webhooks, Transactions & Receipts

## Feature Context
Feature: 004-stripe-webhooks-transactions-receipts
Purpose: Implement robust Stripe webhook handling, transaction persistence, payment status synchronization, and receipt generation

## Technical Architecture

### Webhook Processing
- Endpoint: POST /webhooks/stripe
- Events: payment_intent.succeeded, payment_intent.payment_failed, checkout.session.completed
- Signature verification using Stripe's built-in method
- Idempotency via stripe_event_id uniqueness constraint
- Queue-based processing for reliability

### Data Models
- PaymentTransaction: Stores webhook events with processing status
- PaymentReceipt: Stores generated receipts with delivery status
- Relationships: One transaction to one receipt, many transactions to one payment item

### Receipt Generation
- PDF receipts using Laravel Snappy (wkhtmltopdf)
- HTML templates with transaction details
- Queue-based email delivery
- Database storage of receipt content

## Implementation Notes
- Use Laravel's database queue for webhook and receipt processing
- Implement retry logic with exponential backoff
- Store complete webhook payloads for audit trails
- Generate unique receipt numbers
- Follow Laravel conventions for controllers, models, and services

## Security Considerations
- Verify all webhook signatures before processing
- Validate webhook payloads against expected schema
- Prevent duplicate processing using unique constraints
- Sanitize receipt content before PDF generation

## Testing Approach
- Test webhook signature verification
- Test idempotency handling
- Test receipt generation and delivery
- Test failure scenarios and retries
- Use Stripe test events for validation