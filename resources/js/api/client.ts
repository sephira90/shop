import axios, { AxiosHeaders } from "axios";

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
