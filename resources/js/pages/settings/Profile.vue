<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const initials = computed(() => {
    const name = user.value.name?.trim() || '';

    return (
        name
            .split(/\s+/)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('') || 'U'
    );
});
</script>

<template>
    <Head title="My Account | Document Server" />

    <div class="min-h-screen w-full bg-[#f4f7fb] px-5 py-7 lg:px-10 lg:py-9">
        <!-- Navigation -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <Link
                href="/dashboard"
                class="inline-flex min-h-11 items-center gap-3 rounded-lg border border-[#d6e0ec] bg-white px-5 py-3 text-sm font-bold text-[#183758] shadow-sm transition hover:border-[#1769d7] hover:text-[#1769d7]"
            >
                <svg
                    width="19"
                    height="19"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="m15 18-6-6 6-6" />
                </svg>

                Back to Dashboard
            </Link>

            <span
                class="text-xs font-bold tracking-[0.14em] text-[#647991] uppercase"
            >
                Document Server / Account
            </span>
        </div>

        <!-- Header -->
        <header
            class="flex flex-wrap items-center justify-between gap-6 rounded-2xl bg-[#10243e] px-8 py-8 text-white shadow-sm lg:px-10"
        >
            <div>
                <p
                    class="mb-2 text-xs font-bold tracking-[0.16em] text-[#8ab9fa] uppercase"
                >
                    Account management
                </p>

                <h1 class="text-3xl font-extrabold tracking-tight lg:text-4xl">
                    My Account
                </h1>

                <p class="mt-3 text-sm text-[#c3d2e4] lg:text-base">
                    Manage your personal information and account details.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl border border-[#5c85b5] bg-[#244e7d] text-xl font-extrabold text-white"
                >
                    {{ initials }}
                </div>

                <div>
                    <p class="text-sm font-bold text-white">
                        {{ user.name }}
                    </p>
                    <p class="mt-1 text-xs text-[#b8cce4]">
                        {{ user.email }}
                    </p>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <div class="mt-7 grid grid-cols-1 gap-6 xl:grid-cols-12">
            <!-- Personal information -->
            <section
                class="min-w-0 rounded-2xl border border-[#dfe7f0] bg-white shadow-sm xl:col-span-8"
            >
                <div
                    class="flex items-center gap-4 border-b border-[#e7edf5] px-7 py-6 lg:px-9"
                >
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#eaf2ff] text-[#1769d7]"
                    >
                        <svg
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 21v-2a8 8 0 0 1 16 0v2" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-xl font-extrabold text-[#152b47]">
                            Personal Information
                        </h2>
                        <p class="mt-1 text-sm text-[#657991]">
                            Update the details associated with your account.
                        </p>
                    </div>
                </div>

                <Form
                    v-bind="ProfileController.update.form()"
                    v-slot="{ errors, processing, recentlySuccessful }"
                    class="px-7 py-8 lg:px-9"
                >
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Name -->
                        <div class="space-y-3">
                            <Label
                                for="name"
                                class="text-sm font-bold text-[#243c5a]"
                            >
                                Full name
                            </Label>

                            <Input
                                id="name"
                                name="name"
                                type="text"
                                :default-value="user.name"
                                autocomplete="name"
                                placeholder="Your full name"
                                required
                                class="!h-14 !w-full !rounded-lg !border-[#cbd7e5] !bg-white !px-4 !text-base !text-[#152b47]"
                            />

                            <InputError :message="errors.name" />
                        </div>

                        <!-- Email -->
                        <div class="space-y-3">
                            <Label
                                for="email"
                                class="text-sm font-bold text-[#243c5a]"
                            >
                                Email address
                            </Label>

                            <Input
                                id="email"
                                name="email"
                                type="email"
                                :default-value="user.email"
                                autocomplete="email"
                                placeholder="name@company.com"
                                required
                                class="!h-14 !w-full !rounded-lg !border-[#cbd7e5] !bg-white !px-4 !text-base !text-[#152b47]"
                            />

                            <InputError :message="errors.email" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div
                        class="mt-9 flex flex-wrap items-center justify-between gap-5 border-t border-[#e7edf5] pt-7"
                    >
                        <p class="text-sm text-[#657991]">
                            Your changes are saved to your Document Server
                            account.
                        </p>

                        <div class="flex flex-wrap items-center gap-4">
                            <span
                                v-if="recentlySuccessful"
                                class="text-sm font-bold text-[#17683d]"
                                role="status"
                            >
                                ✓ Changes saved
                            </span>

                            <Link
                                href="/dashboard"
                                class="inline-flex min-h-12 items-center justify-center rounded-lg border border-[#ced9e6] px-6 text-sm font-bold text-[#344b66] transition hover:bg-[#f0f4f9]"
                            >
                                Cancel
                            </Link>

                            <Button
                                type="submit"
                                :disabled="processing"
                                data-test="update-profile-button"
                                class="!min-h-12 !rounded-lg !bg-[#1769d7] !px-7 !text-sm !font-bold !text-white hover:!bg-[#1155af]"
                            >
                                {{ processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </div>
                </Form>
            </section>

            <!-- Account summary -->
            <aside class="space-y-6 xl:col-span-4">
                <section
                    class="rounded-2xl border border-[#dfe7f0] bg-white shadow-sm"
                >
                    <div class="border-b border-[#e7edf5] px-7 py-6">
                        <h2 class="text-lg font-extrabold text-[#152b47]">
                            Account Overview
                        </h2>
                        <p class="mt-1 text-sm text-[#657991]">
                            Your current account information
                        </p>
                    </div>

                    <div class="space-y-6 px-7 py-7">
                        <div>
                            <p
                                class="mb-2 text-xs font-bold tracking-wide text-[#72849b] uppercase"
                            >
                                Account name
                            </p>
                            <p
                                class="text-base font-bold break-words text-[#183758]"
                            >
                                {{ user.name }}
                            </p>
                        </div>

                        <div class="border-t border-[#e7edf5] pt-6">
                            <p
                                class="mb-2 text-xs font-bold tracking-wide text-[#72849b] uppercase"
                            >
                                Email address
                            </p>
                            <p
                                class="text-base font-semibold break-all text-[#183758]"
                            >
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-[#cfe0f5] bg-[#edf5ff] px-7 py-7"
                >
                    <div
                        class="mb-4 flex h-11 w-11 items-center justify-center rounded-lg bg-white text-[#1769d7]"
                    >
                        <svg
                            width="23"
                            height="23"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <rect x="5" y="10" width="14" height="11" rx="2" />
                            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                        </svg>
                    </div>

                    <h3 class="text-base font-extrabold text-[#183758]">
                        Your Document Server account
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-[#516b89]">
                        Your profile details are used to identify you throughout
                        your document workspace.
                    </p>
                </section>
            </aside>
        </div>

        <!-- Delete account -->
        <section
            class="mt-6 rounded-2xl border border-[#e8d3d3] bg-white px-7 py-7 shadow-sm lg:px-9"
        >
            <DeleteUser />
        </section>
    </div>
</template>
