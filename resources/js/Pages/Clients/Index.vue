

<template>
    <Head :title="`Liste des clients | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Gestion des clients'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button type="button" class="btn btn-primary"  @click="showModal = true"><i class="fa fa-plus"></i> Ajouter un client</button>
            </template>
        </BreadcrumbsAndActions>

        <div>
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">Rechercher un client</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Nom</label>
                                <input v-model="searchParams.name" type="text" class="form-control" placeholder="Rechercher par nom" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Adresse</label>
                                <input v-model="searchParams.address" type="text" class="form-control" placeholder="Rechercher par adresse" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input v-model="searchParams.phone" type="text" class="form-control" placeholder="Rechercher par téléphone" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Email</label>
                                <input v-model="searchParams.email" type="email" class="form-control" placeholder="Rechercher par email" />
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-right">
                            <button class="btn btn-primary mr-2" @click="searchClients">Rechercher</button>
                            <button class="btn btn-secondary" @click="resetSearch">Réinitialiser</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="body">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="thead-dark">
                            <tr>
                                <th>Nom</th>
                                <th>Adresse</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="client in clients.data" :key="client.id">
                                <td>{{ client.name }}</td>
                                <td>{{ client.address }}</td>
                                <td>{{ client.phone }}</td>
                                <td>{{ client.email }}</td>
                                <td>
                                    <button class="btn btn-primary btn-sm m-1"
                                            @click="showClient(client.id)">
                                        Compte client
                                    </button>
                                    <button class="btn btn-warning btn-sm m-1"
                                            @click="openEditModal(client)">
                                        Modifier
                                    </button>
                                    <button class="btn btn-danger btn-sm m-1" @click="deleteClient(client.id)">Supprimer</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal d'ajout de client -->
            <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Ajouter un Client</h5>
                            <button type="button" class="close" @click="showModal = false">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form @submit.prevent="handleSubmit">
                                <div class="form-group">
                                    <label>Nom *</label>
                                    <input v-model="form.name" type="text" class="form-control" required />
                                </div>
                                <div class="form-group">
                                    <label>Adresse</label>
                                    <input v-model="form.address" type="text" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input v-model="form.phone" type="text" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input v-model="form.email" type="email" class="form-control" />
                                </div>
                                <button type="submit" class="btn btn-success">
                                    {{editModal ? 'Modifier':'Ajouter'}}
                                </button>
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import {Inertia} from "@inertiajs/inertia";
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des clients' }
];
const clients = ref({
    data: [],
    meta: {},
    links: {}
});

const searchParams = ref({
    name: '',
    address: '',
    phone: '',
    email: ''
});

const showModal = ref(false);
const editModal = ref(false);
const clientId = ref('');

function openEditModal(client){
    editModal.value = true;
    showModal.value = true;
    clientId.value = client.id;
    form.value.name = client.name;
    form.value.phone = client.phone;
    form.value.email = client.email;
    form.value.address = client.address;
}
const form = ref({
    name: '',
    address: '',
    phone: '',
    email: ''
});

// Récupérer les clients avec pagination
function fetchClients(page = 1) {
    // Construire l'URL avec les paramètres de recherche
    let url = `/admin/clients/liste-clients?page=${page}`;

    // Ajouter les paramètres de recherche s'ils existent
    if (searchParams.value.name) {
        url += `&name=${encodeURIComponent(searchParams.value.name)}`;
    }
    if (searchParams.value.address) {
        url += `&address=${encodeURIComponent(searchParams.value.address)}`;
    }
    if (searchParams.value.phone) {
        url += `&phone=${encodeURIComponent(searchParams.value.phone)}`;
    }
    if (searchParams.value.email) {
        url += `&email=${encodeURIComponent(searchParams.value.email)}`;
    }

    axios.get(url)
        .then(response => {
            clients.value = response.data;
            console.log(clients.value);
        });
}

// Fonction pour lancer la recherche
function searchClients() {
    fetchClients(1); // Rechercher sur la première page
}

// Fonction pour réinitialiser la recherche
function resetSearch() {
    searchParams.value = {
        name: '',
        address: '',
        phone: '',
        email: ''
    };
    fetchClients(1); // Rafraîchir la liste sans filtres
}

// Calculer le nombre total de pages
const totalPages = computed(() => {
    if (clients.value.meta) {
        return Array.from({ length: clients.value.meta.last_page }, (_, i) => i + 1);
    }
    return [];
});

// Ajouter un client
function addClient() {
    axios.post('/admin/clients/store', form.value)
        .then(() => {
            fetchClients(); // Rafraîchir la liste des clients
            form.value = { name: '', address: '', phone: '', email: '' };
            showModal.value = false;
        });
}
// Ajouter un client
function editClient(id) {
    axios.post('/admin/clients/'+id+'/update', form.value)
        .then(() => {
            fetchClients(); // Rafraîchir la liste des clients
            form.value = { name: '', address: '', phone: '', email: '' };
            showModal.value = false;
        });
}

// Supprimer un client
function deleteClient(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) {
        axios.delete(`/admin/clients/destroy/${id}`)
            .then(() => {
                fetchClients();
            });
    }
}
function handleSubmit() {
    if (editModal.value) {
        editClient(clientId.value);
    } else {
        addClient();
    }
}
function showClient(id) {
    Inertia.visit('/admin/clients/'+id+'/consultation');
}

// Charger les clients au démarrage
onMounted(() => {
    fetchClients();
});
</script>
<style scoped>

</style>



