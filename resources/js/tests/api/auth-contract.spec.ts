import { describe, expect, it } from "vitest";

import {
    assertAuthTokenResultWireDto,
    assertAuthUserWireDto,
} from "@/contracts/api/v1/assertions/auth";

describe("auth dto contract assertions", () => {
    it("parses auth user payload", () => {
        const user = assertAuthUserWireDto({
            id: 12,
            first_name: "Jane",
            last_name: "Doe",
            name: "Jane Doe",
            email: "jane@example.com",
            roles: ["customer"],
            phone: null,
            is_email_verified: true,
        });

        expect(user.id).toBe(12);
        expect(user.roles).toEqual(["customer"]);
        expect(user.phone).toBeNull();
    });

    it("parses auth token payload", () => {
        const payload = assertAuthTokenResultWireDto({
            token: "token-1",
            user: {
                id: 12,
                first_name: "Jane",
                last_name: "Doe",
                name: "Jane Doe",
                email: "jane@example.com",
                roles: ["customer"],
                phone: "+15550001111",
                is_email_verified: false,
            },
        });

        expect(payload.token).toBe("token-1");
        expect(payload.user.email).toBe("jane@example.com");
    });

    it("rejects invalid auth payload", () => {
        expect(() =>
            assertAuthTokenResultWireDto({
                token: "token-1",
                user: {
                    id: "12",
                },
            }),
        ).toThrowError(/Auth payload field/);
    });
});
