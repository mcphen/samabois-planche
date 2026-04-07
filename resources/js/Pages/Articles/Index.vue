<template>
    <Head :title="`Liste des stocks | ${appName}`" />
    <AuthenticatedLayout>
        <div>
            <!-- Utilisation du composant BreadcrumbsAndActions avec des breadcrumbs dynamiques -->
            <BreadcrumbsAndActions
                :title="'Gestion des stocks'"
                :breadcrumbs="breadcrumbs"
            >
                <!-- Contenu du slot action : le bouton -->
                <template #action>
                    <button class="btn btn-primary mb-3 me-2" @click="goToCreatePage">Ajouter un stock</button>
                    <button class="btn btn-outline-secondary mb-3" @click="downloadPdf">Enregistrer PDF</button>
                </template>
            </BreadcrumbsAndActions>

            <div>
                <div class="card">
                    <div class="body">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="thead-dark">
                                <tr>
                                    <th>Essence</th>
                                    <th>Fournisseur</th>
                                    <th>Numéro du Contrat</th>
                                    <th>Benefice</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="article in articles.data" :key="article.id">
                                    <td>{{ article.essence }}</td>
                                    <td>{{ article.supplier.name }}</td>
                                    <td>{{ article.contract_number }}</td>
                                    <td>{{ formatTotalPrice(article.profit) }}</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm m-1"
                                                @click="showArticle(article.id)">
                                            Voir plus
                                        </button>
                                        <button class="btn btn-danger btn-sm" @click="deleteArticle(article.id)">Supprimer</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>


</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Inertia } from '@inertiajs/inertia';
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import Swal from "sweetalert2";

const articles = ref({ data: [] });

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des articles' }
];
function fetchArticles() {
    axios.get('/admin/articles/fetch').then(response => {
        articles.value = response.data;
    });
}
function formatTotalPrice(price) {
    let priceTotal =  price ? parseInt(price) : 0;
    return  new Intl.NumberFormat('de-DE').format(priceTotal); //.toLocaleString('fr-FR');
}
function deleteArticle(id) {

    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Tous les articles seront supprimés ",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, annuler",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/admin/articles/${id}/destroy`)
                .then(response => {
                    fetchArticles();
                })
                .catch(error => {
                    Swal.fire("Erreur", error.response.data.error || "Impossible d'annuler la facture.", "error");
                });
        }
    });


}

function showArticle(id) {
    Inertia.visit('/admin/articles/'+id);
}
function goToCreatePage() {
    Inertia.visit('/admin/articles/create');
}
function downloadPdf() {
    window.open('/admin/articles/liste-pdf', '_blank');
}

onMounted(() => {
    fetchArticles();
});
</script>
<style scoped>

</style>
