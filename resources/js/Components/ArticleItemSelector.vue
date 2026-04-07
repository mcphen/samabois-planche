<template>
    <div>
        <button class="btn btn-primary mt-3" @click="openSearchModal">➕ Ajouter des Articles</button>

        <!-- Modal pour Recherche d'Articles -->

        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">🔍 Rechercher des Articles</h5>
                        <button type="button" class="close" @click="showModal = false">&times;</button>
                    </div>
                    <div class="modal-body">

                        <ArticleItemSearch @search="fetchArticleItems" ></ArticleItemSearch>
                        <table class="table table-bordered mt-3">
                            <thead>
                            <tr>
                                <th>Numéro Colis</th>
                                <th>Longueur</th>
                                <th>Largeur</th>
                                <th>Épaisseur</th>
                                <th>Volume</th>
                                <th>Sélectionner</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="item in searchResults" :key="item.id">
                                <td>{{ item.numero_colis }}</td>
                                <td>{{ item.longueur }}</td>
                                <td>{{ item.largeur }}</td>
                                <td>{{ item.epaisseur }}</td>
                                <td>{{ formatVolume(item.volume) }}</td>
                                <td><input type="checkbox" v-model="selectedArticleItems" :value="item.id" /></td>
                            </tr>
                            </tbody>
                        </table>


                    </div>

                    <div class="modal-footer text-right">
                        <button class="btn btn-success mt-2" @click="addArticleItems">Ajouter</button>
                        <button class="btn btn-danger mt-2" @click="closeSearchModal">Annuler</button>

                    </div>
                </div>
            </div>
        </div>



    </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import axios from 'axios';
import ArticleItemSearch from '@/Components/ArticleItemSearch.vue';

// Déclaration des props
const props = defineProps({
    invoiceId: Number,
    essences: Array
});

// Variables
const showModal = ref(false);
const searchResults = ref([]);
const selectedArticleItems = ref([]);
const emit = defineEmits(['articleItemsAdded']);

// Ouvrir le modal
function openSearchModal() {
    showModal.value = true;
}

function formatVolume(price) {
    return price ? `${parseFloat(price).toFixed(3)} ` : '0.00';
}
// Fermer le modal
function closeSearchModal() {
    showModal.value = false;
}

// Rechercher des articles
function fetchArticleItems(searchParams) {
    axios.get('/admin/article-items/search', { params: searchParams })
        .then(response => {
            searchResults.value = response.data.data;
        })
        .catch(error => {
            console.error("Erreur lors de la recherche :", error);
        });
}

// Ajouter les articles sélectionnés
function addArticleItems() {
    axios.post(`/admin/invoices/${props.invoiceId}/add-article-items`, {
        article_items: selectedArticleItems.value
    }).then(response => {
        emit('articleItemsAdded', response.data.newArticleItems);
        closeSearchModal();
    }).catch(error => {
        console.error("Erreur lors de l'ajout des articles :", error);
    });
}
</script>

<style>
.modal-body{
    max-height: 70vh; /* Limite la hauteur de la section body à 60% de la hauteur de la fenêtre */
    overflow-y: auto; /* Ajout du scroll vertical */
    padding: 10px; /* Espacement interne */
}
@media (min-width: 576px) {
    .modal-dialog {
        max-width: 750px;
        margin: 1.75rem auto;
    }
}
</style>
