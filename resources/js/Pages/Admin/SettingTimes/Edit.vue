<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    shuffleIntervalSeconds: Number,
})

const form = useForm({
    shuffle_interval_seconds: props.shuffleIntervalSeconds ?? 10,
})

const submit = () => {
    form.put(route('admin.setting-times.update'))
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-10">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Paramètres de temps</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Réglages globaux liés au temps et au rythme des animations.
                        </p>
                    </div>

                    <Link
                        :href="route('admin.dashboard')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Retour
                    </Link>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-800">
                                Intervalle global de rotation
                            </label>

                            <input
                                v-model="form.shuffle_interval_seconds"
                                type="number"
                                min="1"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                            />

                            <p class="mt-2 text-xs text-gray-500">
                                Cette valeur s’applique à tous les tableaux visibles sur le site.
                            </p>

                            <div v-if="form.errors.shuffle_interval_seconds" class="mt-2 text-sm text-red-600">
                                {{ form.errors.shuffle_interval_seconds }}
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Enregistrement...</span>
                                <span v-else>Enregistrer</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>