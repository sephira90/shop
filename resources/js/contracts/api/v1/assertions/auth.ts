import { ApiContractError } from "@/api/response";
import type { AuthTokenResultWireDto, AuthUserWireDto } from "@/contracts/api/v1/auth";

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === "object" && value !== null && !Array.isArray(value);

const requireString = (record: Record<string, unknown>, key: string): string => {
    const value = record[key];

    if (typeof value !== "string") {
        throw new ApiContractError(`Auth payload field \`${key}\` must be string.`);
    }

    return value;
};

const requireBoolean = (record: Record<string, unknown>, key: string): boolean => {
    const value = record[key];

    if (typeof value !== "boolean") {
        throw new ApiContractError(`Auth payload field \`${key}\` must be boolean.`);
    }

    return value;
};

const requireNumber = (record: Record<string, unknown>, key: string): number => {
    const value = Number(record[key]);

    if (!Number.isFinite(value)) {
        throw new ApiContractError(`Auth payload field \`${key}\` must be number.`);
    }

    return value;
};

const requireStringArray = (record: Record<string, unknown>, key: string): string[] => {
    const value = record[key];

    if (!Array.isArray(value) || value.some((item) => typeof item !== "string")) {
        throw new ApiContractError(`Auth payload field \`${key}\` must be string[].`);
    }

    return [...value];
};

const parseNullableString = (record: Record<string, unknown>, key: string): string | null => {
    const value = record[key];

    if (value === null) {
        return null;
    }

    if (typeof value === "string") {
        return value;
    }

    throw new ApiContractError(`Auth payload field \`${key}\` must be string|null.`);
};

export const assertAuthUserWireDto = (value: unknown): AuthUserWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Auth user payload must be an object.");
    }

    return {
        id: requireNumber(value, "id"),
        first_name: requireString(value, "first_name"),
        last_name: requireString(value, "last_name"),
        name: requireString(value, "name"),
        email: requireString(value, "email"),
        roles: requireStringArray(value, "roles"),
        phone: parseNullableString(value, "phone"),
        is_email_verified: requireBoolean(value, "is_email_verified"),
    };
};

export const assertAuthTokenResultWireDto = (value: unknown): AuthTokenResultWireDto => {
    if (!isRecord(value)) {
        throw new ApiContractError("Auth token payload must be an object.");
    }

    return {
        token: requireString(value, "token"),
        user: assertAuthUserWireDto(value.user),
    };
};
