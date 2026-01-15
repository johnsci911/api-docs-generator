<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Filter, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { show } from '@/actions/App/Http/Controllers/DocumentationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface ApiEndpoint {
    id: number;
    uri: string;
    methods: string[];
    controller: string;
    action: string;
    description: string;
    group: string;
    is_deprecated: boolean;
}

interface Props {
    endpoints: ApiEndpoint[];
    groups: string[];
    methods: string[];
    stats: {
        total: number;
        get: number;
        post: number;
        put: number;
        patch: number;
        delete: number;
    };
    filters: {
        search: string;
        method: string;
        group: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'API Endpoints', href: dashboard().url },
];

const search = ref(props.filters.search);
const selectedMethod = ref(props.filters.method);
const selectedGroup = ref(props.filters.group);

const methodColors: Record<string, string> = {
    GET: 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30',
    POST: 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/30',
    PUT: 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30',
    PATCH: 'bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-500/20 dark:text-orange-400 dark:border-orange-500/30',
    DELETE: 'bg-red-100 text-red-700 border-red-300 dark:bg-red-500/20 dark:text-red-400 dark:border-red-500/30',
};

const applyFilters = () => {
    router.get(dashboard().url, {
        search: search.value || undefined,
        method: selectedMethod.value || undefined,
        group: selectedGroup.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedMethod.value = '';
    selectedGroup.value = '';
    router.get(dashboard().url, {}, { preserveState: true });
};

const hasActiveFilters = computed(() => {
    return search.value || selectedMethod.value || selectedGroup.value;
});

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

const groupedEndpoints = computed(() => {
    const grouped: Record<string, ApiEndpoint[]> = {};
    props.endpoints.forEach(endpoint => {
        const group = endpoint.group || 'Ungrouped';
        if (!grouped[group]) grouped[group] = [];
        grouped[group].push(endpoint);
    });
    return grouped;
});
</script>

<template>
    <Head title="API Endpoints" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col min-w-0 flex-1 gap-8 p-6 lg:p-10">
            <!-- Filters Area -->
            <div class="space-y-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">API Reference</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Browse and test all available endpoints across the system.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden md:flex -space-x-1.5 overflow-hidden">
                            <Badge variant="outline" class="rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-3">{{ stats.total }} Endpoints</Badge>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4 rounded-xl border border-border bg-card p-5 lg:flex-row lg:items-center lg:justify-between shadow-sm">
                    <div class="relative w-full lg:max-w-md">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-zinc-500" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Search by URI, description or action..."
                            class="pl-10 h-10 rounded-lg border-border bg-muted/50 focus:bg-background"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div class="relative min-w-[140px]">
                                <select
                                    v-model="selectedMethod"
                                    @change="applyFilters"
                                    class="h-10 w-full appearance-none rounded-lg border border-border bg-muted/50 pl-3 pr-10 text-sm font-medium transition-all focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 dark:text-foreground"
                                >
                                    <option value="">Methods: All</option>
                                    <option v-for="method in methods" :key="method" :value="method">{{ method }}</option>
                                </select>
                                <ChevronRight class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-gray-400" />
                            </div>
                            <div class="relative min-w-[140px]">
                                <select
                                    v-model="selectedGroup"
                                    @change="applyFilters"
                                    class="h-10 w-full appearance-none rounded-lg border border-border bg-muted/50 pl-3 pr-10 text-sm font-medium transition-all focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 dark:text-foreground"
                                >
                                    <option value="">Groups: All</option>
                                    <option v-for="group in groups" :key="group" :value="group">{{ group }}</option>
                                </select>
                                <ChevronRight class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-gray-400" />
                            </div>
                        </div>
                        <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters" class="h-10 rounded-lg text-muted-foreground hover:text-foreground">
                            Reset Filters
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Endpoints List grouped -->
            <div class="flex-1 space-y-12 pb-20">
                <template v-if="endpoints.length === 0">
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/30 py-24">
                        <div class="rounded-full bg-gray-100 p-4 dark:bg-zinc-800">
                            <Filter class="h-8 w-8 text-gray-400 dark:text-zinc-600" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-foreground">No endpoints found</h3>
                        <p class="mt-1 text-sm text-muted-foreground text-center max-w-xs">We couldn't find any endpoints matching your current search or filters.</p>
                        <Button @click="clearFilters" variant="outline" class="mt-6">Clear all filters</Button>
                    </div>
                </template>

                <template v-else>
                    <section 
                        v-for="(groupEndpoints, groupName) in groupedEndpoints" 
                        :key="groupName" 
                        :id="`group-${groupName.toLowerCase().replace(/\s+/g, '-')}`"
                        class="space-y-6 scroll-mt-20"
                    >
                        <div class="sticky top-16 z-10 -mx-4 flex items-center gap-4 bg-background px-5 py-4 backdrop-blur-xs lg:top-[64px] lg:-mx-10 lg:px-11">
                            <h2 class="text-xl font-bold tracking-tight text-foreground border-l-4 border-primary pl-4">{{ groupName }}</h2>
                            <div class="h-px flex-1 bg-linear-to-r from-border to-transparent"></div>
                            <span class="text-xs font-medium text-muted-foreground">{{ groupEndpoints.length }} Endpoints</span>
                        </div>

                        <div class="grid gap-3 lg:grid-cols-1">
                            <Link
                                v-for="endpoint in groupEndpoints"
                                :key="endpoint.id"
                                :href="show.url(endpoint.id)"
                                class="group relative flex flex-col gap-4 rounded-xl border border-border bg-card p-5 transition-all hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 md:flex-row md:items-center"
                            >
                                <!-- Method Badges -->
                                <div class="flex shrink-0 gap-1.5">
                                    <Badge
                                        v-for="method in endpoint.methods"
                                        :key="method"
                                        :class="['h-7 border-none px-2.5 font-mono text-[10px] font-bold tracking-wider ring-1 ring-inset', methodColors[method] || 'bg-gray-100 text-gray-600 ring-gray-300 dark:bg-zinc-500/20 dark:text-zinc-400 dark:ring-zinc-500/30']"
                                    >
                                        {{ method }}
                                    </Badge>
                                </div>

                                <!-- Endpoint Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <code class="font-mono text-[15px] font-bold text-foreground truncate">
                                            {{ endpoint.uri }}
                                        </code>
                                        <Badge v-if="endpoint.is_deprecated" variant="outline" class="border-amber-400/50 bg-amber-400/5 text-amber-600 text-[10px] font-bold uppercase tracking-widest dark:border-amber-500/30 dark:text-amber-500">
                                            Deprecated
                                        </Badge>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-4">
                                        <p v-if="endpoint.description" class="truncate text-sm text-muted-foreground">
                                            {{ endpoint.description }}
                                        </p>
                                        <div class="flex items-center gap-1.5 text-[11px] font-mono text-muted-foreground/60">
                                            <span class="truncate">{{ endpoint.controller.split('\\').pop() }}@{{ endpoint.action }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action -->
                                <div class="flex items-center justify-end pl-4">
                                    <div class="flex items-center gap-2 rounded-full border border-border bg-muted p-1.5 px-3 text-xs font-semibold text-muted-foreground transition-all group-hover:border-primary/30 group-hover:bg-primary/10 group-hover:text-primary">
                                        <span>Explore</span>
                                        <ChevronRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </section>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
