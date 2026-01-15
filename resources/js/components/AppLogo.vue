<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center gap-2 px-1 py-1">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground shrink-0">
            <AppLogoIcon class="h-5 w-5" />
        </div>
        <div class="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
            <span class="truncate font-semibold">API Document Generator</span>
        </div>
    </div>
</template>
