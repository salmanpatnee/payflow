<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { email } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Log in to PayFlow"
        description="Enter your credentials to access your account"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/20 dark:text-green-200"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="space-y-5">
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="name@company.com"
                        class="input-stripe h-10"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-medium">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="email()"
                            class="text-xs font-medium text-primary hover:text-primary/80"
                            :tabindex="5"
                        >
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
                        placeholder="Enter your password"
                        class="input-stripe h-10"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <Label for="remember" class="text-sm font-normal leading-none cursor-pointer">
                        Remember me for 30 days
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="h-10 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    {{ processing ? 'Logging in...' : 'Log in' }}
                </Button>
            </div>

        </Form>
    </AuthBase>
</template>
