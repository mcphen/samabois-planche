<template>
    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-12">
            <h2>{{ title }}</h2>
            <ul class="breadcrumb">
                <!-- Dynamiser la génération des liens dans le breadcrumb -->
                <li class="breadcrumb-item" v-for="(breadcrumb, index) in breadcrumbs" :key="index">
                    <!-- Si un lien existe, on l'affiche -->
                    <a v-if="breadcrumb.link" :href="breadcrumb.link">
                        <i v-if="breadcrumb.icon" :class="breadcrumb.icon"></i>
                        {{ breadcrumb.label }}
                    </a>
                    <!-- Si ce n'est pas un lien, on affiche simplement le texte -->
                    <span v-else>{{ breadcrumb.label }}</span>
                </li>
            </ul>
        </div>
        <div class="col-lg-8 col-md-8 col-sm-12">
            <div class="d-flex flex-row-reverse">
                <div class="page_action">
                    <!-- Slot pour insérer le bouton ou d'autres éléments -->
                    <slot name="action"></slot>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

// Props pour gérer le titre et les breadcrumbs dynamiques
const props = defineProps({
    title: {
        type: String,
        required: true
    },
    breadcrumbs: {
        type: Array,
        required: true
    },

});
</script>

<style scoped>
/* Style optionnel */
</style>
