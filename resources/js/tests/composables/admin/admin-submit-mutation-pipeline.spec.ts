import { describe, expect, it, vi } from "vitest";

import { executeAdminSubmitMutationPipeline } from "@/composables/admin/adminSubmitMutationPipeline";
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

describe("executeAdminSubmitMutationPipeline", () => {
    it("runs create branch, create hook, and shared success hook", async () => {
        const setPending = vi.fn();
        const buildPayload = vi.fn(() => ({ name: "Created" }));
        const runCreate = vi.fn(async () => {});
        const runUpdate = vi.fn(async () => {});
        const showSuccess = vi.fn();
        const onCreateSuccess = vi.fn();
        const onSuccess = vi.fn();

        await executeAdminSubmitMutationPipeline({
            executeMutation: createExecuteMutation(),
            setPending,
            errorMessage: "Unable to save.",
            buildPayload,
            editingId: null as number | null,
            runCreate,
            runUpdate,
            showSuccess,
            successMessages: {
                create: "Created.",
                update: "Updated.",
            },
            onCreateSuccess,
            onSuccess,
        });

        expect(buildPayload).toHaveBeenCalledTimes(1);
        expect(runCreate).toHaveBeenCalledWith({ name: "Created" });
        expect(runUpdate).not.toHaveBeenCalled();
        expect(showSuccess).toHaveBeenCalledWith("Created.");
        expect(onCreateSuccess).toHaveBeenCalledTimes(1);
        expect(onSuccess).toHaveBeenCalledWith({ mode: "create", id: null });
        expect(setPending).toHaveBeenNthCalledWith(1, true);
        expect(setPending).toHaveBeenLastCalledWith(false);
    });

    it("runs update branch, update hook, and shared success hook", async () => {
        const runCreate = vi.fn(async () => {});
        const runUpdate = vi.fn(async () => {});
        const showSuccess = vi.fn();
        const onUpdateSuccess = vi.fn();
        const onSuccess = vi.fn();

        await executeAdminSubmitMutationPipeline({
            executeMutation: createExecuteMutation(),
            setPending: vi.fn(),
            errorMessage: "Unable to save.",
            buildPayload: () => ({ name: "Updated" }),
            editingId: 7,
            runCreate,
            runUpdate,
            showSuccess,
            successMessages: {
                create: "Created.",
                update: "Updated.",
            },
            onUpdateSuccess,
            onSuccess,
        });

        expect(runCreate).not.toHaveBeenCalled();
        expect(runUpdate).toHaveBeenCalledWith(7, { name: "Updated" });
        expect(showSuccess).toHaveBeenCalledWith("Updated.");
        expect(onUpdateSuccess).toHaveBeenCalledWith(7);
        expect(onSuccess).toHaveBeenCalledWith({ mode: "update", id: 7 });
    });
});
