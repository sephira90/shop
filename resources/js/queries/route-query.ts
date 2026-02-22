export const toSingleQueryValue = (value: unknown): string => {
    if (Array.isArray(value)) {
        const first = value.find((item) => typeof item === "string");

        return typeof first === "string" ? first : "";
    }

    return typeof value === "string" ? value : "";
};

export const normalizePageFromQuery = (value: unknown): number => {
    const raw = toSingleQueryValue(value).trim();
    const parsed = Number(raw);

    if (!Number.isInteger(parsed) || parsed < 1) {
        return 1;
    }

    return parsed;
};

export const normalizeEnumQuery = <TAllowed extends string>(
    value: unknown,
    allowed: readonly TAllowed[],
    fallback: TAllowed,
): TAllowed => {
    const normalized = toSingleQueryValue(value).trim().toLowerCase();

    return allowed.includes(normalized as TAllowed) ? (normalized as TAllowed) : fallback;
};
