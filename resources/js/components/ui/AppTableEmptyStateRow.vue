<template>
    <tr>
        <td :colspan="colspan">
            <AppEmptyState :message="message" />
        </td>
    </tr>
</template>

<script setup lang="ts">
import AppEmptyState from "@/components/ui/AppEmptyState.vue";

defineProps<{
    colspan: number;
    message: string;
}>();
</script>
