import type { CatalogProduct, CatalogProductVariant } from '@/types/catalog';

import { asArray, asRecord, toBoolean, toInteger, toNullableString, toNumber, toString } from '@/mappers/common';

const mapCatalogVariantFromApi = (value: unknown): CatalogProductVariant => {
    const record = asRecord(value);

    return {
        id: toInteger(record.id),
        sku: toString(record.sku),
        name: toString(record.name),
        price: toNumber(record.price),
        currency: toString(record.currency, 'USD'),
        is_active: toBoolean(record.is_active, true),
    };
};

export const mapCatalogProductFromApi = (value: unknown): CatalogProduct => {
    const record = asRecord(value);

    return {
        id: toInteger(record.id),
        name: toString(record.name),
        slug: toString(record.slug),
        short_description: toNullableString(record.short_description),
        description: toNullableString(record.description),
        variants: asArray(record.variants).map((variant) => mapCatalogVariantFromApi(variant)),
    };
};

export const mapCatalogProductListFromApi = (value: unknown): CatalogProduct[] => {
    return asArray(value).map((item) => mapCatalogProductFromApi(item));
};
