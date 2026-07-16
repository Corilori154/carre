<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

defineProps({ galleries: Array })

const form = useForm({
    name: '',
    slug: '',
    email: '',
    password: '',
})

const submit = () => form.post(route('admin.galleries.store'), {
    onSuccess: () => form.reset(),
})

const copy = async (url) => {
    await navigator.clipboard.writeText(url)
}

const destroyGallery = (gallery) => {
    if (confirm(`Supprimer la galerie « ${gallery.name} » ?`)) {
        router.delete(route('admin.galleries.destroy', gallery.slug))
    }
}

const resetAccess = (gallery) => {
    const password = prompt('Nouveau mot de passe (8 caractères minimum) :')

    if (password === null) return

    router.put(route('admin.galleries.access.reset', gallery.slug), { password }, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Galeries" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Galeries clientes
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
                        :href="route('admin.galleries.index')"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                    >
                        Galeries clientes
                    </Link>
                    <Link
                        :href="route('admin.setting-times.edit')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        Temps
                    </Link>
                </div>
            </div>
        </template>

    <div class="mx-auto max-w-6xl space-y-8 p-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Galeries clientes</h1>
            <p class="mt-1 text-sm text-gray-500">Chaque galerie reçoit automatiquement ses deux adresses publiques.</p>
        </div>

        <form class="grid gap-4 rounded-2xl border bg-white p-6 shadow-sm md:grid-cols-2 lg:grid-cols-4" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium">Nom *</label>
                <input v-model="form.name" class="w-full rounded-lg border-gray-300" required />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Identifiant d’URL</label>
                <input v-model="form.slug" class="w-full rounded-lg border-gray-300" placeholder="généré depuis le nom" />
                <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">E-mail</label>
                <input v-model="form.email" type="email" class="w-full rounded-lg border-gray-300" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Mot de passe d’accès *</label>
                <input v-model="form.password" type="password" minlength="8" autocomplete="new-password" class="w-full rounded-lg border-gray-300" required />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>
            <div class="md:col-span-2 lg:col-span-4">
                <button :disabled="form.processing" class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white disabled:opacity-50">
                    Créer la galerie
                </button>
            </div>
        </form>

        <div v-if="!galleries.length" class="rounded-xl border border-dashed p-8 text-center text-gray-500">Aucune galerie pour le moment.</div>
        <div v-else class="space-y-4">
            <article v-for="gallery in galleries" :key="gallery.id" class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <Link :href="route('admin.galleries.edit', gallery.slug)" class="font-semibold text-gray-900 hover:underline">{{ gallery.name }}</Link>
                        <p class="text-sm text-gray-500">{{ gallery.email || 'Aucun e-mail' }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ gallery.artworks_count }} tableau{{ gallery.artworks_count === 1 ? '' : 'x' }} sélectionné{{ gallery.artworks_count === 1 ? '' : 's' }}</p>
                        <p class="mt-1 text-xs font-medium" :class="gallery.is_claimed ? 'text-amber-700' : (gallery.is_configured ? 'text-emerald-700' : 'text-red-700')">
                            {{ gallery.is_claimed ? `Accès activé le ${gallery.claimed_at}` : (gallery.is_configured ? 'Mot de passe disponible — aucun appareil autorisé' : 'Mot de passe à configurer') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <Link :href="route('admin.galleries.edit', gallery.slug)" class="text-sm font-medium text-blue-700">Choisir les tableaux</Link>
                        <button class="text-sm font-medium text-gray-700" type="button" @click="resetAccess(gallery)">Réinitialiser l’accès</button>
                        <button class="text-sm font-medium text-red-600" type="button" @click="destroyGallery(gallery)">Supprimer</button>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div v-for="item in [{ label: 'Galerie', url: gallery.gallery_url }, { label: 'Composition', url: gallery.composer_url }]" :key="item.url" class="rounded-lg bg-gray-50 p-3">
                        <div class="mb-1 text-xs font-semibold uppercase text-gray-500">{{ item.label }}</div>
                        <div class="flex items-center gap-2">
                            <a :href="item.url" target="_blank" class="min-w-0 flex-1 truncate text-sm text-blue-700 hover:underline">{{ item.url }}</a>
                            <button type="button" class="rounded border bg-white px-2 py-1 text-xs" @click="copy(item.url)">Copier</button>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
    </AuthenticatedLayout>
</template>
