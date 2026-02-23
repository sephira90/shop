<template>
    <AppCard>
        <AppStackBetween>
            <h2>Products list</h2>
            <AppButton
                variant="muted"
                type="button"
                :disabled="isLoading"
                @click="$emit('refresh')"
            >
                Refresh
            </AppButton>
        </AppStackBetween>

        <AppActionsRow with-top-spacing>
            <AppSearchInput
                v-model="searchQuery"
                placeholder="Search products by name, SKU or slug"
                :disabled="isLoading"
            />
        </AppActionsRow>

        <AppTableSection with-top-spacing>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Status</th>
                    <th>Category</th>
                    <th>Published</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody v-if="products.length">
                <tr v-for="product in products" :key="product.id">
                    <td>{{ product.id }}</td>
                    <td>
                        <strong>{{ product.name }}</strong>
                        <AppMutedText>/{{ product.slug }}</AppMutedText>
                    </td>
                    <td>{{ product.sku }}</td>
                    <td>
                        <AppBadge :label="product.status" :tone="statusBadgeTone(product.status)" />
                    </td>
                    <td>{{ product.category?.name ?? "-" }}</td>
                    <td>{{ formatDateTime(product.published_at) }}</td>
                    <AppTableActionsCell>
                        <AppButton variant="muted" type="button" @click="$emit('edit', product)">
                            Edit
                        </AppButton>
                        <AppButton
                            variant="muted"
                            type="button"
                            :disabled="isVisibilityUpdatingId === product.id"
                            @click="$emit('toggleVisibility', product)"
                        >
                            {{
                                isVisibilityUpdatingId === product.id
                                    ? "Applying..."
                                    : isVisibleInCatalog(product)
                                      ? "Hide from catalog"
                                      : "Show in catalog"
                            }}
                        </AppButton>
                        <AppButton
                            variant="muted"
                            type="button"
                            :disabled="isDeletingId === product.id || !canDeleteProducts"
                            @click="$emit('remove', product)"
                        >
                            {{ isDeletingId === product.id ? "Deleting..." : "Delete" }}
                        </AppButton>
                    </AppTableActionsCell>
                </tr>
            </tbody>
            <tbody v-else>
                <AppTableEmptyStateRow
                    :colspan="7"
                    :message="isLoading ? 'Loading products...' : 'No products on this page.'"
                />
            </tbody>
        </AppTableSection>

        <AppPaginationBar
            class="actions--top"
            :page="page"
            :meta="meta"
            :is-loading="isLoading"
            total-label="Total products"
            @load-prev="$emit('loadPrev')"
            @load-next="$emit('loadNext')"
        />
    </AppCard>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/AppActionsRow.vue";
import AppBadge from "@/components/ui/AppBadge.vue";
import AppButton from "@/components/ui/AppButton.vue";
import AppCard from "@/components/ui/AppCard.vue";
import AppMutedText from "@/components/ui/AppMutedText.vue";
import AppPaginationBar from "@/components/ui/AppPaginationBar.vue";
import AppSearchInput from "@/components/ui/AppSearchInput.vue";
import AppStackBetween from "@/components/ui/AppStackBetween.vue";
import AppTableSection from "@/components/ui/AppTableSection.vue";
import AppTableActionsCell from "@/components/ui/AppTableActionsCell.vue";
import AppTableEmptyStateRow from "@/components/ui/AppTableEmptyStateRow.vue";
import { formatDateTime } from "@/utils/datetime";
import type { PaginationMeta } from "@/types/pagination";
import type { AdminProduct, ProductStatus } from "@/types/admin-products";
import type { BadgeTone } from "@/utils/order-presentation";

defineProps<{
    products: AdminProduct[];
    isLoading: boolean;
    page: number;
    meta: PaginationMeta;
    isDeletingId: number | null;
    isVisibilityUpdatingId: number | null;
    canDeleteProducts: boolean;
    statusBadgeTone: (status: ProductStatus) => BadgeTone;
    isVisibleInCatalog: (product: AdminProduct) => boolean;
}>();

defineEmits<{
    (event: "refresh"): void;
    (event: "edit", product: AdminProduct): void;
    (event: "toggleVisibility", product: AdminProduct): void;
    (event: "remove", product: AdminProduct): void;
    (event: "loadPrev"): void;
    (event: "loadNext"): void;
}>();

const searchQuery = defineModel<string>("searchQuery", { required: true });
</script>
