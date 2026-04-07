<template>
    <Head :title="`Modification Facture | ${appName}`" />
    <AuthenticatedLayout>
        <!-- Utilisation du composant BreadcrumbsAndActions avec des breadcrumbs dynamiques -->
        <BreadcrumbsAndActions
            :title="'✏️ Modifier la Facture #'+invoice.matricule"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button type="button" class="btn btn-outline-primary" @click="goBack">
                    <i class="fa fa-arrow-left"></i>
                    Retour à la Liste
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <!-- Modifier Client et Date -->
                        <form @submit.prevent="updateInvoice">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Client *</label>
                                    <select v-model="form.client_id" class="form-control" required>
                                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Date *</label>
                                    <input v-model="form.date" type="date" class="form-control" required />
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">💾 Enregistrer les Modifications</button>
                        </form>
                    </div>

                    <div class="card-body">
                        <!-- Articles de la Facture -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mt-4">📜 Articles</h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <!-- Bouton pour ajouter d'autres contrats -->
                                <ArticleItemSelector :invoiceId="invoice.id" :essences="essences" @articleItemsAdded="handleArticleItemsAdded" />

                            </div>
                        </div>

                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Numéro Colis</th>
                                <th>Longueur</th>
                                <th>Largeur</th>
                                <th>Épaisseur</th>
                                <th>Volume</th>
                                <th>Prix</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(item, index) in articleItems" :key="item.id">
                                <td>
                                    <span v-if="item.editing || item.new_save">
                                      <input type="text" v-model="item.numero_colis" class="form-control" />
                                    </span>
                                    <span v-else>
                                      {{ item.numero_colis }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.editing || item.new_save">
                                      <input type="number" v-model="item.longueur" class="form-control" />
                                    </span>
                                    <span v-else>
                                      {{ item.longueur }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.editing || item.new_save">
                                      <input type="number" v-model="item.largeur" class="form-control" />
                                    </span>
                                    <span v-else>
                                      {{ item.largeur }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.editing || item.new_save">
                                      <input type="number" v-model="item.epaisseur" class="form-control" />
                                    </span>
                                    <span v-else>
                                      {{ item.epaisseur }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.editing || item.new_save">
                                      <input type="number" step="0.001" v-model="item.volume" class="form-control" />
                                    </span>
                                    <span v-else>
                                      {{ item.volume }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.editing || item.new_save">
                                      <input type="number" v-model="item.price" class="form-control" />
                                    </span>
                                    <span v-else>
                                      {{ item.price }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.editing  ">
                                      <!-- Boutons Enregistrer et Annuler -->
                                      <button class="btn btn-success btn-sm m-1" @click="saveEdit(index, item)">
                                        <i class="fa fa-check"></i>
                                      </button>
                                      <button class="btn btn-secondary btn-sm m-1" @click="cancelEdit(index, item)">
                                        <i class="fa fa-times"></i>
                                      </button>
                                    </span>
                                    <span v-else>
                                      <!-- Si l'article est nouveau on affiche le bouton d'ajout sinon le bouton d'édition -->
                                      <button v-if="!item.new_save" class="btn btn-info btn-sm m-1" @click="editArticle(index, item)">
                                        <i class="fa fa-pencil"></i>
                                      </button>
                                      <button v-if="item.new_save" class="btn btn-success btn-sm m-1" @click="addArticleItem(index, item)">
                                        <i class="fa fa-check"></i>
                                      </button>
                                      <button class="btn btn-danger btn-sm m-1" @click="removeArticle(index, item.id)">🗑️</button>
                                    </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>

<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { Inertia } from '@inertiajs/inertia';
import ArticleItemSearch from '@/Components/ArticleItemSearch.vue';
import ArticleItemSelector from "@/Components/ArticleItemSelector.vue";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des factures', link: '/admin/invoices', icon: 'fa fa-plus-square' },
    { label: '✏️ Modification Facture' }
];
const props = defineProps({
    invoice: Object,
    clients: Array,
    articleItems: Array,
    essences: Array
});

const form = ref({
    client_id: props.invoice.client.id,
    date: props.invoice.date
});

const showModal = ref(false);
const searchResults = ref([]);
const selectedArticleItems = ref([]);

// Modifier la facture (client et date)
function updateInvoice() {
    axios.post(`/admin/invoices/${props.invoice.id}/update`, form.value)
        .then(response => {
            Swal.fire("Facture mise à jour !", response.data.message, "success");
        })
        .catch(error => {
            Swal.fire("Erreur", "Impossible de modifier la facture.", "error");
        });
}

// Fonction pour ajouter un article à la facture
function addArticleItem(index, articleItem) {
    // Envoi des données au backend
    axios.post(`/admin/invoices/${props.invoice.id}/add-item`, {
        article_item_id: articleItem.id,
        numero_colis: articleItem.numero_colis,
        longueur: articleItem.longueur,
        largeur: articleItem.largeur,
        epaisseur: articleItem.epaisseur,
        volume: articleItem.volume,
        price: props.articleItems[index].price
    }).then(response => {
        // Réponse de succès, ajouter l'article à la liste
        props.articleItems[index].new_save = false; // Marque l'article comme ajouté
        Swal.fire("Article ajouté à la facture", "L'article a bien été ajouté.", "success");
    }).catch(error => {
        Swal.fire("Erreur", "Une erreur est survenue lors de l'ajout.", "error");
    });
}

function goBack() {
    Inertia.visit('/admin/invoices/'+props.invoice.id+'/consultation');
}

// Supprimer un article
function removeArticle(index, articleItemId) {
    console.log(index, articleItemId);
    //return false
    Swal.fire({
        title: "Supprimer cet article ?",
        text: "L'article sera supprimé de la facture.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/invoices/${props.invoice.id}/remove-item`, { article_item_id: articleItemId })
                .then(response => {
                    // Supprimer l'élément du tableau directement
                    props.articleItems.splice(index, 1);
                    Swal.fire("Article supprimé !", response.data.message, "success");
                })
                .catch(error => {
                    Swal.fire("Erreur", "Impossible de supprimer l'article.", "error");
                });
        }
    });
}

// Gérer l'ajout d'articles
function handleArticleItemsAdded(newItems) {


    // Vérifier si l'article existe déjà dans la liste avant d'ajouter
    newItems.forEach(article => {
        if (!props.articleItems.some(existingItem => existingItem.id === article.id)) {
            article.new_save = true;
            //article.editing = true;
            props.articleItems.push(article);
        }
    });
}

// Formater le prix
function formatPrice(price) {
    return price ? `${parseFloat(price).toFixed(2)} €` : '0.00 €';
}

// Fonction pour passer en mode édition
function editArticle(index, item) {
    item.editing = true;
    // Sauvegarder les valeurs originales pour pouvoir annuler
    item.backup = { ...item };
}

// Enregistrer les modifications
function saveEdit(index, item) {
    axios.post(`/admin/invoices/${props.invoice.id}/update-item`, item)
        .then(response => {
            item.editing = false;
            delete item.backup;
            Swal.fire("Article mis à jour !", response.data.message, "success");
        })
        .catch(error => {
            Swal.fire("Erreur", "Impossible de mettre à jour l'article.", "error");
        });
}

// Annuler la modification et restaurer les valeurs originales
function cancelEdit(index, item) {
    if (item.backup) {
        Object.assign(item, item.backup);
        delete item.backup;
    }
    item.editing = false;
}
</script>



<style scoped>

</style>



