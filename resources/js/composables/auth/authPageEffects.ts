export interface AuthGuestTokenStorageAdapter {
    getGuestToken: () => string | null;
}

const createNoopAuthGuestTokenStorage = (): AuthGuestTokenStorageAdapter => ({
    getGuestToken: () => null,
});

export const createBrowserAuthGuestTokenStorage = (): AuthGuestTokenStorageAdapter => ({
    getGuestToken: () => localStorage.getItem("shop_guest_token"),
});

export const resolveAuthGuestTokenStorageAdapter = (
    adapter?: Partial<AuthGuestTokenStorageAdapter>,
): AuthGuestTokenStorageAdapter => {
    const fallback = createNoopAuthGuestTokenStorage();

    return {
        getGuestToken: adapter?.getGuestToken ?? fallback.getGuestToken,
    };
};
