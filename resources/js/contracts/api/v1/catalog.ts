export interface CatalogProductVariantWireDto {
    id: number;
    sku: string;
    name: string;
    price: number;
    currency: string;
    is_active: boolean;
}

export interface CatalogProductWireDto {
    id: number;
    name: string;
    slug: string;
    short_description: string | null;
    description: string | null;
    variants: CatalogProductVariantWireDto[];
}
