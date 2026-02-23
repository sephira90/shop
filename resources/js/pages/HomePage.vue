<template>
    <section class="grid">
        <HomeHeroSection
            :can-access-admin="canAccessAdmin"
            :can-access-account="canAccessAccount"
        />
        <HomeKpiGrid />
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";

import HomeHeroSection from "@/components/home/HomeHeroSection.vue";
import HomeKpiGrid from "@/components/home/HomeKpiGrid.vue";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const canAccessAdmin = computed<boolean>(() => authStore.canAccessAdmin);
const canAccessAccount = computed<boolean>(() => authStore.canAccessAccount);
</script>
