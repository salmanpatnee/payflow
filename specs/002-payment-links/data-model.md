# Data Model: Payment Link Generation & Client Flow

**Feature**: Payment Link Generation & Client Flow
**Date**: 2025-12-30

## Entity: PaymentCollection (Extended)

### Fields
- `id` (integer, primary key, auto-increment)
- `uuid` (string, unique, indexed) - existing field
- `payment_link_token` (string, unique, nullable, indexed) - new field
- `payment_link_expires_at` (timestamp, nullable) - new field
- `title` (string) - existing field
- `description` (text, nullable) - existing field
- `status` (string, default: 'active') - existing field
- `total_amount` (decimal) - existing field
- `due_date` (date, nullable) - existing field
- `admin_user_id` (integer, foreign key) - existing field
- `created_at` (timestamp)
- `updated_at` (timestamp)
- `deleted_at` (timestamp, nullable) - for soft deletes

### Relationships
- `hasMany` PaymentItem (existing relationship)
- `hasMany` ClientAccessRecord (new relationship)
- `belongsTo` User (admin user) (existing relationship)

### Validation Rules
- `payment_link_token` must be unique when present
- `payment_link_expires_at` must be a future date when present
- `payment_link_token` must be 32 characters when generated

### State Transitions
- When generating a payment link: `payment_link_token` and `payment_link_expires_at` are set
- When link expires: access is denied but collection state remains unchanged
- When all payment items are completed: collection status changes to 'completed'

## Entity: ClientAccessRecord

### Fields
- `id` (integer, primary key, auto-increment)
- `payment_collection_id` (integer, foreign key, indexed)
- `client_name` (string, nullable)
- `client_email` (string, nullable, validated as email)
- `access_token` (string, indexed) - the token used to access
- `ip_address` (string)
- `accessed_at` (timestamp)
- `user_agent` (text, nullable)
- `created_at` (timestamp)

### Relationships
- `belongsTo` PaymentCollection
- `hasOne` PaymentTransaction (optional, when payment is made via this access)

### Validation Rules
- `payment_collection_id` must reference an existing payment collection
- `client_email` must be a valid email address when provided
- `ip_address` should be validated as a proper IP format
- `access_token` should match the payment collection's token

### State Transitions
- New record created when client accesses payment link
- Information can be updated if client provides name/email later

## Entity: PaymentItem (No Changes)

### Fields (existing)
- `id` (integer, primary key, auto-increment)
- `payment_collection_id` (integer, foreign key, indexed)
- `description` (string)
- `amount` (decimal)
- `status` (string, default: 'pending')
- `due_date` (date, nullable)
- `stripe_payment_intent_id` (string, nullable)
- `paid_at` (timestamp, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

### Relationships (existing)
- `belongsTo` PaymentCollection
- `hasMany` PaymentTransaction

## Database Schema Changes

### Migration for PaymentCollection
```php
Schema::table('payment_collections', function (Blueprint $table) {
    $table->string('payment_link_token', 32)->nullable()->unique();
    $table->timestamp('payment_link_expires_at')->nullable();
    $table->index(['payment_link_token']);
    $table->index(['payment_link_expires_at']);
});
```

### Migration for ClientAccessRecord
```php
Schema::create('client_access_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payment_collection_id')->constrained('payment_collections');
    $table->string('client_name')->nullable();
    $table->string('client_email')->nullable();
    $table->string('access_token', 32)->index();
    $table->string('ip_address');
    $table->timestamp('accessed_at');
    $table->text('user_agent')->nullable();
    $table->timestamps();
    
    $table->index(['payment_collection_id', 'accessed_at']);
});
```

## Indexing Strategy

### Required Indexes
1. `payment_collections.payment_link_token` - for fast token lookup
2. `payment_collections.payment_link_expires_at` - for expiration queries
3. `client_access_records.payment_collection_id` - for access history queries
4. `client_access_records.access_token` - for access validation
5. `client_access_records.accessed_at` - for access pattern analysis

### Composite Indexes
1. `client_access_records(payment_collection_id, accessed_at)` - for access history by collection

## Data Integrity Rules

1. **Token Uniqueness**: Each payment link token must be unique across all collections
2. **Expiration Validation**: Payment link expiration date must be in the future when set
3. **Access Record Integrity**: Client access records must reference valid payment collections
4. **Email Validation**: Client email addresses must be properly formatted when provided
5. **IP Address Validation**: IP addresses must be in valid format (IPv4 or IPv6)

## Privacy & Data Retention

### Client Information Handling
- Client name and email are optional fields
- Information is stored only for the duration of the payment collection lifecycle
- Data is retained according to the application's data retention policy
- Clear privacy notice must be provided when capturing client information

### Audit Trail
- All access attempts are logged regardless of client information provided
- IP addresses and user agents are stored for security monitoring
- Access logs are retained for security and analytics purposes