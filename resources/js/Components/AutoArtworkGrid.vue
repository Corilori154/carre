<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    images: {
        type: Array,
        required: true,
    },
    initialIntervalSeconds: {
        type: Number,
        default: 10,
    },
    showControls: {
        type: Boolean,
        default: true,
    },
    showFullscreenButton: {
        type: Boolean,
        default: false,
    },
    isFullscreenActive: {
        type: Boolean,
        default: false,
    },
     backgroundColor: {
        type: String,
        default: '#f5f5f4',
    },
})

const emit = defineEmits(['fullscreen'])

const ROTATIONS = [0, 90, 180, 270]
const ROTATE_STEP_MS = 350
const MOVE_STEP_MS = 550
const MOVE_EASING = 'cubic-bezier(.2,.8,.2,1)'
const ROTATE_EASING = 'cubic-bezier(.22,1,.36,1)'

const gridRef = ref(null)
const tiles = ref([])
const autoAnimating = ref(false)
const intervalSeconds = ref(props.initialIntervalSeconds)
const nextTickAt = ref(Date.now() + intervalSeconds.value * 1000)
const countdown = ref(intervalSeconds.value)
let autoTimer = null
let countdownTimer = null

const hasEnoughImages = computed(() => props.images.length === 9)

function getAnimationHost() {
    return document.fullscreenElement || document.body
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
}

function shuffleArray(arr) {
    const a = arr.slice()

    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1))
        ;[a[i], a[j]] = [a[j], a[i]]
    }

    return a
}

function randomRotation() {
    return ROTATIONS[Math.floor(Math.random() * ROTATIONS.length)]
}

function initTiles() {
    tiles.value = props.images.map((image) => ({
        id: image.id,
        url: image.url,
        position: image.position,
        rotation: randomRotation(),
    }))
}

function getTileElement(id) {
    return gridRef.value?.querySelector(`[data-tile-id="${id}"]`)
}

async function rotateOneByOne(items) {
    for (const item of items) {
        const tile = tiles.value.find(t => t.id === item.id)
        if (!tile) continue

        tile.rotation = item.toDeg
        await nextTick()
        await sleep(ROTATE_STEP_MS + 60)
    }
}

async function moveOneByOne(moveItems, newOrder) {
    if (!moveItems.length) {
        tiles.value = newOrder
        await nextTick()
        return
    }

    const firstRects = new Map()

    moveItems.forEach(item => {
        const el = getTileElement(item.id)
        if (!el) return
        firstRects.set(item.id, el.getBoundingClientRect())
    })

    tiles.value = newOrder
    await nextTick()

    const lastRects = new Map()

    moveItems.forEach(item => {
        const el = getTileElement(item.id)
        if (!el) return
        lastRects.set(item.id, el.getBoundingClientRect())
    })

    for (const item of moveItems) {
        const realEl = getTileElement(item.id)
        const first = firstRects.get(item.id)
        const last = lastRects.get(item.id)

        if (!realEl || !first || !last) continue

        const clone = realEl.cloneNode(true)

        clone.style.position = 'fixed'
        clone.style.left = `${first.left}px`
        clone.style.top = `${first.top}px`
        clone.style.width = `${first.width}px`
        clone.style.height = `${first.height}px`
        clone.style.margin = '0'
        clone.style.zIndex = '9999'
        clone.style.pointerEvents = 'none'
        clone.style.transition = `transform ${MOVE_STEP_MS}ms ${MOVE_EASING}`
        clone.style.transform = 'translate(0px, 0px)'
        clone.style.willChange = 'transform'

        getAnimationHost().appendChild(clone)

        realEl.style.visibility = 'hidden'

        const dx = last.left - first.left
        const dy = last.top - first.top

        clone.getBoundingClientRect()
        clone.style.transform = `translate(${dx}px, ${dy}px)`

        await sleep(MOVE_STEP_MS + 70)

        clone.remove()
        realEl.style.visibility = ''
    }
}

async function shuffleSequentially() {
    if (autoAnimating.value || !hasEnoughImages.value) return

    autoAnimating.value = true

    const oldOrder = tiles.value.slice()
    const newOrder = shuffleArray(tiles.value.slice())
    const targetRotations = newOrder.map(() => randomRotation())

    const oldIndexById = new Map(oldOrder.map((item, index) => [item.id, index]))
    const stationary = []
    const moving = []

    newOrder.forEach((item, newIndex) => {
        const oldIndex = oldIndexById.get(item.id)
        const payload = {
            id: item.id,
            oldIndex,
            newIndex,
            toDeg: targetRotations[newIndex],
        }

        if (oldIndex === newIndex) {
            stationary.push(payload)
        } else {
            moving.push(payload)
        }
    })

    await rotateOneByOne(stationary)
    await moveOneByOne(moving, newOrder)
    await rotateOneByOne(moving)

    autoAnimating.value = false
    resetNextTick()
}

function resetNextTick() {
    nextTickAt.value = Date.now() + intervalSeconds.value * 1000
}

function startAutoTimer() {
    stopAutoTimer()

    resetNextTick()

    autoTimer = setInterval(() => {
        shuffleSequentially()
    }, intervalSeconds.value * 1000)

    countdownTimer = setInterval(() => {
        const msLeft = Math.max(0, nextTickAt.value - Date.now())
        countdown.value = Math.ceil(msLeft / 1000)
    }, 200)
}

function stopAutoTimer() {
    if (autoTimer) {
        clearInterval(autoTimer)
        autoTimer = null
    }

    if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
    }
}

function applyInterval() {
    if (!Number.isFinite(intervalSeconds.value) || intervalSeconds.value <= 0) {
        intervalSeconds.value = 10
    }

    startAutoTimer()
}

watch(
    () => props.images,
    () => {
        initTiles()
        startAutoTimer()
    },
    { deep: true }
)

watch(
    () => props.initialIntervalSeconds,
    (value) => {
        intervalSeconds.value = value
        startAutoTimer()
    }
)

onMounted(() => {
    initTiles()
    startAutoTimer()
})

onBeforeUnmount(() => {
    stopAutoTimer()
})
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="showControls"
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <div class="flex flex-wrap items-center gap-2">
                <label class="text-sm text-neutral-300">Intervalle auto :</label>

                <input
                    v-model.number="intervalSeconds"
                    type="number"
                    min="1"
                    step="1"
                    class="w-24 border border-neutral-700 bg-neutral-900 px-3 py-2 text-sm text-white"
                />

                <span class="text-sm text-neutral-400">secondes</span>

                <button
                    type="button"
                    class="bg-neutral-800 px-3 py-2 text-sm transition hover:bg-neutral-700"
                    @click="applyInterval"
                >
                    Appliquer
                </button>

                <button
                    type="button"
                    class="bg-neutral-800 px-3 py-2 text-sm transition hover:bg-neutral-700"
                    @click="shuffleSequentially"
                >
                    Mélanger maintenant
                </button>

                <span class="text-sm tabular-nums text-neutral-300">
                    Prochain shuffle : {{ countdown }}s
                </span>
            </div>
        </div>

        <div
            v-if="!hasEnoughImages"
            class="border border-red-800 bg-red-950/40 p-4 text-sm text-red-200"
        >
            Ce tableau ne contient pas exactement 9 images.
        </div>

        <div v-else class="relative">
            <div
                class="relative mx-auto aspect-square w-full max-w-[680px] shadow-2xl"
                :style="{ backgroundColor: backgroundColor }"
                :class="props.isFullscreenActive ? 'max-w-[min(88vw,88vh)]' : ''"
                style="
                    padding: 8.75%;
                "
            >
                <div
                    ref="gridRef"
                    class="grid h-full w-full grid-cols-3"
                    style="gap: 4.5454545455%;"
                >
                    <div
                        v-for="tile in tiles"
                        :key="tile.id"
                        :data-tile-id="tile.id"
                        class="relative aspect-square overflow-hidden bg-neutral-900 shadow"
                        style="will-change: transform; transform-origin: center;"
                    >
                        <img
                            :src="tile.url"
                            :alt="`Image ${tile.position}`"
                            class="h-full w-full object-cover"
                            :style="{
                                transform: `rotate(${tile.rotation}deg)`,
                                transition: `transform ${ROTATE_STEP_MS}ms ${ROTATE_EASING}`,
                                willChange: 'transform',
                                transformOrigin: 'center',
                            }"
                        />
                    </div>
                </div>

                <button
                    v-if="showFullscreenButton"
                    type="button"
                    class="absolute bottom-3 right-3 z-10 flex h-11 w-11 items-center justify-center border border-neutral-700 bg-black/70 text-white backdrop-blur-sm transition hover:bg-black/85"
                    :aria-label="isFullscreenActive ? 'Quitter le plein écran' : 'Passer en plein écran'"
                    :title="isFullscreenActive ? 'Quitter le plein écran' : 'Passer en plein écran'"
                    @click="emit('fullscreen')"
                >
                    <svg
                        v-if="!isFullscreenActive"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5"
                    >
                        <path d="M8 3H3v5" />
                        <path d="M16 3h5v5" />
                        <path d="M21 16v5h-5" />
                        <path d="M3 16v5h5" />
                    </svg>

                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5"
                    >
                        <path d="M9 3H3v6" />
                        <path d="M15 3h6v6" />
                        <path d="M21 15v6h-6" />
                        <path d="M3 15v6h6" />
                        <path d="M8 8L3 3" />
                        <path d="M16 8l5-5" />
                        <path d="M8 16l-5 5" />
                        <path d="M16 16l5 5" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>