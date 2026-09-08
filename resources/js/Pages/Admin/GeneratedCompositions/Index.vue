<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { exportCompositionPng } from '@/lib/exportCompositionPng'

const downloadingId = ref(null)
const exportMessage = ref('')
const downloadComposition = async (composition) => {
    if (downloadingId.value !== null) return
    downloadingId.value = composition.id
    exportMessage.value = ''
    try {
        const lowResolution = await exportCompositionPng(composition)
        exportMessage.value = lowResolution
            ? 'PNG téléchargé. Certaines images sources font moins de 2 363 pixels de côté : leur netteté à l’impression peut être limitée malgré la résolution de l’export.'
            : 'PNG téléchargé : 9 449 × 9 449 pixels, 80 × 80 cm à 300 ppp.'
    } catch (error) {
        exportMessage.value = error.message || 'Le téléchargement a échoué. Veuillez réessayer.'
    } finally {
        downloadingId.value = null
    }
}

const props = defineProps({ compositions: Object, filters: Object })
const search = ref(props.filters.search)
watch(() => props.filters.search, value => { search.value = value })
const searchCompositions = () => router.get(route('admin.generated-compositions.index'),
    search.value.trim() ? { search: search.value.trim() } : {},
    { preserveState: true, preserveScroll: true })
const resetSearch = () => { search.value = ''; searchCompositions() }

const deleteComposition = (composition) => {
    const name = `${composition.first_name || ''} ${composition.last_name || ''}`.trim()

    if (confirm(`Supprimer les informations${name ? ` de ${name}` : ''} ?`)) {
        router.delete(route('admin.generated-compositions.destroy', composition.id), {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <Head title="Tableaux générés" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Tableaux générés</h2>
                <div class="flex gap-3">
                    <Link :href="route('admin.artworks.index')" class="rounded-lg border bg-white px-4 py-2 text-sm font-medium text-gray-700">Tableaux</Link>
                    <Link :href="route('admin.galleries.index')" class="rounded-lg border bg-white px-4 py-2 text-sm font-medium text-gray-700">Galeries clientes</Link>
                    <Link :href="route('admin.generated-compositions.index')" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white">Générations</Link>
                </div>
            </div>
        </template>
        <div class="mx-auto max-w-7xl p-6">
            <p class="mb-3 text-sm text-gray-600">Export pour impression : PNG de 9 449 × 9 449 pixels · 80 × 80 cm · 300 ppp.</p>
            <p v-if="exportMessage" role="status" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">{{ exportMessage }}</p>
            <form class="mb-5 flex flex-wrap items-end gap-3" @submit.prevent="searchCompositions">
                <div class="min-w-64 flex-1">
                    <label for="composition-search" class="mb-1 block text-sm font-medium text-gray-700">Rechercher un tableau généré</label>
                    <input id="composition-search" v-model="search" type="search" maxlength="255" placeholder="Prénom, nom, e-mail, tableau ou galerie…" class="w-full rounded-lg border-gray-300">
                </div>
                <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-white">Rechercher</button>
                <button v-if="search || filters.search" type="button" class="rounded-lg border px-4 py-2" @click="resetSearch">Réinitialiser</button>
            </form>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div v-if="!compositions.data.length" class="p-10 text-center text-gray-500">{{ filters.search ? 'Aucun tableau ne correspond à votre recherche.' : 'Aucun tableau n’a encore été généré.' }}</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Prévisualisation</th>
                            <th v-for="heading in ['Prénom', 'Nom', 'Adresse e-mail', 'Nom du tableau', 'Galerie', 'Date']" :key="heading" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ heading }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="composition in compositions.data" :key="composition.id" class="hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <div role="img" :aria-label="`Prévisualisation de ${composition.type}`" class="h-32 w-32 overflow-hidden shadow-sm" :style="{ backgroundColor: composition.preview.background_color, padding: '11.2px' }">
                                        <div class="grid h-full w-full grid-cols-3 grid-rows-3" style="gap: 4.54545%">
                                            <div v-for="(slot, index) in composition.preview.slots" :key="index" class="relative overflow-hidden bg-white">
                                                <img v-if="slot.url" :src="slot.url" alt="" loading="lazy" class="h-full w-full object-cover" :style="{ transform: `rotate(${slot.rotation}deg)` }" @error="($event) => { $event.target.style.display = 'none' }">
                                                <span v-else-if="slot.missing" title="Image supprimée" class="flex h-full items-center justify-center text-xs text-gray-400">?</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-900">{{ composition.first_name || '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-900">{{ composition.last_name || '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm"><a v-if="composition.email" :href="`mailto:${composition.email}`" class="text-blue-700 hover:underline">{{ composition.email }}</a><span v-else>—</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900">{{ composition.type }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ composition.gallery || 'Publique' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ composition.created_at }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <button type="button" :disabled="downloadingId !== null" class="mb-3 block rounded-lg bg-gray-900 px-3 py-2 font-medium text-white disabled:cursor-wait disabled:opacity-50" @click="downloadComposition(composition)">
                                        {{ downloadingId === composition.id ? 'Préparation du PNG…' : 'Télécharger en PNG' }}
                                    </button>
                                    <button type="button" class="font-medium text-red-600 hover:text-red-800" @click="deleteComposition(composition)">
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <nav v-if="compositions.total" aria-label="Pagination des tableaux générés" class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-4 text-sm">
                    <p class="text-gray-600">{{ compositions.from }}–{{ compositions.to }} sur {{ compositions.total }} tableaux</p>
                    <div class="flex items-center gap-3">
                        <Link v-if="compositions.prev_page_url" :href="compositions.prev_page_url" preserve-scroll class="rounded-lg border px-3 py-2">Précédent</Link>
                        <span>Page {{ compositions.current_page }} / {{ compositions.last_page }}</span>
                        <Link v-if="compositions.next_page_url" :href="compositions.next_page_url" preserve-scroll class="rounded-lg border px-3 py-2">Suivant</Link>
                    </div>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
