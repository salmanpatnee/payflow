<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard, Shield, Zap, ArrowRight, Check, TrendingUp, BarChart3, Lock } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome to PayFlow" />
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-background via-secondary/30 to-primary/5">
        <!-- Animated Background Pattern -->
        <div class="grain-overlay fixed inset-0 opacity-[0.02]"></div>
        <div class="fixed inset-0 opacity-[0.015]" style="background-image: radial-gradient(circle at 1px 1px, hsl(160 84% 39%) 1px, transparent 0); background-size: 40px 40px;"></div>

        <!-- Floating gradient orbs for depth -->
        <div class="fixed -top-1/2 -left-1/4 h-[800px] w-[800px] rounded-full bg-primary/5 blur-3xl"></div>
        <div class="fixed -bottom-1/2 -right-1/4 h-[800px] w-[800px] rounded-full bg-accent/5 blur-3xl"></div>

        <!-- Navigation -->
        <nav class="relative border-b border-border/40 bg-card/60 backdrop-blur-xl">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8">
                <div class="flex items-center gap-3 animate-slide-in-left">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary/80 shadow-lg shadow-primary/25">
                        <CreditCard class="h-6 w-6 text-primary-foreground" stroke-width="2.5" />
                    </div>
                    <span class="font-display text-2xl font-bold tracking-tight">PayFlow</span>
                </div>
                <div class="flex items-center gap-3 animate-slide-in-right">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-primary px-8 font-medium text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:bg-primary/90 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98]"
                    >
                        Go to Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex h-11 items-center justify-center rounded-xl px-6 font-medium text-foreground transition-all hover:bg-muted/80"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="inline-flex h-11 items-center justify-center rounded-xl bg-primary px-8 font-medium text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:bg-primary/90 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98]"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative mx-auto max-w-7xl px-6 py-20 sm:py-32 lg:px-8 lg:py-40">
            <div class="mx-auto max-w-4xl text-center">
                <!-- Badge -->
                <div class="mb-8 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-medium text-primary backdrop-blur-sm animate-fade-in-down">
                    <div class="h-2 w-2 rounded-full bg-primary animate-pulse"></div>
                    Secure Payment Collections Platform
                </div>

                <!-- Main Headline -->
                <h1 class="mb-8 font-display text-5xl font-bold leading-[1.1] tracking-tight sm:text-6xl lg:text-7xl xl:text-8xl animate-fade-in-up">
                    Financial
                    <span class="block bg-gradient-to-r from-primary via-primary/80 to-accent bg-clip-text text-transparent">
                        Confidence
                    </span>
                    Simplified
                </h1>

                <!-- Subheadline -->
                <p class="mx-auto mb-12 max-w-2xl text-lg leading-relaxed text-muted-foreground sm:text-xl lg:text-2xl" style="animation: fadeInUp 0.6s ease-out 0.2s backwards;">
                    Create, manage, and track payment collections with precision and elegance. Built for modern businesses that demand excellence.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row" style="animation: fadeInUp 0.6s ease-out 0.4s backwards;">
                    <Link
                        v-if="!$page.props.auth.user && canRegister"
                        :href="register()"
                        class="group inline-flex h-14 items-center justify-center gap-3 rounded-xl bg-gradient-to-r from-primary to-primary/90 px-10 text-base font-semibold text-primary-foreground shadow-xl shadow-primary/30 transition-all hover:shadow-2xl hover:shadow-primary/40 hover:-translate-y-1 active:scale-[0.98]"
                    >
                        Start Free Today
                        <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                    </Link>
                    <Link
                        v-else
                        :href="dashboard()"
                        class="group inline-flex h-14 items-center justify-center gap-3 rounded-xl bg-gradient-to-r from-primary to-primary/90 px-10 text-base font-semibold text-primary-foreground shadow-xl shadow-primary/30 transition-all hover:shadow-2xl hover:shadow-primary/40 hover:-translate-y-1 active:scale-[0.98]"
                    >
                        Go to Dashboard
                        <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                    </Link>
                    <button class="inline-flex h-14 items-center justify-center gap-2 rounded-xl border-2 border-border bg-card/60 px-8 text-base font-semibold backdrop-blur-sm transition-all hover:border-primary/50 hover:bg-card/80 hover:-translate-y-0.5">
                        <TrendingUp class="h-5 w-5 text-primary" />
                        See Demo
                    </button>
                </div>

                <!-- Social Proof -->
                <div class="mt-16 flex flex-col items-center gap-6" style="animation: fadeInUp 0.6s ease-out 0.6s backwards;">
                    <div class="flex items-center gap-8 text-sm text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <Check class="h-5 w-5 text-primary" />
                            <span>No credit card required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Check class="h-5 w-5 text-primary" />
                            <span>PCI DSS compliant</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Check class="h-5 w-5 text-primary" />
                            <span>Powered by Stripe</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="relative mx-auto max-w-7xl px-6 py-24 lg:px-8">
            <!-- Section Header -->
            <div class="mb-16 text-center">
                <h2 class="mb-4 font-display text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                    Everything you need
                </h2>
                <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                    Professional payment collection tools designed for efficiency and security
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Feature 1 -->
                <div class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card/60 p-8 backdrop-blur-sm transition-all hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 card-hover-lift" style="animation: fadeInUp 0.6s ease-out backwards;">
                    <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gradient-to-br from-primary/10 to-transparent blur-2xl transition-transform group-hover:scale-150"></div>
                    <div class="relative">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-primary/10 to-primary/5 ring-1 ring-primary/20 transition-transform group-hover:scale-110 group-hover:rotate-3">
                            <Zap class="h-7 w-7 text-primary" stroke-width="2" />
                        </div>
                        <h3 class="mb-3 font-display text-2xl font-bold">Lightning Fast</h3>
                        <p class="leading-relaxed text-muted-foreground">
                            Create payment collections in seconds. Our streamlined interface makes payment management effortless and intuitive.
                        </p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card/60 p-8 backdrop-blur-sm transition-all hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 card-hover-lift" style="animation: fadeInUp 0.6s ease-out 0.1s backwards;">
                    <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gradient-to-br from-accent/10 to-transparent blur-2xl transition-transform group-hover:scale-150"></div>
                    <div class="relative">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-accent/10 to-accent/5 ring-1 ring-accent/20 transition-transform group-hover:scale-110 group-hover:rotate-3">
                            <Shield class="h-7 w-7 text-accent" stroke-width="2" />
                        </div>
                        <h3 class="mb-3 font-display text-2xl font-bold">Bank-Grade Security</h3>
                        <p class="leading-relaxed text-muted-foreground">
                            Powered by Stripe. Your payment data is encrypted end-to-end and fully PCI DSS compliant for maximum security.
                        </p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card/60 p-8 backdrop-blur-sm transition-all hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 card-hover-lift" style="animation: fadeInUp 0.6s ease-out 0.2s backwards;">
                    <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gradient-to-br from-primary/10 to-transparent blur-2xl transition-transform group-hover:scale-150"></div>
                    <div class="relative">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-primary/10 to-primary/5 ring-1 ring-primary/20 transition-transform group-hover:scale-110 group-hover:rotate-3">
                            <BarChart3 class="h-7 w-7 text-primary" stroke-width="2" />
                        </div>
                        <h3 class="mb-3 font-display text-2xl font-bold">Real-Time Tracking</h3>
                        <p class="leading-relaxed text-muted-foreground">
                            Monitor payment status in real-time with comprehensive analytics, instant notifications, and detailed reporting.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trust Section -->
        <div class="relative mx-auto max-w-5xl px-6 py-24 text-center lg:px-8">
            <div class="rounded-3xl border border-border/50 bg-gradient-to-br from-card/80 to-card/40 p-12 backdrop-blur-xl shadow-2xl">
                <div class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/80 shadow-lg shadow-primary/25">
                    <Lock class="h-8 w-8 text-primary-foreground" />
                </div>
                <h2 class="mb-4 font-display text-3xl font-bold sm:text-4xl">
                    Built on Trust
                </h2>
                <p class="mx-auto mb-8 max-w-2xl text-lg leading-relaxed text-muted-foreground">
                    We partner with Stripe to ensure your payment data is always secure, compliant, and protected. Focus on your business while we handle the complexity.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-8 text-sm font-medium text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                        256-bit SSL Encryption
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                        PCI DSS Level 1
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                        SOC 2 Type II Certified
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="relative border-t border-border/40 bg-card/40 backdrop-blur-xl">
            <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary/80">
                            <CreditCard class="h-5 w-5 text-primary-foreground" />
                        </div>
                        <span class="font-display text-xl font-bold">PayFlow</span>
                    </div>
                    <p class="text-center text-sm text-muted-foreground md:text-left">
                        &copy; 2026 PayFlow. Secure payment collections made simple.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
