# API Contracts: Stripe Webhooks, Transactions & Receipts

## Webhook Endpoint

### POST /webhooks/stripe

Receives and processes Stripe webhook events.

#### Purpose
Securely receive and process webhook events from Stripe to update payment status in real-time.

#### Authentication
Signature verification using Stripe webhook secret (no session/auth required)

#### Request
- **Content-Type**: `application/json`
- **Body**: Raw webhook payload from Stripe
- **Headers**: 
  - `Stripe-Signature`: Signature header from Stripe for verification

#### Response
- **Success**: `200 OK`
- **Invalid Signature**: `400 Bad Request`
- **Processing Error**: `500 Internal Server Error`

#### Error Responses
- `400 Bad Request`: 
  - Invalid webhook signature
  - Malformed request body
- `500 Internal Server Error`: 
  - Database connection error
  - Processing failure

## Receipt Generation Service

### POST /receipts/generate

Generate receipt for a completed payment.

#### Purpose
Generate a PDF receipt for a completed payment transaction.

#### Authentication
Requires authentication for admin access (for re-generation purposes)

#### Request
- **Content-Type**: `application/json`
- **Body**: 
  ```json
  {
    "payment_transaction_id": 123
  }
  ```

#### Response
- **Success**: `200 OK`
  ```json
  {
    "id": 456,
    "payment_transaction_id": 123,
    "receipt_number": "RCP-2025-00123",
    "delivery_status": "pending",
    "created_at": "2025-01-01T10:00:00Z"
  }
  ```

#### Error Responses
- `400 Bad Request`: Invalid payment transaction ID
- `404 Not Found`: Payment transaction not found
- `500 Internal Server Error`: PDF generation failure

## Receipt Delivery Service

### POST /receipts/deliver

Deliver receipt to customer via email.

#### Purpose
Send generated receipt to customer via email.

#### Authentication
Requires authentication for admin access (for re-delivery purposes)

#### Request
- **Content-Type**: `application/json`
- **Body**: 
  ```json
  {
    "receipt_id": 456,
    "email": "customer@example.com"
  }
  ```

#### Response
- **Success**: `200 OK`
  ```json
  {
    "receipt_id": 456,
    "email": "customer@example.com",
    "delivery_status": "sent",
    "delivered_at": "2025-01-01T10:00:00Z"
  }
  ```

#### Error Responses
- `400 Bad Request`: Invalid receipt ID or email format
- `404 Not Found`: Receipt not found
- `500 Internal Server Error`: Email delivery failure

## Transaction Query Service

### GET /transactions/{id}

Retrieve details of a specific payment transaction.

#### Purpose
Get detailed information about a payment transaction for audit or debugging purposes.

#### Authentication
Requires authentication (admin access)

#### Response
- **Success**: `200 OK`
  ```json
  {
    "id": 123,
    "payment_item_id": 456,
    "stripe_event_id": "evt_1234567890",
    "stripe_event_type": "payment_intent.succeeded",
    "payload": {
      // Full webhook payload from Stripe
    },
    "processed_at": "2025-01-01T10:00:00Z",
    "processing_status": "completed",
    "processing_attempts": 1,
    "created_at": "2025-01-01T10:00:00Z"
  }
  ```

#### Error Responses
- `404 Not Found`: Transaction not found
- `403 Forbidden`: Insufficient permissions