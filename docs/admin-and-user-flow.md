# Admin & User Flow - Payment Link Application

## Complete User Journey Map

---

## ADMIN FLOW

### 1. Admin Registration/Login

```
┌─────────────────────────────────────────┐
│  Visit Application                      │
│  www.paymentlinks.com                   │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Landing Page                           │
│  - Login Button                         │
│  - Register Button                      │
│  - Features Overview                    │
└──────────────┬──────────────────────────┘
               │
        ┌──────┴──────┐
        │             │
        ▼             ▼
   [LOGIN]      [REGISTER]
        │             │
        └──────┬──────┘
               ▼
┌─────────────────────────────────────────┐
│  Authenticated as Admin                 │
│  Redirect to Dashboard                  │
└──────────────┬──────────────────────────┘
```

**Routes Involved**:
- `GET /register` - Registration form
- `POST /register` - Submit registration
- `GET /login` - Login form
- `POST /login` - Submit login
- `GET /dashboard` - Admin dashboard

**Components**:
- Laravel Breeze authentication pages (auto-generated)

---

### 2. Admin Dashboard

```
┌──────────────────────────────────────────────────┐
│                  DASHBOARD                       │
├──────────────────────────────────────────────────┤
│                                                  │
│  ┌────────────────────────────────────────────┐  │
│  │ Quick Stats (Cards)                        │  │
│  │ ├─ Total Collections: 12                   │  │
│  │ ├─ Active Collections: 8                   │  │
│  │ ├─ Completed Collections: 4                │  │
│  │ └─ Total Revenue: $12,450.00               │  │
│  └────────────────────────────────────────────┘  │
│                                                  │
│  ┌────────────────────────────────────────────┐  │
│  │ Recent Collections (Table)                 │  │
│  │ ┌──────────────────────────────────────┐   │  │
│  │ │ Title │ Items │ Amount │ Status │   │   │  │
│  │ ├──────────────────────────────────────┤   │  │
│  │ │ Client ABC   │  3   │ $700   │ Active│   │  │
│  │ │ Client XYZ   │  5   │ $1500  │ Comp. │   │  │
│  │ │ ...          │      │        │       │   │  │
│  │ └──────────────────────────────────────┘   │  │
│  │                                             │  │
│  │                [Create New Collection]      │  │
│  └────────────────────────────────────────────┘  │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Route**: `GET /dashboard`

**Page Components**:
- `Dashboard.vue`
- Quick stats cards
- Recent collections table
- "Create New Collection" button

**Data Passed to Frontend**:
```php
[
    'collections' => Collection of PaymentCollection,
    'stats' => [
        'total' => 12,
        'active' => 8,
        'completed' => 4,
        'revenue' => 12450.00
    ]
]
```

---

### 3. View All Collections

```
┌──────────────────────────────────────────────────┐
│           PAYMENT COLLECTIONS (List)             │
├──────────────────────────────────────────────────┤
│                                                  │
│  [+ Create New]  [Filter▼] [Search...]          │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ Title         │ Items │ Amt  │ Status │ │   │
│  ├──────────────────────────────────────────┤   │
│  │ Client ABC    │   3   │$700  │ ✓Active│View│   │
│  │ Client XYZ    │   5   │$1500 │ ✓Comp. │View│   │
│  │ Client PQR    │   2   │$400  │ ⏳Proc │View│   │
│  │ Client JKL    │   1   │$100  │ ✗Exp. │View│   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│  Status Legend:                                 │
│  ✓ Active = Awaiting payment                    │
│  ✓ Completed = All payments done                │
│  ⏳ Processing = Payment in progress            │
│  ✗ Expired = Link expired                       │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Route**: `GET /payment-collections`

**Page Components**:
- `PaymentCollections/Index.vue`
- DataTable with columns
- Filter/search functionality
- Status badges

**Capabilities**:
- View all collections
- Filter by status (Active, Completed, Expired)
- Search by title
- Sort by created date, amount, status
- Actions: View, Edit, Copy Link, Delete

---

### 4. Create New Collection

```
┌──────────────────────────────────────────────────┐
│       CREATE NEW PAYMENT COLLECTION              │
├──────────────────────────────────────────────────┤
│                                                  │
│  Title*        [_________________________]       │
│                                                  │
│  Description   [_________________________]       │
│  (optional)    [_________________________]       │
│                [_________________________]       │
│                                                  │
│  Expires In    [Select Date/Time ▼]             │
│  (optional)    [2025-01-10 ▼]                   │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ PAYMENT ITEMS                            │   │
│  ├──────────────────────────────────────────┤   │
│  │  Amount*      │ Description*              │   │
│  ├──────────────────────────────────────────┤   │
│  │ [$100.00]     │ [Initial Deposit      ] │   │
│  │ [$200.00]     │ [Monthly Fee           ] │   │
│  │ [$400.00]     │ [Annual Subscription   ] │   │
│  │ [+ Add Item]  │ [- Remove]             │   │
│  │               │                         │   │
│  │ Total: $700.00                          │   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│              [Cancel]  [Create Collection]      │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Route**: `GET /payment-collections/create`

**Page Components**:
- `PaymentCollections/Create.vue`
- `PaymentCollectionForm.vue` (shared form)
- Dynamic payment items array handling

**Form Fields**:
```
- title (required, string, max 255)
- description (optional, text)
- expires_at (optional, future date)
- items[] (required, array min 1, max 50)
  - items[].amount (required, numeric, min 0.50)
  - items[].description (required, string)
```

**Form Submission Flow**:
```
Admin Fills Form
        ▼
[Cancel] or [Create Collection]
        ▼
   [Create]
        ▼
Backend validates (StorePaymentCollectionRequest)
        ▼
Create PaymentCollection (with UUID)
        ▼
Create PaymentItems (linked to collection)
        ▼
Redirect to Show Page
```

**Form Validation Rules**:
```php
'title' => 'required|string|max:255',
'description' => 'nullable|string',
'expires_at' => 'nullable|date|after:today',
'items' => 'required|array|min:1|max:50',
'items.*.amount' => 'required|numeric|min:0.50|max:999999',
'items.*.description' => 'required|string|max:255',
```

---

### 5. View Collection Details

```
┌──────────────────────────────────────────────────┐
│       CLIENT ABC INVOICES                        │
├──────────────────────────────────────────────────┤
│                                                  │
│  Status: ✓ ACTIVE                               │
│  Created: Dec 20, 2024                          │
│  Expires: Jan 10, 2025                          │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ SHAREABLE LINK                           │   │
│  │                                          │   │
│  │ www.paymentlinks.com/pay/abc-123-xyz    │   │
│  │                                [COPY ✓] │   │
│  │                                          │   │
│  │ Share this link with your client to    │   │
│  │ start making payments.                  │   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ PAYMENT ITEMS                            │   │
│  ├──────────────────────────────────────────┤   │
│  │ Amount │ Description      │ Status │    │   │
│  ├──────────────────────────────────────────┤   │
│  │ $100   │ Initial Deposit  │ ✓ Paid │    │   │
│  │ $200   │ Monthly Fee      │ ⏳ Proc│    │   │
│  │ $400   │ Annual Subscription│ ○ Pend│    │   │
│  │                            │       │    │   │
│  │ Total Collected: $300 / $700           │   │
│  │ Progress: ████░░░░░░░░░ 43%            │   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ TRANSACTION HISTORY                      │   │
│  ├──────────────────────────────────────────┤   │
│  │ Date │ Amount │ Description │ Receipt  │   │
│  ├──────────────────────────────────────────┤   │
│  │ 12/21│ $100   │ Initial Depo│ stripe.. │   │
│  │ 12/22│ $200   │ Monthly Fee │ stripe.. │   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│              [Edit]  [Delete]  [Back]           │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Route**: `GET /payment-collections/{id}`

**Page Components**:
- `PaymentCollections/[id]/index.vue`
- Status badge
- Shareable link with copy button
- Payment items table
- Transaction history
- Progress indicator

**Data Passed**:
```php
[
    'collection' => PaymentCollection with relations,
    'items' => PaymentItems,
    'transactions' => PaymentTransactions,
    'stats' => [
        'totalCollected' => 300.00,
        'totalAmount' => 700.00,
        'progress' => 43,
        'completedItems' => 2,
        'totalItems' => 3
    ]
]
```

---

### 6. Edit Collection

```
┌──────────────────────────────────────────────────┐
│       EDIT PAYMENT COLLECTION                    │
├──────────────────────────────────────────────────┤
│                                                  │
│  Title*        [CLIENT ABC INVOICES______]       │
│                                                  │
│  Description   [Description text......]         │
│                [.............................]  │
│                                                  │
│  Status: ACTIVE (no payments made)              │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ PAYMENT ITEMS (Editable)                │   │
│  ├──────────────────────────────────────────┤   │
│  │  Amount*      │ Description*              │   │
│  ├──────────────────────────────────────────┤   │
│  │ [$100.00]     │ [Initial Deposit      ] │   │
│  │ [$200.00]     │ [Monthly Fee           ] │   │
│  │ [$400.00]     │ [Annual Subscription   ] │   │
│  │ [+ Add]       │ [- Remove]             │   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│  ⚠️  Cannot edit once payments have started     │
│                                                  │
│              [Cancel]  [Save Changes]           │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Route**: `GET /payment-collections/{id}/edit`

**Page Components**:
- `PaymentCollections/[id]/edit.vue`
- `PaymentCollectionForm.vue` (shared)

**Edit Rules**:
- ✅ Editable: Title, description, items (if all pending)
- ❌ Not Editable: If any payment is completed/failed
- Show read-only view if payments started

**Form Submission**:
```
[Save Changes]
        ▼
PUT /payment-collections/{id}
        ▼
UpdatePaymentCollectionRequest validates
        ▼
Check: Any completed items?
        ├─ YES → Return error (cannot edit)
        └─ NO → Update collection & items
        ▼
Redirect to Show Page
```

---

### 7. Delete Collection

```
Scenario 1: No payments made
[Delete Button]
        ▼
Confirmation Dialog:
"Are you sure? This will permanently delete this collection."
        ▼
[Cancel] or [Delete Permanently]
        ▼
DELETE /payment-collections/{id}
        ▼
Soft delete collection (status = deleted)
        ▼
Redirect to List Page
```

**Route**: `DELETE /payment-collections/{id}`

**Logic**:
- Soft delete collection (keeps transactions for audit trail)
- Mark status as "deleted"
- Remove from normal views
- Keep transaction history

---

## USER (CLIENT) FLOW

### 1. Client Receives Link

```
Admin creates collection with UUID: abc-123-xyz
                ▼
Admin clicks [COPY] button
                ▼
Link copied: www.paymentlinks.com/pay/abc-123-xyz
                ▼
Admin sends link to client via:
- Email
- SMS
- WhatsApp
- Slack
- etc.
                ▼
Client receives link in their inbox
```

---

### 2. Client Opens Payment Page

```
Client clicks link: www.paymentlinks.com/pay/abc-123-xyz
                ▼
Browser requests: GET /pay/abc-123-xyz
                ▼
Backend validates:
├─ Does collection exist?
├─ Is it active (not expired)?
├─ Is it not already completed?
└─ All checks pass? Proceed
                ▼
┌──────────────────────────────────────────────────┐
│           PAYMENT COLLECTION                     │
│                                                  │
│  Client ABC Invoices                            │
│  Complete all payments below                    │
│                                                  │
│  Progress: 0 of 3 payments completed            │
│  ████░░░░░░░░░ 0%                               │
│                                                  │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐ │
│  │   PAYMENT  │  │   PAYMENT  │  │   PAYMENT  │ │
│  │            │  │            │  │            │ │
│  │  $100.00   │  │  $200.00   │  │  $400.00   │ │
│  │            │  │            │  │            │ │
│  │ Initial    │  │ Monthly    │  │ Annual     │ │
│  │ Deposit    │  │ Fee        │  │ Subs       │ │
│  │            │  │            │  │            │ │
│  │ [PAY NOW]  │  │ [PAY NOW]  │  │ [PAY NOW]  │ │
│  └────────────┘  └────────────┘  └────────────┘ │
│                                                  │
│  Expires: Jan 10, 2025                          │
│  ⏱️ Time remaining: 14 days                      │
│                                                  │
│         Powered by Stripe ⚡                    │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Route**: `GET /pay/{uuid}`

**Page Components**:
- `Payment/Show.vue`
- Payment cards grid (responsive)
- Progress indicator
- Expiry timer

**Card States**:
```
1. PENDING (Blue)
   - Amount displayed
   - Description shown
   - [PAY NOW] button active

2. PROCESSING (Gray + Spinner)
   - Disabled state
   - Loading spinner
   - "Processing..." text

3. COMPLETED (Green)
   - Green checkmark ✓
   - Grayed out
   - "Paid on Dec 21, 2024"
   - Disabled

4. FAILED (Red)
   - Red styling
   - Error message
   - [TRY AGAIN] button active
```

---

### 3. Client Clicks "Pay Now" on First Payment

```
Payment Card: $100 - Initial Deposit

Client clicks [PAY NOW]
                ▼
┌──────────────────────────────────────────────────┐
│  ENTER PAYMENT DETAILS                           │
├──────────────────────────────────────────────────┤
│                                                  │
│  Amount: $100.00                                │
│                                                  │
│  Card Number  [4242 4242 4242 4242        ]    │
│  Expiry       [12 / 25        ]  CVC [123 ]    │
│  Name         [John Doe              ]         │
│  Email        [john@example.com     ]          │
│                                                  │
│  ☑️  Save card for future use                  │
│                                                  │
│              [Cancel]  [Pay $100.00]            │
│                                                  │
│  🔒 Secured by Stripe                           │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Modal/Dialog Components**:
- `StripePaymentForm.vue`
- Stripe CardElement integration
- Amount display
- Submit button

---

### 4. Client Enters Card Details

```
Backend API Call: POST /pay/abc-123-xyz/payment-intent

Request Body:
{
  "item_id": 1,
  "amount": 100.00
}
                ▼
Backend validates:
├─ Does item exist?
├─ Is status "pending"?
├─ Is amount correct?
└─ All pass? Continue
                ▼
Create Stripe PaymentIntent:
{
  "amount": 10000 (cents),
  "currency": "usd",
  "metadata": {
    "payment_item_id": 1,
    "payment_collection_uuid": "abc-123-xyz"
  }
}
                ▼
Return clientSecret to frontend:
{
  "clientSecret": "pi_1234567890_secret_abc123",
  "paymentIntentId": "pi_1234567890"
}
                ▼
Client fills card form and clicks [Pay $100.00]
                ▼
Frontend confirms payment with Stripe:
stripe.confirmCardPayment(clientSecret, {
  payment_method: {
    card: cardElement,
    billing_details: { name, email }
  }
})
                ▼
Stripe processes card (3D Secure, etc)
```

---

### 5. Payment Success/Failure

#### Success Path:

```
Stripe returns: paymentIntent.status = "succeeded"
                ▼
Frontend calls: POST /pay/abc-123-xyz/confirm
{
  "payment_intent_id": "pi_1234567890"
}
                ▼
Backend:
├─ Verify payment intent
├─ Update PaymentItem status to "completed"
├─ Set paid_at timestamp
├─ Create PaymentTransaction record
├─ Check if all items paid
│  └─ YES? Set collection status to "completed"
└─ Return success response
                ▼
Frontend:
├─ Close modal
├─ Update card to COMPLETED state ✓
├─ Update progress bar
├─ Show toast: "Payment successful!"
└─ Check if all items complete
   └─ YES? Redirect to /pay/abc-123-xyz/thank-you
```

#### Failure Path:

```
Stripe returns: paymentIntent.status = "requires_action" or error
                ▼
Error shown in modal:
"Card declined. Please try another card."
                ▼
Frontend options:
├─ [Try Again] → Clear form, allow retry
├─ [Use Different Card] → Reset card element
└─ [Cancel] → Close modal
                ▼
Item status remains "pending"
Card stays in FAILED state (red)
User can click [TRY AGAIN] anytime
                ▼
No transaction record created
```

---

### 6. Client Completes All Payments

```
Progress after each successful payment:

Initial:      0 of 3 completed → ░░░░░░░░░░░
After 1st:    1 of 3 completed → ████░░░░░░░
After 2nd:    2 of 3 completed → ████████░░░
After 3rd:    3 of 3 completed → ████████████

After 3rd payment succeeds:
                ▼
Frontend detects: allCompleted = true
                ▼
Automatic redirect to:
GET /pay/abc-123-xyz/thank-you
                ▼
Backend confirms collection status = "completed"
                ▼
```

---

### 7. Thank You Page

```
┌──────────────────────────────────────────────────┐
│                                                  │
│                   🎉 SUCCESS! 🎉                 │
│                                                  │
│        All Payments Completed Successfully       │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │                                          │   │
│  │  CLIENT ABC INVOICES                    │   │
│  │                                          │   │
│  │  Payment Summary:                        │   │
│  │  ✓ Initial Deposit ........... $100.00  │   │
│  │  ✓ Monthly Fee ............... $200.00  │   │
│  │  ✓ Annual Subscription ....... $400.00  │   │
│  │                                          │   │
│  │  Total Paid: $700.00                    │   │
│  │  Date: December 23, 2024                │   │
│  │                                          │   │
│  │  Receipt #: stripe_ch_1234567890        │   │
│  │                                          │   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│  Thank you for your payment!                    │
│                                                  │
│  A receipt has been sent to your email.        │
│                                                  │
│             [Download Receipt PDF]              │
│             [Close Window]                      │
│                                                  │
│         Powered by Stripe ⚡                    │
│                                                  │
└──────────────────────────────────────────────────┘
```

**Route**: `GET /pay/{uuid}/thank-you`

**Page Components**:
- `Payment/ThankYou.vue`
- Success animation
- Summary of all payments
- Download receipt button
- Close window button

**Data Passed**:
```php
[
    'collection' => PaymentCollection,
    'items' => PaymentItems (all with paid_at),
    'totalAmount' => 700.00,
    'completedAt' => '2024-12-23 14:30:00',
    'receiptUrl' => 'stripe_receipt_link'
]
```

---

## Payment State Transitions

### Payment Item Status Flow:

```
                    ┌─────────────────┐
                    │    PENDING      │
                    │  (Initial)      │
                    └────────┬────────┘
                             │
                    Click "Pay Now"
                             │
                    ┌────────▼────────┐
                    │  PROCESSING     │
                    │ (Awaiting Auth) │
                    └────────┬────────┘
                             │
                 ┌───────────┴───────────┐
                 │                       │
          Success                    Failed
                 │                       │
        ┌────────▼────────┐   ┌─────────▼──────┐
        │   COMPLETED     │   │     FAILED     │
        │  ✓ Paid         │   │  ✗ Error       │
        │  (Final)        │   │  [Try Again]   │
        └─────────────────┘   └────────┬───────┘
                                       │
                           Click "Try Again"
                                       │
                                       └──→ PENDING
```

### Collection Status Flow:

```
┌─────────────┐
│   ACTIVE    │
│  (Default)  │
└──────┬──────┘
       │
   ┌───┴──────────────────┐
   │                      │
   ▼ All paid            ▼ Expired
┌─────────┐          ┌─────────┐
│COMPLETED│          │ EXPIRED │
│(Final)  │          │(Final)  │
└─────────┘          └─────────┘
```

---

## API Endpoints Summary

### Admin Routes (Authenticated)
| Method | Route | Action | Returns |
|--------|-------|--------|---------|
| GET | `/dashboard` | View stats | Dashboard data |
| GET | `/payment-collections` | List all | Collections list |
| GET | `/payment-collections/create` | Show form | Form page |
| POST | `/payment-collections` | Create | Redirect to show |
| GET | `/payment-collections/{id}` | Show details | Collection + items |
| GET | `/payment-collections/{id}/edit` | Edit form | Edit page |
| PUT | `/payment-collections/{id}` | Update | Redirect to show |
| DELETE | `/payment-collections/{id}` | Delete | Redirect to list |

### Client Routes (Public)
| Method | Route | Action | Returns |
|--------|-------|--------|---------|
| GET | `/pay/{uuid}` | Show payment page | Payment page |
| POST | `/pay/{uuid}/payment-intent` | Create intent | clientSecret |
| POST | `/pay/{uuid}/confirm/{itemId}` | Confirm payment | Success/error |
| GET | `/pay/{uuid}/check-status` | Check status | Current items status |
| GET | `/pay/{uuid}/thank-you` | Thank you page | Thank you page |

### Webhooks (Unguarded)
| Method | Route | Action | Returns |
|--------|-------|--------|---------|
| POST | `/stripe/webhook` | Handle events | 200 OK |

---

## Error Handling Flows

### Client Side - Payment Modal Errors

```
User clicks [Pay Now]
                ▼
Fills card form
                ▼
[Pay $100.00]
                ▼
Network error / Stripe error
                ▼
┌──────────────────────────────────────┐
│ ⚠️ Error Message                    │
│                                      │
│ "Your card was declined.             │
│  Please try another card."           │
│                                      │
│ Card Error Code: card_declined       │
│                                      │
│              [Try Again]  [Cancel]   │
│                                      │
└──────────────────────────────────────┘
                ▼
User can:
├─ Try with different card
├─ Retry same card
└─ Close modal and try later
```

### Collection Expired Flow

```
Client opens link after expiration date
                ▼
Backend checks: expires_at < now()
                ▼
YES → Update collection status to "expired"
                ▼
┌──────────────────────────────────────┐
│      Link Expired                    │
│                                      │
│  This payment link has expired.      │
│                                      │
│  Please contact the admin for a      │
│  new payment link.                   │
│                                      │
└──────────────────────────────────────┘
```

### Already Completed Flow

```
Client opens link after all payments done
                ▼
Backend checks: collection.status = "completed"
                ▼
Auto-redirect to: /pay/{uuid}/thank-you
                ▼
Show thank you page
```

---

## Email Notifications (Optional)

### Send to Client
```
After Each Payment:
"Payment Received - $100.00"
- Amount paid
- Description
- Receipt link
- Remaining payments

After All Payments:
"All Payments Complete!"
- Summary of all payments
- Total amount
- Invoice PDF
```

### Send to Admin
```
When Payment Received:
"New Payment: $100.00"
- Which collection
- Payment details
- Client email
- Link to view

When Collection Completed:
"Collection Complete: Client ABC"
- Total collected
- All items paid
- Link to view
```

---

## Mobile User Flow

### Payment Page on Mobile

```
┌────────────────────────────┐
│  ← Client ABC Invoices     │
├────────────────────────────┤
│                            │
│ Progress: 0 of 3 (0%)     │
│ ▓▓░░░░░░░░░░░░░░░░░░░░░  │
│                            │
│ ┌──────────────────────┐   │
│ │  PAYMENT             │   │
│ │                      │   │
│ │  $100.00             │   │
│ │                      │   │
│ │  Initial Deposit     │   │
│ │                      │   │
│ │  [PAY NOW]           │   │
│ └──────────────────────┘   │
│                            │
│ ┌──────────────────────┐   │
│ │  PAYMENT             │   │
│ │                      │   │
│ │  $200.00             │   │
│ │                      │   │
│ │  Monthly Fee         │   │
│ │                      │   │
│ │  [PAY NOW]           │   │
│ └──────────────────────┘   │
│                            │
│ ┌──────────────────────┐   │
│ │  PAYMENT             │   │
│ │                      │   │
│ │  $400.00             │   │
│ │                      │   │
│ │  Annual Subscription │   │
│ │                      │   │
│ │  [PAY NOW]           │   │
│ └──────────────────────┘   │
│                            │
│ Expires: 14 days          │
│                            │
└────────────────────────────┘
```

Cards stack vertically, full width
Responsive Stripe CardElement
Touch-friendly buttons

---

## Summary

**Admin Flow**:
1. Register/Login
2. View Dashboard
3. View/Create/Edit/Delete Collections
4. Share link with clients
5. Monitor payment progress

**Client Flow**:
1. Receive shareable link
2. Open payment page
3. Pay items one by one
4. Instant status updates
5. See thank you page when complete

**Key Features**:
- No client authentication needed
- Multiple attempts for failed payments
- Real-time progress tracking
- Expiry protection
- Transaction history
- Mobile responsive
- Secure Stripe integration
