import { reactive } from 'vue';

import { useApiError } from '@/composables/useApiError';

export interface AdminNotice {
    type: 'success' | 'error';
    message: string;
}

export const useAdminNotice = () => {
    const { parseApiError } = useApiError();
    const notice = reactive<AdminNotice>({
        type: 'success',
        message: '',
    });

    const clearNotice = (): void => {
        notice.message = '';
    };

    const showSuccess = (message: string): void => {
        notice.type = 'success';
        notice.message = message;
    };

    const showError = (message: string): void => {
        notice.type = 'error';
        notice.message = message;
    };

    const showApiError = (error: unknown, fallback: string): void => {
        showError(parseApiError(error, fallback));
    };

    return {
        notice,
        clearNotice,
        showSuccess,
        showError,
        showApiError,
    };
};
