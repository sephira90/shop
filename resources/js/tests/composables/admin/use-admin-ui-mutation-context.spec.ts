import { describe, expect, it, vi } from "vitest";

import { useAdminUiMutationContext } from "@/composables/admin/useAdminUiMutationContext";

const resolveAdminUiEffectsAdapterMock = vi.fn();
const useAdminMutationContextMock = vi.fn();

vi.mock("@/composables/admin/adminUiEffects", () => ({
    resolveAdminUiEffectsAdapter: (adapter: unknown) => resolveAdminUiEffectsAdapterMock(adapter),
}));

vi.mock("@/composables/admin/useAdminMutationContext", () => ({
    useAdminMutationContext: () => useAdminMutationContextMock(),
}));

describe("useAdminUiMutationContext", () => {
    it("returns resolved ui effects and shared mutation context", () => {
        const adapter = {
            confirm: vi.fn(async () => true),
            scrollToTop: vi.fn(),
        };
        const uiEffects = {
            confirm: vi.fn(async () => false),
            scrollToTop: vi.fn(),
        };
        const mutationContext = {
            notice: { type: "success" as const, message: "" },
            executeMutation: vi.fn(),
            queryNotice: {
                clearNotice: vi.fn(),
                showApiError: vi.fn(),
            },
            mutationNotice: {
                clearNotice: vi.fn(),
                showSuccess: vi.fn(),
                showError: vi.fn(),
                showApiError: vi.fn(),
            },
        };
        resolveAdminUiEffectsAdapterMock.mockReturnValue(uiEffects);
        useAdminMutationContextMock.mockReturnValue(mutationContext);

        const result = useAdminUiMutationContext(adapter);

        expect(resolveAdminUiEffectsAdapterMock).toHaveBeenCalledWith(adapter);
        expect(useAdminMutationContextMock).toHaveBeenCalledTimes(1);
        expect(result).toEqual({
            uiEffects,
            mutationContext,
        });
    });
});
