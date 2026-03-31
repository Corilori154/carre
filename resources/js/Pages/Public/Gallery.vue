<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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
const isFullscreen = ref(false)
const fullscreenContainerRef = ref(null)
const liveGeneratedCount = ref(0)

const selectedArtwork = computed(() => {
    return props.artworks.find(artwork => artwork.id === selectedArtworkId.value) || null
})

function updateLiveGeneratedCount() {
    liveGeneratedCount.value = selectedArtwork.value?.generated_count ?? 0
}

function handleShuffleStart() {
    liveGeneratedCount.value += 1
}

async function toggleFullscreen() {
    const container = fullscreenContainerRef.value

    if (!container) return

    try {
        if (document.fullscreenElement) {
            await document.exitFullscreen()
        } else {
            await container.requestFullscreen()
        }
    } catch (error) {
        console.error('Erreur fullscreen:', error)
    }
}

function handleFullscreenChange() {
    isFullscreen.value = document.fullscreenElement === fullscreenContainerRef.value
}

watch(selectedArtworkId, () => {
    updateLiveGeneratedCount()
})

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange)
    updateLiveGeneratedCount()
})

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
})
</script>

<template>
    <div class="min-h-screen bg-neutral-950 text-neutral-100">
        <div
            v-if="!isFullscreen"
            class="mx-auto max-w-5xl px-4 py-6 md:px-6 md:py-8"
        >
            <header class="mb-6">
                <h1 class="text-2xl font-bold md:text-3xl">Galerie</h1>
                <p class="mt-2 max-w-2xl text-sm text-neutral-400">
                    Sélectionnez un tableau pour découvrir sa grille animée.
                </p>
            </header>

            <div
                v-if="artworks.length === 0"
                class="border border-neutral-800 bg-neutral-900 p-6 text-neutral-300"
            >
                Aucun tableau n’est disponible pour le moment.
            </div>

            <div v-else class="space-y-6">
                <div class="max-w-md border border-neutral-800 bg-neutral-900/70 p-4">
                    <label for="artwork-select" class="mb-2 block text-sm font-medium text-neutral-300">
                        Choisir un tableau
                    </label>

                    <select
                        id="artwork-select"
                        v-model="selectedArtworkId"
                        class="w-full border border-neutral-700 bg-neutral-950 px-4 py-3 text-white shadow-sm outline-none transition focus:border-neutral-500"
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
            </div>
        </div>

        <div
            v-if="selectedArtwork"
            ref="fullscreenContainerRef"
            :class="isFullscreen
                ? 'min-h-screen w-full bg-black flex items-center justify-center p-6'
                : 'mx-auto max-w-5xl px-4 pb-6 md:px-6 md:pb-8'"
        >
            <section
                :class="isFullscreen
                    ? 'w-full max-w-[min(92vw,92vh)]'
                    : 'border border-neutral-800 bg-neutral-900/60 p-4 shadow-xl md:p-5'"
            >
                <div
                    v-if="!isFullscreen"
                    class="mb-4 text-center"
                >
                    
                <h2 class="text-xl font-semibold text-white md:text-2xl">
                        série carré magique
                    </h2>
                    
                <h2 class="text-xl font-semibold text-white md:text-2xl">
                        {{ selectedArtwork.title }}
                    </h2>

                    <p class="mt-1 text-sm text-neutral-400">
                        Tableau composé de 9 images.
                    </p>

                    <p class="mt-2 text-sm text-neutral-300">
                        Nombre de tableaux générés depuis sa création :
                        <span class="font-semibold text-white">
                            {{ liveGeneratedCount }}
                        </span>
                    </p>
                    <p class="mt-2 text-sm text-neutral-300">
                        Date de création :
                        <span class="font-semibold text-white">
                            {{ selectedArtwork.created_at }}
                        </span>
                    </p>
                </div>

                <div class="mx-auto w-full">
                    <AutoArtworkGrid
                        :key="`${selectedArtwork.id}-${isFullscreen ? 'fullscreen' : 'normal'}`"
                        :images="selectedArtwork.images"
                        :initial-interval-seconds="shuffleIntervalSeconds"
                        :show-controls="!isFullscreen"
                        :show-manual-buttons="false"
                        :allow-video-download="!isFullscreen"
                        :show-fullscreen-button="true"
                        :is-fullscreen-active="isFullscreen"
                        :background-color="selectedArtwork.background_color"
                        @fullscreen="toggleFullscreen"
                        @shuffle-start="handleShuffleStart"
                    />
                </div>
            </section>
        </div>
    </div>
</template>