# Data Model: Foundation & System Setup

## Core Entities

### Admin User
Represents an authenticated administrator with access to the dashboard and administrative functions

**Fields:**
- `id` (unsignedBigInteger, primary key, auto-increment)
- `name` (string, max 255, required)
- `email` (string, max 255, unique, required)
- `email_verified_at` (timestamp, nullable)
- `password` (string, required)
- `remember_token` (string, max 100, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Relationships:**
- One-to-many: `payment_collections` (admin user creates multiple payment collections)

**Validation Rules:**
- Name: required, string, max:255
- Email: required, string, email, max:255, unique:users
- Password: required, string, min:8, confirmed

### Payment Collection
Represents a collection of payment items that can be shared with clients

**Fields:**
- `id` (unsignedBigInteger, primary key, auto-increment)
- `uuid` (string, unique, indexed, required) - for shareable links
- `title` (string, max 255, required)
- `description` (text, nullable)
- `status` (enum: 'active', 'completed', 'expired', default: 'active', indexed)
- `expires_at` (timestamp, nullable) - when the collection link expires
- `admin_user_id` (unsignedBigInteger, foreign key to users.id)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Relationships:**
- Many-to-one: `admin_user` (belongs to admin user)
- One-to-many: `payment_items` (one collection has multiple payment items)

**Validation Rules:**
- Title: required, string, max:255
- Description: nullable, string
- Status: in ['active', 'completed', 'expired']

### Payment Item
Represents an individual payment amount and description within a collection

**Fields:**
- `id` (unsignedBigInteger, primary key, auto-increment)
- `payment_collection_id` (unsignedBigInteger, foreign key to payment_collections.id)
- `description` (string, max 255, required)
- `amount` (unsignedDecimal, precision 10, scale 2, required) - in cents
- `status` (enum: 'pending', 'processing', 'completed', 'failed', default: 'pending', indexed)
- `stripe_payment_intent_id` (string, max 255, nullable) - reference to Stripe
- `paid_at` (timestamp, nullable) - when payment was completed
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Relationships:**
- Many-to-one: `payment_collection` (belongs to payment collection)
- One-to-many: `payment_transactions` (one item can have multiple transaction attempts)

**Validation Rules:**
- Description: required, string, max:255
- Amount: required, numeric, min:0.50 (minimum 50 cents)
- Status: in ['pending', 'processing', 'completed', 'failed']

### Payment Transaction
Records all payment attempts for audit trail purposes

**Fields:**
- `id` (unsignedBigInteger, primary key, auto-increment)
- `payment_item_id` (unsignedBigInteger, foreign key to payment_items.id)
- `stripe_response` (json, required) - complete Stripe response
- `status` (string, max 255, required) - status from Stripe
- `error_message` (text, nullable) - if payment failed
- `created_at` (timestamp)

**Relationships:**
- Many-to-one: `payment_item` (belongs to payment item)

**Validation Rules:**
- Stripe_response: required, json
- Status: required, string, max:255

## State Transitions

### Payment Collection States
- `active` → `completed`: When all payment items are completed
- `active` → `expired`: When expires_at is reached

### Payment Item States
- `pending` → `processing`: When payment intent is created
- `processing` → `completed`: When payment is successfully confirmed
- `processing` → `failed`: When payment fails
- `failed` → `processing`: When client retries payment

## Indexes
- `payment_collections.uuid` (unique, for shareable links)
- `payment_collections.status` (for filtering)
- `payment_items.status` (for filtering)
- `payment_items.stripe_payment_intent_id` (for lookups)

## Constraints
- Payment collections cannot be edited once any payment item has a status other than 'pending'
- Payment items become immutable once status is 'completed'
- Amount validation happens on the backend to prevent client-side manipulation
- Soft deletes are used for payment collections to preserve transaction history