import { describe, expect, it, vi } from "vitest";

import { executeAdminDeleteMutationPipeline } from "@/composables/admin/adminDeleteMutationPipeline";
import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

interface DemoItem {
    id: number;
    name: string;
}

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

describe("executeAdminDeleteMutationPipeline", () => {
    it("rejects delete when permission is denied", async () => {
        const showDenied = vi.fn();
        const confirm = vi.fn(async () => true);
        const runDelete = vi.fn(async () => {});

        await executeAdminDeleteMutationPipeline<DemoItem>({
            item: { id: 1, name: "Item 1" },
            executeMutation: createExecuteMutation(),
            permission: {
                isAllowed: false,
                deniedMessage: "Denied.",
                showDenied,
            },
            confirm,
            confirmMessage: (item) => `Delete "${item.name}"?`,
            setPending: vi.fn(),
            errorMessage: "Unable to delete item.",
            runDelete,
            showSuccess: vi.fn(),
            successMessage: "Deleted.",
        });

        expect(showDenied).toHaveBeenCalledWith("Denied.");
        expect(confirm).not.toHaveBeenCalled();
        expect(runDelete).not.toHaveBeenCalled();
    });

    it("stops when delete is not confirmed", async () => {
        const confirm = vi.fn(async () => false);
        const runDelete = vi.fn(async () => {});

        await executeAdminDeleteMutationPipeline<DemoItem>({
            item: { id: 1, name: "Item 1" },
            executeMutation: createExecuteMutation(),
            confirm,
            confirmMessage: (item) => `Delete "${item.name}"?`,
            setPending: vi.fn(),
            errorMessage: "Unable to delete item.",
            runDelete,
            showSuccess: vi.fn(),
            successMessage: "Deleted.",
        });

        expect(confirm).toHaveBeenCalledWith('Delete "Item 1"?');
        expect(runDelete).not.toHaveBeenCalled();
    });

    it("executes delete mutation, success notice, and post-delete callback", async () => {
        const setPending = vi.fn();
        const runDelete = vi.fn(async () => {});
        const showSuccess = vi.fn();
        const onDeleted = vi.fn(async () => {});

        await executeAdminDeleteMutationPipeline<DemoItem>({
            item: { id: 7, name: "Item 7" },
            executeMutation: createExecuteMutation(),
            confirm: vi.fn(async () => true),
            confirmMessage: (item) => `Delete "${item.name}"?`,
            setPending,
            errorMessage: "Unable to delete item.",
            runDelete,
            showSuccess,
            successMessage: "Deleted.",
            onDeleted,
        });

        expect(setPending).toHaveBeenNthCalledWith(1, true);
        expect(runDelete).toHaveBeenCalledWith({ id: 7, name: "Item 7" });
        expect(showSuccess).toHaveBeenCalledWith("Deleted.");
        expect(onDeleted).toHaveBeenCalledWith({ id: 7, name: "Item 7" });
        expect(setPending).toHaveBeenLastCalledWith(false);
    });
});
