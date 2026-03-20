<script setup>
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
    artworks: Array,
    selectedArtworkId: Number,
    selectedArtwork: Object,
})

const selectedId = ref(props.selectedArtworkId ?? null)

watch(
    () => props.selectedArtworkId,
    (value) => {
        selectedId.value = value
    }
)

const loadArtwork = () => {
    if (!selectedId.value) return

    router.get(
        route('composed-gallery'),
        { artwork_id: selectedId.value },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}
</script>

<template>
    <div class="min-h-screen bg-neutral-950 text-neutral-100">
        <div class="mx-auto max-w-6xl px-6 py-10">
            <header class="mb-10">
                <h1 class="text-3xl font-bold md:text-4xl">
                    Galerie composée
                </h1>
                <p class="mt-3 max-w-2xl text-sm text-neutral-400 md:text-base">
                    Découvrez les compostions faites manuellement
                </p>
            </header>

            <div class="mb-8 max-w-md rounded-2xl border border-neutral-800 bg-neutral-900/70 p-4">
                <label for="artwork-select" class="mb-2 block text-sm font-medium text-neutral-300">
                    Choisir un tableau
                </label>

                <select
                    id="artwork-select"
                    v-model="selectedId"
                    @change="loadArtwork"
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

            <div
                v-if="!selectedArtwork"
                class="rounded-2xl border border-neutral-800 bg-neutral-900 p-8 text-neutral-300"
            >
                Aucun tableau disponible.
            </div>

            <section
                v-else
                class="rounded-3xl border border-neutral-800 bg-neutral-900/60 p-6 shadow-xl"
            >
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-white">
                        {{ selectedArtwork.title }}
                    </h2>
                    <p class="mt-2 text-sm text-neutral-400">
                        Composition enregistrée depuis l’admin.
                    </p>
                </div>

                <div class="mx-auto grid max-w-[700px] grid-cols-3 gap-4">
                    <div
                        v-for="(slot, index) in selectedArtwork.slots"
                        :key="index"
                        class="relative aspect-square overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900 shadow"
                    >
                        <img
                            v-if="slot"
                            :src="slot.url"
                            :alt="`Image ${slot.position}`"
                            class="h-full w-full object-cover"
                            :style="{ transform: `rotate(${slot.rotation}deg)` }"
                        />

                        <div
                            v-else
                            class="flex h-full items-center justify-center text-sm text-neutral-500"
                        >
                            Case vide
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>