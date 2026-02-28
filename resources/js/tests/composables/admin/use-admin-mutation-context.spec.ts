import { describe, expect, it, vi } from "vitest";

import { useAdminMutationContext } from "@/composables/admin/useAdminMutationContext";

const useAdminNoticeMock = vi.fn();
const useAdminMutationMock = vi.fn();

vi.mock("@/composables/useAdminNotice", () => ({
    useAdminNotice: () => useAdminNoticeMock(),
}));

vi.mock("@/composables/useAdminMutation", () => ({
    useAdminMutation: (notice: unknown) => useAdminMutationMock(notice),
}));

describe("useAdminMutationContext", () => {
    it("builds shared query/mutation adapters and wires executeMutation", () => {
        const notice = {
            type: "success" as const,
            message: "",
        };
        const clearNotice = vi.fn();
        const showSuccess = vi.fn();
        const showError = vi.fn();
        const showApiError = vi.fn();
        const executeMutation = vi.fn();
        useAdminNoticeMock.mockReturnValue({
            notice,
            clearNotice,
            showSuccess,
            showError,
            showApiError,
        });
        useAdminMutationMock.mockReturnValue({
            executeMutation,
        });

        const context = useAdminMutationContext();

        expect(context.notice).toBe(notice);
        expect(context.executeMutation).toBe(executeMutation);
        expect(context.queryNotice).toEqual({
            clearNotice,
            showApiError,
        });
        expect(context.mutationNotice).toEqual({
            clearNotice,
            showSuccess,
            showError,
            showApiError,
        });
        expect(useAdminMutationMock).toHaveBeenCalledWith({
            clearNotice,
            showApiError,
        });
    });
});
