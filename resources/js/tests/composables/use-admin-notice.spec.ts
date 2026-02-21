import { describe, expect, it } from "vitest";
import { effectScope } from "vue";

import { useAdminNotice } from "@/composables/useAdminNotice";

describe("useAdminNotice", () => {
    it("manages success/error state and clear behavior", () => {
        const scope = effectScope();
        const api = scope.run(() => useAdminNotice());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        expect(api.notice.type).toBe("success");
        expect(api.notice.message).toBe("");

        api.showSuccess("Saved");
        expect(api.notice.type).toBe("success");
        expect(api.notice.message).toBe("Saved");

        api.showError("Failed");
        expect(api.notice.type).toBe("error");
        expect(api.notice.message).toBe("Failed");

        api.clearNotice();
        expect(api.notice.type).toBe("error");
        expect(api.notice.message).toBe("");

        scope.stop();
    });

    it("builds error message from api error helper", () => {
        const scope = effectScope();
        const api = scope.run(() => useAdminNotice());

        expect(api).not.toBeNull();
        if (!api) {
            scope.stop();
            return;
        }

        api.showApiError(new Error("Boom"), "Fallback");
        expect(api.notice.type).toBe("error");
        expect(api.notice.message).toBe("Boom");

        api.showApiError({}, "Fallback");
        expect(api.notice.type).toBe("error");
        expect(api.notice.message).toBe("Fallback");

        scope.stop();
    });
});
