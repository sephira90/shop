import { describe, expect, it } from "vitest";

import { ApiContractError, extractData, normalizeListResponse } from "@/api/response";

describe("api response contract parser", () => {
    it("normalizes list payload from strict envelope", () => {
        const response = normalizeListResponse<{ id: number }>({
            data: [{ id: 1 }, { id: 2 }],
            meta: {
                current_page: 2,
                last_page: 4,
                per_page: 10,
                total: 40,
            },
        });

        expect(response).toEqual({
            data: [{ id: 1 }, { id: 2 }],
            meta: {
                current_page: 2,
                last_page: 4,
                per_page: 10,
                total: 40,
            },
        });
    });

    it("rejects legacy nested list envelope", () => {
        expect(() =>
            normalizeListResponse({
                data: {
                    data: [{ id: 1 }],
                    meta: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 1,
                    },
                },
            }),
        ).toThrowError(ApiContractError);
    });

    it("rejects list payload without meta object", () => {
        expect(() =>
            normalizeListResponse({
                data: [{ id: 1 }],
                meta: "invalid-meta",
            }),
        ).toThrowError(ApiContractError);
    });

    it("extracts data from strict envelope", () => {
        expect(extractData<{ id: number }>({ data: { id: 7 } })).toEqual({ id: 7 });
    });

    it("rejects payload without data field", () => {
        expect(() => extractData({ meta: {} })).toThrowError(ApiContractError);
    });
});
