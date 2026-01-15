<script setup lang="ts">
import { Coffee, LayoutGrid, Monitor, Moon, Sun, Waves } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, palette, updateAppearance, updatePalette } = useAppearance();

const modes = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const palettes = [
    { value: 'standard', Icon: LayoutGrid, label: 'Standard' },
    { value: 'catppuccin', Icon: Coffee, label: 'Catppuccin' },
    { value: 'kanagawa', Icon: Waves, label: 'Kanagawa' },
] as const;
</script>

<template>
    <div class="space-y-8 max-w-2xl">
        <!-- Appearance Mode -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <label class="docs-label">Appearance Mode</label>
                <span class="text-[10px] font-medium text-muted-foreground/40 italic">Global light/dark preference</span>
            </div>
            <div class="flex flex-wrap gap-1.5 rounded-xl bg-muted/30 p-1.5 border border-border/40">
                <button
                    v-for="mode in modes"
                    :key="mode.value"
                    @click="updateAppearance(mode.value)"
                    :class="[
                        'flex flex-1 items-center justify-center rounded-lg px-4 py-2.5 transition-all duration-200',
                        appearance === mode.value
                            ? 'bg-background shadow-md text-foreground border border-border/50 ring-1 ring-border/10'
                            : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                    ]"
                >
                    <component :is="mode.Icon" class="h-4 w-4" />
                    <span class="ml-2.5 text-sm font-semibold tracking-tight">{{ mode.label }}</span>
                </button>
            </div>
        </div>

        <!-- Color Palette -->
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <label class="docs-label">Color Palette</label>
                <span class="text-[10px] font-medium text-muted-foreground/40 italic">Theme-specific color scheme</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 rounded-xl bg-muted/30 p-1.5 border border-border/40">
                <button
                    v-for="p in palettes"
                    :key="p.value"
                    @click="updatePalette(p.value)"
                    :class="[
                        'flex items-center justify-center rounded-lg px-4 py-2.5 transition-all duration-200',
                        palette === p.value
                            ? 'bg-background shadow-md text-foreground border border-border/50 ring-1 ring-border/10'
                            : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                    ]"
                >
                    <component :is="p.Icon" class="h-4 w-4 opacity-70" />
                    <span class="text-sm font-semibold tracking-tight ml-2.5">{{ p.label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
