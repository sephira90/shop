import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

type AdminSubmitMode = "create" | "update";

interface ExecuteAdminSubmitMutationPipelineOptions<TPayload, TId> {
    executeMutation: ExecuteAdminMutation;
    setPending: (pending: boolean) => void;
    errorMessage: string;
    buildPayload: () => TPayload;
    editingId: TId | null;
    runCreate: (payload: TPayload) => Promise<void>;
    runUpdate: (id: TId, payload: TPayload) => Promise<void>;
    showSuccess: (message: string) => void;
    successMessages: {
        create: string;
        update: string;
    };
    onCreateSuccess?: () => void | Promise<void>;
    onUpdateSuccess?: (id: TId) => void | Promise<void>;
    onSuccess?: (context: { mode: AdminSubmitMode; id: TId | null }) => void | Promise<void>;
}

export const executeAdminSubmitMutationPipeline = async <TPayload, TId>(
    options: ExecuteAdminSubmitMutationPipelineOptions<TPayload, TId>,
): Promise<void> => {
    await options.executeMutation<void>({
        setPending: options.setPending,
        errorMessage: options.errorMessage,
        run: async () => {
            const payload = options.buildPayload();

            if (options.editingId !== null) {
                const id = options.editingId;
                await options.runUpdate(id, payload);
                options.showSuccess(options.successMessages.update);
                await options.onUpdateSuccess?.(id);
                await options.onSuccess?.({ mode: "update", id });
                return;
            }

            await options.runCreate(payload);
            options.showSuccess(options.successMessages.create);
            await options.onCreateSuccess?.();
            await options.onSuccess?.({ mode: "create", id: null });
        },
    });
};
