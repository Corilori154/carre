<script setup>
import { Link, router } from '@inertiajs/vue3'

defineProps({
    artworks: Array,
})

const destroyArtwork = (id) => {
    if (confirm('Supprimer ce tableau ?')) {
        router.delete(route('admin.artworks.destroy', id))
    }
}
</script>

<template>
    <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
            </h2>

            <div class="flex items-center gap-3">
                      <!-- Lien vers artworks -->
                    <Link
                        :href="route('admin.artworks.index')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Tableaux
                    </Link>
                    
                    <Link
                        :href="route('admin.composer.edit')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Composer tableau
                    </Link>

                    <!-- Lien vers paramètres temps -->
                    <Link
                        :href="route('admin.setting-times.edit')"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                    >
                        Temps
                    </Link>
            </div>
    </div>
    
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Tableaux</h1>

            <Link
                :href="route('admin.artworks.create')"
                class="rounded bg-black px-4 py-2 text-white"
            >
                Créer un tableau
            </Link>
        </div>

        <div v-if="artworks.length === 0" class="rounded border p-6">
            Aucun tableau pour le moment.
        </div>

        <div v-else class="space-y-6">
            <div
                v-for="artwork in artworks"
                :key="artwork.id"
                class="rounded border p-4"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">{{ artwork.title }}</h2>
                        <p class="text-sm text-gray-500">
                            {{ artwork.images.length }} images
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="destroyArtwork(artwork.id)"
                        class="rounded border border-red-600 px-3 py-2 text-red-600"
                    >
                        Supprimer
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-1 max-w-[720px]">
                    <img
                        v-for="image in artwork.images"
                        :key="image.id"
                        :src="image.url"
                        :alt="`Image ${image.position}`"
                        class="aspect-square w-full rounded-md object-cover"
                    />
                </div>
            </div>
        </div>
    </div>
</template>