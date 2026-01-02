# API Contracts: Payment Link Generation & Client Flow

**Feature**: Payment Link Generation & Client Flow
**Date**: 2025-12-30

## Public Route: GET /pay/{token}

### Purpose
Allows clients to access payment collection details using a secure token.

### Request
```
GET /pay/{token}
```

**Path Parameters:**
- `token` (string, required): The secure payment link token

**Query Parameters:**
- None

**Headers:**
- `Accept: application/json` (for API responses)
- `Content-Type: application/json` (for POST requests)

### Response

**Success Response (200 OK):**
```json
{
  "payment_collection": {
    "id": 1,
    "uuid": "collection-uuid",
    "title": "Project Payment",
    "description": "Payment for project work",
    "total_amount": 1500.00,
    "due_date": "2024-12-31",
    "status": "active",
    "items": [
      {
        "id": 1,
        "description": "Design Phase",
        "amount": 500.00,
        "status": "pending",
        "due_date": "2024-12-15"
      },
      {
        "id": 2,
        "description": "Development Phase",
        "amount": 1000.00,
        "status": "pending",
        "due_date": "2024-12-31"
      }
    ]
  },
  "client_access_record": {
    "id": 1,
    "access_token": "token",
    "accessed_at": "2024-12-01T10:30:00Z"
  }
}
```

**Error Responses:**
- `404 Not Found`: Invalid or expired token
- `429 Too Many Requests`: Rate limit exceeded

### Business Logic
1. Validate the token against the database
2. Check if the payment collection is still active and not expired
3. Create/update client access record
4. Return payment collection details with items

## Admin Route: POST /admin/payment-collections/{id}/generate-link

### Purpose
Allows authenticated admins to generate a new payment link for a payment collection.

### Request
```
POST /admin/payment-collections/{id}/generate-link
```

**Path Parameters:**
- `id` (integer, required): The payment collection ID

**Request Body:**
```json
{
  "expiration_days": 90
}
```

**Headers:**
- `Accept: application/json`
- `Content-Type: application/json`
- `X-CSRF-TOKEN`: [CSRF token]

### Response

**Success Response (200 OK):**
```json
{
  "payment_link_url": "https://payflow.test/pay/abc123def456...",
  "token": "abc123def456...",
  "expires_at": "2025-03-30T10:30:00Z",
  "created_at": "2024-12-30T10:30:00Z"
}
```

**Error Responses:**
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User doesn't have permission
- `404 Not Found`: Payment collection doesn't exist
- `422 Unprocessable Entity`: Validation errors

### Business Logic
1. Verify user is authenticated and authorized
2. Validate payment collection exists and belongs to user
3. Generate secure token and set expiration
4. Return the payment link URL

## Admin Route: GET /admin/payment-collections/{id}/payment-link

### Purpose
Allows authenticated admins to retrieve existing payment link information for a payment collection.

### Request
```
GET /admin/payment-collections/{id}/payment-link
```

**Path Parameters:**
- `id` (integer, required): The payment collection ID

**Headers:**
- `Accept: application/json`

### Response

**Success Response (200 OK):**
```json
{
  "payment_link_url": "https://payflow.test/pay/abc123def456...",
  "token": "abc123def456...",
  "expires_at": "2025-03-30T10:30:00Z",
  "created_at": "2024-12-30T10:30:00Z",
  "access_count": 5,
  "last_accessed_at": "2024-12-30T09:15:00Z"
}
```

**Error Responses:**
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User doesn't have permission
- `404 Not Found`: Payment collection doesn't exist
- `410 Gone`: No payment link exists for this collection

### Business Logic
1. Verify user is authenticated and authorized
2. Validate payment collection exists and belongs to user
3. Check if payment link exists
4. Return payment link information with access statistics

## Admin Route: DELETE /admin/payment-collections/{id}/payment-link

### Purpose
Allows authenticated admins to revoke an existing payment link.

### Request
```
DELETE /admin/payment-collections/{id}/payment-link
```

**Path Parameters:**
- `id` (integer, required): The payment collection ID

**Headers:**
- `Accept: application/json`
- `X-CSRF-TOKEN`: [CSRF token]

### Response

**Success Response (200 OK):**
```json
{
  "message": "Payment link revoked successfully"
}
```

**Error Responses:**
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User doesn't have permission
- `404 Not Found`: Payment collection doesn't exist
- `410 Gone`: No payment link exists for this collection

### Business Logic
1. Verify user is authenticated and authorized
2. Validate payment collection exists and belongs to user
3. Remove the payment link token and expiration
4. Return success message

## Public Route: POST /pay/{token}/capture-info

### Purpose
Allows clients to submit their information when accessing a payment link.

### Request
```
POST /pay/{token}/capture-info
```

**Path Parameters:**
- `token` (string, required): The secure payment link token

**Request Body:**
```json
{
  "client_name": "John Doe",
  "client_email": "john@example.com"
}
```

**Headers:**
- `Accept: application/json`
- `Content-Type: application/json`
- `X-CSRF-TOKEN`: [CSRF token]

### Response

**Success Response (200 OK):**
```json
{
  "message": "Client information captured successfully",
  "client_access_record": {
    "id": 1,
    "client_name": "John Doe",
    "client_email": "john@example.com",
    "accessed_at": "2024-12-30T10:30:00Z"
  }
}
```

**Error Responses:**
- `404 Not Found`: Invalid or expired token
- `422 Unprocessable Entity`: Validation errors for client information
- `429 Too Many Requests`: Rate limit exceeded

### Business Logic
1. Validate the token against the database
2. Validate client information (email format, etc.)
3. Update the existing client access record with provided information
4. Return confirmation and updated record