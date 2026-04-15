<template>
    <Head :title="`Gestion des contrats | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Gestion des contrats'"
            :breadcrumbs="breadcrumbs"
        >
            <template #action>
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <Link class="btn btn-outline-info" href="/admin/planche-bons-livraison">
                        <i class="fa fa-truck"></i> Factures
                    </Link>
                    <Link class="btn btn-primary" href="/admin/planches/create">
                        <i class="fa fa-plus"></i> Ajouter des planches
                    </Link>
                </div>
            </template>
        </BreadcrumbsAndActions>

        <div class="card">
            <div class="body">
                <div class="row mb-3">
                    <!-- Fournisseur -->
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label class="small font-weight-bold">Fournisseur</label>
                        <select v-model="filters.supplier_id" class="form-control form-control-sm">
                            <option value="">Tous les fournisseurs</option>
                            <option
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                :value="String(supplier.id)"
                            >
                                {{ supplier.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Numéro contrat -->
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label class="small font-weight-bold">Numéro contrat</label>
                        <input
                            v-model="filters.numero_contrat"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Rechercher un contrat"
                        />
                    </div>

                    <!-- Code couleur (autocomplete) -->
                    <div class="col-md-3 col-sm-6 mb-2 position-relative">
                        <label class="small font-weight-bold">Code couleur</label>
                        <input
                            v-model="filters.code_couleur"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Ex: Bleu, Rouge…"
                            @input="showCouleurDropdown = true"
                            @focus="showCouleurDropdown = true"
                            @blur="hideCouleurDropdown"
                            autocomplete="off"
                        />
                        <ul v-if="showCouleurDropdown && filteredCouleurs.length"
                            class="dropdown-menu show w-100 mb-0 p-0"
                            style="max-height:180px;overflow-y:auto;top:100%;z-index:1050;">
                            <li>
                                <a class="dropdown-item small py-1" href="#" @mousedown.prevent="selectCouleur(null)">
                                    <em class="text-muted">Tous les codes</em>
                                </a>
                            </li>
                            <li v-for="c in filteredCouleurs" :key="c.id">
                                <a class="dropdown-item small py-1" href="#" @mousedown.prevent="selectCouleur(c)">
                                    {{ c.code }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Client (autocomplete) -->
                    <div class="col-md-3 col-sm-6 mb-2 position-relative">
                        <label class="small font-weight-bold">Client</label>
                        <input
                            v-model="clientSearch"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Rechercher un client…"
                            @input="showClientDropdown = true"
                            @focus="showClientDropdown = true"
                            @blur="hideClientDropdown"
                            autocomplete="off"
                        />
                        <ul v-if="showClientDropdown && filteredClients.length"
                            class="dropdown-menu show w-100 mb-0 p-0"
                            style="max-height:180px;overflow-y:auto;top:100%;z-index:1050;">
                            <li>
                                <a class="dropdown-item small py-1" href="#" @mousedown.prevent="selectClient(null)">
                                    <em class="text-muted">Tous les clients</em>
                                </a>
                            </li>
                            <li v-for="c in filteredClients" :key="c.id">
                                <a class="dropdown-item small py-1" href="#" @mousedown.prevent="selectClient(c)">
                                    {{ c.name }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12 d-flex">
                        <button type="button" class="btn btn-outline-secondary btn-sm mr-2" @click="resetFilters">
                            <i class="fa fa-times mr-1"></i>Réinitialiser
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" @click="fetchContrats()">
                            <i class="fa fa-filter mr-1"></i>Filtrer
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fournisseur</th>
                                <th>Contrat</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="3" class="text-center py-4">Chargement...</td>
                            </tr>

                            <tr v-else-if="!contrats.data.length">
                                <td colspan="3" class="text-center py-4">Aucun contrat enregistre.</td>
                            </tr>

                            <template v-else>
                                <tr v-for="contrat in contrats.data" :key="contrat.id">
                                    <td>{{ contrat.supplier?.name || '-' }}</td>
                                    <td class="font-weight-bold">{{ contrat.numero || '-' }}</td>
                                    <td>
                                        <Link
                                            :href="`/admin/contrats/${contrat.id}`"
                                            class="btn btn-info btn-sm"
                                        >
                                            Ouvrir
                                        </Link>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <nav v-if="contrats.last_page > 1" class="mt-4" aria-label="Pagination des contrats">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" :class="{ disabled: !contrats.prev_page_url }">
                            <button
                                class="page-link"
                                :disabled="!contrats.prev_page_url"
                                @click="fetchContrats(contrats.current_page - 1)"
                            >
                                Precedent
                            </button>
                        </li>

                        <li
                            v-for="page in totalPages"
                            :key="page"
                            class="page-item"
                            :class="{ active: page === contrats.current_page }"
                        >
                            <button class="page-link" @click="fetchContrats(page)">
                                {{ page }}
                            </button>
                        </li>

                        <li class="page-item" :class="{ disabled: !contrats.next_page_url }">
                            <button
                                class="page-link"
                                :disabled="!contrats.next_page_url"
                                @click="fetchContrats(contrats.current_page + 1)"
                            >
                                Suivant
                            </button>
                        </li>
                    </ul>
                </nav>
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

defineProps({
    suppliers: {
        type: Array,
        default: () => [],
    },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Gestion des contrats' },
];

const loading = ref(false);
const filters = reactive({
    supplier_id:    '',
    numero_contrat: '',
    code_couleur:   '',
    client_id:      '',
});

// ── Données pour les autocompletes ──────────────────────────────────────────
const couleurs = ref([]);
const clients  = ref([]);

// ── Autocomplete code couleur ────────────────────────────────────────────────
const showCouleurDropdown = ref(false);

const filteredCouleurs = computed(() =>
    couleurs.value.filter(c =>
        !filters.code_couleur || c.code.toLowerCase().includes(filters.code_couleur.toLowerCase())
    )
);

const selectCouleur = (c) => {
    filters.code_couleur      = c ? c.code : '';
    showCouleurDropdown.value = false;
};

const hideCouleurDropdown = () => {
    setTimeout(() => { showCouleurDropdown.value = false; }, 150);
};

// ── Autocomplete client ──────────────────────────────────────────────────────
const clientSearch       = ref('');
const showClientDropdown = ref(false);

const filteredClients = computed(() =>
    clients.value.filter(c =>
        !clientSearch.value || c.name.toLowerCase().includes(clientSearch.value.toLowerCase())
    )
);

const selectClient = (c) => {
    filters.client_id        = c ? c.id : '';
    clientSearch.value       = c ? c.name : '';
    showClientDropdown.value = false;
};

const hideClientDropdown = () => {
    setTimeout(() => { showClientDropdown.value = false; }, 150);
};

// ── Contrats ─────────────────────────────────────────────────────────────────
const contrats = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    prev_page_url: null,
    next_page_url: null,
});

const totalPages = computed(() => {
    const lastPage = contrats.value.last_page || 0;
    return Array.from({ length: lastPage }, (_, index) => index + 1);
});

function fetchContrats(page = 1) {
    loading.value = true;

    axios.get('/admin/contrats/listes', {
        params: {
            page,
            supplier_id:    filters.supplier_id    || undefined,
            numero_contrat: filters.numero_contrat || undefined,
            code_couleur:   filters.code_couleur   || undefined,
            client_id:      filters.client_id      || undefined,
        },
    }).then((response) => {
        contrats.value = response.data;
    }).finally(() => {
        loading.value = false;
    });
}

function resetFilters() {
    filters.supplier_id    = '';
    filters.numero_contrat = '';
    filters.code_couleur   = '';
    filters.client_id      = '';
    clientSearch.value     = '';
    fetchContrats();
}

onMounted(async () => {
    fetchContrats();
    const [{ data: col }, { data: cli }] = await Promise.all([
        axios.get('/admin/configuration/planche-couleurs'),
        axios.get('/admin/clients/liste-clients'),
    ]);
    couleurs.value = col;
    clients.value  = cli.data ?? cli;
});
</script>
