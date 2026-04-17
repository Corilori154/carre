<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import Draggable from 'vuedraggable'
import { toPng } from 'html-to-image'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    artworks: {
        type: Array,
        required: true,
    },
})

const selectedArtworkId = ref(props.artworks.length ? props.artworks[0].id : null)

const selectedArtwork = computed(() => {
    return props.artworks.find(artwork => artwork.id === selectedArtworkId.value) || null
})

const availableImages = ref([])
const boardSlots = ref(Array.from({ length: 9 }, () => []))
const boardRef = ref(null)

const BOARD_OUTER_SIZE = 80
const FRAME_SIZE = 7
const INNER_GAP = 3
const TILE_SIZE = 20

const FRAME_PERCENT = (FRAME_SIZE / BOARD_OUTER_SIZE) * 100
const INNER_SIZE = BOARD_OUTER_SIZE - (FRAME_SIZE * 2)
const GAP_PERCENT = (INNER_GAP / INNER_SIZE) * 100
const TILE_PERCENT = (TILE_SIZE / INNER_SIZE) * 100

const innerStyle = {
    padding: `${FRAME_PERCENT}%`,
    boxSizing: 'border-box',
    overflow: 'hidden',
}

const gridStyle = {
    display: 'grid',
    width: 'calc(100% + 1px)',
    height: '100%',
    gridTemplateColumns: `repeat(3, ${TILE_PERCENT}%)`,
    gridTemplateRows: `repeat(3, ${TILE_PERCENT}%)`,
    gap: `${GAP_PERCENT}%`,
    marginRight: '-1px',
}

const touchState = ref({
    startX: 0,
    startY: 0,
    moved: false,
})

const dragState = ref({
    isDragging: false,
})


function onImagePointerUp(slotIndex, event) {
    if (event.pointerType === 'mouse') {
        rotateImage(slotIndex)
    }
}


function updatePageTitle() {
    if (selectedArtwork.value) {
        document.title = `Composition - ${selectedArtwork.value.title}`
    } else {
        document.title = 'Composition'
    }
}

watch(selectedArtworkId, updatePageTitle)

onMounted(() => {
    updatePageTitle()
})

function makeImageInstance(image) {
    return {
        ...image,
        uid: `${image.id}-${crypto.randomUUID()}`,
        rotation: 0,
    }
}

function resetComposer() {
    if (!selectedArtwork.value) {
        availableImages.value = []
        boardSlots.value = Array.from({ length: 9 }, () => [])
        return
    }

    availableImages.value = selectedArtwork.value.images.map(image => makeImageInstance(image))
    boardSlots.value = Array.from({ length: 9 }, () => [])
}

watch(selectedArtworkId, () => {
    resetComposer()
}, { immediate: true })

function cloneImage(image) {
    return makeImageInstance(image)
}

function normalizeSlot(slotIndex) {
    const slot = boardSlots.value[slotIndex]

    if (slot.length > 1) {
        boardSlots.value[slotIndex] = [slot[slot.length - 1]]
    }
}

function rotateImage(slotIndex) {
    const slot = boardSlots.value[slotIndex]

    if (!slot.length) return

    slot[0].rotation = (slot[0].rotation + 90) % 360
}

function onTouchStart(event) {
    const touch = event.changedTouches[0]

    touchState.value = {
        startX: touch.clientX,
        startY: touch.clientY,
        moved: false,
    }
}

function onTouchMove(event) {
    const touch = event.changedTouches[0]
    const deltaX = Math.abs(touch.clientX - touchState.value.startX)
    const deltaY = Math.abs(touch.clientY - touchState.value.startY)

    if (deltaX > 8 || deltaY > 8) {
        touchState.value.moved = true
    }
}

function onTouchEnd(slotIndex) {
    setTimeout(() => {
        if (!touchState.value.moved && !dragState.value.isDragging) {
            rotateImage(slotIndex)
        }
    }, 0)
}

async function exportImage() {
    if (!boardRef.value) return

    try {
        const dataUrl = await toPng(boardRef.value, {
            cacheBust: true,
            pixelRatio: 2,
            skipFonts: true,
            backgroundColor: selectedArtwork.value?.background_color || '#f5f5f4',
        })

        const link = document.createElement('a')
        link.href = dataUrl
        link.download = 'tableau-compose.png'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    } catch (error) {
        console.error('Erreur export image :', error)
        alert('Export échoué')
    }
}
</script>

<template>
    <Head title="Composition" />
    <div class="min-h-screen bg-neutral-950 text-neutral-100">
        <div class="px-4 py-6 md:px-8 md:py-8">
            <header class="mb-6 text-center">
                <h1 class="text-3xl font-bold md:text-4xl">
                    Composer votre tableau
                </h1>

                <p class="mt-2 text-sm text-neutral-400">
                    Glissez les images dans le tableau, cliquez dessus pour les tourner, puis exportez votre création.
                </p>
            </header>

            <div
                v-if="artworks.length"
                class="mx-auto mb-6 max-w-md"
            >
                <label
                    for="artwork-select"
                    class="mb-2 block text-sm font-medium text-neutral-300"
                >
                    Choisir une série d’images
                </label>

                <select
                    id="artwork-select"
                    v-model="selectedArtworkId"
                    class="w-full border border-neutral-700 bg-neutral-900 px-4 py-3 text-white outline-none transition focus:border-neutral-500"
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
                v-if="selectedArtwork"
                class="flex justify-center"
            >
                <div
                    ref="boardRef"
                    class="relative aspect-square shadow-2xl"
                    :style="{
                        width: 'min(92vw, 92vh, 980px)',
                        backgroundColor: selectedArtwork.background_color || '#f5f5f4',
                    }"
                >
                    <div class="absolute inset-0" :style="innerStyle">
                        <div :style="gridStyle">
                            <div
                                v-for="(slot, index) in boardSlots"
                                :key="index"
                                class="relative overflow-hidden bg-white aspect-square"
                            >
                                <Draggable
                                    v-model="boardSlots[index]"
                                    item-key="uid"
                                    :group="{ name: 'board-images', pull: true, put: true }"
                                    :sort="true"
                                    :delay="100"
                                    :delay-on-touch-only="true"
                                    class="h-full w-full"
                                    @change="normalizeSlot(index)"
                                    @start="dragState.isDragging = true"
                                    @end="dragState.isDragging = false"
                                >
                                    <template #item="{ element }">
                                        <img
                                            :src="element.url"
                                            :alt="`Image de composition ${index + 1}`"
                                            class="h-full w-full cursor-pointer object-cover select-none"
                                            :style="{
                                                transform: `rotate(${element.rotation}deg)`,
                                                WebkitUserSelect: 'none',
                                                WebkitTouchCallout: 'none',
                                                userSelect: 'none',
                                            }"
                                            draggable="false"
                                            @contextmenu.prevent
                                            @pointerup="onImagePointerUp(index, $event)"
                                            @touchstart.passive="onTouchStart"
                                            @touchmove.passive="onTouchMove"
                                            @touchend.prevent.stop="onTouchEnd(index)"
                                        >
                                    </template>
                                </Draggable>

                                <div
                                    v-if="!slot.length"
                                    class="pointer-events-none absolute inset-0 flex items-center justify-center text-center text-[10px] sm:text-xs text-neutral-600"
                                >
                                    Déposer
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           <div class="mb-6 mt-6 flex justify-center">
                <button
                    type="button"
                    @click="exportImage"
                    class="rounded-xl bg-white px-6 py-3 font-semibold text-black transition hover:bg-neutral-200"
                >
                    Télécharger le tableau en image
                </button>
            </div> 

            <div
                v-if="selectedArtwork"
                class="mt-10"
            >
                <h2 class="mb-4 text-center text-lg font-semibold">
                    Images disponibles
                </h2>

                <Draggable
                    :model-value="availableImages"
                    item-key="uid"
                    :group="{ name: 'board-images', pull: 'clone', put: false }"
                    :sort="false"
                    :clone="cloneImage"
                    :delay="100"
                    :delay-on-touch-only="true"
                    class="flex flex-wrap justify-center gap-3 sm:gap-4"
                >
                    <template #item="{ element }">
                        <div class="h-20 w-20 overflow-hidden border border-neutral-700 bg-neutral-900 shadow sm:h-24 sm:w-24">
                            <img
                                :src="element.url"
                                :alt="`Image ${element.position}`"
                                class="h-full w-full object-cover"
                                draggable="false"
                            >
                        </div>
                    </template>
                </Draggable>
            </div>

            <div
                v-if="!artworks.length"
                class="mx-auto mt-12 max-w-xl border border-neutral-800 bg-neutral-900 p-6 text-center text-neutral-300"
            >
                Aucun tableau n’est disponible pour le moment.
            </div>
        </div>
    </div>
</template>

<style scoped>
.drag-ghost {
    opacity: 0.2;
}

.drag-chosen {
    opacity: 1;
}

.drag-dragging {
    opacity: 0.9;
}
</style>