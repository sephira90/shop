import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

interface AdminDeletePermissionOptions {
    isAllowed: boolean;
    deniedMessage: string;
    showDenied: (message: string) => void;
}

interface ExecuteAdminDeleteMutationPipelineOptions<TItem> {
    item: TItem;
    executeMutation: ExecuteAdminMutation;
    permission?: AdminDeletePermissionOptions;
    confirm: (message: string) => boolean | Promise<boolean>;
    confirmMessage: (item: TItem) => string;
    setPending: (pending: boolean) => void;
    errorMessage: string;
    runDelete: (item: TItem) => Promise<void>;
    showSuccess: (message: string) => void;
    successMessage: string;
    onDeleted?: (item: TItem) => void | Promise<void>;
}

export const executeAdminDeleteMutationPipeline = async <TItem>(
    options: ExecuteAdminDeleteMutationPipelineOptions<TItem>,
): Promise<void> => {
    if (options.permission && !options.permission.isAllowed) {
        options.permission.showDenied(options.permission.deniedMessage);
        return;
    }

    const confirmed = await options.confirm(options.confirmMessage(options.item));
    if (!confirmed) {
        return;
    }

    await options.executeMutation<void>({
        setPending: options.setPending,
        errorMessage: options.errorMessage,
        run: async () => {
            await options.runDelete(options.item);
            options.showSuccess(options.successMessage);
            await options.onDeleted?.(options.item);
        },
    });
};
