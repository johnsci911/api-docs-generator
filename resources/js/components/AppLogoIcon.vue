<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Pixelated Terminal Icon (>_) -->
        <!-- > -->
        <path fill="currentColor" d="M4 4h2v2H4V4Zm2 2h2v2H6V6Zm2 2h2v2H8V8Zm-2 2h2v2H6v-2Zm-2 2h2v2H4v-2Z" />
        <!-- _ -->
        <path fill="currentColor" d="M12 14h8v2h-8v-2Z" />
    </svg>
</template>
