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

    axios.get('/admin/contrats/listes', {
        params: {
            page,
            supplier_id: filters.supplier_id || undefined,
            numero_contrat: filters.numero_contrat || undefined,
            code_couleur: filters.code_couleur || undefined,
        },
    }).then((response) => {
        contrats.value = response.data;
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

onMounted(() => {
    fetchContrats();
});
</script>
