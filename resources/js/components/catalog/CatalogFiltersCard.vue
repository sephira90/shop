<template>
    <AppCard>
        <AppSectionTitle tag="h1">Catalog</AppSectionTitle>
        <AppMutedText>Search and sort products available in the storefront.</AppMutedText>
        <AppActionsRow with-top-spacing>
            <AppSearchInput
                v-model="query"
                placeholder="Search products"
                :disabled="isLoading"
                @enter="$emit('apply')"
            />
            <AppFilterSelect v-model="sort">
                <option value="newest">Newest</option>
                <option value="price_asc">Price ascending</option>
                <option value="price_desc">Price descending</option>
                <option value="name_asc">Name ascending</option>
            </AppFilterSelect>
            <AppButton
                variant="primary"
                type="button"
                :disabled="isLoading"
                @click="$emit('apply')"
            >
                {{ isLoading ? "Loading..." : "Apply filters" }}
            </AppButton>
        </AppActionsRow>
        <AppNotice v-if="loadError" class="actions--top" :message="loadError" />
    </AppCard>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppFilterSelect from "@/components/ui/forms/AppFilterSelect.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import AppSearchInput from "@/components/ui/forms/AppSearchInput.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import type { CatalogSort } from "@/types/catalog";

defineProps<{
    isLoading: boolean;
    loadError: string;
}>();

defineEmits<{
    (event: "apply"): void;
}>();

const query = defineModel<string>("query", { required: true });
const sort = defineModel<CatalogSort>("sort", { required: true });
</script>
