# ADR-0002: DTO Strategy and Boundary Enforcement

## Status

Accepted

## Context

Project currently contains multiple array-based payload contracts in application/service layers and
`unknown`-based frontend parsing in API/mappers.

We need a deterministic migration strategy that:

- preserves `/api/v1/*` HTTP envelope compatibility,
- improves internal typing at transport/application/service/frontend boundaries,
- prevents regression to ad-hoc array contracts.

## Decision

1. DTO naming convention:
- `*InputDto` for use-case inputs.
- `*FilterDto` for list/query filters.
- `*ResultDto` for handler/service outputs.
- `*PayloadDto` for integration/webhook payload wrappers.

2. Placement:
- `app/Application/<Domain>/Dto/*`
- `app/Services/<Domain>/Dto/*`
- `resources/js/contracts/api/v1/*`
- `resources/js/contracts/api/v1/assertions/*`

3. Construction:
- DTOs are `final readonly class`.
- FormRequest builds DTO via `toDto()` and `fromValidated(...)`.
- Normalization lives in DTO factory, not in handlers/services.

4. Presentation:
- `toArray()` is allowed only at transport/presentation boundary.
- HTTP response envelopes (`data/meta/error`) remain unchanged.

5. Enforcement:
- Architecture tests enforce allowlisted legacy array payload usage.
- Frontend baseline test blocks growth of `unknown` usage.
- Allowlists must shrink wave by wave until removed.

## Consequences

- Clear migration path without big-bang rewrite.
- Improved static guarantees in business-critical flows.
- Additional architecture tests become mandatory quality gate signal.
