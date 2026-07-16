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

            <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <label
                    v-for="artwork in artworks"
                    :key="artwork.id"
                    class="flex cursor-pointer items-center gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0 hover:bg-gray-50"
                >
                    <input
                        type="checkbox"
                        :checked="isSelected(artwork.id)"
                        class="h-5 w-5 rounded border-gray-300 text-gray-900 focus:ring-gray-700"
                        @change="toggle(artwork.id)"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900">{{ artwork.title }}</p>
                        <p class="text-xs text-gray-500">{{ artwork.is_public ? 'Public' : 'Privé' }}</p>
                    </div>
                </label>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
