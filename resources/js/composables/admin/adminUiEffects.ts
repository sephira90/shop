export interface AdminUiEffectsAdapter {
    confirm: (message: string) => boolean | Promise<boolean>;
    scrollToTop: () => void;
}

const createNoopUiEffects = (): AdminUiEffectsAdapter => ({
    confirm: () => false,
    scrollToTop: () => {},
});

export const createBrowserAdminUiEffects = (): AdminUiEffectsAdapter => ({
    confirm: (message: string) => window.confirm(message),
    scrollToTop: () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    },
});

export const resolveAdminUiEffectsAdapter = (
    adapter?: Partial<AdminUiEffectsAdapter>,
): AdminUiEffectsAdapter => {
    const fallback = createNoopUiEffects();

    return {
        confirm: adapter?.confirm ?? fallback.confirm,
        scrollToTop: adapter?.scrollToTop ?? fallback.scrollToTop,
    };
};
