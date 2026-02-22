<template>
    <section class="grid">
        <div class="card">
            <div class="stack stack--between">
                <h1 class="section-title">Admin products</h1>
                <div class="actions">
                    <button
                        class="btn btn-muted"
                        type="button"
                        :disabled="isRefreshingCatalogCache"
                        @click="refreshCatalogCache"
                    >
                        {{
                            isRefreshingCatalogCache
                                ? "Refreshing cache..."
                                : "Refresh catalog cache"
                        }}
                    </button>
                    <button class="btn btn-muted" type="button" @click="resetForm">
                        New product
                    </button>
                </div>
            </div>

            <p class="muted">Create, update and remove products from one screen.</p>

            <form class="form-grid actions--top" @submit.prevent="submitProduct">
                <div class="grid grid-2">
                    <label class="field">
                        <span class="field__label">Name</span>
                        <input v-model="form.name" placeholder="Product name" required />
                    </label>

                    <label class="field">
                        <span class="field__label">SKU</span>
                        <input v-model="form.sku" placeholder="SKU-0001" required />
                    </label>

                    <label class="field">
                        <span class="field__label">Status</span>
                        <select v-model="form.status" required>
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>

                    <label class="field">
                        <span class="field__label">Category</span>
                        <select v-model="form.category_id">
                            <option value="">No category</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <button
                            class="btn btn-muted"
                            type="button"
                            :disabled="isLoadingCategories"
                            @click="loadCategories"
                        >
                            {{ isLoadingCategories ? "Refreshing..." : "Refresh categories" }}
                        </button>
                    </label>

                    <label class="field">
                        <span class="field__label">Slug (optional)</span>
                        <input v-model="form.slug" placeholder="auto-generated-if-empty" />
                    </label>

                    <label class="field">
                        <span class="field__label">Brand (optional)</span>
                        <input v-model="form.brand" placeholder="Brand" />
                    </label>

                    <label class="field">
                        <span class="field__label">Weight grams (optional)</span>
                        <input v-model="form.weight_grams" type="number" min="1" max="1000000" />
                    </label>

                    <label class="field">
                        <span class="field__label">Publish date (optional)</span>
                        <input v-model="form.published_at" type="datetime-local" />
                    </label>
                </div>

                <label class="field">
                    <span class="field__label">Short description</span>
                    <textarea
                        v-model="form.short_description"
                        rows="3"
                        placeholder="Brief product description"
                    />
                </label>

                <label class="field">
                    <span class="field__label">Description</span>
                    <textarea
                        v-model="form.description"
                        rows="5"
                        placeholder="Detailed product description"
                    />
                </label>

                <div class="variant-section actions--top">
                    <div class="variant-section__header">
                        <h2 class="variant-section__title">Variants and pricing</h2>
                        <button class="btn btn-muted" type="button" @click="addVariant">
                            Add variant
                        </button>
                    </div>
                    <p class="muted variant-section__hint">
                        Each variant controls its own price and inventory values.
                    </p>

                    <div class="variant-list">
                        <div
                            v-for="(variant, index) in form.variants"
                            :key="variant.local_id"
                            class="variant-card"
                        >
                            <div class="variant-card__header">
                                <strong>Variant #{{ index + 1 }}</strong>
                                <button
                                    class="btn btn-muted"
                                    type="button"
                                    :disabled="form.variants.length <= 1"
                                    @click="removeVariant(index)"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid grid-2">
                                <label class="field">
                                    <span class="field__label">Variant SKU</span>
                                    <input
                                        v-model="variant.sku"
                                        placeholder="SKU-0001-BLACK-M"
                                        required
                                    />
                                </label>

                                <label class="field">
                                    <span class="field__label">Variant name</span>
                                    <input
                                        v-model="variant.name"
                                        placeholder="Black / M"
                                        required
                                    />
                                </label>

                                <label class="field">
                                    <span class="field__label">Price</span>
                                    <input
                                        v-model="variant.price"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        required
                                    />
                                </label>

                                <label class="field">
                                    <span class="field__label">Compare at price</span>
                                    <input
                                        v-model="variant.compare_at_price"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                    />
                                </label>

                                <label class="field">
                                    <span class="field__label">Currency</span>
                                    <input
                                        v-model="variant.currency"
                                        maxlength="3"
                                        placeholder="USD"
                                        required
                                    />
                                </label>
                            </div>

                            <label class="checkbox-field">
                                <input v-model="variant.is_active" type="checkbox" />
                                <span>Variant is active</span>
                            </label>

                            <label class="field">
                                <span class="field__label">Attributes (JSON object)</span>
                                <textarea
                                    class="variant-attributes"
                                    v-model="variant.attributes_json"
                                    rows="3"
                                    placeholder='{"size":"M","color":"black"}'
                                />
                            </label>

                            <div class="grid grid-3">
                                <label class="field">
                                    <span class="field__label">Inventory quantity</span>
                                    <input
                                        v-model="variant.inventory_quantity"
                                        type="number"
                                        min="0"
                                        step="1"
                                        required
                                    />
                                </label>

                                <label class="field">
                                    <span class="field__label">Reserved quantity</span>
                                    <input
                                        v-model="variant.inventory_reserved_quantity"
                                        type="number"
                                        min="0"
                                        step="1"
                                        required
                                    />
                                </label>

                                <label class="field">
                                    <span class="field__label">Low stock threshold</span>
                                    <input
                                        v-model="variant.inventory_low_stock_threshold"
                                        type="number"
                                        min="0"
                                        step="1"
                                        required
                                    />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

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
                    <input v-model="form.is_featured" type="checkbox" />
                    <span>Mark as featured</span>
                </label>

                <div class="actions">
                    <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                        {{
                            isSubmitting
                                ? "Saving..."
                                : editingId
                                  ? "Update product"
                                  : "Create product"
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
                <h2>Products list</h2>
                <button
                    class="btn btn-muted"
                    type="button"
                    :disabled="isLoading"
                    @click="loadProducts(page)"
                >
                    Refresh
                </button>
            </div>

            <div class="actions actions--top">
                <input
                    v-model="searchQuery"
                    placeholder="Search products by name, SKU or slug"
                    :disabled="isLoading"
                />
            </div>

            <div class="table-wrap actions--top">
                <table class="table">
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
                    <tbody v-if="filteredProducts.length">
                        <tr v-for="product in filteredProducts" :key="product.id">
                            <td>{{ product.id }}</td>
                            <td>
                                <strong>{{ product.name }}</strong>
                                <p class="muted">/{{ product.slug }}</p>
                            </td>
                            <td>{{ product.sku }}</td>
                            <td>
                                <span :class="['badge', statusBadgeClass(product.status)]">
                                    {{ product.status }}
                                </span>
                            </td>
                            <td>{{ product.category?.name ?? "-" }}</td>
                            <td>{{ formatDateTime(product.published_at) }}</td>
                            <td>
                                <div class="actions">
                                    <button
                                        class="btn btn-muted"
                                        type="button"
                                        @click="startEdit(product)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        class="btn btn-muted"
                                        type="button"
                                        :disabled="isVisibilityUpdatingId === product.id"
                                        @click="toggleCatalogVisibility(product)"
                                    >
                                        {{
                                            isVisibilityUpdatingId === product.id
                                                ? "Applying..."
                                                : isVisibleInCatalog(product)
                                                  ? "Hide from catalog"
                                                  : "Show in catalog"
                                        }}
                                    </button>
                                    <button
                                        class="btn btn-muted"
                                        type="button"
                                        :disabled="
                                            isDeletingId === product.id || !canDeleteProducts
                                        "
                                        @click="removeProduct(product)"
                                    >
                                        {{ isDeletingId === product.id ? "Deleting..." : "Delete" }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <p>
                                        {{
                                            isLoading
                                                ? "Loading products..."
                                                : "No products on this page."
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
                    Page {{ meta.current_page }} of {{ meta.last_page }}. Total products:
                    {{ meta.total }}.
                </p>
                <div class="actions">
                    <button
                        class="btn btn-muted"
                        type="button"
                        :disabled="page <= 1 || isLoading"
                        @click="loadProducts(page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        class="btn btn-muted"
                        type="button"
                        :disabled="page >= meta.last_page || isLoading"
                        @click="loadProducts(page + 1)"
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
import { useAdminProducts } from "@/composables/admin/useAdminProducts";
import { formatDateTime } from "@/utils/datetime";

const uiEffects = createBrowserAdminUiEffects();
const route = useRoute();
const router = useRouter();

const {
    categories,
    page,
    isLoading,
    isLoadingCategories,
    isSubmitting,
    isDeletingId,
    isVisibilityUpdatingId,
    isRefreshingCatalogCache,
    editingId,
    searchQuery,
    canDeleteProducts,
    meta,
    notice,
    form,
    filteredProducts,
    statusBadgeClass,
    resetForm,
    addVariant,
    removeVariant,
    loadCategories,
    loadProducts,
    submitProduct,
    startEdit,
    removeProduct,
    refreshCatalogCache,
    toggleCatalogVisibility,
    isVisibleInCatalog,
} = useAdminProducts({
    uiEffects,
    routeSync: {
        route,
        router,
    },
});

onMounted(async () => {
    await Promise.all([loadCategories(), loadProducts()]);
});
</script>
