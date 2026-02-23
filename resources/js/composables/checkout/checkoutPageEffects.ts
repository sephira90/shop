export interface CheckoutGuestTokenStorageAdapter {
    getGuestToken: () => string | null;
    setGuestToken: (token: string) => void;
}

const createNoopCheckoutGuestTokenStorage = (): CheckoutGuestTokenStorageAdapter => ({
    getGuestToken: () => null,
    setGuestToken: () => {},
});

export const createBrowserCheckoutGuestTokenStorage = (): CheckoutGuestTokenStorageAdapter => ({
    getGuestToken: () => localStorage.getItem("shop_guest_token"),
    setGuestToken: (token: string) => {
        localStorage.setItem("shop_guest_token", token);
    },
});

export const resolveCheckoutGuestTokenStorageAdapter = (
    adapter?: Partial<CheckoutGuestTokenStorageAdapter>,
): CheckoutGuestTokenStorageAdapter => {
    const fallback = createNoopCheckoutGuestTokenStorage();

    return {
        getGuestToken: adapter?.getGuestToken ?? fallback.getGuestToken,
        setGuestToken: adapter?.setGuestToken ?? fallback.setGuestToken,
    };
};
