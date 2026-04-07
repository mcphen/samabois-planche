
<template>
    <Head :title="`Liste des fournisseurs | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Liste des fournisseurs'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button type="button" class="btn btn-primary"  @click="showModal = true"><i class="fa fa-plus"></i> Ajouter un fournisseur</button>
            </template>
        </BreadcrumbsAndActions>

        <div>
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
                            <tr v-for="supplier in suppliers.data" :key="supplier.id">
                                <td>{{ supplier.name }}</td>
                                <td>{{ supplier.address }}</td>
                                <td>{{ supplier.phone }}</td>
                                <td>{{ supplier.email }}</td>
                                <td>
                                    <button class="btn btn-danger btn-sm" @click="deleteSupplier(supplier.id)">Supprimer</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <nav v-if="suppliers.meta" aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item" :class="{ disabled: !suppliers.links.prev }">
                                    <button class="page-link" @click="fetchSuppliers(suppliers.meta.current_page - 1)" :disabled="!suppliers.links.prev">Précédent</button>
                                </li>

                                <li v-for="page in totalPages" :key="page" class="page-item" :class="{ active: page === suppliers.meta.current_page }">
                                    <button class="page-link" @click="fetchSuppliers(page)">{{ page }}</button>
                                </li>

                                <li class="page-item" :class="{ disabled: !suppliers.links.next }">
                                    <button class="page-link" @click="fetchSuppliers(suppliers.meta.current_page + 1)" :disabled="!suppliers.links.next">Suivant</button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Modal d'ajout de fournisseur -->
            <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Ajouter un Fournisseur</h5>
                            <button type="button" class="close" @click="showModal = false">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form @submit.prevent="addSupplier">
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";

const appName = import.meta.env.VITE_APP_NAME;

const suppliers = ref({
    data: [],
    meta: {},
    links: {}
});
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des fournisseurs' }
];
const showModal = ref(false);
const form = ref({
    name: '',
    address: '',
    phone: '',
    email: ''
});

function fetchSuppliers(page = 1) {
    axios.get(`/admin/suppliers/liste-suppliers?page=${page}`)
        .then(response => {
            suppliers.value = response.data;
        });
}

const totalPages = computed(() => {
    if (suppliers.value.meta) {
        return Array.from({ length: suppliers.value.meta.last_page }, (_, i) => i + 1);
    }
    return [];
});

function addSupplier() {
    axios.post('/admin/suppliers/store', form.value)
        .then(() => {
            fetchSuppliers();
            form.value = { name: '', address: '', phone: '', email: '' };
            showModal.value = false;
        });
}

function deleteSupplier(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?')) {
        axios.delete(`/admin/suppliers/destroy/${id}`)
            .then(() => {
                fetchSuppliers();
            });
    }
}

onMounted(() => {
    fetchSuppliers();
});
</script>

<style scoped>

</style>



