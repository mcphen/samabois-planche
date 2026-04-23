<template>
    <Head :title="`Vue globale des planches | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Vue globale des planches'"
            :breadcrumbs="breadcrumbs"
        />

        <div class="card">
            <div class="body">
                <!-- Filtres -->
                <div class="row mb-3 g-2">
                    <div class="col-md-2">
                        <label class="small font-weight-bold">Fournisseur</label>
                        <select v-model="filters.supplier_id" class="form-control form-control-sm">
                            <option value="">Tous</option>
                            <option v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="small font-weight-bold">N° Contrat</label>
                        <input v-model="filters.numero_contrat" type="text" class="form-control form-control-sm" placeholder="Ex: C-2024-01" />
                    </div>

                    <div class="col-md-2">
                        <label class="small font-weight-bold">Code couleur</label>
                        <input v-model="filters.code_couleur" type="text" class="form-control form-control-sm" placeholder="Ex: Bleu" />
                    </div>

                    <div class="col-md-1">
                        <label class="small font-weight-bold">Épaisseur</label>
                        <input v-model="filters.epaisseur" type="text" class="form-control form-control-sm" placeholder="Ex: 3.5" />
                    </div>

                    <div class="col-md-2">
                        <label class="small font-weight-bold">Disponibilité</label>
                        <select v-model="filters.disponibilite" class="form-control form-control-sm">
                            <option value="">Tous</option>
                            <option value="disponible">Disponible</option>
                            <option value="epuise">Épuisé</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end gap-1">
                        <button class="btn btn-primary btn-sm" @click="fetchDetails">
                            <i class="fa fa-search"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" @click="resetFilters">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Résumé -->
                <div class="row mb-3" v-if="details.length">
                    <div class="col-auto">
                        <span class="badge badge-primary mr-2">{{ details.length }} ligne(s)</span>
                        <span class="badge badge-success mr-2">{{ totalDisponible }} disponible(s)</span>
                        <span class="badge badge-danger mr-2">{{ totalEpuise }} épuisé(s)</span>
                        <span class="badge badge-info">{{ totalPrevues }} prévues · {{ totalLivrees }} livrées · {{ totalDisponibles }} restantes</span>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Fournisseur</th>
                                <th>Contrat</th>
                                <th>Couleur</th>
                                <th class="text-right">Épaisseur (mm)</th>
                                <th class="text-right">Prévues</th>
                                <th class="text-right">Livrées</th>
                                <th class="text-right">Disponibles</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="9" class="text-center py-4">
                                    <i class="fa fa-spinner fa-spin"></i> Chargement...
                                </td>
                            </tr>
                            <tr v-else-if="!details.length">
                                <td colspan="9" class="text-center py-4 text-muted">Aucun résultat.</td>
                            </tr>
                            <tr
                                v-else
                                v-for="d in details"
                                :key="d.id"
                                :class="{ 'table-danger': !d.disponible, 'table-success': d.disponible && d.quantite_disponible === d.quantite_prevue }"
                            >
                                <td class="text-muted small">#{{ d.id }}</td>
                                <td>{{ d.supplier_name || '-' }}</td>
                                <td class="font-weight-bold">{{ d.numero_contrat || '-' }}</td>
                                <td>
                                    <span class="badge badge-info" v-if="d.code_couleur">{{ d.code_couleur }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td class="text-right">{{ formatInteger(d.epaisseur) }}</td>
                                <td class="text-right">{{ d.quantite_prevue }}</td>
                                <td class="text-right">{{ d.quantite_livree }}</td>
                                <td class="text-right font-weight-bold">{{ d.quantite_disponible }}</td>
                                <td class="text-center">
                                    <span v-if="d.disponible" class="badge badge-success">Disponible</span>
                                    <span v-else class="badge badge-danger">Épuisé</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="details.length">
                            <tr class="font-weight-bold bg-light">
                                <td colspan="5" class="text-right">Totaux :</td>
                                <td class="text-right">{{ totalPrevues }}</td>
                                <td class="text-right">{{ totalLivrees }}</td>
                                <td class="text-right">{{ totalDisponibles }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const appName = import.meta.env.VITE_APP_NAME;

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Gestion des planches', link: '/admin/planches' },
    { label: 'Vue globale' },
];

const loading = ref(false);
const details = ref([]);

const filters = reactive({
    supplier_id: '',
    numero_contrat: '',
    code_couleur: '',
    epaisseur: '',
    disponibilite: 'disponible',
});

async function fetchDetails() {
    loading.value = true;
    try {
        const params = {};
        if (filters.supplier_id)    params.supplier_id    = filters.supplier_id;
        if (filters.numero_contrat) params.numero_contrat = filters.numero_contrat;
        if (filters.code_couleur)   params.code_couleur   = filters.code_couleur;
        if (filters.epaisseur)      params.epaisseur      = filters.epaisseur;
        if (filters.disponibilite)  params.disponibilite  = filters.disponibilite;

        const response = await axios.get('/admin/planches/details-global/search', { params });
        details.value = response.data;
    } finally {
        loading.value = false;
    }
}

function resetFilters() {
    filters.supplier_id    = '';
    filters.numero_contrat = '';
    filters.code_couleur   = '';
    filters.epaisseur      = '';
    filters.disponibilite  = '';
    fetchDetails();
}

const totalPrevues     = computed(() => details.value.reduce((s, d) => s + d.quantite_prevue, 0));
const totalLivrees     = computed(() => details.value.reduce((s, d) => s + d.quantite_livree, 0));
const totalDisponibles = computed(() => details.value.reduce((s, d) => s + d.quantite_disponible, 0));
const totalDisponible  = computed(() => details.value.filter(d => d.disponible).length);
const totalEpuise      = computed(() => details.value.filter(d => !d.disponible).length);

function formatInteger(value) {
    if (value === null || value === undefined || value === '') return '-';
    return Math.round(Number(value));
}

onMounted(fetchDetails);
</script>
