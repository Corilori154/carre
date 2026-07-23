<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    gallery: Object,
    artworks: Array,
})

const form = useForm({
    artwork_ids: props.artworks.filter(artwork => artwork.selected).map(artwork => artwork.id),
})

const isSelected = id => form.artwork_ids.includes(id)

const toggle = id => {
    form.artwork_ids = isSelected(id)
        ? form.artwork_ids.filter(artworkId => artworkId !== id)
        : [...form.artwork_ids, id]
}

const submit = () => form.put(route('admin.galleries.artworks.update', props.gallery.slug), {
    preserveScroll: true,
})
</script>

<template>
    <Head :title="`Tableaux - ${gallery.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ gallery.name }}</h2>
                    <p class="text-sm text-gray-500">Sélection des tableaux visibles</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.artworks.index')" class="rounded-lg border bg-white px-4 py-2 text-sm font-medium text-gray-700">Tableaux</Link>
                    <Link :href="route('admin.composer.edit')" class="rounded-lg border bg-white px-4 py-2 text-sm font-medium text-gray-700">Composer tableau</Link>
                    <Link :href="route('admin.galleries.index')" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white">Galeries clientes</Link>
                    <Link :href="route('admin.setting-times.edit')" class="rounded-lg border bg-white px-4 py-2 text-sm font-medium text-gray-700">Temps</Link>
                </div>
            </div>
        </template>

        <form class="mx-auto max-w-6xl p-6" @submit.prevent="submit">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link :href="route('admin.galleries.index')" class="text-sm text-gray-600 hover:underline">← Retour aux galeries</Link>
                    <h1 class="mt-2 text-2xl font-bold">Choisir les tableaux</h1>
                    <p class="mt-1 text-sm text-gray-500">Par défaut, aucun tableau n’est visible. Cliquez sur les tableaux à publier pour cette galerie.</p>
                </div>
                <button :disabled="form.processing" class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
                    Enregistrer la sélection ({{ form.artwork_ids.length }})
                </button>
            </div>

            <p v-if="form.errors.artwork_ids" class="mb-4 text-sm text-red-600">{{ form.errors.artwork_ids }}</p>

            <div v-if="!artworks.length" class="rounded-xl border border-dashed p-8 text-center text-gray-500">
                Aucun tableau n’a encore été créé.
            </div>

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <label
                    v-for="artwork in artworks"
                    :key="artwork.id"
                    class="group relative cursor-pointer overflow-hidden rounded-xl border-2 bg-white shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg"
                    :class="isSelected(artwork.id)
                        ? 'selected-artwork -translate-y-0.5 scale-[1.02] border-emerald-500 shadow-lg shadow-emerald-100'
                        : 'border-gray-200 hover:border-gray-300'"
                >
                    <input
                        type="checkbox"
                        :checked="isSelected(artwork.id)"
                        class="peer sr-only"
                        @change="toggle(artwork.id)"
                    />

                    <div
                        class="grid aspect-square grid-cols-3 grid-rows-3 gap-1 p-2 transition duration-300 group-hover:brightness-95"
                        :style="{ backgroundColor: artwork.background_color || '#f5f5f4' }"
                    >
                        <div
                            v-for="image in artwork.images"
                            :key="image.id"
                            class="min-h-0 min-w-0 overflow-hidden"
                        >
                            <img
                                :src="image.url"
                                :alt="`Aperçu de ${artwork.title}`"
                                class="h-full w-full object-cover"
                            />
                        </div>
                    </div>

                    <div
                        class="absolute right-3 top-3 flex h-7 w-7 scale-75 items-center justify-center rounded-full bg-emerald-500 text-white opacity-0 shadow-md transition-all duration-300"
                        :class="isSelected(artwork.id) ? 'scale-100 opacity-100' : ''"
                        aria-hidden="true"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                            <path d="m5 10 3 3 7-7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <div
                        class="border-t border-gray-100 px-3 py-3 text-center transition-colors duration-300"
                        :class="isSelected(artwork.id) ? 'bg-emerald-50' : 'bg-white'"
                    >
                        <p class="truncate text-sm font-semibold text-gray-900" :title="artwork.title">{{ artwork.title }}</p>
                    </div>
                </label>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes selected-pop {
    0% { transform: scale(1); }
    45% { transform: scale(1.045); }
    100% { transform: scale(1.02) translateY(-0.125rem); }
}

.selected-artwork {
    animation: selected-pop 320ms ease-out;
}

@media (prefers-reduced-motion: reduce) {
    .selected-artwork {
        animation: none;
    }
}
</style>
