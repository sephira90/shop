import { describe, expect, it, vi } from "vitest";

import { executeAdminActionMutationPipeline } from "@/composables/admin/adminActionMutationPipeline";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

const createExecuteMutation = (): ExecuteAdminMutation => {
    return async (options) => {
        options.setPending?.(true);

        try {
            const result = await options.run();
            await options.onSuccess?.(result);
            return result;
        } catch (error: unknown) {
            await options.onError?.(error);
            return null;
        } finally {
            options.setPending?.(false);
        }
    };
};

describe("executeAdminActionMutationPipeline", () => {
    it("runs mutation, shows success message, and executes success hook", async () => {
        const setPending = vi.fn();
        const run = vi.fn(async () => 42);
        const showSuccess = vi.fn();
        const afterSuccess = vi.fn();

        await executeAdminActionMutationPipeline<number>({
            executeMutation: createExecuteMutation(),
            setPending,
            errorMessage: "Unable to run.",
            run,
            resolveSuccessMessage: (result) => `Done ${result}`,
            showSuccess,
            afterSuccess,
        });

        expect(run).toHaveBeenCalledTimes(1);
        expect(showSuccess).toHaveBeenCalledWith("Done 42");
        expect(afterSuccess).toHaveBeenCalledWith(42);
        expect(setPending).toHaveBeenNthCalledWith(1, true);
        expect(setPending).toHaveBeenLastCalledWith(false);
    });

    it("allows success hook without success message", async () => {
        const showSuccess = vi.fn();
        const afterSuccess = vi.fn();

        await executeAdminActionMutationPipeline<void>({
            executeMutation: createExecuteMutation(),
            errorMessage: "Unable to run.",
            run: async () => {},
            resolveSuccessMessage: () => null,
            showSuccess,
            afterSuccess,
        });

        expect(showSuccess).not.toHaveBeenCalled();
        expect(afterSuccess).toHaveBeenCalledTimes(1);
    });
});
