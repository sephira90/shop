import axios, { AxiosHeaders, isAxiosError, type AxiosError, type AxiosInstance } from "axios";
import { parseApiErrorMessage } from "@/api/response";

export const apiClient = axios.create({
    baseURL: "/api/v1",
    headers: {
        Accept: "application/json",
    },
});

const buildCorrelationId = (): string => {
    if (
        typeof globalThis.crypto !== "undefined" &&
        typeof globalThis.crypto.randomUUID === "function"
    ) {
        return globalThis.crypto.randomUUID();
    }

    return `cid-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};

export interface ApiClientResponseHandlingAdapter {
    currentPath(): string;
    clearAuthSession(): Promise<void> | void;
    redirectToAuth(redirectPath: string): Promise<void> | void;
    showForbiddenNotice(message: string): void;
}

const AUTH_ROUTE_PATH = "/auth";
const LOGOUT_ENDPOINT_SUFFIX = "/auth/logout";
const DEFAULT_FORBIDDEN_MESSAGE = "You do not have permission to perform this action.";

let responseInterceptorId: number | null = null;
let unauthorizedHandling: Promise<void> | null = null;

apiClient.interceptors.request.use((config) => {
    const headers = AxiosHeaders.from(config.headers ?? {});
    config.headers = headers;

    const token = localStorage.getItem("shop_api_token");

    if (token) {
        headers.set("Authorization", `Bearer ${token}`);
    } else {
        headers.delete("Authorization");
    }

    const correlationId = buildCorrelationId();
    headers.set("X-Correlation-Id", correlationId);

    return config;
});

const isAuthRoute = (path: string): boolean =>
    path === AUTH_ROUTE_PATH || path.startsWith("/auth?");

const shouldSkipUnauthorizedHandling = (url: string | undefined): boolean =>
    typeof url === "string" && url.endsWith(LOGOUT_ENDPOINT_SUFFIX);

const handleUnauthorizedResponse = async (
    adapter: ApiClientResponseHandlingAdapter,
): Promise<void> => {
    if (unauthorizedHandling !== null) {
        await unauthorizedHandling;

        return;
    }

    unauthorizedHandling = (async () => {
        const currentPath = adapter.currentPath();
        await adapter.clearAuthSession();

        if (!isAuthRoute(currentPath)) {
            await adapter.redirectToAuth(currentPath);
        }
    })().finally(() => {
        unauthorizedHandling = null;
    });

    await unauthorizedHandling;
};

export const handleApiClientResponseError = async (
    error: AxiosError | null | undefined,
    adapter: ApiClientResponseHandlingAdapter,
): Promise<void> => {
    if (!isAxiosError(error) || error.response === undefined) {
        return;
    }

    if (error.response.status === 401) {
        if (shouldSkipUnauthorizedHandling(error.config?.url)) {
            return;
        }

        await handleUnauthorizedResponse(adapter);

        return;
    }

    if (error.response.status === 403) {
        adapter.showForbiddenNotice(
            parseApiErrorMessage(error.response.data, DEFAULT_FORBIDDEN_MESSAGE),
        );
    }
};

export const installApiClientResponseHandling = (
    adapter: ApiClientResponseHandlingAdapter,
    client: AxiosInstance = apiClient,
): void => {
    if (responseInterceptorId !== null) {
        client.interceptors.response.eject(responseInterceptorId);
    }

    unauthorizedHandling = null;
    responseInterceptorId = client.interceptors.response.use(
        (response) => response,
        async (error: AxiosError | null | undefined) => {
            await handleApiClientResponseError(error, adapter);

            return Promise.reject(error);
        },
    );
};

export const resetApiClientResponseHandlingForTests = (): void => {
    if (responseInterceptorId !== null) {
        apiClient.interceptors.response.eject(responseInterceptorId);
    }

    responseInterceptorId = null;
    unauthorizedHandling = null;
};
