<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, ChevronLeft, Info, Play, Save, Send } from 'lucide-vue-next';
import { ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface ApiParameter {
    id: number;
    name: string;
    type: string;
    location: 'path' | 'query' | 'header' | 'body';
    required: boolean;
    description: string;
    default_value: any;
    validation_rules: string[];
}

interface ApiResponse {
    id: number;
    status_code: number;
    description: string;
    schema: any;
    example: any;
    is_error: boolean;
}

interface ApiEndpoint {
    id: number;
    uri: string;
    methods: string[];
    controller: string;
    action: string;
    description: string;
    group: string;
    is_deprecated: boolean;
    middleware: string[];
    parameters: ApiParameter[];
    responses: ApiResponse[];
}

interface Props {
    endpoint: ApiEndpoint;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'API Endpoints', href: dashboard().url },
    { title: props.endpoint.uri, href: '#' },
];

const activeTab = ref('details');
const testResponse = ref<any>(null);
const testLoading = ref(false);
const testPayload = ref<Record<string, any>>({});

const switchToTesting = () => {
    activeTab.value = 'testing';
};

const isPasswordField = (name: string) => name.toLowerCase().includes('password');
const hasConfirmation = (param: ApiParameter) => param.validation_rules?.includes('confirmed');

// Initialize test payload with default values
props.endpoint.parameters.forEach(param => {
    testPayload.value[param.name] = param.default_value || '';
    if (hasConfirmation(param)) {
        testPayload.value[`${param.name}_confirmation`] = '';
    }
});

const methodColors: Record<string, string> = {
    GET: 'bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30',
    POST: 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/30',
    PUT: 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30',
    PATCH: 'bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-500/20 dark:text-orange-400 dark:border-orange-500/30',
    DELETE: 'bg-red-100 text-red-700 border-red-300 dark:bg-red-500/20 dark:text-red-400 dark:border-red-500/30',
};

const executeTest = async () => {
    testLoading.value = true;
    testResponse.value = null;

    try {
        let url = props.endpoint.uri;
        if (!url.startsWith('/')) {
            url = '/' + url;
        }
        const queryParams = new URLSearchParams();
        const headers: Record<string, string> = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        // Determine if we need FormData (for files)
        const hasFiles = props.endpoint.parameters.some(p =>
            (p.type === 'file' || p.type === 'image') && testPayload.value[p.name] instanceof File
        );

        let body: any = null;
        if (hasFiles) {
            body = new FormData();
        } else {
            body = {};
            headers['Content-Type'] = 'application/json';
        }

        // Process parameters based on location
        props.endpoint.parameters.forEach(param => {
            const value = testPayload.value[param.name];
            if (value === undefined || value === null || value === '') return;

            if (param.location === 'path') {
                url = url.replace(`{${param.name}}`, encodeURIComponent(String(value)));
            } else if (param.location === 'query') {
                queryParams.append(param.name, String(value));
            } else if (param.location === 'body') {
                if (hasFiles) {
                    body.append(param.name, value);
                    if (hasConfirmation(param)) {
                        body.append(`${param.name}_confirmation`, testPayload.value[`${param.name}_confirmation`]);
                    }
                } else {
                    body[param.name] = value;
                    if (hasConfirmation(param)) {
                        body[`${param.name}_confirmation`] = testPayload.value[`${param.name}_confirmation`];
                    }
                }
            } else if (param.location === 'header') {
                headers[param.name] = String(value);
            }
        });

        const fullUrl = url + (queryParams.toString() ? `?${queryParams.toString()}` : '');
        const method = props.endpoint.methods[0];

        const response = await fetch(fullUrl, {
            method,
            headers,
            body: method !== 'GET' && method !== 'HEAD' ? (hasFiles ? body : JSON.stringify(body)) : undefined,
        });

        const status = response.status;
        const statusText = response.statusText;
        const responseHeaders: Record<string, string> = {};
        response.headers.forEach((v, k) => responseHeaders[k] = v);

        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            data = await response.text();
        }

        testResponse.value = {
            status,
            statusText,
            headers: responseHeaders,
            data
        };
    } catch (error: any) {
        testResponse.value = {
            error: 'Failed to execute request',
            message: error.message
        };
    } finally {
        testLoading.value = false;
    }
};
</script>

<template>
    <Head :title="endpoint.uri" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header section -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-3">
                        <Link :href="dashboard().url" class="rounded-full p-2 hover:bg-gray-100 dark:hover:bg-zinc-800">
                            <ChevronLeft class="h-5 w-5 text-gray-500" />
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ endpoint.uri }}</h1>
                        <Badge v-if="endpoint.is_deprecated" variant="outline" class="border-yellow-400 text-yellow-600 dark:border-yellow-500/50 dark:text-yellow-500">
                            Deprecated
                        </Badge>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pl-12">
                        <Badge
                            v-for="method in endpoint.methods"
                            :key="method"
                            :class="['border font-mono text-xs uppercase', methodColors[method]]"
                        >
                            {{ method }}
                        </Badge>
                        <span class="text-sm text-gray-500 dark:text-zinc-400">{{ endpoint.controller }}@{{ endpoint.action }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button @click="switchToTesting" size="sm" class="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                        <Play class="mr-2 h-4 w-4" />
                        Try it out
                    </Button>
                </div>
            </div>

            <!-- Content tabs -->
            <div class="flex border-b border-gray-200 dark:border-zinc-800">
                <button
                    @click="activeTab = 'details'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'details' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200']"
                >
                    Details
                </button>
                <button
                    @click="activeTab = 'parameters'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'parameters' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200']"
                >
                    Parameters
                </button>
                <button
                    @click="activeTab = 'responses'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'responses' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200']"
                >
                    Responses
                </button>
                <button
                    @click="activeTab = 'testing'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'testing' ? 'border-b-2 border-emerald-500 text-emerald-600' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200']"
                >
                    Live Testing
                </button>
            </div>

            <div class="flex-1 overflow-auto rounded-xl border border-gray-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
                <!-- Details Tab -->
                <div v-if="activeTab === 'details'" class="space-y-6">
                    <div>
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Description</h3>
                        <p class="text-gray-700 dark:text-zinc-300">
                            {{ endpoint.description || 'No description provided for this endpoint.' }}
                        </p>
                    </div>

                    <div v-if="endpoint.middleware && endpoint.middleware.length > 0">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Middleware</h3>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="mw in endpoint.middleware" :key="mw" variant="secondary" class="bg-gray-100 dark:bg-zinc-800">
                                {{ mw }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Parameters Tab -->
                <div v-if="activeTab === 'parameters'" class="space-y-6">
                    <div v-for="location in ['path', 'query', 'header', 'body']" :key="location">
                        <template v-if="endpoint.parameters.filter(p => p.location === location).length > 0">
                            <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                <span class="capitalize">{{ location }}</span> Parameters
                            </h3>
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-800">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50 dark:bg-zinc-800/50">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Name</th>
                                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Type</th>
                                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Required</th>
                                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
                                        <tr v-for="param in endpoint.parameters.filter(p => p.location === location)" :key="param.id">
                                            <td class="px-4 py-3 font-mono text-emerald-600 dark:text-emerald-400">{{ param.name }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-zinc-400">{{ param.type }}</td>
                                            <td class="px-4 py-3">
                                                <Badge v-if="param.required" class="bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Required</Badge>
                                                <Badge v-else variant="outline">Optional</Badge>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-zinc-400">{{ param.description }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                    <div v-if="endpoint.parameters.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-500">
                        <Info class="mb-4 h-12 w-12 opacity-20" />
                        <p>No parameters documented for this endpoint.</p>
                    </div>
                </div>

                <!-- Responses Tab -->
                <div v-if="activeTab === 'responses'" class="space-y-8">
                    <div v-for="response in endpoint.responses" :key="response.id" class="overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-800">
                        <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-800/30">
                            <div class="flex items-center gap-4">
                                <Badge
                                    :class="[
                                        'px-3 py-1 text-sm font-bold',
                                        response.status_code >= 400
                                            ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20'
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20'
                                    ]"
                                    variant="outline"
                                >
                                    {{ response.status_code }}
                                </Badge>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ response.description }}</span>
                            </div>
                        </div>

                        <div class="grid divide-y divide-gray-200 lg:grid-cols-2 lg:divide-x lg:divide-y-0 dark:divide-zinc-800">
                            <!-- Schema Column -->
                            <div class="p-6">
                                <h4 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-zinc-500">Response Schema</h4>
                                <div v-if="response.schema" class="relative group">
                                    <pre class="overflow-auto rounded-lg bg-zinc-950 p-4 font-mono text-xs text-zinc-300 leading-relaxed max-h-[400px]"><code>{{ JSON.stringify(response.schema, null, 2) }}</code></pre>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-8 text-center bg-gray-50/30 rounded-lg border border-dashed border-gray-200 dark:bg-zinc-900/20 dark:border-zinc-800">
                                    <Info class="mb-2 h-5 w-5 opacity-20" />
                                    <p class="text-xs text-gray-400 dark:text-zinc-600">No schema defined</p>
                                </div>
                            </div>

                            <!-- Example Column -->
                            <div class="p-6">
                                <h4 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-zinc-500">Response Example</h4>
                                <div v-if="response.example" class="relative group">
                                    <pre class="overflow-auto rounded-lg bg-zinc-950 p-4 font-mono text-xs text-emerald-400/90 leading-relaxed max-h-[400px]"><code>{{ JSON.stringify(response.example, null, 2) }}</code></pre>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-8 text-center bg-gray-50/30 rounded-lg border border-dashed border-gray-200 dark:bg-zinc-900/20 dark:border-zinc-800">
                                    <Play class="mb-2 h-5 w-5 opacity-20" />
                                    <p class="text-xs text-gray-400 dark:text-zinc-600">No example provided</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="endpoint.responses.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-500 border border-dashed border-gray-200 rounded-xl dark:border-zinc-800">
                        <CheckCircle2 class="mb-4 h-12 w-12 opacity-10" />
                        <p class="font-medium">No response documentation available for this endpoint.</p>
                    </div>
                </div>

                <!-- Testing Tab -->
                <div v-if="activeTab === 'testing'" class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Request Body</h3>
                        <div class="space-y-4">
                            <div v-for="param in endpoint.parameters" :key="param.id" class="space-y-4">
                                <div class="space-y-1.5">
                                    <label class="flex text-sm font-medium text-gray-700 dark:text-zinc-300">
                                        {{ param.name }}
                                        <span v-if="param.required" class="ml-1 text-red-500">*</span>
                                    </label>
                                    <template v-if="param.type === 'file' || param.type === 'image'">
                                        <input
                                            type="file"
                                            @change="(e: any) => testPayload[param.name] = e.target.files[0]"
                                            class="flex h-9 w-full rounded-md border border-input bg-white px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-800"
                                        />
                                    </template>
                                    <Input
                                        v-else
                                        v-model="testPayload[param.name]"
                                        :type="isPasswordField(param.name) ? 'password' : 'text'"
                                        :placeholder="param.description"
                                        class="bg-white dark:bg-zinc-800"
                                    />
                                </div>

                                <!-- Confirmation field if needed -->
                                <div v-if="hasConfirmation(param)" class="space-y-1.5">
                                    <label class="flex text-sm font-medium text-gray-700 dark:text-zinc-300">
                                        Confirm {{ param.name }}
                                        <span v-if="param.required" class="ml-1 text-red-500">*</span>
                                    </label>
                                    <Input
                                        v-model="testPayload[`${param.name}_confirmation`]"
                                        :type="isPasswordField(param.name) ? 'password' : 'text'"
                                        :placeholder="`Confirm ${param.name.toLowerCase()}`"
                                        class="bg-white dark:bg-zinc-800"
                                    />
                                </div>
                            </div>
                        </div>
                        <Button
                            @click="executeTest"
                            :disabled="testLoading"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500"
                        >
                            <Send class="mr-2 h-4 w-4" />
                            {{ testLoading ? 'Executing...' : 'Execute Request' }}
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Response</h3>
                        <div v-if="testResponse" class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
                            <pre class="overflow-auto font-mono text-xs text-gray-800 dark:text-zinc-300"><code>{{ JSON.stringify(testResponse, null, 2) }}</code></pre>
                        </div>
                        <div v-else class="flex h-[300px] flex-col items-center justify-center px-8 text-center rounded-lg border border-dashed border-gray-300 text-gray-400 dark:border-zinc-700">
                            <Play class="mb-4 h-12 w-12 opacity-20" />
                            <p>Execute a request to see the response here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
