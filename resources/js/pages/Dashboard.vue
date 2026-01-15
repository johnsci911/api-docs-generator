<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, Filter, ChevronRight } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

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
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                    <div class="text-sm text-gray-500 dark:text-zinc-400">Total</div>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                    <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ stats.get }}</div>
                    <div class="text-sm text-emerald-600 dark:text-emerald-400/70">GET</div>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/30 dark:bg-blue-500/10">
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ stats.post }}</div>
                    <div class="text-sm text-blue-600 dark:text-blue-400/70">POST</div>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <div class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ stats.put }}</div>
                    <div class="text-sm text-amber-600 dark:text-amber-400/70">PUT</div>
                </div>
                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-500/30 dark:bg-orange-500/10">
                    <div class="text-2xl font-bold text-orange-700 dark:text-orange-400">{{ stats.patch }}</div>
                    <div class="text-sm text-orange-600 dark:text-orange-400/70">PATCH</div>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-500/30 dark:bg-red-500/10">
                    <div class="text-2xl font-bold text-red-700 dark:text-red-400">{{ stats.delete }}</div>
                    <div class="text-sm text-red-600 dark:text-red-400/70">DELETE</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 lg:flex-row lg:items-center lg:justify-between shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="relative w-full lg:max-w-md">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-zinc-500" />
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Search endpoints..."
                        class="pl-10 h-9"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <select
                            v-model="selectedMethod"
                            @change="applyFilters"
                            class="h-9 w-full min-w-[130px] appearance-none rounded-md border border-gray-200 bg-white pl-3 pr-8 text-sm text-gray-900 transition-shadow focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
                        >
                            <option value="">All Methods</option>
                            <option v-for="method in methods" :key="method" :value="method">{{ method }}</option>
                        </select>
                        <ChevronRight class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-gray-400" />
                    </div>
                    <div class="relative">
                        <select
                            v-model="selectedGroup"
                            @change="applyFilters"
                            class="h-9 w-full min-w-[130px] appearance-none rounded-md border border-gray-200 bg-white pl-3 pr-8 text-sm text-gray-900 transition-shadow focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
                        >
                            <option value="">All Groups</option>
                            <option v-for="group in groups" :key="group" :value="group">{{ group }}</option>
                        </select>
                        <ChevronRight class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-gray-400" />
                    </div>
                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters" class="h-9 text-gray-500 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-white">
                        Clear
                    </Button>
                </div>
            </div>

            <!-- Endpoints List -->
            <div class="flex-1 space-y-6">
                <template v-if="endpoints.length === 0">
                    <div class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-white py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
                        <Filter class="mb-4 h-12 w-12 text-gray-400 dark:text-zinc-600" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">No endpoints found</h3>
                        <p class="text-sm text-gray-500 dark:text-zinc-400">Try adjusting your search or filters</p>
                    </div>
                </template>

                <template v-else>
                    <div v-for="(groupEndpoints, groupName) in groupedEndpoints" :key="groupName" class="space-y-3">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ groupName }}</h2>
                        <div class="space-y-2">
                            <div
                                v-for="endpoint in groupEndpoints"
                                :key="endpoint.id"
                                class="group flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-4 transition-all hover:border-gray-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700"
                            >
                                <div class="flex gap-1">
                                    <Badge
                                        v-for="method in endpoint.methods"
                                        :key="method"
                                        :class="['border font-mono text-xs', methodColors[method] || 'bg-gray-100 text-gray-600 border-gray-300 dark:bg-zinc-500/20 dark:text-zinc-400 dark:border-zinc-500/30']"
                                    >
                                        {{ method }}
                                    </Badge>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <code class="font-mono text-sm text-gray-900 dark:text-white">{{ endpoint.uri }}</code>
                                        <Badge v-if="endpoint.is_deprecated" variant="outline" class="border-yellow-400 text-yellow-600 text-xs dark:border-yellow-500/50 dark:text-yellow-500">
                                            Deprecated
                                        </Badge>
                                    </div>
                                    <p v-if="endpoint.description" class="mt-1 truncate text-sm text-gray-500 dark:text-zinc-400">
                                        {{ endpoint.description }}
                                    </p>
                                    <p v-else class="mt-1 text-sm text-gray-400 dark:text-zinc-600">
                                        {{ endpoint.controller }}@{{ endpoint.action }}
                                    </p>
                                </div>
                                <ChevronRight class="h-5 w-5 text-gray-400 transition-colors group-hover:text-gray-600 dark:text-zinc-600 dark:group-hover:text-zinc-400" />
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
