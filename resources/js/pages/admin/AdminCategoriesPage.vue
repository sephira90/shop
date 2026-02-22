<template>
    <section class="grid">
        <div class="card">
            <div class="stack stack--between">
                <h1 class="section-title">Admin categories</h1>
                <button class="btn btn-muted" type="button" @click="resetForm">New category</button>
            </div>

            <p class="muted">Create and maintain category tree for catalog navigation.</p>

            <form class="form-grid actions--top" @submit.prevent="submitCategory">
                <div class="grid grid-2">
                    <label class="field">
                        <span class="field__label">Name</span>
                        <input v-model="form.name" placeholder="Category name" required />
                    </label>

                    <label class="field">
                        <span class="field__label">Slug (optional)</span>
                        <input v-model="form.slug" placeholder="auto-generated-if-empty" />
                    </label>

                    <label class="field">
                        <span class="field__label">Parent category</span>
                        <select v-model="form.parent_id">
                            <option value="">No parent</option>
                            <option
                                v-for="category in parentOptions"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </label>

                    <label class="field">
                        <span class="field__label">Sort order</span>
                        <input v-model="form.sort_order" type="number" min="0" max="1000000" />
                    </label>
                </div>

                <label class="field">
                    <span class="field__label">Description</span>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        placeholder="Category description"
                    />
                </label>

                <div class="grid grid-2">
                    <label class="field">
                        <span class="field__label">Meta title</span>
                        <input v-model="form.meta_title" placeholder="Meta title" />
                    </label>
                    <label class="field">
                        <span class="field__label">Meta description</span>
                        <input v-model="form.meta_description" placeholder="Meta description" />
                    </label>
                </div>

                <label class="checkbox-field">
                    <input v-model="form.is_active" type="checkbox" />
                    <span>Category is active</span>
                </label>

                <div class="actions">
                    <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                        {{
                            isSubmitting
                                ? "Saving..."
                                : editingId
                                  ? "Update category"
                                  : "Create category"
                        }}
                    </button>
                    <button v-if="editingId" class="btn btn-muted" type="button" @click="resetForm">
                        Cancel editing
                    </button>
                </div>
            </form>

            <p
                v-if="notice.message"
                :class="['notice', notice.type === 'success' ? 'notice--success' : 'notice--error']"
            >
                {{ notice.message }}
            </p>
        </div>

        <div class="card">
            <div class="stack stack--between">
                <h2>Categories list</h2>
                <button
                    class="btn btn-muted"
                    type="button"
                    :disabled="isLoading"
                    @click="loadCategories(page)"
                >
                    Refresh
                </button>
            </div>

            <div class="actions actions--top">
                <input
                    v-model="searchQuery"
                    placeholder="Filter by name, slug or parent"
                    :disabled="isLoading"
                />
                <select v-model="statusFilter" :disabled="isLoading">
                    <option value="all">All statuses</option>
                    <option value="active">Only active</option>
                    <option value="inactive">Only inactive</option>
                </select>
            </div>

            <div class="table-wrap actions--top">
                <table class="table">
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
                    <tbody v-if="filteredCategories.length">
                        <tr v-for="category in filteredCategories" :key="category.id">
                            <td>{{ category.id }}</td>
                            <td>
                                <strong>{{ category.name }}</strong>
                            </td>
                            <td>/{{ category.slug }}</td>
                            <td>{{ category.parent?.name ?? "-" }}</td>
                            <td>
                                <span
                                    :class="[
                                        'badge',
                                        category.is_active ? 'badge--active' : 'badge--inactive',
                                    ]"
                                >
                                    {{ category.is_active ? "active" : "inactive" }}
                                </span>
                            </td>
                            <td>{{ category.sort_order }}</td>
                            <td>{{ category.products_count }}</td>
                            <td>{{ category.children_count }}</td>
                            <td>
                                <div class="actions">
                                    <button
                                        class="btn btn-muted"
                                        type="button"
                                        @click="startEdit(category)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        class="btn btn-muted"
                                        type="button"
                                        :disabled="
                                            isDeletingId === category.id || !canDeleteCategories
                                        "
                                        @click="removeCategory(category)"
                                    >
                                        {{
                                            isDeletingId === category.id ? "Deleting..." : "Delete"
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <p>
                                        {{
                                            isLoading
                                                ? "Loading categories..."
                                                : "No categories on this page."
                                        }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="stack stack--between actions--top">
                <p class="muted">
                    Page {{ meta.current_page }} of {{ meta.last_page }}. Total categories:
                    {{ meta.total }}.
                </p>
                <div class="actions">
                    <button
                        class="btn btn-muted"
                        type="button"
                        :disabled="page <= 1 || isLoading"
                        @click="loadCategories(page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        class="btn btn-muted"
                        type="button"
                        :disabled="page >= meta.last_page || isLoading"
                        @click="loadCategories(page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import { createBrowserAdminUiEffects } from "@/composables/admin/adminUiEffects";
import { useAdminCategories } from "@/composables/admin/useAdminCategories";

const uiEffects = createBrowserAdminUiEffects();
const route = useRoute();
const router = useRouter();

const {
    page,
    isLoading,
    isSubmitting,
    isDeletingId,
    editingId,
    searchQuery,
    statusFilter,
    canDeleteCategories,
    meta,
    notice,
    form,
    filteredCategories,
    parentOptions,
    resetForm,
    loadCategories,
    submitCategory,
    startEdit,
    removeCategory,
} = useAdminCategories({
    uiEffects,
    routeSync: {
        route,
        router,
    },
});

onMounted(async () => {
    await loadCategories();
});
</script>
