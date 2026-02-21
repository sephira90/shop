import { isAxiosError } from 'axios';
import { parseApiErrorMessage } from '@/api/response';

export const useApiError = () => {
    const parseApiError = (error: unknown, fallback: string): string => {
        if (isAxiosError(error)) {
            if (error.response) {
                return parseApiErrorMessage(error.response.data, fallback);
            }

            return error.request ? 'No response from server.' : fallback;
        }

        if (error instanceof Error && error.message.trim().length > 0) {
            return error.message;
        }

        return fallback;
    };

    return {
        parseApiError,
    };
};
