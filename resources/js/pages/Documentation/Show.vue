<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, CheckCircle2, ChevronLeft, Copy, Info, Play, Send } from 'lucide-vue-next';
import prism from 'prismjs';
import 'prismjs/components/prism-json';
import 'prismjs/components/prism-bash';
import { computed, ref } from 'vue';

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
const copied = ref(false);

const copyToClipboard = async (text: string) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            // Fallback for non-secure contexts
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
            } catch (err) {
                console.error('Fallback copy failed: ', err);
            }
            document.body.removeChild(textArea);
        }

        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
};

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

const displayParameters = computed(() => {
    return props.endpoint.parameters.filter(param => {
        // If it's a confirmation field, check if the base field exists and has confirmation logic
        if (param.name.endsWith('_confirmation')) {
            const baseFieldName = param.name.replace('_confirmation', '');
            const baseField = props.endpoint.parameters.find(p => p.name === baseFieldName);
            if (baseField && hasConfirmation(baseField)) {
                return false; // Skip this parameter, it's handled by the base field's UI
            }
        }
        return true;
    });
});

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

const computedCurl = computed(() => {
    let url = props.endpoint.uri;
    if (!url.startsWith('/')) {
        url = '/' + url;
    }
    const queryParams = new URLSearchParams();
    const headers: Record<string, string> = {
        'Accept': 'application/json',
    };

    const hasFiles = props.endpoint.parameters.some(p =>
        (p.type === 'file' || p.type === 'image') && testPayload.value[p.name] instanceof File
    );

    const body: Record<string, any> = {};
    const formDataParts: string[] = [];

    props.endpoint.parameters.forEach(param => {
        const value = testPayload.value[param.name];
        if (value === undefined || value === null || value === '') return;

        if (param.location === 'path') {
            url = url.replace(`{${param.name}}`, String(value));
        } else if (param.location === 'query') {
            queryParams.append(param.name, String(value));
        } else if (param.location === 'header') {
            headers[param.name] = String(value);
        } else if (param.location === 'body') {
            if (hasFiles) {
                if (value instanceof File) {
                    formDataParts.push(`-F "${param.name}=@${value.name}"`);
                } else {
                    formDataParts.push(`-F "${param.name}=${value}"`);
                }
            } else {
                body[param.name] = value;
            }
        }
    });

    const method = props.endpoint.methods[0];
    const fullUrl = `${window.location.origin}${url}${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;

    let curl = `curl -X ${method} "${fullUrl}"`;

    Object.entries(headers).forEach(([key, val]) => {
        curl += ` \\\n  -H "${key}: ${val}"`;
    });

    if (method !== 'GET' && method !== 'HEAD') {
        if (hasFiles) {
            formDataParts.forEach(part => {
                curl += ` \\\n  ${part}`;
            });
        } else if (Object.keys(body).length > 0) {
            curl += ` \\\n  -H "Content-Type: application/json"`;
            curl += ` \\\n  -d '${JSON.stringify(body, null, 2)}'`;
        }
    }

    return curl;
});
</script>

<template>
    <Head :title="endpoint.uri" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header section -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-3">
                        <Link :href="dashboard().url" class="rounded-full p-2 hover:bg-muted">
                            <ChevronLeft class="h-5 w-5 text-muted-foreground" />
                        </Link>
                        <h1 class="text-2xl font-bold text-foreground">{{ endpoint.uri }}</h1>
                        <Badge v-if="endpoint.is_deprecated" variant="outline" class="border-amber-400 text-amber-600">
                            Deprecated
                        </Badge>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pl-12">
                        <span
                            v-for="method in endpoint.methods"
                            :key="method"
                            :class="['badge-method', `badge-method-${method.toLowerCase()}`]"
                        >
                            {{ method }}
                        </span>
                        <span class="text-sm text-muted-foreground">{{ endpoint.controller }}@{{ endpoint.action }}</span>
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
            <div class="flex border-b border-border">
                <button
                    @click="activeTab = 'details'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'details' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground']"
                >
                    Details
                </button>
                <button
                    @click="activeTab = 'parameters'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'parameters' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground']"
                >
                    Parameters
                </button>
                <button
                    @click="activeTab = 'responses'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'responses' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground']"
                >
                    Responses
                </button>
                <button
                    @click="activeTab = 'testing'"
                    :class="['px-6 py-3 text-sm font-medium transition-colors', activeTab === 'testing' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground']"
                >
                    Live Testing
                </button>
            </div>

            <div class="flex-1 overflow-auto rounded-xl border border-border bg-card p-6">
                <!-- Details Tab -->
                <div v-if="activeTab === 'details'" class="space-y-6">
                    <div>
                        <h3 class="docs-label mb-4">Description</h3>
                        <p class="text-foreground/90">
                            {{ endpoint.description || 'No description provided for this endpoint.' }}
                        </p>
                    </div>

                    <div v-if="endpoint.middleware && endpoint.middleware.length > 0">
                        <h3 class="docs-label mb-4">Middleware</h3>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="mw in endpoint.middleware" :key="mw" variant="secondary">
                                {{ mw }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Parameters Tab -->
                <div v-if="activeTab === 'parameters'" class="space-y-6">
                    <div v-for="location in ['path', 'query', 'header', 'body']" :key="location">
                        <template v-if="endpoint.parameters.filter(p => p.location === location).length > 0">
                            <h3 class="docs-label mb-4">
                                <span class="capitalize">{{ location }}</span> Parameters
                            </h3>
                            <div class="docs-table-wrapper">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-muted/30 border-b border-border/40">
                                        <tr>
                                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-widest text-muted-foreground/60">Name</th>
                                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-widest text-muted-foreground/60">Type</th>
                                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-widest text-muted-foreground/60">Required</th>
                                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-widest text-muted-foreground/60">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/20">
                                        <tr v-for="param in endpoint.parameters.filter(p => p.location === location)" :key="param.id" class="group transition-colors hover:bg-muted/20">
                                            <td class="px-4 py-3 font-mono text-xs">
                                                <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400 font-medium">
                                                    {{ param.name }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-muted-foreground italic">{{ param.type }}</td>
                                            <td class="px-4 py-3">
                                                <span v-if="param.required" class="badge-required">Required</span>
                                                <span v-else class="badge-optional">Optional</span>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-muted-foreground/90 leading-relaxed">{{ param.description }}</td>
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
                    <div v-for="response in endpoint.responses" :key="response.id" class="overflow-hidden rounded-xl border border-border">
                        <div class="flex items-center justify-between border-b border-border bg-muted/30 px-6 py-4">
                            <div class="flex items-center gap-4">
                                <span
                                    :class="[
                                        'badge-method',
                                        response.status_code >= 400 ? 'badge-method-delete' : 'badge-method-get'
                                    ]"
                                >
                                    {{ response.status_code }}
                                </span>
                                <span class="font-semibold text-foreground">{{ response.description }}</span>
                            </div>
                        </div>

                        <div class="grid divide-y divide-border lg:grid-cols-2 lg:divide-x lg:divide-y-0 text-foreground">
                            <!-- Schema Column -->
                            <div class="p-6">
                                <h4 class="docs-label mb-4">Response Schema</h4>
                                <div v-if="response.schema" class="docs-card">
                                    <pre class="overflow-auto p-5 font-mono! text-xs leading-relaxed max-h-[400px]"><code class="language-json" v-html="prism.highlight(JSON.stringify(response.schema, null, 2), prism.languages.json, 'json')"></code></pre>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-8 text-center bg-muted/10 rounded-lg border border-dashed border-border/50">
                                    <Info class="mb-2 h-5 w-5 opacity-20" />
                                    <p class="text-xs text-muted-foreground/60">No schema defined</p>
                                </div>
                            </div>

                            <!-- Example Column -->
                            <div class="p-6">
                                <h4 class="docs-label mb-4">Response Example</h4>
                                <div v-if="response.example" class="docs-card">
                                    <pre class="overflow-auto p-5 font-mono! text-xs leading-relaxed max-h-[400px]"><code class="language-json" v-html="prism.highlight(JSON.stringify(response.example, null, 2), prism.languages.json, 'json')"></code></pre>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-8 text-center bg-muted/20 rounded-lg border border-dashed border-border">
                                    <Play class="mb-2 h-5 w-5 opacity-20" />
                                    <p class="text-xs text-muted-foreground/60">No example provided</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="endpoint.responses.length === 0" class="flex flex-col items-center justify-center py-20 text-muted-foreground border border-dashed border-border rounded-xl">
                        <CheckCircle2 class="mb-4 h-12 w-12 opacity-10" />
                        <p class="font-medium">No response documentation available for this endpoint.</p>
                    </div>
                </div>

                <!-- Testing Tab -->
                <div v-if="activeTab === 'testing'" class="grid gap-8 lg:grid-cols-2">
                    <!-- Request Config -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 rounded-lg bg-muted/20 px-4 py-2 border border-border/30">
                            <Send class="h-4 w-4 text-emerald-500" />
                            <h3 class="text-sm font-semibold tracking-wide text-foreground">Request Configuration</h3>
                        </div>

                        <div class="rounded-xl border border-border/40 bg-muted/10 p-5 space-y-6 shadow-sm">
                            <div v-for="param in displayParameters" :key="param.id" class="space-y-4">
                                <div class="space-y-2">
                                    <label class="docs-label">
                                        {{ param.name }}
                                        <span v-if="param.required" class="ml-1 text-red-500 font-bold">*</span>
                                    </label>
                                    <template v-if="param.type === 'file' || param.type === 'image'">
                                        <input
                                            type="file"
                                            @change="(e: any) => testPayload[param.name] = e.target.files[0]"
                                            class="docs-field file:border-0 file:bg-transparent file:text-sm file:font-medium"
                                        />
                                    </template>
                                    <Input
                                        v-else
                                        v-model="testPayload[param.name]"
                                        :type="isPasswordField(param.name) ? 'password' : 'text'"
                                        :placeholder="param.description || 'Enter value...'"
                                        class="docs-field"
                                    />
                                    <p v-if="param.description" class="text-[11px] text-muted-foreground/50 px-1 leading-relaxed">
                                        {{ param.description }}
                                    </p>
                                </div>

                                <!-- Confirmation field if needed -->
                                <div v-if="hasConfirmation(param)" class="space-y-2 border-l-2 border-border/30 ml-2 pl-4 py-1">
                                    <label class="docs-label">
                                        Confirm {{ param.name }}
                                        <span v-if="param.required" class="ml-1 text-red-500 font-bold">*</span>
                                    </label>
                                    <Input
                                        v-model="testPayload[`${param.name}_confirmation`]"
                                        :type="isPasswordField(param.name) ? 'password' : 'text'"
                                        :placeholder="`Confirm ${param.name.toLowerCase()}`"
                                        class="docs-field"
                                    />
                                </div>
                            </div>

                            <div v-if="displayParameters.length === 0" class="py-12 text-center text-muted-foreground/40">
                                <Info class="mx-auto mb-2 h-8 w-8 opacity-10" />
                                <p class="text-xs">No parameters required for this request.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-between rounded-lg bg-muted/30 px-4 py-2 border border-border/40 shadow-sm">
                            <div class="flex items-center gap-2">
                                <Play class="h-4 w-4 text-emerald-500" />
                                <h3 class="text-sm font-semibold tracking-wide text-foreground">Execution & Results</h3>
                            </div>
                            <Button
                                @click="executeTest"
                                :disabled="testLoading"
                                size="sm"
                                class="bg-emerald-600 font-semibold shadow-lg shadow-emerald-500/10 transition-all hover:bg-emerald-700 hover:shadow-emerald-500/20 active:scale-95 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                            >
                                <Send v-if="!testLoading" class="mr-2 h-3.5 w-3.5" />
                                <div v-else class="mr-2 h-3.5 w-3.5 animate-spin rounded-full border-2 border-black border-t-transparent"></div>
                                {{ testLoading ? 'Executing...' : 'Execute Request' }}
                            </Button>
                        </div>

                        <!-- Live cURL -->
                        <div class="group/curl space-y-3">
                            <div class="flex items-center justify-between px-1">
                                <h4 class="docs-label">Live cURL Command</h4>
                                <button
                                    @click="copyToClipboard(computedCurl)"
                                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-muted-foreground transition-all hover:bg-primary/10 hover:text-primary"
                                    title="Copy to clipboard"
                                >
                                    <template v-if="copied">
                                        <Check class="h-3 w-3" />
                                        <span>Copied!</span>
                                    </template>
                                    <template v-else>
                                        <Copy class="h-3 w-3" />
                                        <span>Copy</span>
                                    </template>
                                </button>
                            </div>
                            <div class="docs-card">
                                <pre class="overflow-auto p-6 font-mono! text-[13px] leading-relaxed max-h-[220px]"><code class="language-bash" v-html="prism.highlight(computedCurl, prism.languages.bash || prism.languages.javascript, 'bash')"></code></pre>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h4 class="docs-label px-1">Response Analysis</h4>
                            <div v-if="testResponse" class="docs-card">
                                <pre class="overflow-auto p-6 font-mono! text-[13px] leading-relaxed max-h-[450px]"><code class="language-json" v-html="prism.highlight(JSON.stringify(testResponse, null, 2), prism.languages.json, 'json')"></code></pre>
                            </div>
                            <div v-else class="flex h-[280px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-border/60 bg-muted/10 text-center text-muted-foreground transition-all hover:bg-muted/20 hover:border-border/80">
                                <div class="bg-background/40 rounded-full p-6 mb-4 shadow-sm border border-border/30">
                                    <Play class="h-10 w-10 opacity-40" />
                                </div>
                                <p class="text-sm font-bold tracking-tight text-foreground/80">Ready to execute?</p>
                                <p class="text-xs font-medium text-muted-foreground/80 max-w-[200px] mt-1 mx-auto">Fill in the parameters and hit the button to see the results.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



