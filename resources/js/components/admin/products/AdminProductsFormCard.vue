<template>
    <AppCard>
        <AppStackBetween>
            <AppSectionTitle tag="h1">Admin products</AppSectionTitle>
            <AppActionsRow>
                <AppButton
                    variant="muted"
                    type="button"
                    :disabled="isRefreshingCatalogCache"
                    @click="$emit('refreshCatalogCache')"
                >
                    {{ isRefreshingCatalogCache ? "Refreshing cache..." : "Refresh catalog cache" }}
                </AppButton>
                <AppButton variant="muted" type="button" @click="$emit('resetForm')">
                    New product
                </AppButton>
            </AppActionsRow>
        </AppStackBetween>

        <AppMutedText>Create, update and remove products from one screen.</AppMutedText>

        <AppFormLayout with-top-spacing @submit="$emit('submitProduct')">
            <AppGridTwoColumns>
                <AppFormField label="Name">
                    <AppTextInput v-model="form.name" placeholder="Product name" required />
                </AppFormField>

                <AppFormField label="SKU">
                    <AppTextInput v-model="form.sku" placeholder="SKU-0001" required />
                </AppFormField>

                <AppFormField label="Status">
                    <AppEnumSelect v-model="form.status" :options="statusOptions" required />
                </AppFormField>

                <AppFormField label="Category">
                    <AppSelectInput v-model="form.category_id">
                        <option value="">No category</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </AppSelectInput>
                    <AppButton
                        variant="muted"
                        type="button"
                        :disabled="isLoadingCategories"
                        @click="$emit('loadCategories')"
                    >
                        {{ isLoadingCategories ? "Refreshing..." : "Refresh categories" }}
                    </AppButton>
                </AppFormField>

                <AppFormField label="Slug (optional)">
                    <AppTextInput v-model="form.slug" placeholder="auto-generated-if-empty" />
                </AppFormField>

                <AppFormField label="Brand (optional)">
                    <AppTextInput v-model="form.brand" placeholder="Brand" />
                </AppFormField>

                <AppFormField label="Weight grams (optional)">
                    <AppNumberInput v-model="form.weight_grams" min="1" max="1000000" />
                </AppFormField>

                <AppFormField label="Publish date (optional)">
                    <AppDateTimeInput v-model="form.published_at" />
                </AppFormField>
            </AppGridTwoColumns>

            <AppFormField label="Short description">
                <AppTextareaInput
                    v-model="form.short_description"
                    rows="3"
                    placeholder="Brief product description"
                />
            </AppFormField>

            <AppFormField label="Description">
                <AppTextareaInput
                    v-model="form.description"
                    rows="5"
                    placeholder="Detailed product description"
                />
            </AppFormField>

            <AdminProductVariantsSection
                v-model:variants="form.variants"
                @add-variant="$emit('addVariant')"
                @remove-variant="$emit('removeVariant', $event)"
            />

            <AppGridTwoColumns>
                <AppFormField label="Meta title">
                    <AppTextInput v-model="form.meta_title" placeholder="Meta title" />
                </AppFormField>
                <AppFormField label="Meta description">
                    <AppTextInput v-model="form.meta_description" placeholder="Meta description" />
                </AppFormField>
            </AppGridTwoColumns>

            <AppCheckboxField>
                <AppCheckboxInput v-model="form.is_featured" />
                <span>Mark as featured</span>
            </AppCheckboxField>

            <AppSubmitResetActions>
                <template #primary>
                    <AppButton variant="primary" type="submit" :disabled="isSubmitting">
                        {{
                            isSubmitting
                                ? "Saving..."
                                : editingId
                                  ? "Update product"
                                  : "Create product"
                        }}
                    </AppButton>
                </template>
                <template #secondary>
                    <AppButton
                        v-if="editingId"
                        variant="muted"
                        type="button"
                        @click="$emit('resetForm')"
                    >
                        Cancel editing
                    </AppButton>
                </template>
            </AppSubmitResetActions>
        </AppFormLayout>

        <AppNotice
            v-if="noticeMessage"
            :message="noticeMessage"
            :variant="noticeType === 'success' ? 'success' : 'error'"
        />
    </AppCard>
</template>

<script setup lang="ts">
import AdminProductVariantsSection from "@/components/admin/products/AdminProductVariantsSection.vue";
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppCheckboxField from "@/components/ui/forms/AppCheckboxField.vue";
import AppCheckboxInput from "@/components/ui/forms/AppCheckboxInput.vue";
import AppDateTimeInput from "@/components/ui/forms/AppDateTimeInput.vue";
import AppEnumSelect from "@/components/ui/forms/AppEnumSelect.vue";
import AppFormField from "@/components/ui/forms/AppFormField.vue";
import AppFormLayout from "@/components/ui/forms/AppFormLayout.vue";
import AppGridTwoColumns from "@/components/ui/layout/AppGridTwoColumns.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppNotice from "@/components/ui/feedback/AppNotice.vue";
import AppNumberInput from "@/components/ui/forms/AppNumberInput.vue";
import AppSelectInput from "@/components/ui/forms/AppSelectInput.vue";
import AppSectionTitle from "@/components/ui/typography/AppSectionTitle.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";
import AppSubmitResetActions from "@/components/ui/actions/AppSubmitResetActions.vue";
import AppTextareaInput from "@/components/ui/forms/AppTextareaInput.vue";
import AppTextInput from "@/components/ui/forms/AppTextInput.vue";
import type { AdminNotice } from "@/composables/useAdminNotice";
import type { AdminProductCategory } from "@/types/admin-products";
import type { ProductFormState } from "@/validators/admin/products";

defineProps<{
    categories: AdminProductCategory[];
    isLoadingCategories: boolean;
    isSubmitting: boolean;
    isRefreshingCatalogCache: boolean;
    editingId: number | null;
    noticeType: AdminNotice["type"];
    noticeMessage: string;
}>();

defineEmits<{
    (event: "refreshCatalogCache"): void;
    (event: "resetForm"): void;
    (event: "submitProduct"): void;
    (event: "loadCategories"): void;
    (event: "addVariant"): void;
    (event: "removeVariant", index: number): void;
}>();

const form = defineModel<ProductFormState>("form", { required: true });

const statusOptions = [
    { value: "draft", label: "Draft" },
    { value: "active", label: "Active" },
    { value: "archived", label: "Archived" },
];
</script>
