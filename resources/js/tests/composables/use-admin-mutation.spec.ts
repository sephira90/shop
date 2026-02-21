import { describe, expect, it, vi } from "vitest";

import { useAdminMutation } from "@/composables/useAdminMutation";

describe("useAdminMutation", () => {
    it("runs successful mutation with pending state and success hook", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        const onSuccess = vi.fn();
        const pendingStates: boolean[] = [];
        const { executeMutation } = useAdminMutation({
            clearNotice,
            showApiError,
        });

        const result = await executeMutation<number>({
            setPending: (pending) => {
                pendingStates.push(pending);
            },
            errorMessage: "Failed mutation",
            run: async () => 7,
            onSuccess,
        });

        expect(result).toBe(7);
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(showApiError).not.toHaveBeenCalled();
        expect(onSuccess).toHaveBeenCalledWith(7);
        expect(pendingStates).toEqual([true, false]);
    });

    it("routes errors to notice adapter by default", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        const pendingStates: boolean[] = [];
        const { executeMutation } = useAdminMutation({
            clearNotice,
            showApiError,
        });

        const error = new Error("Boom");
        const result = await executeMutation<number>({
            setPending: (pending) => {
                pendingStates.push(pending);
            },
            errorMessage: "Fallback message",
            run: async () => {
                throw error;
            },
        });

        expect(result).toBeNull();
        expect(clearNotice).toHaveBeenCalledTimes(1);
        expect(showApiError).toHaveBeenCalledWith(error, "Fallback message");
        expect(pendingStates).toEqual([true, false]);
    });

    it("supports custom error handler and optional notice clearing", async () => {
        const clearNotice = vi.fn();
        const showApiError = vi.fn();
        const onError = vi.fn();
        const pendingStates: boolean[] = [];
        const { executeMutation } = useAdminMutation({
            clearNotice,
            showApiError,
        });

        const error = new Error("Custom");
        const result = await executeMutation<number>({
            clearNotice: false,
            setPending: (pending) => {
                pendingStates.push(pending);
            },
            errorMessage: "Fallback message",
            run: async () => {
                throw error;
            },
            onError,
        });

        expect(result).toBeNull();
        expect(clearNotice).not.toHaveBeenCalled();
        expect(onError).toHaveBeenCalledWith(error);
        expect(showApiError).not.toHaveBeenCalled();
        expect(pendingStates).toEqual([true, false]);
    });
});
