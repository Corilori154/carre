<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    gallery: Object,
    redirect: String,
    isClaimed: Boolean,
    isConfigured: Boolean,
})

const form = useForm({
    password: '',
    redirect: props.redirect,
})

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
            <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
                <p class="text-sm leading-6 text-neutral-400">
                    Ce mot de passe ne fonctionne qu’une fois. Cet ordinateur restera ensuite autorisé grâce à un cookie sécurisé.
                </p>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium">Mot de passe</label>
                    <input id="password" v-model="form.password" type="password" autocomplete="current-password" required autofocus class="w-full rounded-lg border-neutral-700 bg-neutral-950 text-white focus:border-neutral-500 focus:ring-neutral-500" />
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">{{ form.errors.password }}</p>
                </div>
                <button :disabled="form.processing" class="w-full rounded-lg bg-white px-4 py-3 font-semibold text-neutral-950 disabled:opacity-50">
                    Autoriser cet ordinateur
                </button>
            </form>
        </section>
    </main>
</template>
