import { formatDateTime } from "@/utils/datetime";

export interface OrderAddressLike {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export type StatusTone = "neutral" | "good" | "warn" | "info" | "bad" | "role";
export type BadgeTone = "active" | "inactive";

const statusToneClassMap: Record<StatusTone, string> = {
    neutral: "status-chip--neutral",
    good: "status-chip--good",
    warn: "status-chip--warn",
    info: "status-chip--info",
    bad: "status-chip--bad",
    role: "status-chip--role",
};

const orderStatusToneMap: Record<string, StatusTone> = {
    pending: "warn",
    paid: "good",
    processing: "info",
    shipped: "info",
    completed: "good",
    cancelled: "bad",
    refunded: "neutral",
};

const paymentStatusToneMap: Record<string, StatusTone> = {
    pending: "warn",
    authorized: "info",
    captured: "good",
    failed: "bad",
    refunded: "neutral",
};

const shipmentStatusToneMap: Record<string, StatusTone> = {
    pending: "warn",
    packed: "info",
    shipped: "info",
    delivered: "good",
    returned: "bad",
};

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

export const statusToneClass = (tone: StatusTone): string => {
    return statusToneClassMap[tone];
};

export const orderStatusTone = (status: string): StatusTone => {
    return orderStatusToneMap[status] ?? "neutral";
};

export const paymentStatusTone = (status: string): StatusTone => {
    return paymentStatusToneMap[status] ?? "neutral";
};

export const shipmentStatusTone = (status: string): StatusTone => {
    return shipmentStatusToneMap[status] ?? "neutral";
};

export const verificationStatusTone = (isEmailVerified: boolean): StatusTone => {
    return isEmailVerified ? "good" : "warn";
};

export const productStatusBadgeTone = (status: string): BadgeTone => {
    return status === "active" ? "active" : "inactive";
};

export const orderStatusClass = (status: string): string => {
    return statusToneClass(orderStatusTone(status));
};

export const paymentStatusClass = (status: string): string => {
    return statusToneClass(paymentStatusTone(status));
};

export const shipmentStatusClass = (status: string): string => {
    return statusToneClass(shipmentStatusTone(status));
};
