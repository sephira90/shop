import { useAdminMutation, type ExecuteAdminMutation } from "@/composables/useAdminMutation";
import { useAdminNotice, type AdminNotice } from "@/composables/useAdminNotice";

export interface AdminQueryNoticeAdapter {
    clearNotice: () => void;
    showApiError: (error: unknown, fallback: string) => void;
}

export interface AdminMutationNoticeAdapter extends AdminQueryNoticeAdapter {
    showSuccess: (message: string) => void;
    showError: (message: string) => void;
}

export type AdminClearNoticeAdapter = Pick<AdminQueryNoticeAdapter, "clearNotice">;
export type AdminSuccessNoticeAdapter = Pick<AdminMutationNoticeAdapter, "showSuccess">;
export type AdminSubmitNoticeAdapter = Pick<
    AdminMutationNoticeAdapter,
    "showSuccess" | "showError"
>;

interface UseAdminMutationContextResult {
    notice: AdminNotice;
    executeMutation: ExecuteAdminMutation;
    queryNotice: AdminQueryNoticeAdapter;
    mutationNotice: AdminMutationNoticeAdapter;
}

export const useAdminMutationContext = (): UseAdminMutationContextResult => {
    const { notice, clearNotice, showSuccess, showError, showApiError } = useAdminNotice();
    const { executeMutation } = useAdminMutation({
        clearNotice,
        showApiError,
    });

    return {
        notice,
        executeMutation,
        queryNotice: {
            clearNotice,
            showApiError,
        },
        mutationNotice: {
            clearNotice,
            showSuccess,
            showError,
            showApiError,
        },
    };
};
