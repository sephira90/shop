<template>
    <AppTableSection with-top-spacing>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Parent</th>
                <th>Status</th>
                <th>Sort</th>
                <th>Products</th>
                <th>Children</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody v-if="categories.length">
            <tr v-for="category in categories" :key="category.id">
                <td>{{ category.id }}</td>
                <td>
                    <strong>{{ category.name }}</strong>
                </td>
                <td>/{{ category.slug }}</td>
                <td>{{ category.parent?.name ?? "-" }}</td>
                <td>
                    <BooleanStatusChip :value="category.is_active" />
                </td>
                <td>{{ category.sort_order }}</td>
                <td>{{ category.products_count }}</td>
                <td>{{ category.children_count }}</td>
                <AppTableActionsCell>
                    <AppButton variant="muted" type="button" @click="$emit('edit', category)">
                        Edit
                    </AppButton>
                    <AppButton
                        variant="muted"
                        type="button"
                        :disabled="isDeletingId === category.id || !canDeleteCategories"
                        @click="$emit('remove', category)"
                    >
                        {{ isDeletingId === category.id ? "Deleting..." : "Delete" }}
                    </AppButton>
                </AppTableActionsCell>
            </tr>
        </tbody>
        <tbody v-else>
            <AppTableEmptyStateRow
                :colspan="9"
                :message="isLoading ? 'Loading categories...' : 'No categories on this page.'"
            />
        </tbody>
    </AppTableSection>
</template>

<script setup lang="ts">
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppTableSection from "@/components/ui/table/AppTableSection.vue";
import AppTableActionsCell from "@/components/ui/table/AppTableActionsCell.vue";
import AppTableEmptyStateRow from "@/components/ui/table/AppTableEmptyStateRow.vue";
import BooleanStatusChip from "@/components/ui/data-display/BooleanStatusChip.vue";
import type { AdminCategory } from "@/types/admin-categories";

defineProps<{
    categories: AdminCategory[];
    isLoading: boolean;
    isDeletingId: number | null;
    canDeleteCategories: boolean;
}>();

defineEmits<{
    (event: "edit", category: AdminCategory): void;
    (event: "remove", category: AdminCategory): void;
}>();
</script>
