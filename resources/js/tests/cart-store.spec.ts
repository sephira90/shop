import { describe, expect, it } from 'vitest';

import { useCartStore } from '@/stores/cart';

import { createPinia, setActivePinia } from 'pinia';

describe('cart store', () => {
    it('starts with empty cart', () => {
        setActivePinia(createPinia());
        const cartStore = useCartStore();

        expect(cartStore.cart).toBeNull();
        expect(cartStore.itemCount).toBe(0);
    });
});
