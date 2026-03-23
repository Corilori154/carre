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
        <div class="mx-auto max-w-5xl px-4 py-6 md:px-6 md:py-8">
            <header class="mb-6">
                <h1 class="text-2xl font-bold md:text-3xl">Galerie</h1>
                <p class="mt-2 max-w-2xl text-sm text-neutral-400">
                    Sélectionnez un tableau pour découvrir sa grille animée.
                </p>
            </header>

            <div
                v-if="artworks.length === 0"
                class="rounded-2xl border border-neutral-800 bg-neutral-900 p-6 text-neutral-300"
            >
                Aucun tableau n’est disponible pour le moment.
            </div>

            <div v-else class="space-y-6">
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
                    class="rounded-3xl border border-neutral-800 bg-neutral-900/60 p-4 md:p-5 shadow-xl"
                >
                    <div class="mb-4 text-center">
                        <h2 class="text-xl font-semibold text-white md:text-2xl">
                            {{ selectedArtwork.title }}
                        </h2>
                        <p class="mt-1 text-sm text-neutral-400">
                            Tableau composé de 9 images.
                        </p>
                    </div>

                    <div class="mx-auto max-w-[680px]">
                        <AutoArtworkGrid
                            :key="selectedArtwork.id"
                            :images="selectedArtwork.images"
                            :initial-interval-seconds="shuffleIntervalSeconds"
                        />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>