<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

defineProps({ galleries: Array })

const form = useForm({
    name: '',
    email: '',
    password: '',
    validity_days: 30,
})

const submit = () => form.post(route('admin.galleries.store'), {
    onSuccess: () => form.reset(),
})

const editedGallery = ref(null)
const showPassword = ref(false)
const editForm = useForm({
    name: '',
    email: '',
    validity_days: 30,
})

const openEdit = (gallery) => {
    editedGallery.value = gallery
    editForm.clearErrors()
    editForm.name = gallery.name
    editForm.email = gallery.email || ''
    editForm.validity_days = gallery.validity_days
}

const closeEdit = () => {
    editedGallery.value = null
    editForm.reset()
    editForm.clearErrors()
}

const updateGallery = () => {
    editForm.patch(route('admin.galleries.update', editedGallery.value.slug), {
        preserveScroll: true,
        onSuccess: closeEdit,
    })
}

const resetGallery = ref(null)
const showResetPassword = ref(false)
const resetForm = useForm({
    password: '',
    validity_days: 30,
})

const openResetAccess = (gallery) => {
    resetGallery.value = gallery
    showResetPassword.value = false
    resetForm.clearErrors()
    resetForm.password = ''
    resetForm.validity_days = 30
}

const closeResetAccess = () => {
    resetGallery.value = null
    showResetPassword.value = false
    resetForm.reset()
    resetForm.clearErrors()
}

const resetAccess = () => {
    resetForm.put(route('admin.galleries.access.reset', resetGallery.value.slug), {
        preserveScroll: true,
        onSuccess: closeResetAccess,
    })
}

const copy = async (url) => {
    await navigator.clipboard.writeText(url)
}

const destroyGallery = (gallery) => {
    if (confirm(`Supprimer la galerie « ${gallery.name} » ?`)) {
        router.delete(route('admin.galleries.destroy', gallery.slug))
    }
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
                <label class="mb-1 block text-sm font-medium">E-mail</label>
                <input v-model="form.email" type="email" class="w-full rounded-lg border-gray-300" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Mot de passe d’accès *</label>
                <div class="relative">
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        minlength="8"
                        autocomplete="new-password"
                        class="w-full rounded-lg border-gray-300 pr-11"
                        required
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-800"
                        :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        :title="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="showPassword" aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.7a2 2 0 002.7 2.7M9.9 4.2A10.8 10.8 0 0112 4c5.5 0 9 5.5 9 5.5a15.7 15.7 0 01-3.2 3.7M6.6 6.6C4.2 8.1 3 9.5 3 9.5S6.5 15 12 15c.7 0 1.4-.1 2-.3" />
                        </svg>
                        <svg v-else aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12s3.5-5.5 9-5.5 9 5.5 9 5.5-3.5 5.5-9 5.5S3 12 3 12z" />
                            <circle cx="12" cy="12" r="2.5" />
                        </svg>
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Validité du mot de passe (jours) *</label>
                <input v-model="form.validity_days" type="number" min="1" max="3650" class="w-full rounded-lg border-gray-300" required />
                <p v-if="form.errors.validity_days" class="mt-1 text-sm text-red-600">{{ form.errors.validity_days }}</p>
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
                        <p class="mt-1 text-xs font-medium" :class="gallery.is_claimed ? 'text-amber-700' : (gallery.is_configured && !gallery.is_password_expired ? 'text-emerald-700' : 'text-red-700')">
                            {{ gallery.is_claimed ? `Accès activé le ${gallery.claimed_at}` : (gallery.is_password_expired ? 'Mot de passe expiré' : (gallery.is_configured ? 'Mot de passe disponible — aucun appareil autorisé' : 'Mot de passe à configurer')) }}
                        </p>
                        <p v-if="gallery.password_expires_at && !gallery.is_claimed" class="mt-1 text-xs" :class="gallery.is_password_expired ? 'font-medium text-red-700' : 'text-gray-500'">
                            {{ gallery.is_password_expired ? `Mot de passe expiré le ${gallery.password_expires_at}` : `Mot de passe valable jusqu’au ${gallery.password_expires_at}` }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="text-sm font-medium text-gray-700" type="button" @click="openEdit(gallery)">Modifier</button>
                        <Link :href="route('admin.galleries.edit', gallery.slug)" class="text-sm font-medium text-blue-700">Choisir les tableaux</Link>
                        <button class="text-sm font-medium text-gray-700" type="button" @click="openResetAccess(gallery)">Réinitialiser l’accès</button>
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

    <div v-if="editedGallery" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeEdit">
        <form class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-xl" @submit.prevent="updateGallery">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Modifier la galerie</h2>
                <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Fermer" @click="closeEdit">&times;</button>
            </div>
            <div>
                <label for="edit-name" class="mb-1 block text-sm font-medium">Nom *</label>
                <input id="edit-name" v-model="editForm.name" class="w-full rounded-lg border-gray-300" required />
                <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
            </div>
            <div>
                <label for="edit-email" class="mb-1 block text-sm font-medium">E-mail</label>
                <input id="edit-email" v-model="editForm.email" type="email" class="w-full rounded-lg border-gray-300" />
                <p v-if="editForm.errors.email" class="mt-1 text-sm text-red-600">{{ editForm.errors.email }}</p>
            </div>
            <div>
                <label for="edit-validity-days" class="mb-1 block text-sm font-medium">Validité du mot de passe à partir d’aujourd’hui (jours) *</label>
                <input id="edit-validity-days" v-model="editForm.validity_days" type="number" min="1" max="3650" class="w-full rounded-lg border-gray-300" required />
                <p v-if="editForm.errors.validity_days" class="mt-1 text-sm text-red-600">{{ editForm.errors.validity_days }}</p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700" @click="closeEdit">Annuler</button>
                <button :disabled="editForm.processing" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    <div v-if="resetGallery" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeResetAccess">
        <form class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-xl" @submit.prevent="resetAccess">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Réinitialiser l’accès</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ resetGallery.name }}</p>
                </div>
                <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Fermer" @click="closeResetAccess">&times;</button>
            </div>
            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                L’appareil actuellement autorisé perdra son accès. Le nouveau mot de passe pourra être utilisé une seule fois.
            </p>
            <div>
                <label for="reset-password" class="mb-1 block text-sm font-medium">Nouveau mot de passe *</label>
                <div class="relative">
                    <input
                        id="reset-password"
                        v-model="resetForm.password"
                        :type="showResetPassword ? 'text' : 'password'"
                        minlength="8"
                        autocomplete="new-password"
                        class="w-full rounded-lg border-gray-300 pr-11"
                        required
                        autofocus
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-800"
                        :aria-label="showResetPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        :title="showResetPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        @click="showResetPassword = !showResetPassword"
                    >
                        <svg v-if="showResetPassword" aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.7a2 2 0 002.7 2.7M9.9 4.2A10.8 10.8 0 0112 4c5.5 0 9 5.5 9 5.5a15.7 15.7 0 01-3.2 3.7M6.6 6.6C4.2 8.1 3 9.5 3 9.5S6.5 15 12 15c.7 0 1.4-.1 2-.3" />
                        </svg>
                        <svg v-else aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12s3.5-5.5 9-5.5 9 5.5 9 5.5-3.5 5.5-9 5.5S3 12 3 12z" />
                            <circle cx="12" cy="12" r="2.5" />
                        </svg>
                    </button>
                </div>
                <p v-if="resetForm.errors.password" class="mt-1 text-sm text-red-600">{{ resetForm.errors.password }}</p>
            </div>
            <div>
                <label for="reset-validity-days" class="mb-1 block text-sm font-medium">Validité du mot de passe (jours) *</label>
                <input id="reset-validity-days" v-model="resetForm.validity_days" type="number" min="1" max="3650" class="w-full rounded-lg border-gray-300" required />
                <p v-if="resetForm.errors.validity_days" class="mt-1 text-sm text-red-600">{{ resetForm.errors.validity_days }}</p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700" @click="closeResetAccess">Annuler</button>
                <button :disabled="resetForm.processing" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                    Réinitialiser l’accès
                </button>
            </div>
        </form>
    </div>
    </AuthenticatedLayout>
</template>
