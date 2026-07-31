<script setup>
import { computed, ref, watch, onMounted, nextTick } from 'vue'
import Draggable from 'vuedraggable'
import { toPng } from 'html-to-image'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    artworks: {
        type: Array,
        required: true,
    },
    gallery: {
        type: Object,
        default: null,
    },
})

const selectedArtworkId = ref(props.artworks.length ? props.artworks[0].id : null)

const selectedArtwork = computed(() => {
    return props.artworks.find(artwork => artwork.id === selectedArtworkId.value) || null
})

const availableImages = ref([])
const boardSlots = ref(Array.from({ length: 9 }, () => []))
const boardRef = ref(null)
const isExporting = ref(false)
const exportError = ref('')
const showIdentityForm = ref(false)
const identity = ref({ first_name: '', last_name: '', email: '' })
const identityErrors = ref({})

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



function requestExport() {
    exportError.value = ''
    identityErrors.value = {}
    showIdentityForm.value = true
}

async function exportImage() {
    if (!boardRef.value || !selectedArtwork.value) return

    try {
        isExporting.value = true
        exportError.value = ''
        boardRef.value.classList.add('exporting-board')
        await nextTick()

        const dataUrl = await toPng(boardRef.value, {
            cacheBust: true,
            pixelRatio: 2,
            skipFonts: true,
            backgroundColor: selectedArtwork.value?.background_color || '#f5f5f4',
        })

        const claimRoute = props.gallery
            ? route('galleries.generated-compositions.store', props.gallery.slug)
            : route('generated-compositions.store')

        await axios.post(claimRoute, {
            first_name: identity.value.first_name,
            last_name: identity.value.last_name,
            email: identity.value.email,
            artwork_id: selectedArtwork.value.id,
            slots: boardSlots.value.map(slot => slot.length ? {
                image_id: slot[0].id,
                rotation: slot[0].rotation,
            } : null),
        })

        const link = document.createElement('a')
        link.href = dataUrl
        link.download = 'tableau-compose.png'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        showIdentityForm.value = false
        identity.value = { first_name: '', last_name: '', email: '' }
    } catch (error) {
        console.error('Erreur export image :', error)
        identityErrors.value = error.response?.data?.errors || {}
        exportError.value = error.response?.data?.message
            || 'Le téléchargement a échoué. Veuillez réessayer.'
    } finally {
        if (boardRef.value) {
            boardRef.value.classList.remove('exporting-board')
        }

        isExporting.value = false
        await nextTick()
    }
}
</script>

<template>
    <Head :title="gallery ? `Composition - ${gallery.name}` : 'Composition'" />

    <div class="min-h-screen bg-neutral-950 text-neutral-100">
        <div class="px-4 py-6 md:px-8 md:py-8">
            <header class="mb-6 text-center">
                <h1 class="text-3xl font-bold md:text-4xl">
                    Composer votre tableau
                </h1>
                <p v-if="gallery" class="mt-2 text-sm text-gray-500">{{ gallery.name }}</p>

                <p class="mt-2 text-sm text-neutral-400">
                    Glissez les images dans le tableau, tournez-les avec le bouton ↻, puis exportez votre création.
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
                    class="relative aspect-square overflow-hidden shadow-2xl"
                    :style="{
                        width: 'min(92vw, 92vh, 980px)',
                        backgroundColor: selectedArtwork.background_color || '#f5f5f4',
                    }"
                >
                    <div class="absolute inset-0" :style="innerStyle">
                        <div :style="gridStyle" data-export-grid>
                            <div
                                v-for="(slot, index) in boardSlots"
                                :key="index"
                                class="relative aspect-square overflow-hidden bg-white"
                            >
                                <Draggable
                                    v-model="boardSlots[index]"
                                    item-key="uid"
                                    :group="{ name: 'board-images', pull: true, put: true }"
                                    :sort="true"
                                    :delay="100"
                                    :delay-on-touch-only="true"
                                    :touch-start-threshold="4"
                                    filter=".rotate-btn"
                                    :prevent-on-filter="true"
                                    class="h-full w-full"
                                    ghost-class="drag-ghost"
                                    chosen-class="drag-chosen"
                                    drag-class="drag-dragging"
                                    @change="normalizeSlot(index)"
                                >
                                    <template #item="{ element }">
                                        <div class="relative h-full w-full leading-none">
                                            <img
                                                :src="element.url"
                                                :alt="`Image de composition ${index + 1}`"
                                                class="block h-full w-full object-cover select-none"
                                                :style="{
                                                    transform: `rotate(${element.rotation}deg)`,
                                                    WebkitUserSelect: 'none',
                                                    WebkitTouchCallout: 'none',
                                                    userSelect: 'none',
                                                }"
                                                draggable="false"
                                                @contextmenu.prevent
                                            >

                                            <button
                                                v-if="!isExporting"
                                                type="button"
                                                class="rotate-btn absolute bottom-1 right-1 z-30 flex h-9 w-9 items-center justify-center rounded-full bg-black/75 text-lg font-bold text-white shadow-md active:scale-95"
                                                @pointerdown.stop
                                                @touchstart.stop
                                                @click.stop.prevent="rotateImage(index)"
                                            >
                                                ↻
                                            </button>
                                        </div>
                                    </template>
                                </Draggable>

                                <div
                                    v-if="!slot.length"
                                    class="pointer-events-none absolute inset-0 flex items-center justify-center text-center text-[10px] text-neutral-600 sm:text-xs"
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
                    @click="requestExport"
                    :disabled="isExporting"
                    class="rounded-xl bg-white px-6 py-3 font-semibold text-black transition hover:bg-neutral-200 disabled:cursor-wait disabled:opacity-60"
                >
                    {{ isExporting ? 'Vérification…' : 'Télécharger le tableau en image' }}
                </button>
            </div>

            <div v-if="exportError" class="mx-auto mb-6 max-w-2xl rounded-lg border border-red-800 bg-red-950/50 p-4 text-center text-sm text-red-200">
                {{ exportError }}
            </div>

            <div v-if="showIdentityForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4" @click.self="!isExporting && (showIdentityForm = false)">
                <form class="w-full max-w-md rounded-2xl border border-neutral-700 bg-neutral-900 p-6 shadow-2xl" @submit.prevent="exportImage">
                    <h2 class="text-xl font-bold text-white">Vos informations</h2>
                    <p class="mt-2 text-sm text-neutral-400">Renseignez vos coordonnées pour télécharger votre tableau.</p>
                    <p v-if="exportError" class="mt-4 rounded-lg border border-red-800 bg-red-950/50 p-3 text-sm text-red-200">{{ exportError }}</p>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="download-first-name" class="mb-1 block text-sm font-medium">Prénom *</label>
                            <input id="download-first-name" v-model.trim="identity.first_name" required autocomplete="given-name" class="w-full rounded-lg border-neutral-700 bg-neutral-950 text-white" />
                            <p v-if="identityErrors.first_name" class="mt-1 text-sm text-red-400">{{ identityErrors.first_name[0] }}</p>
                        </div>
                        <div>
                            <label for="download-last-name" class="mb-1 block text-sm font-medium">Nom *</label>
                            <input id="download-last-name" v-model.trim="identity.last_name" required autocomplete="family-name" class="w-full rounded-lg border-neutral-700 bg-neutral-950 text-white" />
                            <p v-if="identityErrors.last_name" class="mt-1 text-sm text-red-400">{{ identityErrors.last_name[0] }}</p>
                        </div>
                        <div>
                            <label for="download-email" class="mb-1 block text-sm font-medium">Adresse e-mail *</label>
                            <input id="download-email" v-model.trim="identity.email" type="email" required autocomplete="email" class="w-full rounded-lg border-neutral-700 bg-neutral-950 text-white" />
                            <p v-if="identityErrors.email" class="mt-1 text-sm text-red-400">{{ identityErrors.email[0] }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" :disabled="isExporting" class="rounded-lg border border-neutral-600 px-4 py-2 text-sm text-neutral-200" @click="showIdentityForm = false">Annuler</button>
                        <button type="submit" :disabled="isExporting" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-black disabled:opacity-60">{{ isExporting ? 'Téléchargement…' : 'Télécharger' }}</button>
                    </div>
                </form>
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
                    :touch-start-threshold="4"
                    class="flex flex-wrap justify-center gap-3 sm:gap-4"
                    ghost-class="drag-ghost"
                    chosen-class="drag-chosen"
                    drag-class="drag-dragging"
                >
                    <template #item="{ element }">
                        <div class="h-20 w-20 overflow-hidden border border-neutral-700 bg-neutral-900 shadow sm:h-24 sm:w-24 leading-none">
                            <img
                                :src="element.url"
                                :alt="`Image ${element.position}`"
                                class="block h-full w-full object-cover select-none"
                                :style="{
                                    WebkitUserSelect: 'none',
                                    WebkitTouchCallout: 'none',
                                    userSelect: 'none',
                                }"
                                draggable="false"
                                @contextmenu.prevent
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

button {
    -webkit-touch-callout: none;
}

:deep(.exporting-board [data-export-grid]) {
    width: 100% !important;
    margin-right: 0 !important;
}
</style>
