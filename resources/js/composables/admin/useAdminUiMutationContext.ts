import type { AdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { resolveAdminUiEffectsAdapter } from "@/composables/admin/adminUiEffects";
import { useAdminMutationContext } from "@/composables/admin/useAdminMutationContext";

interface UseAdminUiMutationContextResult {
    uiEffects: AdminUiEffectsAdapter;
    mutationContext: ReturnType<typeof useAdminMutationContext>;
}

export const useAdminUiMutationContext = (
    adapter?: Partial<AdminUiEffectsAdapter>,
): UseAdminUiMutationContextResult => {
    return {
        uiEffects: resolveAdminUiEffectsAdapter(adapter),
        mutationContext: useAdminMutationContext(),
    };
};
