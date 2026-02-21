import type {
    AdminProduct,
    ProductMutationPayload,
    ProductStatus,
    ProductVariantForm,
} from "@/types/admin-products";

export interface ProductFormState {
    sku: string;
    name: string;
    slug: string;
    short_description: string;
    description: string;
    status: ProductStatus;
    is_featured: boolean;
    category_id: string;
    brand: string;
    weight_grams: string;
    meta_title: string;
    meta_description: string;
    published_at: string;
    variants: ProductVariantForm[];
}

const parseVariantAttributes = (value: unknown, index: number): Record<string, unknown> => {
    if (value && typeof value === "object" && !Array.isArray(value)) {
        return value as Record<string, unknown>;
    }

    const trimmed = String(value ?? "").trim();

    if (trimmed === "") {
        return {};
    }

    let parsed: unknown;

    try {
        parsed = JSON.parse(trimmed);
    } catch {
        throw new Error(`Variant #${index + 1}: attributes must be valid JSON.`);
    }

    if (!parsed || typeof parsed !== "object" || Array.isArray(parsed)) {
        throw new Error(`Variant #${index + 1}: attributes must be a JSON object.`);
    }

    return parsed as Record<string, unknown>;
};

const parseInventoryNumber = (value: unknown, fallback: number): number => {
    const trimmed = String(value ?? "").trim();

    if (trimmed === "") {
        return fallback;
    }

    const numericValue = Number(trimmed);

    return Number.isInteger(numericValue) ? numericValue : Number.NaN;
};

const buildVariantsPayload = (variants: ProductVariantForm[]): Array<Record<string, unknown>> => {
    if (variants.length === 0) {
        throw new Error("At least one variant is required.");
    }

    const seenSkus = new Set<string>();

    return variants.map((variant, index) => {
        const sku = String(variant.sku ?? "").trim();
        const name = String(variant.name ?? "").trim();
        const currency = String(variant.currency ?? "")
            .trim()
            .toUpperCase();
        const price = Number(variant.price ?? "");
        const compareAtPriceRaw = String(variant.compare_at_price ?? "").trim();
        const compareAtPrice = compareAtPriceRaw === "" ? null : Number(compareAtPriceRaw);
        const quantity = parseInventoryNumber(variant.inventory_quantity, 0);
        const reservedQuantity = parseInventoryNumber(variant.inventory_reserved_quantity, 0);
        const lowStockThreshold = parseInventoryNumber(variant.inventory_low_stock_threshold, 3);

        if (sku === "") {
            throw new Error(`Variant #${index + 1}: SKU is required.`);
        }

        if (seenSkus.has(sku.toLowerCase())) {
            throw new Error(`Variant #${index + 1}: duplicate SKU in form.`);
        }

        seenSkus.add(sku.toLowerCase());

        if (name === "") {
            throw new Error(`Variant #${index + 1}: name is required.`);
        }

        if (!Number.isFinite(price) || price <= 0) {
            throw new Error(`Variant #${index + 1}: price must be greater than 0.`);
        }

        if (compareAtPrice !== null && (!Number.isFinite(compareAtPrice) || compareAtPrice <= 0)) {
            throw new Error(`Variant #${index + 1}: compare-at price must be greater than 0.`);
        }

        if (currency.length !== 3) {
            throw new Error(`Variant #${index + 1}: currency must be a 3-letter code.`);
        }

        if (!Number.isInteger(quantity) || quantity < 0) {
            throw new Error(
                `Variant #${index + 1}: inventory quantity must be a non-negative integer.`,
            );
        }

        if (!Number.isInteger(reservedQuantity) || reservedQuantity < 0) {
            throw new Error(
                `Variant #${index + 1}: reserved quantity must be a non-negative integer.`,
            );
        }

        if (!Number.isInteger(lowStockThreshold) || lowStockThreshold < 0) {
            throw new Error(
                `Variant #${index + 1}: low stock threshold must be a non-negative integer.`,
            );
        }

        const safeReservedQuantity = Math.min(reservedQuantity, quantity);
        const payload: Record<string, unknown> = {
            sku,
            name,
            attributes: parseVariantAttributes(variant.attributes_json, index),
            price: Number(price.toFixed(2)),
            compare_at_price: compareAtPrice === null ? null : Number(compareAtPrice.toFixed(2)),
            currency,
            is_active: variant.is_active,
            inventory: {
                quantity,
                reserved_quantity: safeReservedQuantity,
                low_stock_threshold: lowStockThreshold,
            },
        };

        if (variant.id !== null) {
            payload.id = variant.id;
        }

        return payload;
    });
};

export const createProductFormState = (
    initialVariants: ProductVariantForm[],
): ProductFormState => ({
    sku: "",
    name: "",
    slug: "",
    short_description: "",
    description: "",
    status: "draft",
    is_featured: false,
    category_id: "",
    brand: "",
    weight_grams: "",
    meta_title: "",
    meta_description: "",
    published_at: "",
    variants: initialVariants,
});

export const buildProductMutationPayload = (form: ProductFormState): ProductMutationPayload => {
    return {
        sku: form.sku.trim(),
        name: form.name.trim(),
        slug: form.slug.trim() || null,
        short_description: form.short_description.trim() || null,
        description: form.description.trim() || null,
        status: form.status,
        is_featured: form.is_featured,
        category_id: form.category_id !== "" ? Number(form.category_id) : null,
        brand: form.brand.trim() || null,
        weight_grams: form.weight_grams !== "" ? Number(form.weight_grams) : null,
        meta_title: form.meta_title.trim() || null,
        meta_description: form.meta_description.trim() || null,
        published_at: form.published_at !== "" ? new Date(form.published_at).toISOString() : null,
        variants: buildVariantsPayload(form.variants),
    };
};

export const buildProductMutationPayloadFromProduct = (
    product: AdminProduct,
): ProductMutationPayload => {
    return {
        sku: product.sku,
        name: product.name,
        slug: product.slug || null,
        short_description: product.short_description || null,
        description: product.description || null,
        status: product.status,
        is_featured: product.is_featured,
        category_id: product.category?.id ?? null,
        brand: product.brand ?? null,
        weight_grams: product.weight_grams ?? null,
        meta_title: product.meta.title ?? null,
        meta_description: product.meta.description ?? null,
        published_at: product.published_at,
    };
};
