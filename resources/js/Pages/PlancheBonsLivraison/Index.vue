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

                <!-- Filtres de base -->
                <div class="row mb-2">
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Numero facture</label>
                        <input v-model="filters.numero_bl" type="text" class="form-control" placeholder="Rechercher..." @keyup.enter="fetchBons()" />
                    </div>

                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Client</label>
                        <select v-model="filters.client_id" class="form-control">
                            <option value="">Tous les clients</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">Statut</label>
                        <select v-model="filters.statut" class="form-control">
                            <option value="">Tous</option>
                            <option value="brouillon">Brouillon</option>
                            <option value="valide">Valide</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">Afficher</label>
                        <select v-model="perPage" class="form-control" @change="fetchBons()">
                            <option :value="25">25 par page</option>
                            <option :value="50">50 par page</option>
                            <option :value="100">100 par page</option>
                            <option :value="200">200 par page</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-2 d-flex align-items-end" style="gap:6px;">
                        <button type="button" class="btn btn-outline-secondary" title="Reinitialiser" @click="resetFilters">
                            <i class="fa fa-times"></i>
                        </button>
                        <button type="button" class="btn btn-primary flex-fill" @click="fetchBons()">
                            <i class="fa fa-search mr-1"></i>Filtrer
                        </button>
                    </div>
                </div>

                <!-- Toggle filtres avancés -->
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="showAdvanced = !showAdvanced">
                        <i class="fa mr-1" :class="showAdvanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        {{ showAdvanced ? 'Masquer les filtres avancés' : 'Afficher les filtres avancés' }}
                        <span v-if="hasAdvancedFilters" class="badge badge-primary ml-1">actif</span>
                    </button>
                </div>

                <!-- Filtres avancés -->
                <div v-if="showAdvanced" class="border rounded p-3 mb-3 bg-light">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold">Date du</label>
                            <input v-model="filters.date_from" type="date" class="form-control" />
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold">Date au</label>
                            <input v-model="filters.date_to" type="date" class="form-control" />
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold">Numero contrat</label>
                            <input v-model="filters.numero_contrat" type="text" class="form-control" placeholder="ex: CT-001" @keyup.enter="fetchBons()" />
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold">Fournisseur</label>
                            <select v-model="filters.supplier_id" class="form-control">
                                <option value="">Tous les fournisseurs</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small font-weight-bold">Code couleur</label>
                            <input v-model="filters.code_couleur" type="text" class="form-control" placeholder="ex: RAL-9010" @keyup.enter="fetchBons()" />
                        </div>
                    </div>
                </div>

                <!-- Infos résultats -->
                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="gap:6px;">
                    <small class="text-muted">
                        <span v-if="bons.total !== undefined">
                            {{ bons.from || 0 }}–{{ bons.to || 0 }} sur <strong>{{ bons.total }}</strong> facture(s)
                        </span>
                    </small>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Numero facture</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th class="text-center">Qte totale</th>
                                <th class="text-right">Montant total</th>
                                <th>Contrat(s)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="8" class="text-center py-4">
                                    <i class="fa fa-spinner fa-spin mr-2"></i>Chargement...
                                </td>
                            </tr>
                            <tr v-else-if="!bons.data.length">
                                <td colspan="8" class="text-center py-4 text-muted">Aucune facture enregistree.</td>
                            </tr>
                            <tr v-for="bon in bons.data" :key="bon.id">
                                <td class="font-weight-bold">{{ bon.numero_bl }}</td>
                                <td>{{ bon.client_name || '-' }}</td>
                                <td>{{ bon.date_livraison || '-' }}</td>
                                <td>
                                    <span class="badge" :class="bon.statut === 'valide' ? 'badge-success' : 'badge-warning'">
                                        {{ bon.statut }}
                                    </span>
                                </td>
                                <td class="text-center">{{ bon.quantite_totale_livree }}</td>
                                <td class="text-right">{{ bon.montant_total ? formatCurrency(bon.montant_total) : '-' }}</td>
                                <td>
                                    <span v-if="bon.contrats && bon.contrats.length" class="small">{{ bon.contrats.join(', ') }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap" style="gap:4px;">
                                        <Link :href="`/admin/planche-bons-livraison/${bon.id}`" class="btn btn-info btn-sm">
                                            <i class="fa fa-eye"></i> Voir
                                        </Link>
                                        <button
                                            v-if="bon.can_edit"
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            @click="deleteBon(bon.id)"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="bons.last_page > 1" class="d-flex align-items-center justify-content-between mt-3 flex-wrap" style="gap:8px;">
                    <small class="text-muted">Page {{ bons.current_page }} / {{ bons.last_page }}</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: bons.current_page === 1 }">
                                <button class="page-link" @click="fetchBons(1)" :disabled="bons.current_page === 1">
                                    <i class="fa fa-angle-double-left"></i>
                                </button>
                            </li>
                            <li class="page-item" :class="{ disabled: bons.current_page === 1 }">
                                <button class="page-link" @click="fetchBons(bons.current_page - 1)" :disabled="bons.current_page === 1">
                                    <i class="fa fa-angle-left"></i>
                                </button>
                            </li>

                            <li
                                v-for="page in paginationPages"
                                :key="page"
                                class="page-item"
                                :class="{ active: page === bons.current_page, disabled: page === '…' }"
                            >
                                <button v-if="page !== '…'" class="page-link" @click="fetchBons(page)">{{ page }}</button>
                                <span v-else class="page-link">…</span>
                            </li>

                            <li class="page-item" :class="{ disabled: bons.current_page === bons.last_page }">
                                <button class="page-link" @click="fetchBons(bons.current_page + 1)" :disabled="bons.current_page === bons.last_page">
                                    <i class="fa fa-angle-right"></i>
                                </button>
                            </li>
                            <li class="page-item" :class="{ disabled: bons.current_page === bons.last_page }">
                                <button class="page-link" @click="fetchBons(bons.last_page)" :disabled="bons.current_page === bons.last_page">
                                    <i class="fa fa-angle-double-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Factures planche' },
];

const loading = ref(false);
const showAdvanced = ref(false);
const perPage = ref(25);

const filters = reactive({
    numero_bl: '',
    client_id: '',
    statut: '',
    date_from: '',
    date_to: '',
    numero_contrat: '',
    supplier_id: '',
    code_couleur: '',
});

const bons = ref({ data: [], total: 0, current_page: 1, last_page: 1, from: 0, to: 0 });

const hasAdvancedFilters = computed(() =>
    !!filters.date_from || !!filters.date_to || !!filters.numero_contrat || !!filters.supplier_id || !!filters.code_couleur
);

const paginationPages = computed(() => {
    const current = bons.value.current_page;
    const last = bons.value.last_page;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = [];
    if (current <= 4) {
        pages.push(1, 2, 3, 4, 5, '…', last);
    } else if (current >= last - 3) {
        pages.push(1, '…', last - 4, last - 3, last - 2, last - 1, last);
    } else {
        pages.push(1, '…', current - 1, current, current + 1, '…', last);
    }
    return pages;
});

function fetchBons(page = 1) {
    loading.value = true;
    axios.get('/admin/planche-bons-livraison/listes', {
        params: {
            page,
            per_page: perPage.value,
            numero_bl: filters.numero_bl || undefined,
            client_id: filters.client_id || undefined,
            statut: filters.statut || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
            numero_contrat: filters.numero_contrat || undefined,
            supplier_id: filters.supplier_id || undefined,
            code_couleur: filters.code_couleur || undefined,
        },
    }).then((response) => {
        bons.value = response.data;
    }).finally(() => {
        loading.value = false;
    });
}

function resetFilters() {
    filters.numero_bl = '';
    filters.client_id = '';
    filters.statut = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.numero_contrat = '';
    filters.supplier_id = '';
    filters.code_couleur = '';
    fetchBons();
}

function deleteBon(bonId) {
    if (!confirm('Etes-vous sur de vouloir supprimer cette facture ?')) return;
    axios.delete(`/admin/planche-bons-livraison/${bonId}`).then(() => {
        fetchBons(bons.value.current_page || 1);
    });
}

function formatCurrency(value) {
    const num = Math.round(Number(value || 0));
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' CFA';
}

onMounted(() => {
    fetchBons();
});
</script>
