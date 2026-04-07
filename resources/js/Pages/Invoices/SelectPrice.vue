<template>
    <Head :title="`💸 Définir les Prix des Articles | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'💸 Définir les Prix des Articles'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button type="button"
                        class="btn btn-primary mt-3 ms-2"
                        @click="openSearchModal">
                    ➕ Rajouter d'autres contrats
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="body">
                        <form @submit.prevent="finalizeInvoice">
                            <div class="row">
                                <div class="col-lg-6">
                                    <h3>Client : {{ invoice.client.nom }}</h3>
                                    <p>Date : {{ invoice.date }}</p>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" placeholder="Appliquer un prix à tous les articles "  v-model="globalPrice" >
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary"  @click="applyGlobalPrice"
                                                    type="button">Appliquer à tous</button>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <table class="table table-bordered mt-3">
                                <thead>
                                <tr>
                                    <th>Numéro Colis</th>
                                    <th>Longueur</th>
                                    <th>Largeur</th>
                                    <th>Épaisseur</th>
                                    <th>Prix</th>
                                    <th>Supprimer</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in articleItems" :key="item.id">
                                    <td>{{ item.numero_colis }}</td>
                                    <td>{{ item.longueur }}</td>
                                    <td>{{ item.largeur }}</td>
                                    <td>{{ item.epaisseur }}</td>
                                    <td>
                                        <input type="number" v-model="item.price" class="form-control" />
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" @click="removeArticleItem(index, item.id)">
                                            🗑️ Supprimer
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>



                            <button type="submit" class="btn btn-success mt-3">Finaliser la facture</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pour Rechercher et Ajouter d'autres Articles -->
        <!-- Modal d'ajout de client -->
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

    </AuthenticatedLayout>

</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';
import Swal from 'sweetalert2';
import ArticleItemSearch from "@/Components/ArticleItemSearch.vue";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";

const props = defineProps({
    invoice: Object,
    articleItems: Array
});
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des factures', link: '/admin/invoices', icon: 'fa fa-plus-square' },
    { label: '📜 Nouvelle Facture' }
];
const showModal = ref(false);
const searchResults = ref([]);
const selectedArticleItems = ref([]);


// Ouvrir/Fermer le modal de recherche
function openSearchModal() {
    showModal.value = true;
}

function formatVolume(price) {
    return price ? `${parseFloat(price).toFixed(3)} ` : '0.00';
}

function closeSearchModal() {
    showModal.value = false;
    search.value = { contract_number: '', numero_colis: '' };
    selectedArticleItems.value = [];
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

// Ajouter des articles sélectionnés à la session
function addArticleItems() {
    axios.post(`/admin/invoices/${props.invoice.id}/add-article-items`, {
        article_items: selectedArticleItems.value
    }).then(response => {
        const newArticles = response.data.newArticleItems;

        // Vérifier si l'article existe déjà dans la liste avant d'ajouter
        newArticles.forEach(article => {
            if (!props.articleItems.some(existingItem => existingItem.id === article.id)) {
                props.articleItems.push(article);
            }
        });
        closeSearchModal();
    }).catch(error => {
        console.error("Erreur lors de l'ajout des articles :", error);
    });
}

// Supprimer un article de la liste et de la session
function removeArticleItem(index, articleItemId) {

    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Cet article sera supprimé de la facture.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/invoices/${props.invoice.id}/remove-article-item`, { article_item_id: articleItemId })
                .then(() => {
                    props.articleItems.splice(index, 1); // Supprime l'article de la liste Vue.js
                    Swal.fire("Supprimé !", "L'article a été supprimé avec succès.", "success");

                })
                .catch(error => {
                    console.error("Erreur lors de la suppression :", error);
                    Swal.fire("Erreur", "Impossible de supprimer l'article.", "error");

                });

        }
    });

}

const globalPrice = ref(null);

// Mettre à jour le prix de tous les items sélectionnés
function applyGlobalPrice() {
    for (let item of props.articleItems) {
        item.price = globalPrice.value;
    }
}

function finalizeInvoice() {
    const prices = props.articleItems.map(item => ({
        article_item_id: item.id,
        price: item.price
    }));

    Inertia.post(`/admin/invoices/${props.invoice.id}/finalize`, { prices });
}
</script>
<style scoped>
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



