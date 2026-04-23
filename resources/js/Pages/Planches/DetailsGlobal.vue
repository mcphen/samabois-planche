<template>
    <Head :title="`Vue globale du stock | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Vue globale du stock'"
            :breadcrumbs="breadcrumbs"
        />

        <div>
            <div class="card">
                <div class="header">
                    <h4 class="mt-3">🔍 Rechercher</h4>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <input
                                v-model="filters.numero_contrat"
                                type="text"
                                class="form-control"
                                placeholder="N° Contrat"
                                @input="debouncedSearch"
                            />
                        </div>
                        <div class="col-md-3">
                            <input
                                v-model="filters.code_couleur"
                                type="text"
                                class="form-control"
                                placeholder="Code couleur"
                                @input="debouncedSearch"
                            />
                        </div>
                        <div class="col-md-2">
                            <select v-model="filters.categorie" class="form-control" @change="fetchData">
                                <option value="">Toutes catégories</option>
                                <option value="mate">Mate</option>
                                <option value="semi_brillant">Semi-brillant</option>
                                <option value="brillant">Brillant</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input
                                v-model="filters.epaisseur"
                                type="text"
                                class="form-control"
                                placeholder="Épaisseur"
                                @input="debouncedSearch"
                            />
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-secondary w-100" @click="resetFilters">Réinitialiser</button>
                        </div>
                    </div>
                </div>

                <div class="body table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>N° CONTRAT</th>
                                <th>FOURNISSEUR</th>
                                <th>CODE COULEUR</th>
                                <th>CATÉGORIE</th>
                                <th>ÉPAISSEUR</th>
                                <th>QTÉ PRÉVUE</th>
                                <th>QTÉ LIVRÉE</th>
                                <th>QTÉ RESTANTE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="8" class="text-center py-3">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Chargement...
                                </td>
                            </tr>
                            <tr v-else-if="details.data.length === 0">
                                <td colspan="8" class="text-center py-3 text-muted">Aucun résultat.</td>
                            </tr>
                            <tr v-for="item in details.data" :key="item.id" v-else>
                                <td>{{ item.numero_contrat }}</td>
                                <td>{{ item.fournisseur }}</td>
                                <td>{{ item.code_couleur }}</td>
                                <td>{{ categorieLabel(item.categorie) }}</td>
                                <td>{{ item.epaisseur }}</td>
                                <td>{{ item.quantite_prevue }}</td>
                                <td>{{ item.quantite_livree }}</td>
                                <td>
                                    <span :class="item.quantite_restante <= 0 ? 'badge badge-danger' : 'badge badge-success'">
                                        {{ item.quantite_restante }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="details.data.length > 0">
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold">Totaux (page) :</td>
                                <td class="font-weight-bold">{{ totalPrevue }}</td>
                                <td class="font-weight-bold">{{ totalLivree }}</td>
                                <td class="font-weight-bold">{{ totalRestante }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Pagination -->
                    <div v-if="details.last_page > 1" class="d-flex justify-content-center mt-3">
                        <nav>
                            <ul class="pagination pagination-sm">
                                <li class="page-item" :class="{ disabled: details.current_page === 1 }">
                                    <button class="page-link" @click="goToPage(details.current_page - 1)">‹</button>
                                </li>
                                <li
                                    v-for="page in paginationPages"
                                    :key="page"
                                    class="page-item"
                                    :class="{ active: page === details.current_page, disabled: page === '…' }"
                                >
                                    <button class="page-link" @click="page !== '…' && goToPage(page)">{{ page }}</button>
                                </li>
                                <li class="page-item" :class="{ disabled: details.current_page === details.last_page }">
                                    <button class="page-link" @click="goToPage(details.current_page + 1)">›</button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <p v-if="details.total > 0" class="text-muted text-center small mt-1">
                        {{ details.total }} résultat(s) — page {{ details.current_page }}/{{ details.last_page }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const appName = import.meta.env.VITE_APP_NAME;

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Planches', link: '/admin/planches' },
    { label: 'Vue globale du stock' },
];

const details = ref({ data: [], total: 0, current_page: 1, last_page: 1 });
const loading = ref(false);
let debounceTimer = null;

const filters = reactive({
    numero_contrat: '',
    code_couleur: '',
    categorie: '',
    epaisseur: '',
    page: 1,
});

const totalPrevue   = computed(() => details.value.data.reduce((s, d) => s + (d.quantite_prevue ?? 0), 0));
const totalLivree   = computed(() => details.value.data.reduce((s, d) => s + (d.quantite_livree ?? 0), 0));
const totalRestante = computed(() => details.value.data.reduce((s, d) => s + (d.quantite_restante ?? 0), 0));

const paginationPages = computed(() => {
    const total = details.value.last_page;
    const current = details.value.current_page;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set([1, 2, current - 1, current, current + 1, total - 1, total].filter(p => p >= 1 && p <= total));
    const sorted = [...pages].sort((a, b) => a - b);
    const result = [];
    for (let i = 0; i < sorted.length; i++) {
        if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('…');
        result.push(sorted[i]);
    }
    return result;
});

async function fetchData() {
    loading.value = true;
    try {
        const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v !== null));
        const { data } = await axios.get('/admin/planches/details-global/search', { params });
        details.value = data;
    } finally {
        loading.value = false;
    }
}

function debouncedSearch() {
    filters.page = 1;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 350);
}

function goToPage(page) {
    if (page < 1 || page > details.value.last_page) return;
    filters.page = page;
    fetchData();
}

function resetFilters() {
    Object.assign(filters, { numero_contrat: '', code_couleur: '', categorie: '', epaisseur: '', page: 1 });
    fetchData();
}

function categorieLabel(cat) {
    const map = { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' };
    return map[cat] ?? cat;
}

onMounted(fetchData);
</script>
