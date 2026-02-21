export const asRecord = (value: unknown): Record<string, unknown> => {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return {};
    }

    return value as Record<string, unknown>;
};

export const asArray = (value: unknown): unknown[] => {
    return Array.isArray(value) ? value : [];
};

export const toString = (value: unknown, fallback = ''): string => {
    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    return fallback;
};

export const toNullableString = (value: unknown): string | null => {
    const normalized = toString(value).trim();

    return normalized === '' ? null : normalized;
};

export const toBoolean = (value: unknown, fallback = false): boolean => {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value !== 0;
    }

    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();

        if (normalized === 'true' || normalized === '1' || normalized === 'yes') {
            return true;
        }

        if (normalized === 'false' || normalized === '0' || normalized === 'no') {
            return false;
        }
    }

    return fallback;
};

export const toNumber = (value: unknown, fallback = 0): number => {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
};

export const toInteger = (value: unknown, fallback = 0): number => {
    const parsed = Number(value);

    return Number.isInteger(parsed) ? parsed : fallback;
};

export const toNullableInteger = (value: unknown): number | null => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const parsed = Number(value);

    return Number.isInteger(parsed) ? parsed : null;
};
