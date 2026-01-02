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