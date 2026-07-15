# POS Implementation Plan

## Current State Summary:
- ✓ Sales module exists but is "too slow for counter service"
- ✗ POS interface needs to be created from scratch
- ✓ All underlying models (Sales, Payments, Inventory, CashFlow) already exist
- ✓ Laravel (10+) and React frontend are well-architected
- No existing `/pos` route or POS components

## Implementation Plan:

### Phase 1: Backend Setup (Week 1)
- [ ] Create `PosController` in `api/app/Http/Controllers/`
- [ ] Create `PosService` with validation and transaction logic
- [ ] Create `PosRequest` and `PosResource` classes
- [ ] Add `POS` route prefix in `api/routes/` following existing patterns

### Phase 2: Core APIs (Week 2)
- [ ] **Product Search API**: `/api/products/search` (barcode, SKU, name)
- [ ] **Customer Search API**: `/api/customers/search` (phone, number, name)
- [ ] Implement all search results with required fields
- [ ] Add cart validation middleware

### Phase 3: Checkout Process (Week 3)
- [ ] Implement `/api/sales/checkout` transaction endpoint
- [ ] Handle cart validation, inventory deduction, payment processing
- [ ] Add hold/resume/delete cart functionality
- [ ] Implement receipt generation

### Phase 4: Frontend Development (Weeks 4-5)
- [ ] Create dedicated `/pos` React route
- [ ] Build full-screen POS interface
- [ ] Implement barcode scanner support
- [ ] Create cart management components
- [ ] Add checkout modal with payment methods

### Phase 5: UX & Performance (Weeks 6-7)
- [ ] Add keyboard shortcuts (F2, F4, F8, F9, ESC)
- [ ] Implement debounced search for performance
- [ ] Add printer integration
- [ ] Build receipt preview/print/download

### Phase 6: Testing & Integration (Week 8)
- [ ] Implement unit and feature tests for all endpoints
- [ ] Add cart validation, payment, and stock tests
- [ ] Test rollback scenarios
- [ ] Verify permissions and scoping

## Key Architectural Considerations:
- Reuse existing Sale, SaleItem, Payment, Inventory logic
- Follow existing Laravel conventions and patterns
- Maintain business/branch scoping
- Use existing Printer model for thermal integration
- All sensitive operations wrapped in database transactions
- Minimal code changes focused only on POS functionality

## Files to Modify:
- `api/routes/*` - Add POS routes
- `api/app/` - Create POS controllers/services
- `ui/src/` - Create POS page and components
- Test coverage required for all new features

## Technical Requirements from Today's Task:
- Dedicated POS module separate from Sales CRUD
- Support barcode scanner → Add to cart (minimum 1 click)
- Product search by barcode, SKU, name with stock/price
- Customer search with loyalty points
- Cart validation (inventory, business ownership)
- Mixed payment support
- Full-screen `/pos` interface
- Keyboard shortcuts (F2-F9, ESC, Ctrl+Delete)
- Printer integration
- Receipt generation/print/download
- Debounced search for performance
- All operations transaction-wrapped