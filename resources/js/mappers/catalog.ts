import type {
    CatalogProductVariantWireDto,
    CatalogProductWireDto,
} from "@/contracts/api/v1/catalog";
import type { CatalogProduct, CatalogProductVariant } from "@/types/catalog";

const mapCatalogVariantFromWire = (value: CatalogProductVariantWireDto): CatalogProductVariant => {
    return {
        id: value.id,
        sku: value.sku,
        name: value.name,
        price: value.price,
        currency: value.currency,
        is_active: value.is_active,
    };
};

export const mapCatalogProductFromApi = (value: CatalogProductWireDto): CatalogProduct => {
    return {
        id: value.id,
        name: value.name,
        slug: value.slug,
        short_description: value.short_description,
        description: value.description,
        variants: value.variants.map((variant) => mapCatalogVariantFromWire(variant)),
    };
};

export const mapCatalogProductListFromApi = (value: CatalogProductWireDto[]): CatalogProduct[] => {
    return value.map((item) => mapCatalogProductFromApi(item));
};
