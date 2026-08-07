<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({ compositions: Array })

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
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div v-if="!compositions.length" class="p-10 text-center text-gray-500">Aucun tableau n’a encore été généré.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th v-for="heading in ['Prénom', 'Nom', 'Adresse e-mail', 'Nom du tableau', 'Galerie', 'Date']" :key="heading" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ heading }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="composition in compositions" :key="composition.id" class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-900">{{ composition.first_name || '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-900">{{ composition.last_name || '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm"><a v-if="composition.email" :href="`mailto:${composition.email}`" class="text-blue-700 hover:underline">{{ composition.email }}</a><span v-else>—</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900">{{ composition.type }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ composition.gallery || 'Publique' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ composition.created_at }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <button type="button" class="font-medium text-red-600 hover:text-red-800" @click="deleteComposition(composition)">
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
