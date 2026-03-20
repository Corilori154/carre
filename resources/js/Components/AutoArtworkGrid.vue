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
})

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

function getImageElement(id) {
    return gridRef.value?.querySelector(`[data-image-id="${id}"]`)
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

        document.body.appendChild(clone)

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
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">


            <div class="flex flex-wrap items-center gap-2">
                <label class="text-sm text-neutral-300">Intervalle auto :</label>

                <input
                    v-model.number="intervalSeconds"
                    type="number"
                    min="1"
                    step="1"
                    class="w-24 rounded-xl border border-neutral-700 bg-neutral-900 px-3 py-2 text-sm text-white"
                />

                <span class="text-sm text-neutral-400">secondes</span>

                <button
                    type="button"
                    class="rounded-xl bg-neutral-800 px-3 py-2 text-sm transition hover:bg-neutral-700"
                    @click="applyInterval"
                >
                    Appliquer
                </button>

                <button
                    type="button"
                    class="rounded-xl bg-neutral-800 px-3 py-2 text-sm transition hover:bg-neutral-700"
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
            class="rounded-2xl border border-red-800 bg-red-950/40 p-4 text-sm text-red-200"
        >
            Ce tableau ne contient pas exactement 9 images.
        </div>

        <div
            v-else
            ref="gridRef"
            class="grid grid-cols-3 gap-3 md:gap-4"
        >
            <div
                v-for="tile in tiles"
                :key="tile.id"
                :data-tile-id="tile.id"
                class="relative aspect-square overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900 shadow"
                style="will-change: transform; transform-origin: center;"
            >
                <img
                    :data-image-id="tile.id"
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
    </div>
</template>