<template>
    <Head :title="`Factures planche | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="'Factures planche'" :breadcrumbs="breadcrumbs">
            <template #action>
                <Link class="btn btn-primary" href="/admin/planche-bons-livraison/create">
                    <i class="fa fa-plus"></i> Nouvelle facture
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="card">
            <div class="body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Numero facture</label>
                        <input v-model="filters.numero_bl" type="text" class="form-control" placeholder="Rechercher une facture" />
                    </div>

                    <div class="col-md-3">
                        <label class="small font-weight-bold">Date</label>
                        <input v-model="filters.date_livraison" type="date" class="form-control" />
                    </div>

                    <div class="col-md-3">
                        <label class="small font-weight-bold">Statut</label>
                        <select v-model="filters.statut" class="form-control">
                            <option value="">Tous</option>
                            <option value="brouillon">Brouillon</option>
                            <option value="valide">Valide</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary mr-2" @click="resetFilters">
                            Reinitialiser
                        </button>
                        <button type="button" class="btn btn-primary" @click="fetchBons()">
                            Filtrer
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Numero facture</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Statut</th>
                                
                                
                                <th>Qte totale</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="10" class="text-center py-4">Chargement...</td>
                            </tr>
                            <tr v-else-if="!bons.data.length">
                                <td colspan="6" class="text-center py-4">Aucune facture enregistree.</td>
                            </tr>
                            <tr v-for="bon in bons.data" :key="bon.id">
                                <td>{{ bon.numero_bl }}</td>
                                <td>{{ bon.client_name || '-' }}</td>
                                <td>{{ bon.date_livraison }}</td>
                                <td>
                                    <span class="badge" :class="bon.statut === 'valide' ? 'badge-success' : 'badge-warning'">
                                        {{ bon.statut }}
                                    </span>
                                </td>
                                
                                
                                <td>{{ bon.quantite_totale_livree }}</td>
                                <td>
                                    <Link :href="`/admin/planche-bons-livraison/${bon.id}`" class="btn btn-info btn-sm mr-1">
                                        Voir
                                    </Link>
                                    <button
                                        v-if="bon.can_edit"
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        @click="deleteBon(bon.id)"
                                    >
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Factures planche' },
];

const loading = ref(false);
const filters = reactive({
    numero_bl: '',
    date_livraison: '',
    statut: '',
});

const bons = ref({
    data: [],
});

function fetchBons(page = 1) {
    loading.value = true;

    axios.get('/admin/planche-bons-livraison/listes', {
        params: {
            page,
            numero_bl: filters.numero_bl || undefined,
            date_livraison: filters.date_livraison || undefined,
            statut: filters.statut || undefined,
        },
    }).then((response) => {
        bons.value = response.data;
    }).finally(() => {
        loading.value = false;
    });
}

function resetFilters() {
    filters.numero_bl = '';
    filters.date_livraison = '';
    filters.statut = '';
    fetchBons();
}

function deleteBon(bonId) {
    if (!confirm('Etes-vous sur de vouloir supprimer cette facture ?')) {
        return;
    }

    axios.delete(`/admin/planche-bons-livraison/${bonId}`).then(() => {
        fetchBons(bons.value.current_page || 1);
    });
}

onMounted(() => {
    fetchBons();
});
</script>
