<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Terminal } from 'lucide-vue-next';

import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword?: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="REST API Document Generator" />

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-950 via-zinc-900 to-zinc-950 px-4 py-12">
        <!-- Animated background grid -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#18181b_1px,transparent_1px),linear-gradient(to_bottom,#18181b_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_110%)]" />
        </div>

        <!-- Glow effect -->
        <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2">
            <div class="h-[40rem] w-[40rem] rounded-full bg-emerald-500/20 blur-[128px]" />
        </div>

        <!-- Floating code snippets decoration -->
        <div class="absolute left-10 top-20 hidden rotate-[-8deg] opacity-20 lg:block">
            <pre class="font-mono text-xs text-emerald-400"><code>GET /api/v1/users
Authorization: Bearer ...</code></pre>
        </div>
        <div class="absolute bottom-20 right-10 hidden rotate-[5deg] opacity-20 lg:block">
            <pre class="font-mono text-xs text-emerald-400"><code>{ "status": 200,
  "data": [...] }</code></pre>
        </div>

        <div class="relative z-10 w-full max-w-md">
            <!-- Logo and Title -->
            <div class="mb-10 text-center">
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 shadow-lg shadow-emerald-500/10 backdrop-blur-sm">
                    <Terminal class="h-8 w-8 text-emerald-400" />
                </div>
                <h1 class="mb-2 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">REST API <br> Document Generator</h1>
            </div>

            <!-- Navigation for authenticated users -->
            <div v-if="$page.props.auth.user" class="mb-8 flex justify-center">
                <Button as-child variant="default" size="lg">
                    <a :href="dashboard.url()" class="inline-flex items-center gap-2">
                        Go to Dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </Button>
            </div>

            <!-- Login Form Card -->
            <div v-else class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/80 shadow-2xl shadow-black/50 backdrop-blur-xl">
                <div class="border-b border-zinc-800 px-8 pb-6 pt-8">
                    <h2 class="text-xl font-semibold text-white">Welcome back</h2>
                    <p class="mt-1 text-sm text-zinc-400">Enter your credentials to continue</p>
                </div>

                <div class="p-8">
                    <div v-if="status" class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-center text-sm font-medium text-emerald-400">
                        {{ status }}
                    </div>

                    <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="flex flex-col gap-5">
                        <div class="space-y-2">
                            <Label for="email" class="text-zinc-300">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="you@example.com"
                                class="border-zinc-700 bg-zinc-800/50 text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500/20"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-zinc-300">Password</Label>
                                <TextLink v-if="canResetPassword" :href="request()" class="text-sm text-emerald-400 hover:text-emerald-300" :tabindex="5">
                                    Forgot password?
                                </TextLink>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="border-zinc-700 bg-zinc-800/50 text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500/20"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox id="remember" name="remember" :tabindex="3" class="border-zinc-600 data-[state=checked]:border-emerald-500 data-[state=checked]:bg-emerald-500" />
                            <Label for="remember" class="cursor-pointer text-sm text-zinc-400">Remember me for 30 days</Label>
                        </div>

                        <Button
                            type="submit"
                            class="mt-2 w-full bg-emerald-600 font-medium text-white hover:bg-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-zinc-900"
                            :tabindex="4"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <Spinner v-if="processing" class="mr-2" />
                            {{ processing ? 'Signing in...' : 'Sign in' }}
                        </Button>

                        <div v-if="canRegister" class="mt-2 text-center text-sm text-zinc-400">
                            Don't have an account?
                            <TextLink :href="register()" :tabindex="5" class="font-medium text-emerald-400 hover:text-emerald-300">Create one</TextLink>
                        </div>
                    </Form>
                </div>
            </div>

            <!-- API Features hint -->
            <div class="mt-8 text-center">
                <p class="text-xs text-zinc-500">Interactive API documentation with real-time testing</p>
                <div class="mt-4 flex items-center justify-center gap-6 text-xs text-zinc-600">
                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        OpenAPI 3.0
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        Live Testing
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        Auto Docs
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
