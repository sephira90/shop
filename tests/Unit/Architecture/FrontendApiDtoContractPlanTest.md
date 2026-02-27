# Frontend API DTO Contract Plan Test

## Purpose

This document is a test-spec companion for DTO migration on frontend API boundaries.

## Enforced in code

- `resources/js/tests/api/dto-boundary.spec.ts`
  - Baseline check: `unknown` token count in `resources/js/api` and `resources/js/mappers`
    must not exceed baseline.
- `resources/js/tests/api/auth-contract.spec.ts`
  - Runtime assertions for auth wire DTO payloads.

## Next test milestones

1. Add endpoint-specific parser tests for:
   - catalog
   - cart
   - checkout
   - admin categories/orders/products/promotions
   - account orders
2. Narrow `unknown` usage allow-zone to:
   - `resources/js/api/response.ts`
   - `resources/js/contracts/api/v1/assertions/*`
3. Replace baseline guard with strict allowlist guard by file path.
