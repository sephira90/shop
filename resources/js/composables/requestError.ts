export const isAbortLikeError = (error: unknown): boolean => {
    if (!error || typeof error !== "object") {
        return false;
    }

    const payload = error as {
        name?: unknown;
        code?: unknown;
    };

    return (
        payload.name === "AbortError" ||
        payload.name === "CanceledError" ||
        payload.code === "ERR_CANCELED"
    );
};
