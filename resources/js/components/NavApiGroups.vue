<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { 
    SidebarGroup, 
    SidebarGroupLabel, 
    SidebarMenu, 
    SidebarMenuButton, 
    SidebarMenuItem 
} from '@/components/ui/sidebar';

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

interface PageProps extends Record<string, unknown> {
    endpoints?: ApiEndpoint[];
    stats?: Record<string, number>;
    auth: {
        user: any;
    };
    name: string;
    sidebarOpen: boolean;
}

const page = usePage<PageProps>();

const groupedEndpoints = computed(() => {
    const endpoints = page.props.endpoints || [];
    const grouped: Record<string, ApiEndpoint[]> = {};
    
    endpoints.forEach(endpoint => {
        const group = endpoint.group || 'Ungrouped';
        if (!grouped[group]) grouped[group] = [];
        grouped[group].push(endpoint);
    });
    
    return grouped;
});

const groups = computed(() => Object.keys(groupedEndpoints.value).sort());

const stats = computed(() => page.props.stats || null);

const scrollToGroup = (groupName: string) => {
    const id = `group-${groupName.toLowerCase().replace(/\s+/g, '-')}`;
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <template v-if="groups.length > 0">
        <SidebarGroup class="px-2 py-4 border-b border-sidebar-border/50">
            <SidebarGroupLabel class="px-2 text-[10px] font-bold uppercase tracking-[0.2em] opacity-50 group-data-[collapsible=icon]:hidden">API Groups</SidebarGroupLabel>
            <SidebarMenu class="mt-2">
                <SidebarMenuItem v-for="group in groups" :key="group">
                    <SidebarMenuButton 
                        @click="scrollToGroup(group)"
                        class="px-2 py-1.5 h-auto text-sm font-medium transition-all hover:bg-sidebar-accent/50"
                    >
                        <div class="flex flex-1 items-center justify-between">
                            <span class="truncate">{{ group }}</span>
                            <Badge variant="outline" class="ml-2 h-4 px-1 py-0 text-[9px] font-bold border-none bg-sidebar-accent/30 text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden">
                                {{ groupedEndpoints[group].length }}
                            </Badge>
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>

        <SidebarGroup v-if="stats" class="px-2 py-4 group-data-[collapsible=icon]:hidden">
            <SidebarGroupLabel class="px-2 text-[10px] font-bold uppercase tracking-[0.2em] opacity-50">Statistics</SidebarGroupLabel>
            <SidebarMenu class="mt-2 px-2 space-y-2">
                <template v-for="(count, methodName) in stats" :key="methodName">
                    <div v-if="methodName !== 'total'" class="flex items-center justify-between text-[11px]">
                        <span class="font-bold uppercase tracking-widest text-sidebar-foreground/40">{{ methodName }}</span>
                        <span class="font-bold font-mono text-sidebar-foreground/80">{{ count }}</span>
                    </div>
                </template>
            </SidebarMenu>
        </SidebarGroup>
    </template>
</template>
