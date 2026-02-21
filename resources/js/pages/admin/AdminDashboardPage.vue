<template>
    <section class="grid">
        <div class="hero">
            <span class="pill">Admin panel</span>
            <h1>Admin dashboard</h1>
            <p>Manage products, orders and promotions from one place.</p>
        </div>

        <div class="grid grid-3">
            <RouterLink class="card card-link" to="/admin/categories">
                <span class="feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M5 4h14a2 2 0 0 1 2 2v3H3V6a2 2 0 0 1 2-2m-2 7h8v9H5a2 2 0 0 1-2-2zm10 0h8v7a2 2 0 0 1-2 2h-6zm-8 2v2h4v-2zm10 0v2h4v-2z" />
                    </svg>
                </span>
                <h2>Categories</h2>
                <p class="muted">Organize catalog hierarchy and ordering.</p>
            </RouterLink>
            <RouterLink class="card card-link" to="/admin/products">
                <span class="feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M4 7.5a2.5 2.5 0 0 1 2.5-2.5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5zm2.5-.5a.5.5 0 0 0-.5.5v2h12v-2a.5.5 0 0 0-.5-.5zm11.5 4.5H6v5a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5z" />
                    </svg>
                </span>
                <h2>Products</h2>
                <p class="muted">Review and maintain product list.</p>
            </RouterLink>
            <RouterLink class="card card-link" to="/admin/orders">
                <span class="feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M6 4h9l3 3v13H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2m8 1.5V8h2.5zM8 11h8v1.8H8zm0 3.6h8v1.8H8z" />
                    </svg>
                </span>
                <h2>Orders</h2>
                <p class="muted">Track order statuses and totals.</p>
            </RouterLink>
            <RouterLink class="card card-link" to="/admin/promotions">
                <span class="feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M10.6 4a1.8 1.8 0 0 0-1.27.53L4.53 9.33A1.8 1.8 0 0 0 4 10.6v2.8c0 .48.19.94.53 1.27l4.8 4.8c.34.34.8.53 1.27.53h2.8c.48 0 .94-.19 1.27-.53l4.8-4.8c.34-.33.53-.8.53-1.27v-2.8c0-.48-.19-.94-.53-1.27l-4.8-4.8A1.8 1.8 0 0 0 13.4 4zM9 10h6v1.8H9zm0 3.2h4v1.8H9z" />
                    </svg>
                </span>
                <h2>Promotions</h2>
                <p class="muted">Configure discount campaigns.</p>
            </RouterLink>
        </div>
    </section>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>
