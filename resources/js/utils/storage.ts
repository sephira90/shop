export interface StorageAdapter {
    getItem(key: string): string | null;
    setItem(key: string, value: string): void;
    removeItem(key: string): void;
}

const resolveBrowserStorage = (): Storage | null => {
    if (typeof globalThis === "undefined" || !("localStorage" in globalThis)) {
        return null;
    }

    try {
        return globalThis.localStorage;
    } catch {
        return null;
    }
};

export const createNoopStorageAdapter = (): StorageAdapter => ({
    getItem: () => null,
    setItem: () => {},
    removeItem: () => {},
});

export const createBrowserStorageAdapter = (): StorageAdapter => ({
    getItem: (key: string) => resolveBrowserStorage()?.getItem(key) ?? null,
    setItem: (key: string, value: string) => {
        resolveBrowserStorage()?.setItem(key, value);
    },
    removeItem: (key: string) => {
        resolveBrowserStorage()?.removeItem(key);
    },
});

export const createInMemoryStorageAdapter = (
    initial: Record<string, string> = {},
): StorageAdapter => {
    const state = new Map<string, string>(Object.entries(initial));

    return {
        getItem: (key: string) => (state.has(key) ? (state.get(key) ?? null) : null),
        setItem: (key: string, value: string) => {
            state.set(key, value);
        },
        removeItem: (key: string) => {
            state.delete(key);
        },
    };
};
