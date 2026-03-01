import { beforeEach, describe, expect, it, vi } from "vitest";
import { effectScope, nextTick } from "vue";
import { createPinia, setActivePinia } from "pinia";

import { placeCheckoutOrder } from "@/api/checkout";
import { useCheckoutPageViewModel } from "@/composables/checkout/useCheckoutPageViewModel";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";

vi.mock("@/api/checkout", () => ({
    placeCheckoutOrder: vi.fn(),
}));

const placeCheckoutOrderMock = placeCheckoutOrder as unknown as ReturnType<typeof vi.fn>;

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

describe("useCheckoutPageViewModel", () => {
    it("initializes cart and auth context", async () => {
        const authStore = useAuthStore();
        const cartStore = useCartStore();
        authStore.ensureUserLoaded = vi.fn(async () => {}) as typeof authStore.ensureUserLoaded;
        cartStore.fetchCart = vi.fn(async () => {}) as typeof cartStore.fetchCart;

        const scope = effectScope();
        const vm = scope.run(() => useCheckoutPageViewModel());

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        await vm.initialize();

        expect(authStore.ensureUserLoaded).toHaveBeenCalledTimes(1);
        expect(cartStore.fetchCart).toHaveBeenCalledTimes(1);

        scope.stop();
    });

    it("submits checkout with guest token from injected storage", async () => {
        const authStore = useAuthStore();
        const cartStore = useCartStore();
        authStore.token = "";
        cartStore.fetchCart = vi.fn(async () => {}) as typeof cartStore.fetchCart;
        placeCheckoutOrderMock.mockResolvedValue({
            id: "ord-11",
            order_number: "ORD-11",
            payment: {
                payment_id: 91,
                transaction_id: "txn-91",
                status: "pending",
                payload: {},
            },
        });

        const scope = effectScope();
        const vm = scope.run(() =>
            useCheckoutPageViewModel({
                guestTokenStorage: {
                    getGuestToken: () => "guest-token-1",
                    setGuestToken: () => {},
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.form.email = "guest@example.com";
        vm.form.billing_address.line1 = "Main 1";
        vm.form.shipping_address.line1 = "Main 2";

        await vm.submitCheckout();

        expect(placeCheckoutOrderMock).toHaveBeenCalledTimes(1);
        expect(placeCheckoutOrderMock.mock.calls[0][0]).toMatchObject({
            guest_token: "guest-token-1",
            email: "guest@example.com",
        });
        expect(vm.resultMessage.value).toBe("Order created: ORD-11");
        expect(cartStore.fetchCart).toHaveBeenCalledTimes(1);

        scope.stop();
    });

    it("prevents guest checkout without token", async () => {
        const authStore = useAuthStore();
        authStore.token = "";
        placeCheckoutOrderMock.mockResolvedValue(null);

        const scope = effectScope();
        const vm = scope.run(() =>
            useCheckoutPageViewModel({
                guestTokenStorage: {
                    getGuestToken: () => null,
                    setGuestToken: () => {},
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.form.email = "guest@example.com";
        await vm.submitCheckout();

        expect(placeCheckoutOrderMock).not.toHaveBeenCalled();
        expect(vm.resultMessage.value).toBe(
            "Guest token is missing. Open cart and try checkout again.",
        );

        scope.stop();
    });

    it("syncs guest token from cart to injected storage and fills email from profile", async () => {
        const authStore = useAuthStore();
        const cartStore = useCartStore();
        let storedGuestToken = "";
        authStore.user = {
            id: 1,
            name: "Jane Doe",
            email: "jane@example.com",
            roles: ["customer"],
        };

        const scope = effectScope();
        const vm = scope.run(() =>
            useCheckoutPageViewModel({
                guestTokenStorage: {
                    getGuestToken: () => (storedGuestToken === "" ? null : storedGuestToken),
                    setGuestToken: (token) => {
                        storedGuestToken = token;
                    },
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        expect(vm.form.email).toBe("jane@example.com");

        cartStore.cart = {
            id: "cart-1",
            guest_token: "guest-token-2",
            currency: "USD",
            status: "active",
            items: [],
            summary: {
                subtotal: 0,
                total: 0,
                shipping_total: 0,
                discount_total: 0,
            },
        };
        await nextTick();

        expect(storedGuestToken).toBe("guest-token-2");

        scope.stop();
    });

    it("surfaces checkout api errors and clears submitting state", async () => {
        const authStore = useAuthStore();
        authStore.token = "";
        placeCheckoutOrderMock.mockRejectedValue(new Error("Gateway is unavailable."));

        const scope = effectScope();
        const vm = scope.run(() =>
            useCheckoutPageViewModel({
                guestTokenStorage: {
                    getGuestToken: () => "guest-token-9",
                    setGuestToken: () => {},
                },
            }),
        );

        expect(vm).not.toBeNull();
        if (!vm) {
            scope.stop();
            return;
        }

        vm.form.email = "guest@example.com";
        vm.form.billing_address.line1 = "Main 1";
        vm.form.shipping_address.line1 = "Main 2";

        await vm.submitCheckout();

        expect(vm.resultMessage.value).toBe("Gateway is unavailable.");
        expect(vm.isResultSuccess.value).toBe(false);
        expect(vm.isSubmitting.value).toBe(false);

        scope.stop();
    });
});
