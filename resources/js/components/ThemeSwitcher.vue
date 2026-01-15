<script setup lang="ts">
import { Coffee, Monitor, Moon, StickyNote, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, palette, updateAppearance, updatePalette } = useAppearance();

const standardModes = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const customPalettes = [
    { value: 'catppuccin', Icon: Coffee, label: 'Catppuccin' },
    { value: 'paper', Icon: StickyNote, label: 'Paper' },
] as const;

const currentPalette = computed(() => {
    if (palette.value === 'catppuccin') return customPalettes[0];
    if (palette.value === 'paper') return customPalettes[1];
    return { value: 'standard', Icon: Moon, label: 'Standard' }; // Fallback icon
});

const currentMode = computed(() => standardModes.find(m => m.value === appearance.value) || standardModes[2]);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-9 w-9 text-muted-foreground hover:text-foreground">
                <component :is="palette === 'standard' ? currentMode.Icon : currentPalette.Icon" class="h-4 w-4" />
                <span class="sr-only">Toggle theme</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-52 shadow-xl border-border/40">
            <!-- Appearance Modes -->
            <div class="flex items-center gap-1 p-1.5">
                <Button
                    v-for="mode in standardModes"
                    :key="mode.value"
                    variant="ghost"
                    size="icon"
                    @click="updateAppearance(mode.value)"
                    :class="[
                        'h-9 flex-1 group transition-all duration-200',
                        appearance === mode.value ? 'bg-muted text-foreground ring-1 ring-border/20 shadow-sm' : 'text-muted-foreground hover:bg-muted/50'
                    ]"
                    :title="mode.label"
                >
                    <component :is="mode.Icon" class="h-4 w-4" />
                    <span class="sr-only">{{ mode.label }}</span>
                </Button>
            </div>

            <DropdownMenuSeparator />

            <!-- Palette Selection -->
            <div class="px-3 py-2 docs-label italic">Themes</div>
            
            <DropdownMenuItem @click="updatePalette('standard')" class="gap-3 py-2 transition-colors focus:bg-primary/10 focus:text-primary">
                <div class="flex h-5 w-5 items-center justify-center rounded-sm bg-neutral-200/50 dark:bg-neutral-800/50 border border-border/20">
                    <div class="h-1.5 w-1.5 rounded-full bg-neutral-400 dark:bg-neutral-500" />
                </div>
                <span class="font-medium text-sm">Standard</span>
                <div v-if="palette === 'standard'" class="ml-auto h-1.5 w-1.5 rounded-full bg-primary shadow-sm shadow-primary/40" />
            </DropdownMenuItem>

            <template v-for="p in customPalettes" :key="p.value">
                <DropdownMenuItem @click="updatePalette(p.value)" class="gap-3 py-2 transition-colors focus:bg-primary/10 focus:text-primary">
                    <component :is="p.Icon" class="h-4 w-4 opacity-70" />
                    <span class="font-medium text-sm">{{ p.label }}</span>
                    <div v-if="palette === p.value" class="ml-auto h-1.5 w-1.5 rounded-full bg-primary shadow-sm shadow-primary/40" />
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
