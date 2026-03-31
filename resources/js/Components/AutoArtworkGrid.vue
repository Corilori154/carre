<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { toCanvas } from 'html-to-image'

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
    showManualButtons: {
        type: Boolean,
        default: true,
    },
    allowVideoDownload: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['fullscreen', 'shuffle-start'])

const ROTATIONS = [0, 90, 180, 270]
const ROTATE_STEP_MS = 350
const MOVE_STEP_MS = 550
const MOVE_EASING = 'cubic-bezier(.2,.8,.2,1)'
const ROTATE_EASING = 'cubic-bezier(.22,1,.36,1)'

const boardContainerRef = ref(null)
const gridRef = ref(null)
const tiles = ref([])
const autoAnimating = ref(false)
const isRecording = ref(false)

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

function buildShufflePlan() {
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

    return {
        newOrder,
        stationary,
        moving,
    }
}

function getShuffleDurationMs(plan) {
    return (
        (plan.stationary.length * (ROTATE_STEP_MS + 60)) +
        (plan.moving.length * (MOVE_STEP_MS + 70)) +
        (plan.moving.length * (ROTATE_STEP_MS + 60)) +
        250
    )
}

async function runShufflePlan(plan) {
    await rotateOneByOne(plan.stationary)
    await moveOneByOne(plan.moving, plan.newOrder)
    await rotateOneByOne(plan.moving)
}

function resetNextTick(fromTime = Date.now()) {
    nextTickAt.value = fromTime + intervalSeconds.value * 1000
    countdown.value = intervalSeconds.value
}

function startCountdownTimer() {
    if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
    }

    countdownTimer = setInterval(() => {
        const msLeft = Math.max(0, nextTickAt.value - Date.now())
        countdown.value = Math.max(0, Math.ceil(msLeft / 1000))
    }, 200)
}

function scheduleNextShuffle(delayMs) {
    if (autoTimer) {
        clearTimeout(autoTimer)
        autoTimer = null
    }

    autoTimer = setTimeout(async () => {
        await shuffleSequentially()
    }, Math.max(0, delayMs))
}

async function playShufflePlan(plan, { scheduleNext = true } = {}) {
    if (autoAnimating.value || !hasEnoughImages.value) return

    autoAnimating.value = true

    const shuffleStartedAt = Date.now()
    emit('shuffle-start')
    resetNextTick(shuffleStartedAt)

    await runShufflePlan(plan)

    autoAnimating.value = false

    if (scheduleNext) {
        const remainingMs = nextTickAt.value - Date.now()
        scheduleNextShuffle(remainingMs)
    }
}

async function shuffleSequentially() {
    const plan = buildShufflePlan()
    await playShufflePlan(plan)
}

function stopAutoTimer() {
    if (autoTimer) {
        clearTimeout(autoTimer)
        autoTimer = null
    }

    if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
    }
}

function startAutoTimer() {
    stopAutoTimer()

    if (!hasEnoughImages.value) {
        countdown.value = 0
        return
    }

    resetNextTick()
    startCountdownTimer()
    scheduleNextShuffle(intervalSeconds.value * 1000)
}

function applyInterval() {
    if (!Number.isFinite(intervalSeconds.value) || intervalSeconds.value <= 0) {
        intervalSeconds.value = 10
    }

    startAutoTimer()
}

function getSupportedVideoMimeType() {
    const candidates = [
        'video/webm;codecs=vp9',
        'video/webm;codecs=vp8',
        'video/webm',
    ]

    return candidates.find(type => MediaRecorder.isTypeSupported(type)) || ''
}

async function renderBoardToCanvas(targetCanvas, ctx) {
    const sourceEl = boardContainerRef.value
    if (!sourceEl) return

    const sourceRect = sourceEl.getBoundingClientRect()
    const snapshotCanvas = await toCanvas(sourceEl, {
        cacheBust: true,
        pixelRatio: 1,
        backgroundColor: props.backgroundColor,
        skipFonts: true,
        width: Math.round(sourceRect.width),
        height: Math.round(sourceRect.height),
        canvasWidth: targetCanvas.width,
        canvasHeight: targetCanvas.height,
    })

    ctx.clearRect(0, 0, targetCanvas.width, targetCanvas.height)
    ctx.drawImage(snapshotCanvas, 0, 0, targetCanvas.width, targetCanvas.height)
}

async function downloadAnimationVideo() {
    if (isRecording.value || !boardContainerRef.value || !hasEnoughImages.value) return

    if (typeof MediaRecorder === 'undefined') {
        alert('Votre navigateur ne supporte pas l’enregistrement vidéo.')
        return
    }

    const mimeType = getSupportedVideoMimeType()

    if (!mimeType) {
        alert('Aucun format vidéo WebM compatible n’a été trouvé sur ce navigateur.')
        return
    }

    isRecording.value = true
    stopAutoTimer()

    const plan = buildShufflePlan()
    const durationMs = getShuffleDurationMs(plan)
    const fps = 12

    const sourceRect = boardContainerRef.value.getBoundingClientRect()
    const outputCanvas = document.createElement('canvas')
    outputCanvas.width = Math.max(720, Math.round(sourceRect.width * 2))
    outputCanvas.height = Math.max(720, Math.round(sourceRect.height * 2))

    const ctx = outputCanvas.getContext('2d')
    if (!ctx) {
        isRecording.value = false
        startAutoTimer()
        alert('Impossible de créer le contexte vidéo.')
        return
    }

    const stream = outputCanvas.captureStream(fps)
    const recorder = new MediaRecorder(stream, {
        mimeType,
        videoBitsPerSecond: 8_000_000,
    })

    const chunks = []

    recorder.ondataavailable = (event) => {
        if (event.data && event.data.size > 0) {
            chunks.push(event.data)
        }
    }

    const stopPromise = new Promise((resolve) => {
        recorder.onstop = resolve
    })

    let keepRendering = true

    const renderLoop = async () => {
        while (keepRendering) {
            await renderBoardToCanvas(outputCanvas, ctx)
            await sleep(1000 / fps)
        }
    }

    try {
        await nextTick()
        await renderBoardToCanvas(outputCanvas, ctx)

        recorder.start()

        const renderPromise = renderLoop()

        await playShufflePlan(plan, { scheduleNext: false })
        await sleep(300)

        keepRendering = false
        await renderPromise

        recorder.stop()
        await stopPromise

        const blob = new Blob(chunks, { type: mimeType })
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')

        link.href = url
        link.download = 'animation-tableau.webm'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        URL.revokeObjectURL(url)
    } catch (error) {
        console.error('Erreur export vidéo :', error)
        alert('Le téléchargement de la vidéo a échoué.')
    } finally {
        stream.getTracks().forEach(track => track.stop())
        isRecording.value = false
        startAutoTimer()
    }
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
                <button
                    v-if="showManualButtons"
                    type="button"
                    class="bg-neutral-800 px-3 py-2 text-sm transition hover:bg-neutral-700"
                    @click="applyInterval"
                >
                    Appliquer
                </button>

                <button
                    v-if="showManualButtons"
                    type="button"
                    class="bg-neutral-800 px-3 py-2 text-sm transition hover:bg-neutral-700"
                    @click="shuffleSequentially"
                >
                    Mélanger maintenant
                </button>

                <span class="text-sm tabular-nums text-neutral-300">
                    Prochaine combinaison : {{ countdown }}s
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
                ref="boardContainerRef"
                class="relative mx-auto aspect-square w-full max-w-[680px] shadow-2xl"
                :style="{ backgroundColor: backgroundColor, padding: '8.75%' }"
                :class="props.isFullscreenActive ? 'max-w-[min(88vw,88vh)]' : ''"
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

               
            </div>

            <div
                v-if="allowVideoDownload && !isFullscreenActive"
                class="mt-6 flex justify-center"
            >
                <button
                    type="button"
                    class="rounded-xl bg-white px-6 py-3 font-semibold text-black transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isRecording"
                    @click="downloadAnimationVideo"
                >
                    <span v-if="isRecording">Enregistrement vidéo…</span>
                    <span v-else>Télécharger l’animation en vidéo</span>
                </button>
            </div>
             <button
                    v-if="showFullscreenButton"
                    type="button"
                   :class="isFullscreenActive
                        ? 'fixed right-6 bottom-6 z-50'
                        : 'mt-4 w-full flex justify-end'"
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
</template>