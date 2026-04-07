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
                        <label class="small font-weight-bold">N� Contrat</label>
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
                            R�initialiser
                        </button>
                        <button type="button" class="btn btn-primary" @click="fetchPlanches()">
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
                                <th>Code couleur</th>
                                <th>Catégorie</th>
                                <th>Épaisseurs / Feuilles</th>
                                <th>Total feuilles</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="7" class="text-center py-4">Chargement...</td>
                            </tr>
                            <tr v-else-if="!planches.data.length">
                                <td colspan="7" class="text-center py-4">Aucune planche enregistrée.</td>
                            </tr>
                            <tr v-for="planche in planches.data" :key="planche.id">
                                <td>{{ planche.contrat?.supplier?.name || '-' }}</td>
                                <td>{{ planche.contrat?.numero || '-' }}</td>
                                <td>
                                    <span class="badge badge-info">{{ planche.couleur?.code || '-' }}</span>
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
                                        style="font-size:12px;"
                                    >
                                        {{ formatDecimal(detail.epaisseur) }} mm &times; {{ detail.quantite_prevue }}
                                    </span>
                                </td>
                                <td class="font-weight-bold">{{ planche.total_quantite_prevue || 0 }}</td>
                                <td>
                                    <Link
                                        :href="`/admin/planches/${planche.id}`"
                                        class="btn btn-info btn-sm mr-1"
                                    >
                                        Voir
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
                        </tbody>
                    </table>
                </div>

                <nav v-if="planches.last_page > 1" class="mt-4" aria-label="Pagination des planches">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" :class="{ disabled: !planches.prev_page_url }">
                            <button
                                class="page-link"
                                :disabled="!planches.prev_page_url"
                                @click="fetchPlanches(planches.current_page - 1)"
                            >
                                Pr�c�dent
                            </button>
                        </li>

                        <li
                            v-for="page in totalPages"
                            :key="page"
                            class="page-item"
                            :class="{ active: page === planches.current_page }"
                        >
                            <button class="page-link" @click="fetchPlanches(page)">
                                {{ page }}
                            </button>
                        </li>

                        <li class="page-item" :class="{ disabled: !planches.next_page_url }">
                            <button
                                class="page-link"
                                :disabled="!planches.next_page_url"
                                @click="fetchPlanches(planches.current_page + 1)"
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
const filters = reactive({
    supplier_id: '',
    numero_contrat: '',
    code_couleur: '',
});

const planches = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    prev_page_url: null,
    next_page_url: null,
});

const totalPages = computed(() => {
    const lastPage = planches.value.last_page || 0;
    return Array.from({ length: lastPage }, (_, index) => index + 1);
});

function fetchPlanches(page = 1) {
    loading.value = true;

    axios.get('/admin/planches/listes', {
        params: {
            page,
            supplier_id: filters.supplier_id || undefined,
            numero_contrat: filters.numero_contrat || undefined,
            code_couleur: filters.code_couleur || undefined,
        },
    }).then((response) => {
        planches.value = response.data;
    }).finally(() => {
        loading.value = false;
    });
}

function resetFilters() {
    filters.supplier_id = '';
    filters.numero_contrat = '';
    filters.code_couleur = '';
    fetchPlanches();
}

function deletePlanche(plancheId) {
    if (!confirm('�tes-vous s�r de vouloir supprimer cette planche ?')) {
        return;
    }

    axios.delete(`/admin/planches/${plancheId}/destroy`).then(() => {
        fetchPlanches(planches.value.current_page || 1);
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
    fetchPlanches();
});
</script>
