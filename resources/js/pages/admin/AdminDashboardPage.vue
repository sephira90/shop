<template>
    <section class="grid">
        <AdminDashboardHero />
        <AdminDashboardNavGrid :items="navItems" />
    </section>
</template>

<script setup lang="ts">
import AdminDashboardHero from "@/components/admin/dashboard/AdminDashboardHero.vue";
import AdminDashboardNavGrid from "@/components/admin/dashboard/AdminDashboardNavGrid.vue";

const navItems = [
    {
        to: "/admin/categories",
        title: "Categories",
        description: "Organize catalog hierarchy and ordering.",
        iconPath:
            "M5 4h14a2 2 0 0 1 2 2v3H3V6a2 2 0 0 1 2-2m-2 7h8v9H5a2 2 0 0 1-2-2zm10 0h8v7a2 2 0 0 1-2 2h-6zm-8 2v2h4v-2zm10 0v2h4v-2z",
    },
    {
        to: "/admin/products",
        title: "Products",
        description: "Review and maintain product list.",
        iconPath:
            "M4 7.5a2.5 2.5 0 0 1 2.5-2.5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5zm2.5-.5a.5.5 0 0 0-.5.5v2h12v-2a.5.5 0 0 0-.5-.5zm11.5 4.5H6v5a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5z",
    },
    {
        to: "/admin/orders",
        title: "Orders",
        description: "Track order statuses and totals.",
        iconPath:
            "M6 4h9l3 3v13H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2m8 1.5V8h2.5zM8 11h8v1.8H8zm0 3.6h8v1.8H8z",
    },
    {
        to: "/admin/promotions",
        title: "Promotions",
        description: "Configure discount campaigns.",
        iconPath:
            "M10.6 4a1.8 1.8 0 0 0-1.27.53L4.53 9.33A1.8 1.8 0 0 0 4 10.6v2.8c0 .48.19.94.53 1.27l4.8 4.8c.34.34.8.53 1.27.53h2.8c.48 0 .94-.19 1.27-.53l4.8-4.8c.34-.33.53-.8.53-1.27v-2.8c0-.48-.19-.94-.53-1.27l-4.8-4.8A1.8 1.8 0 0 0 13.4 4zM9 10h6v1.8H9zm0 3.2h4v1.8H9z",
    },
];
</script>
