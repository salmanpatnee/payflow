# Implementation Plan: Payment Link Generation & Client Flow

**Feature**: Payment Link Generation & Client Flow
**Branch**: 002-payment-links
**Created**: 2025-12-30
**Status**: Draft

## Technical Context

This feature implements shareable payment links that allow admins to generate unique URLs for payment collections. Clients can access these links to view payment details and provide their information. The system will use secure tokens for access control and track client access.

**Key Technologies**:
- Laravel 12 with PHP 8.3
- Inertia.js v2 with Vue 3
- shadcn-vue components
- Tailwind CSS v4
- Stripe PHP SDK

**Key Dependencies**:
- Payment collections and items (existing functionality)
- Authentication system (admin access)
- Database schema for payment links and access records

**Integration Points**:
- Admin dashboard for generating links
- Public payment page for clients
- Database for storing link tokens and access records

**Unknowns**: None - all research items have been resolved in research.md

## Constitution Check

### Security Compliance
- [x] Payment security: No payment processing in this phase, only viewing
- [x] Data integrity: Proper audit trail for link access
- [x] User experience: Client-friendly interface without authentication
- [x] Simplicity: Following Laravel conventions
- [x] Test-first: Feature tests for all functionality
- [x] Performance: Proper indexing on token fields

### Architecture Alignment
- [x] Uses Eloquent relationships for data models
- [x] Leverages Inertia.js for frontend integration
- [x] Uses shadcn-vue components for UI consistency
- [x] Follows single responsibility principle
- [x] Maintains immutability where appropriate

## Phase 0: Outline & Research

### Research Tasks

1. **Token Generation Mechanism**
   - Research secure token generation approaches in Laravel
   - Compare UUID vs random string approaches
   - Consider token expiration and validation strategies

2. **Client Information Capture Flow**
   - Determine when and how to capture client name/email
   - Research UX patterns for optional information capture
   - Consider privacy implications of data collection

3. **Public Page Security**
   - Research best practices for secure public access to private data
   - Understand token validation and access control patterns
   - Consider rate limiting and abuse prevention

4. **UI/UX Design for Client Page**
   - Design approach for displaying payment items as stack cards
   - Research shadcn-vue components suitable for payment display
   - Consider responsive design for mobile clients

### Implementation Approach

1. **Backend Development**:
   - Extend PaymentCollection model with token generation
   - Create ClientAccessRecord model for tracking
   - Implement public route with token validation
   - Add admin interface for link generation

2. **Frontend Development**:
   - Create public payment page using Vue 3 and Inertia
   - Design payment item cards using shadcn-vue components
   - Implement client information capture form
   - Ensure responsive design for mobile users

## Phase 1: Design & Contracts

### Data Model Design

#### PaymentCollection Model Extension
- Add `payment_link_token` field (string, unique, nullable)
- Add `payment_link_expires_at` field (timestamp, nullable)
- Add `generatePaymentLink()` method
- Add `getPaymentLinkUrl()` method
- Add relationship to ClientAccessRecord

#### ClientAccessRecord Model
- `id` (primary key)
- `payment_collection_id` (foreign key to payment_collections)
- `client_name` (string, nullable)
- `client_email` (string, nullable)
- `access_token` (string, indexed)
- `ip_address` (string)
- `accessed_at` (timestamp)
- `user_agent` (text, nullable)

### API Contracts

#### Public Route: GET /pay/{token}
- Validates payment link token
- Returns payment collection details
- Tracks client access
- Requires: valid token
- Returns: payment collection data, items, client form

#### Admin Route: POST /admin/payment-collections/{id}/generate-link
- Generates a new payment link token
- Sets expiration date
- Returns the link URL
- Requires: authenticated admin, valid collection ID
- Returns: payment link URL

### Frontend Components

#### Public Payment Page
- Displays payment collection details
- Shows items as stack cards using shadcn-vue components
- Includes client information capture form
- Responsive design for mobile users

#### Admin Link Generator
- Button in payment collection view to generate link
- Copy-to-clipboard functionality
- Visual indicator of link status (active/expired)

## Phase 2: Implementation Plan

### Sprint 1: Backend Implementation
1. Extend PaymentCollection model with token generation
2. Create ClientAccessRecord model and migration
3. Implement token validation middleware
4. Create public payment route and controller
5. Add admin link generation endpoint

### Sprint 2: Frontend Implementation
1. Create public payment page UI with Vue 3
2. Implement payment item cards with shadcn-vue
3. Add client information capture form
4. Create admin link generator UI
5. Implement copy-to-clipboard functionality

### Sprint 3: Integration & Testing
1. Connect frontend to backend APIs
2. Implement comprehensive feature tests
3. Add browser tests for user flows
4. Performance testing for concurrent access
5. Security review and validation

## Success Criteria Verification

- [ ] Admins can generate payment links in under 30 seconds
- [ ] 95% of clients can access payment details without issues
- [ ] System handles 10,000+ active payment links
- [ ] Client information capture success rate >90%
- [ ] Zero unauthorized access incidents

## Risks & Mitigation

1. **Token Security Risk**: Tokens could be guessed or brute-forced
   - Mitigation: Use cryptographically secure random tokens with sufficient entropy

2. **Performance Risk**: High number of active links could impact database performance
   - Mitigation: Proper indexing on token fields and regular cleanup of expired links

3. **Privacy Risk**: Captured client information needs proper handling
   - Mitigation: Follow GDPR/privacy best practices, clear privacy notice

## Next Steps

1. Complete research phase to resolve unknowns
2. Finalize data model design
3. Create detailed API contracts
4. Begin backend implementation
5. Implement frontend components