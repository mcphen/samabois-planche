<template>
    <Head :title="`Gestion des planches | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Gestion des planches'"
            :breadcrumbs="breadcrumbs"
        >
            <template #action>
                <Link class="btn btn-primary" href="/admin/planches/create">
                    <i class="fa fa-plus"></i> Ajouter des planches
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="card">
            <div class="body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Fournisseur</label>
                        <select v-model="filters.supplier_id" class="form-control">
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

                    <div class="col-md-3">
                        <label class="small font-weight-bold">Numero contrat</label>
                        <input
                            v-model="filters.numero_contrat"
                            type="text"
                            class="form-control"
                            placeholder="Rechercher un contrat"
                        />
                    </div>

                    <div class="col-md-3">
                        <label class="small font-weight-bold">Code couleur</label>
                        <input
                            v-model="filters.code_couleur"
                            type="text"
                            class="form-control"
                            placeholder="Ex: Bleu, Rouge"
                        />
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary mr-2" @click="resetFilters">
                            Reinitialiser
                        </button>
                        <button type="button" class="btn btn-primary" @click="fetchContrats()">
                            Filtrer
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fournisseur</th>
                                <th>Contrat</th>
                                <th>Nb planches</th>
                                <th>Nb details</th>
                                <th>Total feuilles</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="6" class="text-center py-4">Chargement...</td>
                            </tr>

                            <tr v-else-if="!contrats.data.length">
                                <td colspan="6" class="text-center py-4">Aucun contrat enregistre.</td>
                            </tr>

                            <template v-else>
                                <template v-for="contrat in contrats.data" :key="contrat.id">
                                    <tr>
                                        <td>{{ contrat.supplier?.name || '-' }}</td>
                                        <td class="font-weight-bold">{{ contrat.numero || '-' }}</td>
                                        <td>{{ contrat.total_planches || 0 }}</td>
                                        <td>{{ contrat.total_details || 0 }}</td>
                                        <td class="font-weight-bold">{{ contrat.total_quantite_prevue || 0 }}</td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-info btn-sm"
                                                @click="toggleContrat(contrat.id)"
                                            >
                                                {{ isContratExpanded(contrat.id) ? 'Masquer les planches' : 'Details' }}
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="isContratExpanded(contrat.id)">
                                        <td colspan="6" class="bg-light">
                                            <div class="p-3">
                                                <h6 class="mb-3">Planches du contrat {{ contrat.numero }}</h6>

                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered bg-white mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID planche</th>
                                                                <th>Code couleur</th>
                                                                <th>Categorie</th>
                                                                <th>Resume epaisseurs / feuilles</th>
                                                                <th>Total feuilles</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-if="!contrat.planches?.length">
                                                                <td colspan="6" class="text-center py-3">
                                                                    Aucune planche pour ce contrat.
                                                                </td>
                                                            </tr>

                                                            <template v-for="planche in contrat.planches" :key="planche.id">
                                                                <tr>
                                                                    <td>#{{ planche.id }}</td>
                                                                    <td>
                                                                        <span class="badge badge-info">
                                                                            {{ planche.code_couleur || '-' }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge" :class="categorieBadgeClass(planche.categorie)">
                                                                            {{ categorieLabel(planche.categorie) }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span
                                                                            v-for="detail in planche.details"
                                                                            :key="detail.id"
                                                                            class="badge badge-light border mr-1 mb-1"
                                                                            style="font-size: 12px;"
                                                                        >
                                                                            {{ formatDecimal(detail.epaisseur) }} mm x {{ detail.quantite_prevue }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="font-weight-bold">
                                                                        {{ planche.total_quantite_prevue || 0 }}
                                                                    </td>
                                                                    <td>
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-outline-info btn-sm mr-1"
                                                                            @click="togglePlanche(contrat.id, planche.id)"
                                                                        >
                                                                            {{ isPlancheExpanded(contrat.id, planche.id) ? 'Masquer details' : 'Voir planche_details' }}
                                                                        </button>

                                                                        <Link
                                                                            :href="`/admin/planches/${planche.id}`"
                                                                            class="btn btn-info btn-sm mr-1"
                                                                        >
                                                                            Ouvrir
                                                                        </Link>

                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-danger btn-sm"
                                                                            @click="deletePlanche(planche.id)"
                                                                        >
                                                                            Supprimer
                                                                        </button>
                                                                    </td>
                                                                </tr>

                                                                <tr v-if="isPlancheExpanded(contrat.id, planche.id)">
                                                                    <td colspan="6" class="p-0">
                                                                        <div class="p-3 border-top bg-white">
                                                                            <div class="table-responsive">
                                                                                <table class="table table-sm mb-0">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th>ID detail</th>
                                                                                            <th>Code couleur</th>
                                                                                            <th>Categorie</th>
                                                                                            <th>Epaisseur</th>
                                                                                            <th>Quantite prevue</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <tr v-if="!planche.details?.length">
                                                                                            <td colspan="5" class="text-center py-3">
                                                                                                Aucun detail pour cette planche.
                                                                                            </td>
                                                                                        </tr>

                                                                                        <tr v-for="detail in planche.details" :key="detail.id">
                                                                                            <td>#{{ detail.id }}</td>
                                                                                            <td>{{ detail.couleur?.code || planche.code_couleur || '-' }}</td>
                                                                                            <td>{{ categorieLabel(detail.categorie) }}</td>
                                                                                            <td>{{ formatDecimal(detail.epaisseur) }} mm</td>
                                                                                            <td>{{ detail.quantite_prevue || 0 }}</td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
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

const props = defineProps({
    suppliers: {
        type: Array,
        default: () => [],
    },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Gestion des planches' },
];

const loading = ref(false);
const expandedContrats = ref([]);
const expandedPlanches = ref([]);
const filters = reactive({
    supplier_id: '',
    numero_contrat: '',
    code_couleur: '',
});

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

    axios.get('/admin/planches/listes', {
        params: {
            page,
            supplier_id: filters.supplier_id || undefined,
            numero_contrat: filters.numero_contrat || undefined,
            code_couleur: filters.code_couleur || undefined,
        },
    }).then((response) => {
        contrats.value = response.data;
        expandedContrats.value = [];
        expandedPlanches.value = [];
    }).finally(() => {
        loading.value = false;
    });
}

function resetFilters() {
    filters.supplier_id = '';
    filters.numero_contrat = '';
    filters.code_couleur = '';
    fetchContrats();
}

function toggleContrat(contratId) {
    if (isContratExpanded(contratId)) {
        expandedContrats.value = expandedContrats.value.filter((id) => id !== contratId);
        expandedPlanches.value = expandedPlanches.value.filter((key) => !key.startsWith(`${contratId}-`));
        return;
    }

    expandedContrats.value = [...expandedContrats.value, contratId];
}

function isContratExpanded(contratId) {
    return expandedContrats.value.includes(contratId);
}

function plancheExpansionKey(contratId, plancheId) {
    return `${contratId}-${plancheId}`;
}

function togglePlanche(contratId, plancheId) {
    const key = plancheExpansionKey(contratId, plancheId);

    if (expandedPlanches.value.includes(key)) {
        expandedPlanches.value = expandedPlanches.value.filter((item) => item !== key);
        return;
    }

    expandedPlanches.value = [...expandedPlanches.value, key];
}

function isPlancheExpanded(contratId, plancheId) {
    return expandedPlanches.value.includes(plancheExpansionKey(contratId, plancheId));
}

function deletePlanche(plancheId) {
    if (!confirm('Etes-vous sur de vouloir supprimer cette planche ?')) {
        return;
    }

    axios.delete(`/admin/planches/${plancheId}/destroy`).then(() => {
        fetchContrats(contrats.value.current_page || 1);
    });
}

function categorieLabel(cat) {
    return { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' }[cat] || cat || '-';
}

function categorieBadgeClass(cat) {
    return { mate: 'badge-secondary', semi_brillant: 'badge-warning', brillant: 'badge-success' }[cat] || 'badge-light';
}

function formatDecimal(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return Number(value).toFixed(2);
}

onMounted(() => {
    fetchContrats();
});
</script>
