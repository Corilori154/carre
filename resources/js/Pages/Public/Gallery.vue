<script setup>
import { computed, ref } from 'vue'
import AutoArtworkGrid from '@/Components/AutoArtworkGrid.vue'

const props = defineProps({
    artworks: {
        type: Array,
        required: true,
    },
    shuffleIntervalSeconds: {
        type: Number,
        default: 10,
    },
})

const selectedArtworkId = ref(props.artworks.length ? props.artworks[0].id : null)

const selectedArtwork = computed(() => {
    return props.artworks.find(artwork => artwork.id === selectedArtworkId.value) || null
})
</script>

<template>
    <div class="min-h-screen bg-neutral-950 text-neutral-100">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <header class="mb-10">
                <h1 class="text-3xl font-bold md:text-4xl">Galerie</h1>
                <p class="mt-3 max-w-2xl text-sm text-neutral-400 md:text-base">
                    Sélectionnez un tableau pour découvrir sa grille animée.
                </p>
            </header>

            <div
                v-if="artworks.length === 0"
                class="rounded-2xl border border-neutral-800 bg-neutral-900 p-8 text-neutral-300"
            >
                Aucun tableau n’est disponible pour le moment.
            </div>

            <div v-else class="space-y-8">
                <div class="max-w-md rounded-2xl border border-neutral-800 bg-neutral-900/70 p-4">
                    <label for="artwork-select" class="mb-2 block text-sm font-medium text-neutral-300">
                        Choisir un tableau
                    </label>

                    <select
                        id="artwork-select"
                        v-model="selectedArtworkId"
                        class="w-full rounded-xl border border-neutral-700 bg-neutral-950 px-4 py-3 text-white shadow-sm outline-none transition focus:border-neutral-500"
                    >
                        <option
                            v-for="artwork in artworks"
                            :key="artwork.id"
                            :value="artwork.id"
                        >
                            {{ artwork.title }}
                        </option>
                    </select>
                </div>

                <section
                    v-if="selectedArtwork"
                    class="rounded-3xl border border-neutral-800 bg-neutral-900/60 p-6 shadow-xl"
                >
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-white">
                            {{ selectedArtwork.title }}
                        </h2>
                        <p class="mt-2 text-sm text-neutral-400">
                            Tableau composé de 9 images.
                        </p>
                    </div>

                    <AutoArtworkGrid
                        :key="selectedArtwork.id"
                        :images="selectedArtwork.images"
                        :initial-interval-seconds="shuffleIntervalSeconds"
                    />
                </section>
            </div>
        </div>
    </div>
</template>