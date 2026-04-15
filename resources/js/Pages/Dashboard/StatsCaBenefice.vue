<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="mb-0"><i class="fa fa-bar-chart mr-2 text-primary"></i>Chiffre d'Affaires par Mois — Planches</h6>
            <div class="d-flex align-items-center mt-1 mt-sm-0">
                <button @click="fetchStats" class="btn btn-primary btn-sm mr-1" :disabled="loading">
                    <i class="fa fa-filter mr-1"></i>Appliquer
                </button>
                <button @click="resetFilters" class="btn btn-outline-secondary btn-sm mr-1">
                    <i class="fa fa-times mr-1"></i>Réinitialiser
                </button>
                <button @click="exportPDF" class="btn btn-warning btn-sm">
                    <i class="fa fa-file-pdf-o mr-1"></i>PDF
                </button>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtres -->
            <div class="row mb-3">
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">Client</label>
                    <select v-model="filters.client_id" class="form-control form-control-sm">
                        <option value="">Tous les clients</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-2 position-relative">
                    <label class="small font-weight-bold mb-1">Code couleur</label>
                    <input
                        type="text"
                        v-model="couleurSearch"
                        @input="showCouleurDropdown = true"
                        @focus="showCouleurDropdown = true"
                        @blur="hideCouleurDropdown"
                        class="form-control form-control-sm"
                        placeholder="Rechercher une couleur…"
                        autocomplete="off"
                    />
                    <ul v-if="showCouleurDropdown && filteredCouleurs.length"
                        class="dropdown-menu show w-100 mb-0 p-0"
                        style="max-height:180px;overflow-y:auto;top:100%;z-index:1050;">
                        <li>
                            <a class="dropdown-item small py-1" href="#" @mousedown.prevent="selectCouleur(null)">
                                <em class="text-muted">Toutes les couleurs</em>
                            </a>
                        </li>
                        <li v-for="c in filteredCouleurs" :key="c.id">
                            <a class="dropdown-item small py-1" href="#" @mousedown.prevent="selectCouleur(c)">
                                {{ c.code }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">Épaisseur</label>
                    <select v-model="filters.epaisseur" class="form-control form-control-sm">
                        <option value="">Toutes les épaisseurs</option>
                        <option v-for="e in epaisseurs" :key="e.id" :value="e.intitule">{{ e.intitule }}</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">Catégorie</label>
                    <select v-model="filters.categorie" class="form-control form-control-sm">
                        <option value="">Toutes les catégories</option>
                        <option value="mate">Mate</option>
                        <option value="semi_brillant">Semi-brillant</option>
                        <option value="brillant">Brillant</option>
                    </select>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'chart' }" href="#" @click.prevent="activeTab = 'chart'">
                        <i class="fa fa-bar-chart mr-1"></i>Graphique
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'table' }" href="#" @click.prevent="activeTab = 'table'">
                        <i class="fa fa-table mr-1"></i>Tableau
                    </a>
                </li>
            </ul>

            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>

            <template v-else-if="stats.length">
                <div v-show="activeTab === 'chart'">
                    <BarChart :chart-data="chartData" />
                </div>

                <div v-show="activeTab === 'table'" class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Période</th>
                                <th class="text-right">Chiffre d'Affaires</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in stats" :key="`${row.year}-${row.month}`">
                                <td class="font-weight-bold">{{ monthName(row.month) }} {{ row.year }}</td>
                                <td class="text-right text-primary font-weight-bold">{{ formatPrice(row.total_revenue) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td>Total</td>
                                <td class="text-right text-primary">{{ formatPrice(totalRevenue) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>

            <div v-else class="alert alert-warning text-center mb-0">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                Aucune donnée disponible pour cette période.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import BarChart from "@/Components/stats/BarChart.vue";

const stats      = ref([]);
const clients    = ref([]);
const couleurs   = ref([]);
const epaisseurs = ref([]);
const loading    = ref(false);
const activeTab  = ref("chart");

const filters             = ref({ client_id: "", couleur_id: "", epaisseur: "", categorie: "" });
const couleurSearch       = ref("");
const showCouleurDropdown = ref(false);

const filteredCouleurs = computed(() =>
    couleurs.value.filter(c =>
        !couleurSearch.value || c.code.toLowerCase().includes(couleurSearch.value.toLowerCase())
    )
);

const selectCouleur = (c) => {
    filters.value.couleur_id = c ? c.id : "";
    couleurSearch.value      = c ? c.code : "";
    showCouleurDropdown.value = false;
};

const hideCouleurDropdown = () => {
    setTimeout(() => { showCouleurDropdown.value = false; }, 150);
};

const MONTHS = [
    "", "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
    "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre",
];

const monthName = (n) => MONTHS[n] ?? n;

const formatPrice = (value) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(value ?? 0);

const totalRevenue = computed(() =>
    stats.value.reduce((s, r) => s + Number(r.total_revenue), 0)
);

const chartData = computed(() => {
    if (!stats.value.length) return null;
    return {
        labels: stats.value.map((s) => `${monthName(s.month)} ${s.year}`),
        datasets: [
            {
                label: "Chiffre d'Affaires",
                data: stats.value.map((s) => s.total_revenue),
                backgroundColor: "#007bff",
            },
        ],
    };
});

const fetchStats = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/admin/dashboard/get-chiffres-affaires", { params: filters.value });
        stats.value = data;
    } catch (e) {
        console.error("Erreur CA:", e);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.value             = { client_id: "", couleur_id: "", epaisseur: "", categorie: "" };
    couleurSearch.value       = "";
    showCouleurDropdown.value = false;
    fetchStats();
};

const exportPDF = () => {
    const params = new URLSearchParams(
        Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== ""))
    ).toString();
    window.open(`/admin/dashboard/get-chiffres-affaires/pdf?${params}`, "_blank");
};

onMounted(async () => {
    fetchStats();
    const [{ data: c }, { data: col }, { data: ep }] = await Promise.all([
        axios.get("/admin/clients/liste-clients"),
        axios.get("/admin/configuration/planche-couleurs"),
        axios.get("/admin/configuration/epaisseurs"),
    ]);
    clients.value    = c.data ?? c;
    couleurs.value   = col;
    epaisseurs.value = ep;
});
</script>

<style scoped>
.nav-link { cursor: pointer; }
</style>
