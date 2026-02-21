interface AdminMutationNoticeAdapter {
    clearNotice: () => void;
    showApiError: (error: unknown, fallback: string) => void;
}

interface ExecuteAdminMutationOptions<TResult> {
    run: () => Promise<TResult>;
    errorMessage: string;
    setPending?: (pending: boolean) => void;
    onSuccess?: (result: TResult) => void | Promise<void>;
    onError?: (error: unknown) => void | Promise<void>;
    clearNotice?: boolean;
}

export const useAdminMutation = (notice: AdminMutationNoticeAdapter) => {
    const executeMutation = async <TResult>(options: ExecuteAdminMutationOptions<TResult>): Promise<TResult | null> => {
        if (options.clearNotice ?? true) {
            notice.clearNotice();
        }

        options.setPending?.(true);

        try {
            const result = await options.run();

            if (options.onSuccess) {
                await options.onSuccess(result);
            }

            return result;
        } catch (error: unknown) {
            if (options.onError) {
                await options.onError(error);
            } else {
                notice.showApiError(error, options.errorMessage);
            }

            return null;
        } finally {
            options.setPending?.(false);
        }
    };

    return {
        executeMutation,
    };
};
