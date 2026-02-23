<template>
    <AppCard>
        <AppStackBetween>
            <AppSectionTitle tag="h1">Admin categories</AppSectionTitle>
            <AppButton variant="muted" type="button" @click="$emit('resetForm')">
                New category
            </AppButton>
        </AppStackBetween>

        <AppMutedText>Create and maintain category tree for catalog navigation.</AppMutedText>

        <AppFormLayout with-top-spacing @submit="$emit('submitCategory')">
            <AppGridTwoColumns>
                <AppFormField label="Name">
                    <AppTextInput v-model="form.name" placeholder="Category name" required />
                </AppFormField>

                <AppFormField label="Slug (optional)">
                    <AppTextInput v-model="form.slug" placeholder="auto-generated-if-empty" />
                </AppFormField>

                <AppFormField label="Parent category">
                    <AppSelectInput v-model="form.parent_id">
                        <option value="">No parent</option>
                        <option
                            v-for="category in parentOptions"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </AppSelectInput>
                </AppFormField>

                <AppFormField label="Sort order">
                    <AppNumberInput v-model="form.sort_order" min="0" max="1000000" />
                </AppFormField>
            </AppGridTwoColumns>

            <AppFormField label="Description">
                <AppTextareaInput
                    v-model="form.description"
                    rows="4"
                    placeholder="Category description"
                />
            </AppFormField>

            <AppGridTwoColumns>
                <AppFormField label="Meta title">
                    <AppTextInput v-model="form.meta_title" placeholder="Meta title" />
                </AppFormField>
                <AppFormField label="Meta description">
                    <AppTextInput v-model="form.meta_description" placeholder="Meta description" />
                </AppFormField>
            </AppGridTwoColumns>

            <AppCheckboxField>
                <AppCheckboxInput v-model="form.is_active" />
                <span>Category is active</span>
            </AppCheckboxField>

            <AppSubmitResetActions>
                <template #primary>
                    <AppButton variant="primary" type="submit" :disabled="isSubmitting">
                        {{
                            isSubmitting
                                ? "Saving..."
                                : editingId
                                  ? "Update category"
                                  : "Create category"
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
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppCheckboxField from "@/components/ui/forms/AppCheckboxField.vue";
import AppCheckboxInput from "@/components/ui/forms/AppCheckboxInput.vue";
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
import type { AdminCategory } from "@/types/admin-categories";
import type { CategoryFormState } from "@/validators/admin/categories";

defineProps<{
    parentOptions: AdminCategory[];
    isSubmitting: boolean;
    editingId: number | null;
    noticeType: AdminNotice["type"];
    noticeMessage: string;
}>();

defineEmits<{
    (event: "resetForm"): void;
    (event: "submitCategory"): void;
}>();

const form = defineModel<CategoryFormState>("form", { required: true });
</script>
