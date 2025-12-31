# Data Model: Stripe Webhooks, Transactions & Receipts

## PaymentTransaction Model

Represents a webhook event received from Stripe and its processing status.

### Fields
- `id`: integer (primary key, auto-increment)
- `payment_item_id`: integer (foreign key to payment_items)
- `stripe_event_id`: string (unique identifier from Stripe, indexed, unique)
- `stripe_event_type`: string (type of webhook event, e.g., payment_intent.succeeded)
- `payload`: json (full webhook payload from Stripe)
- `processed_at`: timestamp (when webhook was processed, nullable)
- `processing_status`: string (pending, processing, completed, failed)
- `processing_attempts`: integer (number of processing attempts, default: 0)
- `processing_error`: text (error message if processing failed, nullable)
- `created_at`: timestamp
- `updated_at`: timestamp

### Relationships
- Belongs to: `payment_item` (one payment item to many transactions)

### Indexes
- `stripe_event_id` (unique index to prevent duplicate processing)
- `payment_item_id` (index for querying transactions by payment item)
- `processing_status` (index for querying by processing status)

## PaymentReceipt Model

Represents a generated receipt for a completed payment transaction.

### Fields
- `id`: integer (primary key, auto-increment)
- `payment_transaction_id`: integer (foreign key to payment_transactions, unique)
- `receipt_number`: string (unique receipt identifier, indexed)
- `receipt_data`: json (receipt content and metadata)
- `delivery_status`: string (pending, sent, failed)
- `delivered_at`: timestamp (when receipt was delivered, nullable)
- `delivery_attempts`: integer (number of delivery attempts, default: 0)
- `delivery_error`: text (error message if delivery failed, nullable)
- `created_at`: timestamp
- `updated_at`: timestamp

### Relationships
- Belongs to: `payment_transaction` (one transaction to one receipt)

### Indexes
- `payment_transaction_id` (unique index, since each transaction has one receipt)
- `receipt_number` (unique index for receipt lookup)
- `delivery_status` (index for querying by delivery status)