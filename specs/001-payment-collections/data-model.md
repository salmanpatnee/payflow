# Data Model: Payment Collections & Data Model

## Overview
This document defines the data model for payment collections and their associated items, including database schema, relationships, validation rules, and state transitions.

## Entity Definitions

### Payment Collection
**Description**: Represents a group of items that can be charged together

**Database Table**: `payment_collections`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | BIGINT (Primary Key) | Auto-increment | Unique identifier |
| uuid | CHAR(36) | Unique, Indexed | UUID for shareable links |
| name | VARCHAR(255) | Not null | Name of the collection |
| description | TEXT | Nullable | Detailed description of the collection |
| status | VARCHAR(50) | Not null, Default: 'active' | Collection status (active, completed, expired) |
| admin_user_id | BIGINT | Foreign key, Indexed | ID of admin who created the collection |
| created_at | TIMESTAMP | Not null | Record creation timestamp |
| updated_at | TIMESTAMP | Not null | Record modification timestamp |

**Relationships**:
- One-to-many with PaymentItem (one collection to many items)
- Belongs to User (admin who created the collection)

**Validation Rules**:
- Name: Required, max 255 characters
- Description: Optional, max 65535 characters
- Status: Must be one of 'active', 'completed', 'expired'

### Payment Item
**Description**: Represents an individual item within a payment collection

**Database Table**: `payment_items`

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | BIGINT (Primary Key) | Auto-increment | Unique identifier |
| payment_collection_id | BIGINT | Foreign key, Indexed | Reference to parent collection |
| name | VARCHAR(255) | Not null | Name of the item |
| description | TEXT | Nullable | Detailed description of the item |
| price | DECIMAL(10,2) | Not null | Price of the item |
| quantity | INTEGER | Not null, Default: 1 | Quantity of the item |
| type | VARCHAR(100) | Not null | Type of the item (e.g., 'service', 'product') |
| sort_order | INTEGER | Default: 0 | Order for displaying items |
| created_at | TIMESTAMP | Not null | Record creation timestamp |
| updated_at | TIMESTAMP | Not null | Record modification timestamp |

**Relationships**:
- Many-to-one with PaymentCollection (many items to one collection)

**Validation Rules**:
- Name: Required, max 255 characters
- Price: Required, positive decimal value
- Quantity: Required, positive integer
- Type: Required, must be one of predefined types

### Admin User
**Description**: Represents an authorized user with permissions to create, read, update, and delete payment collections

**Database Table**: `users` (existing Laravel table)

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | BIGINT (Primary Key) | Auto-increment | Unique identifier |
| name | VARCHAR(255) | Not null | User's name |
| email | VARCHAR(255) | Unique, Not null | User's email |
| email_verified_at | TIMESTAMP | Nullable | Email verification timestamp |
| password | VARCHAR(255) | Not null | Hashed password |
| remember_token | VARCHAR(100) | Nullable | Remember token |
| created_at | TIMESTAMP | Not null | Record creation timestamp |
| updated_at | TIMESTAMP | Not null | Record modification timestamp |

## Relationships

### Payment Collection to Payment Items
- One-to-many relationship
- PaymentCollection "has many" PaymentItems
- PaymentItem "belongs to" PaymentCollection
- Foreign key: `payment_collection_id` in `payment_items` table

### Payment Collection to Admin User
- Many-to-one relationship
- PaymentCollection "belongs to" User
- User "has many" PaymentCollections
- Foreign key: `admin_user_id` in `payment_collections` table

## State Transitions

### Payment Collection States
- `active`: Default state, collection is available for management
- `completed`: All items in the collection have been paid (not applicable in this phase)
- `expired`: Collection has expired (not applicable in this phase)

### State Transition Rules
- `active` → `completed`: When all associated payment items have been paid (will be implemented in Phase 3)
- `active` → `expired`: When collection reaches its expiry date (will be implemented in Phase 3)

## Validation Rules

### Payment Collection Validation
1. Name must be provided and not exceed 255 characters
2. Description, if provided, must not exceed 65535 characters
3. Status must be one of: 'active', 'completed', 'expired'
4. Admin user must be authenticated and authorized

### Payment Item Validation
1. Name must be provided and not exceed 255 characters
2. Price must be a positive decimal value
3. Quantity must be a positive integer
4. Type must be one of: 'service', 'product', or other predefined types
5. Must belong to a valid payment collection

## Indexes

### Required Indexes
1. `payment_collections.uuid` - For efficient lookup by UUID
2. `payment_collections.admin_user_id` - For efficient user-based queries
3. `payment_collections.status` - For efficient status-based queries
4. `payment_items.payment_collection_id` - For efficient collection-based queries

## Constraints

### Database Constraints
1. Foreign key constraints to maintain referential integrity
2. Unique constraint on `payment_collections.uuid`
3. Check constraint on `payment_items.price` to ensure positive values
4. Check constraint on `payment_items.quantity` to ensure positive values

## Audit Trail

### Required Audit Information
1. Creation timestamp for all records
2. Last modification timestamp for all records
3. User who created the collection (admin_user_id)
4. All changes to collection and item data will be tracked in Phase 3

## Future Considerations

### For Phase 3 (Payment Processing)
1. Additional fields for payment tracking in both tables
2. Status fields for payment processing
3. Stripe integration fields
4. Expiration date for collections