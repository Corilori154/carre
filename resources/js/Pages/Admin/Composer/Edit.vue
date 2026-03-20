<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

const props = defineProps({
    artworks: Array,
    selectedArtworkId: Number,
    selectedArtwork: Object,
})

const form = useForm({
    artwork_id: props.selectedArtworkId ?? null,
    slots: Array.from({ length: 9 }, () => ({
        image_id: null,
        rotation: 0,
    })),
})

const buildSlotsFromArtwork = (artwork) => {
    const emptySlots = Array.from({ length: 9 }, () => ({
        image_id: null,
        rotation: 0,
    }))

    if (!artwork?.images) {
        return emptySlots
    }

    artwork.images.forEach((image, index) => {
        const position = image.composition_position
            ? image.composition_position - 1
            : index

        if (position >= 0 && position < 9) {
            emptySlots[position] = {
                image_id: image.id,
                rotation: image.rotation ?? 0,
            }
        }
    })

    return emptySlots
}

watch(
    () => props.selectedArtwork,
    (artwork) => {
        form.artwork_id = props.selectedArtworkId ?? null
        form.slots = buildSlotsFromArtwork(artwork)
    },
    { immediate: true }
)

const imageMap = computed(() => {
    const map = {}

    props.selectedArtwork?.images?.forEach((image) => {
        map[image.id] = image
    })

    return map
})

const availableImages = computed(() => {
    return props.selectedArtwork?.images ?? []
})

const loadArtwork = () => {
    if (!form.artwork_id) return

    router.get(
        route('admin.composer.edit'),
        { artwork_id: form.artwork_id },
        { preserveState: true, preserveScroll: true }
    )
}

const dragPayload = (imageId, source, fromSlotIndex = null) => {
    return JSON.stringify({
        imageId,
        source,
        fromSlotIndex,
    })
}

const onDragStartFromBank = (event, imageId) => {
    event.dataTransfer.setData('text/plain', dragPayload(imageId, 'bank'))
}

const onDragStartFromSlot = (event, imageId, fromSlotIndex) => {
    event.dataTransfer.setData(
        'text/plain',
        dragPayload(imageId, 'slot', fromSlotIndex)
    )
}

const placeImageInSlot = (imageId, slotIndex, fromSlotIndex = null) => {
    const existingIndex = form.slots.findIndex(slot => slot.image_id === imageId)
    const targetSlot = form.slots[slotIndex]

    if (fromSlotIndex !== null && fromSlotIndex === slotIndex) {
        return
    }

    const draggedImage = form.slots.find(slot => slot.image_id === imageId)
    const draggedRotation = draggedImage?.rotation ?? 0

    if (existingIndex !== -1) {
        form.slots[existingIndex] = {
            image_id: null,
            rotation: 0,
        }
    }

    if (fromSlotIndex !== null && targetSlot.image_id) {
        form.slots[fromSlotIndex] = {
            image_id: targetSlot.image_id,
            rotation: targetSlot.rotation,
        }
    }

    form.slots[slotIndex] = {
        image_id: imageId,
        rotation: draggedRotation,
    }
}

const onDropOnSlot = (event, slotIndex) => {
    const raw = event.dataTransfer.getData('text/plain')
    if (!raw) return

    const payload = JSON.parse(raw)

    placeImageInSlot(
        payload.imageId,
        slotIndex,
        payload.source === 'slot' ? payload.fromSlotIndex : null
    )
}

const clearSlot = (slotIndex) => {
    form.slots[slotIndex] = {
        image_id: null,
        rotation: 0,
    }
}

const rotateSlot = (slotIndex) => {
    const slot = form.slots[slotIndex]
    if (!slot.image_id) return

    slot.rotation = (slot.rotation + 90) % 360
}

const resetComposition = () => {
    form.slots = buildSlotsFromArtwork(props.selectedArtwork)
}

const submit = () => {
    form.put(route('admin.composer.update'))
}
</script>

<template>
    <Head title="Composer un tableau" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Composer un tableau
                </h2>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.artworks.index')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Artworks
                    </Link>

                    <Link
                        :href="route('admin.setting-times.edit')"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                    >
                        Temps
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="max-w-sm">
                        <label class="mb-2 block text-sm font-semibold text-gray-800">
                            Choisir un tableau
                        </label>

                        <select
                            v-model="form.artwork_id"
                            @change="loadArtwork"
                            class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
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

                <div
                    v-if="!selectedArtwork"
                    class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    Aucun tableau chargé.
                </div>

                <template v-else>
                    <!-- COMPOSITION EN HAUT -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">
                                    Composition
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Glisse les images dans la grille puis clique sur ↻ pour les tourner.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="resetComposition"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                            >
                                Réinitialiser
                            </button>
                        </div>

                        <div class="mx-auto grid max-w-[540px] grid-cols-3 gap-3">
                            <div
                                v-for="(slot, index) in form.slots"
                                :key="index"
                                @dragover.prevent
                                @drop="onDropOnSlot($event, index)"
                                class="relative aspect-square overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50"
                            >
                                <template v-if="slot.image_id">
                                    <img
                                        :src="imageMap[slot.image_id]?.url"
                                        alt=""
                                        class="h-full w-full object-cover cursor-pointer"
                                        :style="{ transform: `rotate(${slot.rotation}deg)` }"
                                        draggable="true"
                                        @dragstart="onDragStartFromSlot($event, slot.image_id, index)"
                                        @click.stop="rotateSlot(index)"
                                    />

                                    <button
                                        type="button"
                                        @click.stop="rotateSlot(index)"
                                        class="absolute right-2 top-2 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-black/80 text-sm font-bold text-white shadow-lg transition hover:bg-black"
                                        title="Rotation 90°"
                                    >
                                        ↻
                                    </button>

                                    <button
                                        type="button"
                                        @click.stop="clearSlot(index)"
                                        class="absolute left-2 top-2 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-sm font-bold text-gray-700 shadow-lg transition hover:bg-white"
                                        title="Retirer l'image"
                                    >
                                        ×
                                    </button>
                                </template>

                                <div
                                    v-else
                                    class="flex h-full items-center justify-center text-sm font-medium text-gray-400"
                                >
                                    Case {{ index + 1 }}
                                </div>
                            </div>
                        </div>

                        <div v-if="form.errors.slots" class="mt-4 text-sm text-red-600">
                            {{ form.errors.slots }}
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Enregistrement...</span>
                                <span v-else>Enregistrer la composition</span>
                            </button>
                        </div>
                    </div>

                    <!-- IMAGES DISPONIBLES EN BAS -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">
                            Images disponibles
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Fais glisser une image vers la grille.
                        </p>

                        <div class="mt-4 mx-auto grid max-w-[720px] grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-9">
                            <div
                                v-for="image in availableImages"
                                :key="image.id"
                                draggable="true"
                                @dragstart="onDragStartFromBank($event, image.id)"
                                class="group cursor-grab overflow-hidden rounded-xl border border-gray-200 bg-gray-50 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
                            >
                                <div class="aspect-square overflow-hidden">
                                    <img
                                        :src="image.url"
                                        alt=""
                                        class="h-full w-full object-cover transition group-hover:scale-[1.02]"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>