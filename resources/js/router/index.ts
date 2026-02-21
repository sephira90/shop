import { createRouter, createWebHistory, type RouteRecordRaw } from "vue-router";
import { useAuthStore } from "@/stores/auth";

type RoleName = "customer" | "manager" | "admin";

interface AuthRouteMeta {
    requiresAuth?: boolean;
    onlyGuests?: boolean;
    roles?: RoleName[];
}

const HomePage = () => import("@/pages/HomePage.vue");
const CatalogPage = () => import("@/pages/CatalogPage.vue");
const ProductPage = () => import("@/pages/ProductPage.vue");
const CartPage = () => import("@/pages/CartPage.vue");
const AuthPage = () => import("@/pages/AuthPage.vue");
const CheckoutPage = () => import("@/pages/CheckoutPage.vue");
const AccountProfilePage = () => import("@/pages/AccountProfilePage.vue");
const AccountOrdersPage = () => import("@/pages/AccountOrdersPage.vue");
const AdminDashboardPage = () => import("@/pages/admin/AdminDashboardPage.vue");
const AdminCategoriesPage = () => import("@/pages/admin/AdminCategoriesPage.vue");
const AdminProductsPage = () => import("@/pages/admin/AdminProductsPage.vue");
const AdminOrdersPage = () => import("@/pages/admin/AdminOrdersPage.vue");
const AdminPromotionsPage = () => import("@/pages/admin/AdminPromotionsPage.vue");

export const appRoutes: RouteRecordRaw[] = [
    { path: "/", component: HomePage },
    { path: "/catalog", component: CatalogPage },
    { path: "/product/:slug", component: ProductPage, props: true },
    { path: "/cart", component: CartPage },
    { path: "/auth", component: AuthPage, meta: { onlyGuests: true } },
    { path: "/checkout", component: CheckoutPage },
    { path: "/account", redirect: "/account/profile" },
    {
        path: "/account/profile",
        component: AccountProfilePage,
        meta: { requiresAuth: true, roles: ["customer", "manager", "admin"] },
    },
    {
        path: "/account/orders",
        component: AccountOrdersPage,
        meta: { requiresAuth: true, roles: ["customer", "manager", "admin"] },
    },
    {
        path: "/admin",
        component: AdminDashboardPage,
        meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
        path: "/admin/categories",
        component: AdminCategoriesPage,
        meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
        path: "/admin/products",
        component: AdminProductsPage,
        meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
        path: "/admin/orders",
        component: AdminOrdersPage,
        meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
    {
        path: "/admin/promotions",
        component: AdminPromotionsPage,
        meta: { requiresAuth: true, roles: ["manager", "admin"] },
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes: appRoutes,
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();
    const meta = to.meta as AuthRouteMeta;

    if (authStore.token && !authStore.user) {
        try {
            await authStore.ensureUserLoaded();
        } catch {
            await authStore.logout();
        }
    }

    if (meta.onlyGuests && authStore.isAuthenticated) {
        return authStore.canAccessAdmin ? "/admin" : "/";
    }

    if (meta.requiresAuth && !authStore.isAuthenticated) {
        return {
            path: "/auth",
            query: { redirect: to.fullPath },
        };
    }

    if (meta.roles && meta.roles.length > 0) {
        const roles = authStore.user?.roles ?? [];
        const allowed = meta.roles.some((role): boolean => roles.includes(role));

        if (!allowed) {
            return "/";
        }
    }

    return true;
});
