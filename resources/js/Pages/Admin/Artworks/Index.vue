<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    artworks: Array,
})

const selectedArtworkId = ref(props.artworks.length ? props.artworks[0].id : null)
const artworkRef = ref(null)

const selectedArtwork = computed(() => {
    return props.artworks.find(artwork => artwork.id === selectedArtworkId.value) || null
})

const destroyArtwork = (id) => {
    if (confirm('Supprimer ce tableau ?')) {
        router.delete(route('admin.artworks.destroy', id))
    }
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image()
        img.crossOrigin = 'anonymous'

        img.onload = () => resolve(img)
        img.onerror = () => reject(new Error(`Impossible de charger l'image : ${src}`))

        img.src = src
    })
}

function drawImageCover(ctx, img, x, y, w, h) {
    const imgRatio = img.width / img.height
    const boxRatio = w / h

    let sx = 0
    let sy = 0
    let sw = img.width
    let sh = img.height

    if (imgRatio > boxRatio) {
        sw = img.height * boxRatio
        sx = (img.width - sw) / 2
    } else {
        sh = img.width / boxRatio
        sy = (img.height - sh) / 2
    }

    ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h)
}

async function downloadArtwork() {
    if (!selectedArtwork.value) return

    try {
        const canvas = document.createElement('canvas')
        const DPI = 300
        const PRINT_SIZE_CM = 80 // ton tableau fait 80 cm
        const INCHES = PRINT_SIZE_CM / 2.54

        const size = Math.round(INCHES * DPI)
        const ctx = canvas.getContext('2d')

        canvas.width = size
        canvas.height = size

        const backgroundColor = selectedArtwork.value.background_color || '#f5f5f4'

        // Dimensions basées sur ton design affiché
        const outerSize = size
        const padding = outerSize * 0.0875 // 8.75%
        const innerSize = outerSize - (padding * 2)
        const gap = innerSize * 0.045454545455 // 4.5454545455%
        const cellSize = (innerSize - (gap * 2)) / 3

        // Fond du tableau
        ctx.fillStyle = backgroundColor
        ctx.fillRect(0, 0, outerSize, outerSize)

        // Fond noir des cases vides ou sous les images
        const images = [...selectedArtwork.value.images].sort((a, b) => a.position - b.position)

        for (let index = 0; index < 9; index++) {
            const row = Math.floor(index / 3)
            const col = index % 3

            const x = padding + (col * (cellSize + gap))
            const y = padding + (row * (cellSize + gap))

            ctx.fillStyle = '#171717'
            ctx.fillRect(x, y, cellSize, cellSize)

            const image = images.find(img => Number(img.position) === index + 1)

            if (!image?.url) continue

            try {
                const loadedImage = await loadImage(image.url)
                drawImageCover(ctx, loadedImage, x, y, cellSize, cellSize)
            } catch (error) {
                console.error(error)
            }
        }

        const dataUrl = canvas.toDataURL('image/png')

        const link = document.createElement('a')
        link.href = dataUrl
        link.download = `${selectedArtwork.value.title || 'tableau'}.png`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    } catch (error) {
        console.error('Erreur export image :', error)
        alert('Téléchargement échoué')
    }
}
</script>

<template>
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Dashboard
        </h2>

        <div class="flex items-center gap-3">
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
            <div class="max-w-md">
                <label
                    for="artwork-select"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Choisir un tableau
                </label>

                <select
                    id="artwork-select"
                    v-model="selectedArtworkId"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 shadow-sm outline-none transition focus:border-gray-500"
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
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ selectedArtwork.title }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ selectedArtwork.images.length }} images
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            :href="route('admin.artworks.edit', selectedArtwork.id)"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Modifier
                        </Link>

                        <button
                            type="button"
                            @click="downloadArtwork"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Télécharger le tableau
                        </button>

                        <button
                            type="button"
                            @click="destroyArtwork(selectedArtwork.id)"
                            class="rounded-lg border border-red-600 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>

                <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6">
                        <div
                            ref="artworkRef"
                            class="relative mx-auto w-[680px] h-[680px] overflow-hidden shadow-2xl"
                            :style="{ backgroundColor: selectedArtwork.background_color || '#f5f5f4' }"
                        >
                            <div
                                class="absolute inset-0"
                                :style="{ padding: '8.75%' }"
                            >
                                <div
                                    class="grid h-full w-full grid-cols-3"
                                    style="gap: 4.5454545455%;"
                                >
                                    <div
                                        v-for="image in selectedArtwork.images"
                                        :key="image.id"
                                        class="relative aspect-square overflow-hidden bg-neutral-900 shadow"
                                    >
                                        <img
                                            :src="image.url"
                                            :alt="`Image ${image.position}`"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Informations
                        </h3>

                        <div class="mt-4 space-y-4 text-sm text-gray-600">
                        <div>
                            <p class="font-medium text-gray-800">Titre</p>
                            <p>{{ selectedArtwork.title }}</p>
                        </div>

                        <div>
                            <p class="font-medium text-gray-800">Visibilité</p>

                            <div class="mt-2">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="selectedArtwork.is_public
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-gray-200 text-gray-700'"
                                >
                                    {{ selectedArtwork.is_public ? 'Public' : 'Privé' }}
                                </span>
                            </div>
                        </div>

    <div>
        <p class="font-medium text-gray-800">Couleur de fond</p>
        <div class="mt-2 flex items-center gap-3">
            <div
                class="h-10 w-10 rounded-lg border border-gray-300"
                :style="{ backgroundColor: selectedArtwork.background_color || '#f5f5f4' }"
            />
            <span>{{ selectedArtwork.background_color || '#f5f5f4' }}</span>
        </div>
    </div>

    <div>
        <p class="font-medium text-gray-800">Images</p>
        <p>{{ selectedArtwork.images.length }}/9</p>
    </div>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>