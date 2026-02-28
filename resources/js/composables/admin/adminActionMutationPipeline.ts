import type { ExecuteAdminMutation } from "@/composables/useAdminMutation";

interface ExecuteAdminActionMutationPipelineOptions<TResult> {
    executeMutation: ExecuteAdminMutation;
    setPending?: (pending: boolean) => void;
    errorMessage: string;
    run: () => Promise<TResult>;
    resolveSuccessMessage?: (result: TResult) => string | null;
    showSuccess?: (message: string) => void;
    afterSuccess?: (result: TResult) => void | Promise<void>;
}

export const executeAdminActionMutationPipeline = async <TResult>(
    options: ExecuteAdminActionMutationPipelineOptions<TResult>,
): Promise<void> => {
    await options.executeMutation<TResult>({
        setPending: options.setPending,
        errorMessage: options.errorMessage,
        run: options.run,
        onSuccess: async (result) => {
            const successMessage = options.resolveSuccessMessage?.(result);

            if (successMessage && options.showSuccess) {
                options.showSuccess(successMessage);
            }

            await options.afterSuccess?.(result);
        },
    });
};
