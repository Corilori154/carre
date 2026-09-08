const SIZE = 9449

// PNG pHYs stores pixels per metre: 300 dpi = 11811 pixels/metre.
async function withPrintResolution(blob) {
    const bytes = new Uint8Array(await blob.arrayBuffer())
    const chunk = new Uint8Array(21)
    const view = new DataView(chunk.buffer)
    view.setUint32(0, 9)
    chunk.set([112, 72, 89, 115], 4)
    view.setUint32(8, 11811)
    view.setUint32(12, 11811)
    chunk[16] = 1
    let crc = 0xffffffff
    for (const byte of chunk.subarray(4, 17)) {
        crc ^= byte
        for (let bit = 0; bit < 8; bit++) crc = (crc >>> 1) ^ ((crc & 1) ? 0xedb88320 : 0)
    }
    view.setUint32(17, (crc ^ 0xffffffff) >>> 0)
    const parts = [bytes.subarray(0, 33), chunk]
    for (let offset = 33; offset < bytes.length;) {
        const length = new DataView(bytes.buffer, bytes.byteOffset + offset, 4).getUint32(0)
        const end = offset + length + 12
        if (String.fromCharCode(...bytes.subarray(offset + 4, offset + 8)) !== 'pHYs') {
            parts.push(bytes.subarray(offset, end))
        }
        offset = end
    }
    return new Blob(parts, { type: 'image/png' })
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image()
        image.crossOrigin = 'anonymous'
        image.onload = () => resolve(image)
        image.onerror = () => reject(new Error('Une image est inaccessible. Le téléchargement a été annulé pour éviter un tableau incomplet.'))
        image.src = url
    })
}

export async function exportCompositionPng(composition) {
    const slots = composition.preview.slots
    if (slots.some(slot => slot.missing)) throw new Error('Une image de ce tableau a été supprimée : export impossible.')
    const canvas = document.createElement('canvas')
    try {
        canvas.width = canvas.height = SIZE
        const context = canvas.getContext('2d')
        if (!context) throw new Error('Mémoire insuffisante pour créer cette image. Fermez des onglets puis réessayez.')
        context.fillStyle = composition.preview.background_color
        context.fillRect(0, 0, SIZE, SIZE)
        context.imageSmoothingEnabled = true
        context.imageSmoothingQuality = 'high'
        const tile = SIZE * 20 / 80
        const frame = SIZE * 7 / 80
        const step = SIZE * 23 / 80
        let lowResolution = false
        for (let index = 0; index < 9; index++) {
            const slot = slots[index]
            const x = frame + (index % 3) * step
            const y = frame + Math.floor(index / 3) * step
            context.fillStyle = '#ffffff'
            context.fillRect(x, y, tile, tile)
            if (!slot.url) continue
            const image = await loadImage(slot.url)
            const crop = Math.min(image.naturalWidth, image.naturalHeight)
            if (crop < Math.ceil(tile)) lowResolution = true
            context.save()
            context.beginPath()
            context.rect(x, y, tile, tile)
            context.clip()
            context.translate(x + tile / 2, y + tile / 2)
            context.rotate(slot.rotation * Math.PI / 180)
            context.drawImage(image, (image.naturalWidth - crop) / 2, (image.naturalHeight - crop) / 2, crop, crop, -tile / 2, -tile / 2, tile, tile)
            context.restore()
        }
        const blob = await new Promise((resolve, reject) => canvas.toBlob(value => value ? resolve(value) : reject(new Error('Impossible de créer le PNG haute résolution.')), 'image/png'))
        const url = URL.createObjectURL(await withPrintResolution(blob))
        const link = document.createElement('a')
        link.href = url
        link.download = `tableau-${composition.id}-80x80cm-300ppp.png`
        link.click()
        setTimeout(() => URL.revokeObjectURL(url), 60000)
        return lowResolution
    } finally {
        canvas.width = canvas.height = 0
    }
}
