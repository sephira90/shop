interface DateTimeFormatConfig {
    locale?: string;
    fallback?: string;
    dateStyle?: Intl.DateTimeFormatOptions['dateStyle'];
    timeStyle?: Intl.DateTimeFormatOptions['timeStyle'];
}

export const formatDateTime = (
    value: string | null,
    {
        locale = 'en-US',
        fallback = '-',
        dateStyle = 'medium',
        timeStyle = 'short',
    }: DateTimeFormatConfig = {},
): string => {
    if (!value) {
        return fallback;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return fallback;
    }

    return new Intl.DateTimeFormat(locale, {
        dateStyle,
        timeStyle,
    }).format(date);
};

export const normalizeDatetimeForInput = (value: string | null): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const timezoneOffset = date.getTimezoneOffset() * 60000;
    const localDate = new Date(date.getTime() - timezoneOffset);

    return localDate.toISOString().slice(0, 16);
};
