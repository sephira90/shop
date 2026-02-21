<template>
    <div class="card">
        <div class="stack stack--between">
            <h2>Campaigns</h2>
            <p class="muted">
                Page {{ meta.current_page }} of {{ meta.last_page }}. Total: {{ meta.total }}.
            </p>
        </div>

        <div class="actions actions--top">
            <input
                v-model="searchQuery"
                placeholder="Search by campaign or code"
                :disabled="isLoading"
            />
            <select v-model="statusFilter" :disabled="isLoading">
                <option value="all">All campaigns</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="table-wrap actions--top">
            <table class="table">
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
                            <p class="muted">Code: {{ promotion.code || "-" }}</p>
                        </td>
                        <td>{{ promotion.type }}</td>
                        <td>{{ formatPromotionValue(promotion.type, promotion.value) }}</td>
                        <td>
                            <span
                                class="status-chip"
                                :class="
                                    promotion.is_active
                                        ? 'status-chip--good'
                                        : 'status-chip--neutral'
                                "
                            >
                                {{ promotion.is_active ? "active" : "inactive" }}
                            </span>
                        </td>
                        <td>{{ promotion.coupons.length }}</td>
                        <td>
                            <div class="actions">
                                <button
                                    class="btn btn-muted"
                                    type="button"
                                    @click="$emit('select', promotion.id)"
                                >
                                    Coupons
                                </button>
                                <button
                                    class="btn btn-muted"
                                    type="button"
                                    @click="$emit('edit', promotion)"
                                >
                                    Edit
                                </button>
                                <button
                                    class="btn btn-muted"
                                    type="button"
                                    :disabled="isDeletingPromotionId === promotion.id"
                                    @click="$emit('remove', promotion)"
                                >
                                    {{
                                        isDeletingPromotionId === promotion.id
                                            ? "Deleting..."
                                            : "Delete"
                                    }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <p>
                                    {{ isLoading ? "Loading campaigns..." : "No campaigns found." }}
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="actions actions--top">
            <button
                class="btn btn-muted"
                type="button"
                :disabled="page <= 1 || isLoading"
                @click="$emit('loadPrev')"
            >
                Previous
            </button>
            <button
                class="btn btn-muted"
                type="button"
                :disabled="page >= meta.last_page || isLoading"
                @click="$emit('loadNext')"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
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
