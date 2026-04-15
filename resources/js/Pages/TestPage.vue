<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-200 p-4">
    <!-- Cadre -->
    <div
      class="bg-white shadow-xl aspect-square w-[min(80vw,80vh)] relative"
    >
      <!-- zone intérieure avec padding -->
      <div class="absolute inset-0" :style="innerStyle">
        
        <!-- grille -->
        <div class="grid w-full h-full" :style="gridStyle">
          <div
            v-for="n in 9"
            :key="n"
            class="bg-gray-100 overflow-hidden"
          >
            <img
              :src="`https://picsum.photos/seed/${n}/600/600`"
              class="w-full h-full object-cover"
            />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
const BORDER = 8.75
const GAP = 3.75
const TILE = (100 - 2 * GAP) / 3 // ≈ 30.83%

const innerStyle = {
  padding: `${BORDER}%`,
}

const gridStyle = {
  display: 'grid',
  gridTemplateColumns: `repeat(3, ${TILE}%)`,
  gridTemplateRows: `repeat(3, ${TILE}%)`,
  gap: `${GAP}%`,
}
</script>