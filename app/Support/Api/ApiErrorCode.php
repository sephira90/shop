<?php

declare(strict_types=1);

namespace App\Support\Api;

/**
 * Closed set of stable machine-readable API error codes.
 *
 * Emitted as the additive `error.code` field in the API error envelope
 * alongside the existing `error.type` (PHP class basename). The `error.type`
 * literal is preserved byte-for-byte for backward compatibility but should be
 * treated as deprecated-but-stable: clients should pin on `error.code` for
 * machine handling. Removing `error.type` is a separate future breaking change
 * with an explicit migration plan and is out of scope for the R1 block that
 * introduces this enum.
 *
 * Members:
 * - validation_failed:        Request payload validation (422).
 * - unauthenticated:          No authenticated identity / invalid credentials (401).
 * - forbidden:                Authenticated but lacking authorization (403).
 * - not_found:                Resource lookup miss routed through the renderer (404).
 * - state_transition_not_allowed: Domain state machine rejected a transition (422).
 * - stale_aggregate:          Aggregate (e.g. Order) became stale under concurrent update (409).
 * - webhook_ingress_rejected: Umbrella for the WebhookIngressErrorCode family (4xx).
 * - domain_failure:           Other domain rule violation without a dedicated code (422).
 * - internal_error:           Masked 5xx; details are never exposed to clients (500).
 */
enum ApiErrorCode: string
{
    case ValidationFailed = 'validation_failed';

    case Unauthenticated = 'unauthenticated';

    case Forbidden = 'forbidden';

    case NotFound = 'not_found';

    case StateTransitionNotAllowed = 'state_transition_not_allowed';

    case StaleAggregate = 'stale_aggregate';

    case WebhookIngressRejected = 'webhook_ingress_rejected';

    case DomainFailure = 'domain_failure';

    case InternalError = 'internal_error';
}
