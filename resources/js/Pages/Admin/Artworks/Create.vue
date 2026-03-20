<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({
    title: '',
    images: Array(9).fill(null),
})

const updateImage = (index, event) => {
    const file = event.target.files[0]
    form.images[index] = file
}

const submit = () => {
    form.post(route('admin.artworks.store'))
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-10">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Créer un tableau</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Téléverse 9 images pour composer une œuvre.
                        </p>
                    </div>

                    <Link
                        :href="route('admin.artworks.index')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Retour à la liste
                    </Link>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-8">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-800">
                                Titre
                            </label>

                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Exemple : Tableau été 2025"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                            />

                            <div v-if="form.errors.title" class="mt-2 text-sm text-red-600">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <div>
                            <div class="mb-4">
                                <h2 class="text-lg font-semibold text-gray-900">Les 9 images du tableau</h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Une image par case de la grille 3 × 3.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <div
                                    v-for="(image, index) in form.images"
                                    :key="index"
                                    class="rounded-2xl border border-gray-200 bg-gray-50 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/30"
                                >
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-800">
                                            Image {{ index + 1 }}
                                        </span>

                                        <span class="rounded-full bg-white px-2 py-1 text-xs text-gray-500 shadow-sm">
                                            Case {{ index + 1 }}
                                        </span>
                                    </div>

                                    <label
                                        :for="`image-${index}`"
                                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white px-4 py-8 text-center transition hover:border-indigo-400 hover:bg-indigo-50"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="mb-3 h-10 w-10 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 16V8m0 0-3 3m3-3 3 3M4 16.5A2.5 2.5 0 0 0 6.5 19h11a2.5 2.5 0 0 0 2.5-2.5"
                                            />
                                        </svg>

                                        <span class="text-sm font-medium text-gray-700">
                                            Cliquer pour téléverser
                                        </span>

                                        <span class="mt-1 text-xs text-gray-500">
                                            JPG, PNG ou WEBP
                                        </span>

                                        <span
                                            v-if="form.images[index]"
                                            class="mt-3 max-w-full break-all text-xs font-medium text-indigo-600"
                                        >
                                            {{ form.images[index].name }}
                                        </span>
                                    </label>

                                    <input
                                        :id="`image-${index}`"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="updateImage(index, $event)"
                                    />

                                    <div
                                        v-if="form.errors[`images.${index}`]"
                                        class="mt-2 text-sm text-red-600"
                                    >
                                        {{ form.errors[`images.${index}`] }}
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.errors.images" class="mt-3 text-sm text-red-600">
                                {{ form.errors.images }}
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                            <Link
                                :href="route('admin.artworks.index')"
                                class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                            >
                                Annuler
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span v-if="form.processing">Enregistrement...</span>
                                <span v-else>Créer le tableau</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>