# PayFlow Constitution

## Core Principles

### I. Payment Security First
- All payment processing must go through Stripe - never store card data locally
- Use PCI-compliant practices: Stripe Elements for card input, server-side validation
- Implement idempotency keys to prevent duplicate charges
- Store complete Stripe responses for audit trails
- Validate payment amounts on backend (never trust client-side amounts)
- Use HTTPS in production (required by Stripe)
- Verify webhook signatures for all Stripe events

### II. Data Integrity & Auditability
- Store all transaction data with timestamps, Stripe IDs, and metadata
- Create audit trail for every payment attempt (successful or failed)
- Never delete payment records - use soft deletes to preserve history
- Maintain immutability: once payment is completed, collection cannot be edited
- Log all state transitions (pending → processing → completed/failed)

### III. User Experience Excellence
**Admin Flow:**
- Clean, intuitive dashboard with quick stats
- One-click shareable link generation with copy functionality
- Clear visibility into payment progress and transaction history
- Prevent editing once payments have started (data consistency)

**Client Flow:**
- No authentication required - frictionless payment experience
- Real-time status updates after each payment
- Unlimited retry attempts for failed payments (client-friendly)
- Mobile-responsive design (cards stack vertically on mobile)
- Clear progress indicators showing completion status
- Helpful error messages with actionable next steps

### IV. Simplicity & Maintainability
- Use Laravel conventions: Eloquent relationships, Form Requests, Resource Controllers
- Leverage Inertia.js to eliminate API boilerplate
- Use shadcn-vue components for consistent UI
- Follow single responsibility: StripePaymentService handles all Stripe logic
- Use observers for automatic state updates (collection completion)
- Keep frontend state management simple (fetch from DB on load)

### V. Test-First Development
- Feature tests for all CRUD operations
- Test Stripe integration with mocked responses
- Validation tests for all form requests
- Edge case testing: expired links, completed collections, concurrent payments
- Browser testing for payment flow (use Pest v4 browser tests)
- Test with Stripe test cards before production

### VI. Performance & Scalability
- Database indexing on: uuid, status, stripe_payment_intent_id
- Eager load relationships to prevent N+1 queries
- Cache collection status where appropriate
- Optimize Vite build for production
- Use queue system for email notifications (optional enhancement)

## Technical Architecture

### Tech Stack
- **Backend**: Laravel 12 + PHP 8.3
- **Frontend**: Inertia.js v2 + Vue 3 + TypeScript
- **UI**: shadcn-vue + Tailwind CSS v4
- **Payments**: Stripe PHP SDK + Stripe.js
- **Testing**: Pest v4

### Database Schema
**payment_collections**:
- uuid (unique, indexed) for shareable links
- status: active | completed | expired
- One-to-many: payment items
- Belongs to: admin user

**payment_items**:
- Individual payment amounts and descriptions
- status: pending | processing | completed | failed
- Stripe references: payment_intent_id, charge_id
- paid_at timestamp for audit

**payment_transactions**:
- Full Stripe response stored as JSON metadata
- Links to payment_item
- Immutable record of all payment attempts

### Route Structure
**Admin Routes** (authenticated):
- `/dashboard` - Collections list and stats
- `/payment-collections/{create,edit,show}` - CRUD operations

**Public Routes** (no auth):
- `/pay/{uuid}` - Client payment page
- `/pay/{uuid}/payment-intent` - Create Stripe PaymentIntent
- `/pay/{uuid}/confirm` - Confirm payment completion
- `/pay/{uuid}/thank-you` - Success page

### Payment Flow
1. Admin creates collection with multiple payment items
2. System generates unique UUID for shareable link
3. Client opens `/pay/{uuid}` (no login required)
4. Client pays items individually via Stripe
5. Each successful payment updates item status to completed
6. When all items paid, collection status changes to completed
7. Client redirected to thank you page

### State Management Rules
**Collection States**:
- `active`: Default state, accepting payments
- `completed`: All items paid, link expires
- `expired`: Past expiry date, no longer accepting payments

**Payment Item States**:
- `pending`: Initial state, awaiting payment
- `processing`: PaymentIntent created, awaiting confirmation
- `completed`: Payment succeeded, immutable
- `failed`: Payment failed, can retry unlimited times

**Edit Permissions**:
- Edit allowed ONLY when all items are pending
- Once any payment completed, collection becomes read-only
- Delete uses soft delete to preserve transaction history

## Security Requirements

### Authentication & Authorization
- Laravel Breeze for admin authentication
- Public payment pages require no authentication
- Validate collection ownership before edit/delete
- CSRF protection on all POST/PUT/DELETE requests

### Payment Security
- Never expose STRIPE_SECRET_KEY in frontend
- Use VITE_STRIPE_PUBLIC_KEY for client-side Stripe.js
- Verify webhook signatures with STRIPE_WEBHOOK_SECRET
- Validate payment amounts match database records
- Use idempotency keys for all Stripe API calls

### Data Protection
- Store Stripe keys in .env (never commit)
- Use .env.example with placeholder values
- Sanitize user inputs (XSS prevention)
- Rate limiting on payment endpoints
- Encrypt sensitive metadata in database

## Development Workflow

### Feature Development
1. Review existing code conventions (check sibling files)
2. Create feature tests first (TDD approach)
3. Implement backend (models, controllers, requests)
4. Build frontend (Vue components, forms)
5. Test with Stripe test cards
6. Run full test suite
7. Code review and merge

### Testing Strategy
- **Unit Tests**: Models, services, helpers
- **Feature Tests**: Controllers, routes, validation
- **Browser Tests**: Complete payment flow end-to-end
- **Integration Tests**: Stripe webhook handling

### Code Quality Gates
- All tests must pass before merge
- Run Laravel Pint for code formatting
- No hardcoded secrets or API keys
- Type hints on all methods
- PHPDoc blocks for complex logic

## Operational Readiness

### Deployment Checklist
- [ ] Switch to production database (MySQL/PostgreSQL)
- [ ] Set production Stripe keys (pk_live_, sk_live_)
- [ ] Configure email service for receipts
- [ ] Enable HTTPS (required by Stripe)
- [ ] Set up Stripe webhooks in Dashboard
- [ ] Database indexes on uuid, status fields
- [ ] Queue worker for async jobs
- [ ] Backup strategy for transactions
- [ ] Monitoring and error tracking

### Monitoring
- Track payment success/failure rates
- Monitor Stripe webhook delivery
- Alert on failed payments
- Log all exceptions to error tracking service
- Track collection completion metrics

### Error Handling
**Client-Facing Errors**:
- "Payment declined" → Show retry button
- "Link expired" → Contact admin message
- "Already completed" → Redirect to thank you page
- "Network error" → Retry button with clear message

**Admin Errors**:
- "Cannot edit" → Explain payments already started
- "Invalid amount" → Show validation errors inline
- "Stripe API error" → Log and show generic message

## Documentation Requirements

### Code Documentation
- PHPDoc blocks for all public methods
- Inline comments for complex business logic
- README.md with setup instructions
- .env.example with all required variables

### User Documentation
- Admin guide: How to create collections
- Sharing instructions: How to send links to clients
- Payment tracking: Understanding statuses
- Stripe dashboard: Where to view transactions

### Technical Documentation
See the following files in `docs/` directory:
- **admin-and-user-flow.md**: Complete user journey maps for admin and client flows
- **payment-links-plan.md**: System architecture and implementation plan
- **stripe-integration-guide.md**: Stripe setup and integration guide

## Governance

### Constitution Authority
- This constitution supersedes all other development practices
- All code reviews must verify compliance with these principles
- Amendments require documentation and approval
- Breaking changes must include migration plan

### Quality Standards
- Code must follow Laravel conventions
- All features require tests
- Security practices are non-negotiable
- User experience must be validated with testing

### Complexity Management
- Favor simple solutions over clever ones
- Extract services for complex business logic
- Use observers for automatic state management
- Document all architectural decisions

**Version**: 1.0.0 | **Ratified**: 2024-12-29 | **Last Amended**: 2024-12-29
