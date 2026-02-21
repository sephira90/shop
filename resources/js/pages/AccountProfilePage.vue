<template>
    <section class="grid">
        <div class="account-hero">
            <div class="account-hero__main">
                <span class="pill">Account center</span>
                <h1>{{ profileName }}</h1>
                <p>Manage profile details, monitor order activity and quickly access account actions.</p>
            </div>

            <div class="account-hero__user">
                <div class="account-avatar" aria-hidden="true">{{ profileInitial }}</div>
                <div class="account-hero__meta">
                    <p class="account-hero__email">{{ profileEmail }}</p>
                    <div class="actions">
                        <span class="status-chip" :class="verificationClass">{{ verificationLabel }}</span>
                        <span v-for="role in roleLabels" :key="role" class="status-chip status-chip--role">{{ role }}</span>
                    </div>
                </div>
            </div>
        </div>

        <nav class="account-tabs" aria-label="Account navigation">
            <RouterLink to="/account/profile" class="account-tab">Profile</RouterLink>
            <RouterLink to="/account/orders" class="account-tab">Orders</RouterLink>
        </nav>

        <div class="grid grid-4">
            <article class="card metric-card">
                <span class="metric-card__label">Total orders</span>
                <strong class="metric-card__value">{{ metrics.totalOrders }}</strong>
            </article>
            <article class="card metric-card">
                <span class="metric-card__label">Paid orders</span>
                <strong class="metric-card__value">{{ metrics.paidOrders }}</strong>
            </article>
            <article class="card metric-card">
                <span class="metric-card__label">In delivery</span>
                <strong class="metric-card__value">{{ metrics.inDelivery }}</strong>
            </article>
            <article class="card metric-card">
                <span class="metric-card__label">Spent (loaded)</span>
                <strong class="metric-card__value">{{ formatPrice(metrics.loadedTotalSpent) }}</strong>
            </article>
        </div>

        <div class="grid grid-2">
            <article class="card">
                <h2 class="section-title">Edit profile</h2>
                <p class="muted">Update your personal details used for orders and account communication.</p>
                <form class="form-grid actions--top" @submit.prevent="submitProfileUpdate">
                    <div class="grid grid-2">
                        <label class="field">
                            <span class="field__label">First name</span>
                            <input v-model="form.first_name" maxlength="80" required />
                        </label>
                        <label class="field">
                            <span class="field__label">Last name</span>
                            <input v-model="form.last_name" maxlength="80" required />
                        </label>
                    </div>
                    <label class="field">
                        <span class="field__label">Email</span>
                        <input :value="profileEmail" type="email" disabled />
                    </label>
                    <label class="field">
                        <span class="field__label">Phone</span>
                        <input v-model="form.phone" maxlength="32" placeholder="+15551234567" />
                    </label>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit" :disabled="isSavingProfile">
                            {{ isSavingProfile ? 'Saving...' : 'Save profile' }}
                        </button>
                        <button class="btn btn-muted" type="button" :disabled="isSavingProfile" @click="resetProfileForm">
                            Reset
                        </button>
                    </div>
                </form>
                <p
                    v-if="profileNotice.message"
                    :class="['notice', profileNotice.type === 'success' ? 'notice--success' : 'notice--error']"
                >
                    {{ profileNotice.message }}
                </p>
            </article>

            <article class="card">
                <h2 class="section-title">Profile summary</h2>
                <dl class="profile-list">
                    <div class="profile-list__row">
                        <dt>Full name</dt>
                        <dd>{{ profileName }}</dd>
                    </div>
                    <div class="profile-list__row">
                        <dt>Email</dt>
                        <dd>{{ profileEmail }}</dd>
                    </div>
                    <div class="profile-list__row">
                        <dt>Phone</dt>
                        <dd>{{ profilePhone }}</dd>
                    </div>
                    <div class="profile-list__row">
                        <dt>Roles</dt>
                        <dd>{{ roleLabels.join(', ') }}</dd>
                    </div>
                </dl>

                <h2 class="section-title">Quick actions</h2>
                <div class="actions actions--top">
                    <RouterLink class="btn btn-primary" to="/account/orders">Open orders</RouterLink>
                    <RouterLink class="btn btn-muted" to="/catalog">Go to catalog</RouterLink>
                    <RouterLink v-if="authStore.canAccessAdmin" class="btn btn-muted" to="/admin">
                        Open admin
                    </RouterLink>
                </div>
                <p class="muted actions--top">
                    Keep your account data up to date and review order statuses in real time.
                </p>
            </article>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';

import { useAccountProfile } from '@/composables/useAccountProfile';

const {
    authStore,
    isSavingProfile,
    metrics,
    form,
    profileNotice,
    profileName,
    profileEmail,
    profilePhone,
    profileInitial,
    verificationLabel,
    verificationClass,
    roleLabels,
    resetProfileForm,
    formatPrice,
    submitProfileUpdate,
    loadProfile,
} = useAccountProfile();

onMounted(async () => {
    await loadProfile();
});
</script>
