import { apiClient } from "@/api/client";
import { extractData } from "@/api/response";
import {
    assertAuthTokenResultWireDto,
    assertAuthUserWireDto,
} from "@/contracts/api/v1/assertions/auth";
import { mapAuthTokenResultFromWire, mapAuthUserFromWire } from "@/mappers/auth";
import type {
    AuthLoginPayload,
    AuthRegisterPayload,
    AuthTokenResult,
    AuthUpdateProfilePayload,
    AuthUser,
} from "@/types/auth";

export const loginAuth = async (payload: AuthLoginPayload): Promise<AuthTokenResult> => {
    const { data } = await apiClient.post("/auth/login", payload);
    const response = extractData(data);

    return mapAuthTokenResultFromWire(assertAuthTokenResultWireDto(response));
};

export const registerAuth = async (payload: AuthRegisterPayload): Promise<AuthTokenResult> => {
    const { data } = await apiClient.post("/auth/register", payload);
    const response = extractData(data);

    return mapAuthTokenResultFromWire(assertAuthTokenResultWireDto(response));
};

export const getAuthProfile = async (): Promise<AuthUser> => {
    const { data } = await apiClient.get("/auth/me");
    const response = extractData(data);

    return mapAuthUserFromWire(assertAuthUserWireDto(response));
};

export const updateAuthProfile = async (payload: AuthUpdateProfilePayload): Promise<AuthUser> => {
    const { data } = await apiClient.patch("/auth/profile", payload);
    const response = extractData(data);

    return mapAuthUserFromWire(assertAuthUserWireDto(response));
};
