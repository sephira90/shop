import { ApiContractError } from "@/api/response";
import { createFieldParsers, isRecord } from "@/contracts/api/v1/assertions/primitives";
import type { AuthTokenResultWireDto, AuthUserWireDto } from "@/contracts/api/v1/auth";

const { parseNullableString, requireBoolean, requireNumber, requireString, requireStringArray } =
    createFieldParsers("Auth");

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
