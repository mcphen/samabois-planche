

<template>
    <Head :title="`Nouvelle Facture | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'📜 Nouvelle Facture'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button type="button" class="btn btn-primary" @click="goBack">
                    <i class="fa fa-arrow-left"></i>
                    Retour à la Liste des factures
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div>
            <div class="card">
                <div class="body">
                    <form @submit.prevent="createInvoice">
                        <div class="form-group">
                            <label>Client *
                                <button type="button" class="btn btn-success ms-3" @click="openModal">➕ Ajouter client</button>
                            </label>
                            <div class="dropdown-select">
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Rechercher un client..."
                                    v-model="clientSearch"
                                    @focus="showClientDropdown = true"
                                    @input="showClientDropdown = true"
                                />
                                <div v-if="showClientDropdown" class="dropdown-menu show w-100">
                                    <div v-if="filteredClients.length === 0" class="dropdown-item text-muted">Aucun client trouvé</div>
                                    <a
                                        v-for="client in filteredClients"
                                        :key="client.id"
                                        class="dropdown-item"
                                        href="#"
                                        @click.prevent="selectClient(client)"
                                    >
                                        {{ client.name }}
                                    </a>
                                </div>
                            </div>
                            <div v-if="selectedClient" class="mt-2">
                                <span class="badge badge-primary p-2">{{ selectedClient.name }} <i class="fa fa-times ml-1" @click="clearSelectedClient"></i></span>
                            </div>
                            <input type="hidden" v-model="form.client_id" required />
                        </div>

                        <div class="form-group">
                            <label>Date *</label>
                            <input v-model="form.date" type="date" class="form-control" required />
                        </div>

                        <h4 class="mt-3">🔍 Rechercher un Article</h4>

                        <ArticleItemSearch @search="fetchArticleItems" />

                        <table class="table table-bordered mt-3">
                            <thead>
                            <tr>
                                <th>Numéro Colis</th>
                                <th>Longueur</th>

                                <th>Épaisseur</th>
                                <th>Volume</th>
                                <th>Sélectionner</th>
                            </tr>
                            <tr>
                                <td>
                                    <input v-model="filters.numero_colis" class="form-control form-control-sm" placeholder="🔍 Rechercher..." />
                                </td>
                                <td>
                                    <input v-model="filters.longueur" class="form-control form-control-sm" placeholder="🔍 Rechercher..." />
                                </td>
                                <td>
                                    <input v-model="filters.epaisseur" class="form-control form-control-sm" placeholder="🔍 Rechercher..." />
                                </td>
                                <td>
                                    <input v-model="filters.volume" class="form-control form-control-sm" placeholder="🔍 Rechercher..." />

                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="item in filteredArticleItems" :key="item.id">
                                <td>{{ item.numero_colis }}</td>
                                <td>{{ item.longueur }}</td>

                                <td>{{ item.epaisseur }}</td>
                                <td>{{ formatVolume(item.volume) }}</td>
                                <td><input type="checkbox" v-model="form.article_items" :value="item.id" /></td>
                            </tr>
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-success mt-3">
                            Suivant
                            <span v-if="form.article_items.length>0">({{form.article_items.length}})</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal pour Ajouter un Client -->
            <!-- Modal d'ajout de client -->
            <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Ajouter un Client</h5>
                            <button type="button" class="close" @click="showModal = false">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form @submit.prevent="storeClient">
                                <div class="form-group">
                                    <label>Nom *</label>
                                    <input v-model="newClient.name" type="text" class="form-control" required />
                                </div>
                                <div class="form-group">
                                    <label>Adresse</label>
                                    <input v-model="newClient.address" type="text" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input v-model="newClient.phone" type="text" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input v-model="newClient.email" type="email" class="form-control" />
                                </div>
                                <button type="submit" class="btn btn-success">Ajouter</button>
                                <button type="button" class="btn btn-secondary ml-2" @click="showModal = false">Annuler</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>

</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';
import ArticleItemSearch from "@/Components/ArticleItemSearch.vue";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";

const props = defineProps({
    clients: Array
});
const showModal = ref(false);
const clientSearch = ref('');
const showClientDropdown = ref(false);
const selectedClient = ref(null);

const newClient = ref({
    name: '',
    address: '',
    phone: '',
    email: ''
});


const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des factures', link: '/admin/invoices', icon: 'fa fa-plus-square' },
    { label: '📜 Nouvelle Facture' }
];

const articleItems = ref({ data: [] });

const form = ref({
    client_id: '',
    date: new Date().toISOString().split("T")[0],
    article_items: []
});

function openModal() {
    showModal.value = true;

    console.log(showModal.value)
}

function formatVolume(price) {
    return price ? `${parseFloat(price).toFixed(3)} ` : '0.00';
}

function closeModal() {
    showModal.value = false;
    newClient.value = { name: '', address: '', phone: '', email: '' };
}

function storeClient() {
    axios.post('/admin/clients/store', newClient.value)
        .then(response => {
            props.clients.push(response.data.client);
            form.value.client_id = response.data.client.id;
            selectedClient.value = response.data.client;
            closeModal();
        })
        .catch(error => {
            console.error("Erreur lors de l'ajout du client", error);
            alert("Erreur : Le client existe peut-être déjà !");
        });
}

function fetchArticleItems(searchParams) {
    axios.get('/admin/article-items/search', { params: searchParams })
        .then(response => {
            // Trier les articles en respectant l'ordre alphanumérique
            articleItems.value = {
                ...response.data,
                data: response.data.data.sort((a, b) => naturalSort(a.numero_colis, b.numero_colis))
            };
        });
}

// Fonction de tri alphanumérique
function naturalSort(a, b) {
    return a.localeCompare(b, 'fr', { numeric: true, sensitivity: 'base' });
}

// Définition des filtres pour chaque colonne
const filters = ref({
    numero_colis: "",
    longueur: "",
    epaisseur: "",
    volume: "",
});

// Filtrage dynamique des résultats
const filteredArticleItems = computed(() => {
    return articleItems.value.data.filter((item) => {
        return (
            (!filters.value.numero_colis || item.numero_colis.toLowerCase().includes(filters.value.numero_colis.toLowerCase())) &&
            (!filters.value.longueur || item.longueur.toString().includes(filters.value.longueur)) &&
            (!filters.value.epaisseur || item.epaisseur.toString().includes(filters.value.epaisseur)) &&
            (!filters.value.volume || item.volume.toString().includes(filters.value.volume))
        );
    });
});

// Filtrage dynamique des clients
const filteredClients = computed(() => {
    if (!clientSearch.value) {
        return props.clients;
    }
    const searchTerm = clientSearch.value.toLowerCase();
    return props.clients.filter(client =>
        client.name.toLowerCase().includes(searchTerm)
    );
});
function createInvoice() {
    Inertia.post('/admin/invoices/store', form.value);
}

function goBack() {
    Inertia.visit('/admin/invoices');
}

// Méthodes pour la sélection de client
function selectClient(client) {
    selectedClient.value = client;
    form.value.client_id = client.id;
    clientSearch.value = '';
    showClientDropdown.value = false;
}

function clearSelectedClient() {
    selectedClient.value = null;
    form.value.client_id = '';
}

// Fermer le dropdown quand on clique ailleurs
function handleClickOutside(event) {
    const dropdownElement = document.querySelector('.dropdown-select');
    if (dropdownElement && !dropdownElement.contains(event.target)) {
        showClientDropdown.value = false;
    }
}

// Ajouter l'écouteur d'événement pour les clics en dehors du dropdown
onMounted(() => {
    document.addEventListener('click', handleClickOutside);

    // Initialiser le client sélectionné si form.client_id a déjà une valeur
    if (form.value.client_id) {
        const client = props.clients.find(c => c.id === form.value.client_id);
        if (client) {
            selectedClient.value = client;
        }
    }
});

// Supprimer l'écouteur d'événement quand le composant est détruit
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
<style scoped>
.dropdown-select {
    position: relative;
}

.dropdown-menu {
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
}

.dropdown-item {
    cursor: pointer;
    padding: 8px 12px;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 14px;
}

.badge i {
    cursor: pointer;
    margin-left: 5px;
}
</style>



