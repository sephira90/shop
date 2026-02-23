<template>
    <nav class="account-tabs" aria-label="Account navigation">
        <RouterLink to="/account/profile" class="account-tab">Profile</RouterLink>
        <RouterLink to="/account/orders" class="account-tab">Orders</RouterLink>
    </nav>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";
</script>
