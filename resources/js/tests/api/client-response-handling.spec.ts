import type { AxiosError } from "axios";
import { beforeEach, describe, expect, it, vi } from "vitest";

import {
    handleApiClientResponseError,
    resetApiClientResponseHandlingForTests,
    type ApiClientResponseHandlingAdapter,
} from "@/api/client";

const makeAxiosError = (status: number, data: unknown, url = "/auth/me"): AxiosError => {
    const config = {
        url,
        headers: {},
    };

    return {
        name: "AxiosError",
        message: `Request failed with status code ${status}`,
        isAxiosError: true,
        config,
        response: {
            status,
            data,
            statusText: "Error",
            headers: {},
            config,
        },
        toJSON: () => ({}),
    } as AxiosError;
};

const createAdapter = (
    overrides?: Partial<ApiClientResponseHandlingAdapter>,
): ApiClientResponseHandlingAdapter => ({
    currentPath: () => "/admin/orders",
    clearAuthSession: vi.fn(async () => {}),
    redirectToAuth: vi.fn(async () => {}),
    showForbiddenNotice: vi.fn(),
    ...overrides,
});

const createDeferred = <T>() => {
    let resolvePromise!: (value: T | PromiseLike<T>) => void;
    const promise = new Promise<T>((resolve) => {
        resolvePromise = resolve;
    });

    return {
        promise,
        resolve: resolvePromise,
    };
};

describe("api client response handling", () => {
    beforeEach(() => {
        resetApiClientResponseHandlingForTests();
        vi.restoreAllMocks();
    });

    it("clears auth session and redirects once for concurrent unauthorized responses", async () => {
        const sessionClear = createDeferred<void>();
        const clearAuthSession = vi.fn(() => sessionClear.promise);
        const adapter = createAdapter({
            clearAuthSession,
        });
        const error = makeAxiosError(401, {
            error: {
                message: "Authentication is required.",
            },
        });

        const first = handleApiClientResponseError(error, adapter);
        const second = handleApiClientResponseError(error, adapter);

        expect(clearAuthSession).toHaveBeenCalledTimes(1);
        sessionClear.resolve();
        await Promise.all([first, second]);

        expect(adapter.redirectToAuth).toHaveBeenCalledTimes(1);
        expect(adapter.redirectToAuth).toHaveBeenCalledWith("/admin/orders");
    });

    it("does not redirect when unauthorized response happens on auth route", async () => {
        const adapter = createAdapter({
            currentPath: () => "/auth",
        });

        await handleApiClientResponseError(makeAxiosError(401, {}, "/auth/me"), adapter);

        expect(adapter.clearAuthSession).toHaveBeenCalledTimes(1);
        expect(adapter.redirectToAuth).not.toHaveBeenCalled();
    });

    it("skips global unauthorized handling for logout endpoint failures", async () => {
        const adapter = createAdapter();

        await handleApiClientResponseError(makeAxiosError(401, {}, "/auth/logout"), adapter);

        expect(adapter.clearAuthSession).not.toHaveBeenCalled();
        expect(adapter.redirectToAuth).not.toHaveBeenCalled();
    });

    it("shows forbidden notice from api error envelope", async () => {
        const adapter = createAdapter();

        await handleApiClientResponseError(
            makeAxiosError(
                403,
                {
                    error: {
                        message: "Forbidden by policy.",
                    },
                },
                "/admin/products",
            ),
            adapter,
        );

        expect(adapter.showForbiddenNotice).toHaveBeenCalledWith("Forbidden by policy.");
    });
});
