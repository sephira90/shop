import { computed, reactive, ref, watch } from "vue";

import { placeCheckoutOrder } from "@/api/checkout";
import {
    resolveCheckoutGuestTokenStorageAdapter,
    type CheckoutGuestTokenStorageAdapter,
} from "@/composables/checkout/checkoutPageEffects";
import { useApiError } from "@/composables/useApiError";
import { useAuthStore } from "@/stores/auth";
import { useCartStore } from "@/stores/cart";
import {
    buildCheckoutIdempotencyKey,
    buildCheckoutPayload,
    createCheckoutFormState,
} from "@/validators/checkout";

interface UseCheckoutPageViewModelOptions {
    guestTokenStorage?: Partial<CheckoutGuestTokenStorageAdapter>;
}

export type CheckoutResultState = "idle" | "success" | "error";

export const useCheckoutPageViewModel = (options?: UseCheckoutPageViewModelOptions) => {
    const authStore = useAuthStore();
    const cartStore = useCartStore();
    const { parseApiError } = useApiError();
    const guestTokenStorage = resolveCheckoutGuestTokenStorageAdapter(options?.guestTokenStorage);

    const resultMessage = ref("");
    const resultState = ref<CheckoutResultState>("idle");
    const isSubmitting = ref(false);
    const form = reactive(createCheckoutFormState());
    const isResultSuccess = computed<boolean>(() => resultState.value === "success");

    const initialize = async (): Promise<void> => {
        await Promise.all([cartStore.fetchCart(), authStore.ensureUserLoaded()]);
    };

    const resolveGuestToken = (): string => {
        return (guestTokenStorage.getGuestToken() ?? cartStore.cart?.guest_token ?? "").trim();
    };

    const submitCheckout = async (): Promise<void> => {
        isSubmitting.value = true;
        resultState.value = "idle";

        try {
            const guestToken = resolveGuestToken();
            const isAuthenticated = authStore.isAuthenticated;

            if (!isAuthenticated && guestToken === "") {
                resultMessage.value = "Guest token is missing. Open cart and try checkout again.";
                resultState.value = "error";
                return;
            }

            const payload = buildCheckoutPayload(form, guestToken === "" ? null : guestToken);
            const order = await placeCheckoutOrder(payload, buildCheckoutIdempotencyKey());
            resultMessage.value = order
                ? `Order created: ${order.order_number}`
                : "Order created successfully.";
            resultState.value = "success";
            await cartStore.fetchCart();
        } catch (error: unknown) {
            resultMessage.value = parseApiError(
                error,
                "Checkout failed. Please verify account and cart.",
            );
            resultState.value = "error";
        } finally {
            isSubmitting.value = false;
        }
    };

    watch(
        () => cartStore.cart?.guest_token,
        (guestToken) => {
            if (!guestToken || guestToken.trim() === "") {
                return;
            }

            const existing = guestTokenStorage.getGuestToken();

            if (!existing || existing.trim() === "") {
                guestTokenStorage.setGuestToken(guestToken);
            }
        },
    );

    watch(
        () => authStore.user?.email,
        (email) => {
            if (form.email.trim() === "" && email) {
                form.email = email;
            }
        },
        { immediate: true },
    );

    return {
        form,
        isSubmitting,
        resultMessage,
        resultState,
        isResultSuccess,
        initialize,
        submitCheckout,
    };
};
