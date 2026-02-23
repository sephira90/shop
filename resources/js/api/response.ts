import type { PaginationMeta } from "@/types/pagination";

export interface ApiErrorPayload {
    message?: string;
    request_id?: string;
    validation?: Record<string, string[]>;
}

export interface ApiErrorEnvelope {
    error?: ApiErrorPayload;
    message?: string;
    errors?: Record<string, string[]>;
}

export interface ListResponse<TItem> {
    data: TItem[];
    meta: PaginationMeta;
}

export class ApiContractError extends Error {
    constructor(message: string) {
        super(message);
        this.name = "ApiContractError";
    }
}

const DEFAULT_META: PaginationMeta = {
    current_page: 1,
    last_page: 1,
    per_page: 1,
    total: 0,
};

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

export const normalizeMeta = (value: unknown): PaginationMeta => {
    if (!isRecord(value)) {
        throw new ApiContractError("API list response `meta` must be an object.");
    }

    const raw = value;
    const currentPage = Number(raw.current_page);
    const lastPage = Number(raw.last_page);
    const perPage = Number(raw.per_page);
    const total = Number(raw.total);

    return {
        current_page: Number.isFinite(currentPage) && currentPage > 0 ? currentPage : 1,
        last_page: Number.isFinite(lastPage) && lastPage > 0 ? lastPage : 1,
        per_page: Number.isFinite(perPage) && perPage > 0 ? perPage : DEFAULT_META.per_page,
        total: Number.isFinite(total) && total >= 0 ? total : 0,
    };
};

export const normalizeListResponse = <TItem>(payload: unknown): ListResponse<TItem> => {
    if (!isRecord(payload)) {
        throw new ApiContractError("API list response must be an object envelope.");
    }

    if (!Object.hasOwn(payload, "data")) {
        throw new ApiContractError("API list response must contain `data`.");
    }

    if (!Array.isArray(payload.data)) {
        throw new ApiContractError("API list response `data` must be an array.");
    }

    if (!Object.hasOwn(payload, "meta")) {
        throw new ApiContractError("API list response must contain `meta`.");
    }

    return {
        data: payload.data as TItem[],
        meta: normalizeMeta(payload.meta),
    };
};

export const extractData = <TData>(payload: unknown): TData => {
    if (!isRecord(payload)) {
        throw new ApiContractError("API response must be an object envelope.");
    }

    if (!Object.hasOwn(payload, "data")) {
        throw new ApiContractError("API response must contain `data`.");
    }

    return payload.data as TData;
};

const extractFirstValidationError = (payload: ApiErrorEnvelope): string | null => {
    const rootValidation = payload.errors;

    if (rootValidation && typeof rootValidation === "object") {
        const firstErrors = Object.values(rootValidation)[0];

        if (
            Array.isArray(firstErrors) &&
            firstErrors.length > 0 &&
            typeof firstErrors[0] === "string"
        ) {
            return firstErrors[0];
        }
    }

    const nestedValidation = payload.error?.validation;

    if (nestedValidation && typeof nestedValidation === "object") {
        const firstErrors = Object.values(nestedValidation)[0];

        if (
            Array.isArray(firstErrors) &&
            firstErrors.length > 0 &&
            typeof firstErrors[0] === "string"
        ) {
            return firstErrors[0];
        }
    }

    return null;
};

export const parseApiErrorMessage = (payload: unknown, fallback: string): string => {
    if (!payload || typeof payload !== "object") {
        return fallback;
    }

    const envelope = payload as ApiErrorEnvelope;

    const firstValidationError = extractFirstValidationError(envelope);

    if (firstValidationError !== null) {
        return firstValidationError;
    }

    const message = envelope.error?.message ?? envelope.message;

    if (!message || message.trim() === "") {
        return fallback;
    }

    const requestId = envelope.error?.request_id;

    if (requestId && requestId.trim() !== "") {
        return `${message} (request: ${requestId})`;
    }

    return message;
};
