import type { AuthTokenResultWireDto, AuthUserWireDto } from "@/contracts/api/v1/auth";
import type { AuthTokenResult, AuthUser } from "@/types/auth";

export const mapAuthUserFromWire = (payload: AuthUserWireDto): AuthUser => {
    return {
        id: payload.id,
        first_name: payload.first_name,
        last_name: payload.last_name,
        name: payload.name,
        email: payload.email,
        roles: [...payload.roles],
        phone: payload.phone,
        is_email_verified: payload.is_email_verified,
    };
};

export const mapAuthTokenResultFromWire = (payload: AuthTokenResultWireDto): AuthTokenResult => {
    return {
        token: payload.token,
        user: mapAuthUserFromWire(payload.user),
    };
};
