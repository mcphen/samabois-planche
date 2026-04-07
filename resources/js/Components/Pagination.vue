<template>
    <nav class="mt-5" v-if="pagination && pagination.links && pagination.links.length > 0" aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <!-- Bouton First -->
            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link" @click="goToPage(1)" :disabled="pagination.current_page === 1">⏪ First</button>
            </li>

            <!-- Bouton Précédent -->
            <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                <button class="page-link" @click="goToPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url">⬅️ Précédent</button>
            </li>

            <!-- Numéros de Page Dynamiques -->
            <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === pagination.current_page }">
                <button class="page-link" @click="goToPage(page)">{{ page }}</button>
            </li>

            <!-- Bouton Suivant -->
            <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                <button class="page-link" @click="goToPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url">Suivant ➡️</button>
            </li>

            <!-- Bouton Last -->
            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link" @click="goToPage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page">⏩ Last</button>
            </li>
        </ul>
    </nav>
</template>

<script setup>
import { computed, defineProps, defineEmits } from 'vue';

// Props reçues du parent (données de pagination)
const props = defineProps({
    pagination: Object
});

// Événement pour informer le parent du changement de page
const emit = defineEmits(['pageChanged']);

// Fonction pour aller à une page spécifique
function goToPage(page) {
    if (page >= 1 && page <= props.pagination.last_page) {
        emit('pageChanged', page);
    }
}

// Calcul des pages visibles
const visiblePages = computed(() => {
    const currentPage = props.pagination.current_page;
    const lastPage = props.pagination.last_page;
    let startPage = Math.max(currentPage - 1, 1);
    let endPage = Math.min(startPage + 2, lastPage);

    if (endPage - startPage < 2) {
        startPage = Math.max(endPage - 2, 1);
    }

    const pages = [];
    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    return pages;
});
</script>
