<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Dashboard admin</h1>

            <Link
                :href="route('admin.setting-times.edit')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6l4 2"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                </svg>
                Paramètres de temps
            </Link>
        </div>
    </div>
</template>