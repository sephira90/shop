<template>
    <AppCard>
        <AppStackBetween>
            <h2>Campaigns</h2>
            <AppMutedText>
                Page {{ meta.current_page }} of {{ meta.last_page }}. Total: {{ meta.total }}.
            </AppMutedText>
        </AppStackBetween>

        <AppActionsRow with-top-spacing>
            <AppSearchInput
                v-model="searchQuery"
                placeholder="Search by campaign or code"
                :disabled="isLoading"
            />
            <AppFilterSelect v-model="statusFilter" :disabled="isLoading">
                <option value="all">All campaigns</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </AppFilterSelect>
        </AppActionsRow>

        <AppTableSection with-top-spacing>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Coupons</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody v-if="promotions.length">
                <tr
                    v-for="promotion in promotions"
                    :key="promotion.id"
                    :class="{ 'table-row-active': selectedPromotionId === promotion.id }"
                >
                    <td>
                        <strong>{{ promotion.name }}</strong>
                        <AppMutedText>Code: {{ promotion.code || "-" }}</AppMutedText>
                    </td>
                    <td>{{ promotion.type }}</td>
                    <td>{{ formatPromotionValue(promotion.type, promotion.value) }}</td>
                    <td>
                        <BooleanStatusChip :value="promotion.is_active" />
                    </td>
                    <td>{{ promotion.coupons.length }}</td>
                    <AppTableActionsCell>
                        <AppButton
                            variant="muted"
                            type="button"
                            @click="$emit('select', promotion.id)"
                        >
                            Coupons
                        </AppButton>
                        <AppButton variant="muted" type="button" @click="$emit('edit', promotion)">
                            Edit
                        </AppButton>
                        <AppButton
                            variant="muted"
                            type="button"
                            :disabled="isDeletingPromotionId === promotion.id"
                            @click="$emit('remove', promotion)"
                        >
                            {{ isDeletingPromotionId === promotion.id ? "Deleting..." : "Delete" }}
                        </AppButton>
                    </AppTableActionsCell>
                </tr>
            </tbody>
            <tbody v-else>
                <AppTableEmptyStateRow
                    :colspan="6"
                    :message="isLoading ? 'Loading campaigns...' : 'No campaigns found.'"
                />
            </tbody>
        </AppTableSection>

        <AppPaginationBar
            class="actions--top"
            :page="page"
            :meta="meta"
            :is-loading="isLoading"
            :show-summary="false"
            @load-prev="$emit('loadPrev')"
            @load-next="$emit('loadNext')"
        />
    </AppCard>
</template>

<script setup lang="ts">
import AppActionsRow from "@/components/ui/actions/AppActionsRow.vue";
import AppButton from "@/components/ui/actions/AppButton.vue";
import AppCard from "@/components/ui/layout/AppCard.vue";
import AppFilterSelect from "@/components/ui/forms/AppFilterSelect.vue";
import AppMutedText from "@/components/ui/typography/AppMutedText.vue";
import AppPaginationBar from "@/components/ui/data-display/AppPaginationBar.vue";
import AppSearchInput from "@/components/ui/forms/AppSearchInput.vue";
import AppStackBetween from "@/components/ui/actions/AppStackBetween.vue";
import AppTableSection from "@/components/ui/table/AppTableSection.vue";
import AppTableActionsCell from "@/components/ui/table/AppTableActionsCell.vue";
import AppTableEmptyStateRow from "@/components/ui/table/AppTableEmptyStateRow.vue";
import BooleanStatusChip from "@/components/ui/data-display/BooleanStatusChip.vue";
import type { Promotion, PromotionStatusFilter, PromotionType } from "@/types/admin-promotions";
import type { PaginationMeta } from "@/types/pagination";

defineProps<{
    promotions: Promotion[];
    selectedPromotionId: number | null;
    isLoading: boolean;
    page: number;
    meta: PaginationMeta;
    isDeletingPromotionId: number | null;
}>();

defineEmits<{
    (event: "select", promotionId: number): void;
    (event: "edit", promotion: Promotion): void;
    (event: "remove", promotion: Promotion): void;
    (event: "loadPrev"): void;
    (event: "loadNext"): void;
}>();

const searchQuery = defineModel<string>("searchQuery", { required: true });
const statusFilter = defineModel<PromotionStatusFilter>("statusFilter", { required: true });

const formatPromotionValue = (type: PromotionType, value: number): string => {
    if (type === "percent") {
        return `${Number(value).toFixed(2)}%`;
    }

    return Number(value).toFixed(2);
};
</script>
