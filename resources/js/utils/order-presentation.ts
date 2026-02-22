import { formatDateTime } from "@/utils/datetime";

export interface OrderAddressLike {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export const formatMoney = (value: number, currency = "USD", locale = "en-US"): string => {
    return new Intl.NumberFormat(locale, {
        style: "currency",
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
};

export const formatOrderAddress = (address: OrderAddressLike | null | undefined): string => {
    if (!address) {
        return "Not provided";
    }

    return (
        [address.line1, address.city, address.country, address.postcode]
            .filter(Boolean)
            .join(", ") || "Not provided"
    );
};

export const formatOrderDate = (value: string | null, fallback = "Unknown date"): string => {
    return formatDateTime(value, { fallback });
};

export const orderStatusClass = (status: string): string => {
    return (
        {
            pending: "status-chip--warn",
            paid: "status-chip--good",
            processing: "status-chip--info",
            shipped: "status-chip--info",
            completed: "status-chip--good",
            cancelled: "status-chip--bad",
            refunded: "status-chip--neutral",
        }[status] ?? "status-chip--neutral"
    );
};

export const paymentStatusClass = (status: string): string => {
    return (
        {
            pending: "status-chip--warn",
            authorized: "status-chip--info",
            captured: "status-chip--good",
            failed: "status-chip--bad",
            refunded: "status-chip--neutral",
        }[status] ?? "status-chip--neutral"
    );
};

export const shipmentStatusClass = (status: string): string => {
    return (
        {
            pending: "status-chip--warn",
            packed: "status-chip--info",
            shipped: "status-chip--info",
            delivered: "status-chip--good",
            returned: "status-chip--bad",
        }[status] ?? "status-chip--neutral"
    );
};
