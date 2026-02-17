import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

import HomePage from '@/pages/HomePage.vue';
import CatalogPage from '@/pages/CatalogPage.vue';
import ProductPage from '@/pages/ProductPage.vue';
import CartPage from '@/pages/CartPage.vue';
import CheckoutPage from '@/pages/CheckoutPage.vue';
import AccountOrdersPage from '@/pages/AccountOrdersPage.vue';
import AdminDashboardPage from '@/pages/admin/AdminDashboardPage.vue';
import AdminProductsPage from '@/pages/admin/AdminProductsPage.vue';
import AdminOrdersPage from '@/pages/admin/AdminOrdersPage.vue';
import AdminPromotionsPage from '@/pages/admin/AdminPromotionsPage.vue';

const routes: RouteRecordRaw[] = [
    { path: '/', component: HomePage },
    { path: '/catalog', component: CatalogPage },
    { path: '/product/:slug', component: ProductPage, props: true },
    { path: '/cart', component: CartPage },
    { path: '/checkout', component: CheckoutPage },
    { path: '/account/orders', component: AccountOrdersPage },
    { path: '/admin', component: AdminDashboardPage },
    { path: '/admin/products', component: AdminProductsPage },
    { path: '/admin/orders', component: AdminOrdersPage },
    { path: '/admin/promotions', component: AdminPromotionsPage },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});
