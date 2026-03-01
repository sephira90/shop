export const formatPrice = (
    value: number | null | undefined,
    currency = "USD",
    locale = "en-US",
): string => {
    const normalizedCurrency = currency.trim() === "" ? "USD" : currency.trim().toUpperCase();

    return new Intl.NumberFormat(locale, {
        style: "currency",
        currency: normalizedCurrency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
};
