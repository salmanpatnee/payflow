# Tasks: Payment Link Generation & Client Flow

**Feature**: Payment Link Generation & Client Flow
**Created**: 2025-12-30
**Status**: Draft

## Implementation Strategy

This feature implements shareable payment links that allow admins to generate unique URLs for payment collections. The implementation will follow an incremental approach with the following phases:

1. **Setup**: Create necessary database migrations and models
2. **Foundational**: Implement core backend functionality (models, middleware, controllers)
3. **User Story 1**: Create and share payment links (P1 priority)
4. **User Story 2**: Client view payment details (P1 priority)
5. **User Story 3**: Capture client information (P2 priority)
6. **Polish**: Cross-cutting concerns and final touches

The MVP scope will include User Story 1 and User Story 2, allowing admins to generate links and clients to view payment details.

## Phase 1: Setup

### Goal
Initialize the project with necessary database schema and basic structure.

- [x] T001 Create migration to extend payment_collections table with payment_link_token and payment_link_expires_at fields
- [x] T002 Create migration for client_access_records table with all required fields and indexes
- [x] T003 Run migrations to update database schema
- [ ] T004 Install shadcn-vue components if not already installed

## Phase 2: Foundational

### Goal
Implement core backend functionality that will be used by all user stories.

- [x] T005 Create ClientAccessRecord model with proper relationships and validation
- [x] T006 Extend PaymentCollection model with payment link functionality (generatePaymentLink, getPaymentLinkUrl methods)
- [x] T007 Create ValidatePaymentLink middleware for token validation
- [x] T008 [P] Create PaymentLinkController for public routes
- [x] T009 [P] Create Admin\\PaymentCollectionLinkController for admin routes
- [x] T010 Register payment link routes in web.php

## Phase 3: [US1] Create and Share Payment Link (Priority: P1)

### Goal
Admin users need to generate unique payment links that can be shared with clients to view and pay for specific collections.

### Independent Test
Admin can create a payment link for a specific collection and share it with a client, who can then access the payment details via the link.

- [x] T011 [US1] Add generatePaymentLink method to PaymentCollection model to create secure tokens
- [x] T012 [US1] Implement POST /admin/payment-collections/{id}/generate-link endpoint
- [x] T013 [US1] Implement GET /admin/payment-collections/{id}/payment-link endpoint to retrieve link info
- [x] T014 [US1] Implement DELETE /admin/payment-collections/{id}/payment-link endpoint to revoke links
- [x] T015 [P] [US1] Create UI component for generating payment link in admin collection view
- [x] T016 [P] [US1] Implement copy-to-clipboard functionality for payment link
- [x] T017 [US1] Add visual indicator for link status (active/expired) in admin interface

## Phase 4: [US2] Client View Payment Details (Priority: P1)

### Goal
Client users need to view the details of what they're being asked to pay when accessing a payment link.

### Independent Test
Client can access a payment link and view all relevant payment collection details including items, amounts, and due dates.

- [x] T018 [US2] Implement GET /pay/{token} endpoint with token validation
- [x] T019 [US2] Create public payment page component using Vue 3 and Inertia
- [x] T020 [P] [US2] Implement payment item cards using shadcn-vue Card components
- [x] T021 [P] [US2] Design responsive layout for payment items as stack cards
- [x] T022 [US2] Add proper error handling for invalid/expired tokens (404 page)

## Phase 5: [US3] Capture Client Information (Priority: P2)

### Goal
System needs to capture basic client information (name, email) when they access payment links for tracking and communication purposes.

### Independent Test
When a client accesses a payment link, they can provide their name and email which gets recorded in the system.

- [ ] T023 [US3] Implement POST /pay/{token}/capture-info endpoint
- [ ] T024 [US3] Add client information form to public payment page
- [ ] T025 [US3] Update client access record with submitted information
- [ ] T026 [US3] Add validation for client information (email format, etc.)
- [ ] T027 [US3] Display privacy notice for data collection

## Phase 6: Polish & Cross-Cutting Concerns

### Goal
Address cross-cutting concerns and finalize the implementation.

- [ ] T028 Add rate limiting to public payment routes to prevent abuse
- [ ] T029 Implement proper logging for payment link access attempts
- [x] T030 Add feature tests for all user stories
- [ ] T031 Add browser tests for client payment flow
- [ ] T032 Optimize database queries with proper eager loading
- [ ] T033 Add proper meta tags and SEO for public payment pages
- [ ] T034 Update documentation with payment link functionality

## Dependencies

### User Story Dependencies
- US2 (Client View Payment Details) requires US1 (Create and Share Payment Link) to have the links to access
- US3 (Capture Client Information) requires US2 (Client View Payment Details) as the form will be on the same page

### Implementation Dependencies
- T005-T010 (Foundational) must be completed before any user story tasks
- T001-T003 (Setup) must be completed before any other tasks

## Parallel Execution Examples

### Parallel Tasks Within User Stories
- T015, T016, T017 (Admin UI) can be developed in parallel with T012, T013, T014 (Backend API)
- T019, T020, T021 (Client UI) can be developed in parallel with T018 (Backend API)
- T024, T027 (Client form UI) can be developed in parallel with T023, T025, T026 (Backend API)

### Parallel Development Teams
- Backend team: Focus on T005-T010 (Foundational) and API endpoints (T012, T013, T014, T018, T023)
- Frontend team: Focus on UI components (T015, T016, T017, T019, T020, T021, T024, T027)
- QA team: Prepare tests for T030, T031 while development is in progress

## Success Criteria Verification

- [ ] SC-001: Admins can generate a payment link in under 30 seconds from the payment collection view (T011-T017)
- [ ] SC-002: 95% of clients can successfully access payment details using shared links without technical issues (T018-T022)
- [ ] SC-003: System can handle at least 10,000 active payment links simultaneously without performance degradation (T028, T032)
- [ ] SC-004: Client information capture has a success rate of 90% or higher when accessing payment links (T023-T027)
- [ ] SC-005: Payment link access is secure with 0 unauthorized access incidents in the first 30 days of production (T007, T028)