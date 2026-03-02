import { ApiContractError } from "@/api/response";

export type AssertionRecord = Record<string, unknown>;

export interface AssertionFieldParsers {
    requireString(record: AssertionRecord, key: string): string;
    requireBoolean(record: AssertionRecord, key: string): boolean;
    requireNumber(record: AssertionRecord, key: string): number;
    requireStringArray(record: AssertionRecord, key: string): string[];
    parseNullableString(record: AssertionRecord, key: string): string | null;
    parseNullableNumber(record: AssertionRecord, key: string): number | null;
}

export const isRecord = (value: unknown): value is AssertionRecord =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireStringField = (record: AssertionRecord, key: string, scope: string): string => {
    const value = record[key];

    if (typeof value === "string") {
        return value;
    }

    throw new ApiContractError(`${scope} payload field \`${key}\` must be string.`);
};

const requireBooleanField = (record: AssertionRecord, key: string, scope: string): boolean => {
    const value = record[key];

    if (typeof value === "boolean") {
        return value;
    }

    throw new ApiContractError(`${scope} payload field \`${key}\` must be boolean.`);
};

const requireNumberField = (record: AssertionRecord, key: string, scope: string): number => {
    const value = Number(record[key]);

    if (Number.isFinite(value)) {
        return value;
    }

    throw new ApiContractError(`${scope} payload field \`${key}\` must be number.`);
};

const requireStringArrayField = (record: AssertionRecord, key: string, scope: string): string[] => {
    const value = record[key];

    if (Array.isArray(value) && value.every((item): item is string => typeof item === "string")) {
        return [...value];
    }

    throw new ApiContractError(`${scope} payload field \`${key}\` must be string[].`);
};

const parseNullableStringField = (
    record: AssertionRecord,
    key: string,
    scope: string,
): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value === "string") {
        return value;
    }

    throw new ApiContractError(`${scope} payload field \`${key}\` must be string|null.`);
};

const parseNullableNumberField = (
    record: AssertionRecord,
    key: string,
    scope: string,
): number | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    const numeric = Number(value);
    if (Number.isFinite(numeric)) {
        return numeric;
    }

    throw new ApiContractError(`${scope} payload field \`${key}\` must be number|null.`);
};

export const createFieldParsers = (scope: string): AssertionFieldParsers => ({
    requireString: (record, key) => requireStringField(record, key, scope),
    requireBoolean: (record, key) => requireBooleanField(record, key, scope),
    requireNumber: (record, key) => requireNumberField(record, key, scope),
    requireStringArray: (record, key) => requireStringArrayField(record, key, scope),
    parseNullableString: (record, key) => parseNullableStringField(record, key, scope),
    parseNullableNumber: (record, key) => parseNullableNumberField(record, key, scope),
});
