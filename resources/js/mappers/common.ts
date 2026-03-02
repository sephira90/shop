export interface OptionalAddressFields {
    line1?: string | null;
    city?: string | null;
    country?: string | null;
    postcode?: string | null;
}

export interface NormalizedAddressFields {
    line1?: string;
    city?: string;
    country?: string;
    postcode?: string;
}

export const mapOptionalAddressFields = (
    value: OptionalAddressFields | null,
): NormalizedAddressFields | null => {
    if (value === null) {
        return null;
    }

    const line1 = (value.line1 ?? "").trim();
    const city = (value.city ?? "").trim();
    const country = (value.country ?? "").trim();
    const postcode = (value.postcode ?? "").trim();

    if (line1 === "" && city === "" && country === "" && postcode === "") {
        return null;
    }

    const address: NormalizedAddressFields = {};

    if (line1 !== "") {
        address.line1 = line1;
    }
    if (city !== "") {
        address.city = city;
    }
    if (country !== "") {
        address.country = country;
    }
    if (postcode !== "") {
        address.postcode = postcode;
    }

    return address;
};
