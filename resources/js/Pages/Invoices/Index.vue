<template>
    <Head :title="`Liste des factures clients | ${appName}`" />
    <AuthenticatedLayout>
        <!-- Utilisation du composant BreadcrumbsAndActions avec des breadcrumbs dynamiques -->
        <BreadcrumbsAndActions
            :title="'📄 Liste des factures clients'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button class="btn btn-primary mb-3" @click="goToCreatePage">➕ Nouvelle Facture</button>
            </template>
        </BreadcrumbsAndActions>

<!--        <div class="card mb-3">-->
<!--            <div class="card-body">-->
<!--                <div class="row">-->
<!--                    <div class="col-md-6">-->
<!--                        <div class="form-group">-->
<!--                            <label>Rechercher par numéro de colis</label>-->
<!--                            <div class="input-group">-->
<!--                                <input-->
<!--                                    type="text"-->
<!--                                    class="form-control"-->
<!--                                    v-model="numeroColis"-->
<!--                                    placeholder="Entrez le numéro de colis"-->
<!--                                    @input="fetchInvoices"-->
<!--                                />-->
<!--                                <div class="input-group-append">-->
<!--                                    <button-->
<!--                                        class="btn btn-primary"-->
<!--                                        @click="fetchInvoices"-->
<!--                                    >-->
<!--                                         Rechercher-->
<!--                                    </button>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->


        <div >

            <div class="card mb-3">
                <div class="body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Rechercher par numéro de colis</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="numeroColis"
                                :disabled="isLoading"
                                placeholder="Ex: 2024-001"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rechercher par essence</label>

                            <select  :disabled="isLoading" v-model="essence" class="form-control" required>
                                <option value="" disabled>Sélectionner l'essence</option>
                                <option v-for="ess in essences" :key="ess" :value="ess">{{ ess }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button class="btn btn-primary mt-4" @click="fetchInvoices" :disabled="isLoading">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                                🔎 Rechercher
                            </button>
                            <button class="btn btn-secondary mt-4" @click="resetAdvancedFilters" :disabled="isLoading">♻️ Réinitialiser</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="body">
                    <!-- Loader / placeholder while fetching data -->
                    <div v-if="isLoading" class="py-4 text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <div class="mt-2">Recherche en cours, veuillez patienter…</div>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table mb-0">
                            <thead class="thead-dark">
                            <tr>
                                <th>Matricule</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Total (FCFA)</th>
                                <!--th>Statut</th-->
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <input type="text" class="form-control" v-model="filters.matricule" placeholder="🔎 Rechercher par matricule">

                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="filters.client" placeholder="🔎 Rechercher par client">

                                </td>
                                <td>
                                    <input type="date" class="form-control" v-model="filters.date">

                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr v-if="!filteredInvoices.length">
                                <td colspan="5" class="text-center py-4">Aucun résultat</td>
                            </tr>
                            <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                                <td>{{ invoice.matricule }}</td>
                                <td>{{ invoice.client.name }}</td>
                                <td>{{ formatDate(invoice.date) }}</td>
                                <td>{{ formatPrice(invoice.total_price) }}</td>
                                <!--td>
                                    <span :class="statusClass(invoice.status)">{{ statusText(invoice.status) }}</span>
                                </td-->
                                <td>
                                    <button class="btn btn-info btn-sm m-1" @click="viewInvoice(invoice.id)">👁️ Voir</button>
                                    <button class="btn btn-success btn-sm m-1" @click="downloadPDF(invoice.id)"><i class="fa fa-print"></i> Imprimer</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>

</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { ref, onMounted, computed, watch } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';
import dayjs from 'dayjs';
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: '📄 Liste des Factures' }
];
const invoices = ref({ data: [], meta: {} });
const perPage = ref(10); // Nombre d'éléments par page par défaut
const numeroColis = ref('');
const essence = ref('');
const isLoading = ref(false);
const essences = ['Ayous', 'Frake', 'Dibetou', 'Bois Rouge','Dabema'];

async function fetchInvoices() {
    try {
        isLoading.value = true;
        const response = await axios.get(`/admin/invoices/listes`,{
            params:{
                numero_colis: numeroColis.value,
                essence: essence.value
            }
        });
        invoices.value = response.data;
    } catch (e) {
        console.error('Erreur lors du chargement des factures', e);
    } finally {
        isLoading.value = false;
    }
}

const totalPages = computed(() => {
    if (invoices.value.meta) {
        return Array.from({ length: invoices.value.meta.last_page }, (_, i) => i + 1);
    }
    return [];
});

// 📌 Filtres réactifs
const filters = ref({
    matricule: '',
    client: '',
    date: ''
});

// 📌 Filtrer dynamiquement les factures
const filteredInvoices = computed(() => {
    return invoices.value.data.filter(invoice => {
        return (
            (filters.value.matricule === '' || invoice.matricule.toLowerCase().includes(filters.value.matricule.toLowerCase())) &&
            (filters.value.client === '' || invoice.client.name.toLowerCase().includes(filters.value.client.toLowerCase())) &&
            (filters.value.date === '' || invoice.date === filters.value.date)
        );
    });
});

function statusText(status) {
    return status === 'pending' ? '🟡 En attente' :
        status === 'validated' ? '✅ Validée' :
            status === 'canceled' ? '❌ Annulée' : 'Inconnu';
}

function statusClass(status) {
    return status === 'pending' ? 'badge bg-warning' :
        status === 'validated' ? 'badge bg-success' :
            status === 'canceled' ? 'badge bg-danger' : 'badge bg-secondary';
}

function goToCreatePage() {
    Inertia.visit('/admin/invoices/create');
}

function viewInvoice(id) {
    Inertia.visit(`/admin/invoices/${id}/consultation`);
}

function downloadPDF(id) {
    window.open(`/admin/invoices/${id}/generate-pdf`, '_blank');
}

function formatDate(date) {
    return dayjs(date).format('DD/MM/YYYY');
}

function formatPrice(price) {

    let priceTotal =  price ? parseInt(price) : 0;
    return  new Intl.NumberFormat('de-DE').format(priceTotal); //.toLocaleString('fr-FR');

}

function resetAdvancedFilters(){
    numeroColis.value = '';
    essence.value = '';
    fetchInvoices();
}


// Extraire le numéro de page depuis l'URL des liens de pagination
function getPageNumber(url) {
    if (!url) return 1;
    const match = url.match(/page=(\d+)/);
    return match ? parseInt(match[1]) : 1;
}

watch(perPage, () => {
    fetchInvoices(1);
});

onMounted(() => {
    fetchInvoices();


});

</script>



<style scoped>

</style>



