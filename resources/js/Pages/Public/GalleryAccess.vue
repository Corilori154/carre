<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    gallery: Object,
    redirect: String,
    isClaimed: Boolean,
    isConfigured: Boolean,
    isExpired: Boolean,
})

const form = useForm({
    password: '',
    redirect: props.redirect,
})

const showPassword = ref(false)

const submit = () => form.post(route('galleries.access.store', props.gallery.slug))
</script>

<template>
    <Head :title="`Accès - ${gallery.name}`" />
    <main class="flex min-h-screen items-center justify-center bg-neutral-950 px-4 text-neutral-100">
        <section class="w-full max-w-md rounded-2xl border border-neutral-800 bg-neutral-900 p-7 shadow-2xl">
            <p class="text-xs font-semibold uppercase tracking-widest text-neutral-500">Accès privé</p>
            <h1 class="mt-2 text-2xl font-bold">{{ gallery.name }}</h1>

            <div v-if="isClaimed" class="mt-6 rounded-lg border border-amber-800 bg-amber-950/40 p-4 text-sm text-amber-200">
                Cet accès a déjà été activé sur un autre appareil. Contactez l’administrateur pour le réinitialiser.
            </div>
            <div v-else-if="!isConfigured" class="mt-6 rounded-lg border border-amber-800 bg-amber-950/40 p-4 text-sm text-amber-200">
                Aucun mot de passe n’est encore configuré pour cette galerie.
            </div>
            <div v-else-if="isExpired" class="mt-6 rounded-lg border border-red-800 bg-red-950/40 p-4 text-sm text-red-200">
                Ce mot de passe a expiré. Contactez l’administrateur pour en obtenir un nouveau.
            </div>
            <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
                <p class="text-sm leading-6 text-neutral-400">
                    Ce mot de passe ne fonctionne qu’une fois. Cet ordinateur restera ensuite autorisé grâce à un cookie sécurisé.
                </p>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium">Mot de passe</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            autofocus
                            class="w-full rounded-lg border-neutral-700 bg-neutral-950 pr-11 text-white focus:border-neutral-500 focus:ring-neutral-500"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-neutral-400 hover:text-white"
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
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">{{ form.errors.password }}</p>
                </div>
                <button :disabled="form.processing" class="w-full rounded-lg bg-white px-4 py-3 font-semibold text-neutral-950 disabled:opacity-50">
                    Autoriser cet ordinateur
                </button>
            </form>
        </section>
    </main>
</template>
