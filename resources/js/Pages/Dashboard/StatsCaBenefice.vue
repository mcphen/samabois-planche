<template>
    <div class="card">
        <!-- En-tête -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="mb-0"><i class="fa fa-bar-chart mr-2 text-primary"></i>Chiffre d'Affaires &amp; Bénéfice par Mois</h6>
            <div class="d-flex align-items-center gap-2 mt-1 mt-sm-0">
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
            <!-- ── Filtres ──────────────────────────────────────────────── -->
            <div class="row mb-3">
                <div class="col-md-4 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">Client</label>
                    <select v-model="filters.client_id" class="form-control form-control-sm">
                        <option value="">Tous les clients</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div class="col-md-4 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">Essence</label>
                    <select v-model="filters.essence" class="form-control form-control-sm">
                        <option value="">Toutes les essences</option>
                        <option v-for="ess in essences" :key="ess" :value="ess">{{ ess }}</option>
                    </select>
                </div>

                <div class="col-md-4 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">Épaisseur (mm)</label>
                    <select v-model="filters.epaisseur" class="form-control form-control-sm">
                        <option value="">Toutes les épaisseurs</option>
                        <option value="6">6 mm</option>
                        <option value="27">27 mm</option>
                        <option value="40">40 mm</option>
                    </select>
                </div>

                <div class="col-md-4 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">Fournisseur</label>
                    <select v-model="filters.fournisseur_id" class="form-control form-control-sm">
                        <option value="">Tous les fournisseurs</option>
                        <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.name }}</option>
                    </select>
                </div>

                <div class="col-md-4 col-sm-6 mb-2">
                    <label class="small font-weight-bold mb-1">N° Contrat</label>
                    <input
                        v-model="filters.contract_number"
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="Ex: CONT-2025-01"
                    />
                </div>
            </div>

            <!-- ── Nav-tabs ─────────────────────────────────────────────── -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a
                        class="nav-link"
                        :class="{ active: activeTab === 'chart' }"
                        href="#"
                        @click.prevent="activeTab = 'chart'"
                    >
                        <i class="fa fa-bar-chart mr-1"></i>Graphique
                    </a>
                </li>
                <li class="nav-item">
                    <a
                        class="nav-link"
                        :class="{ active: activeTab === 'table' }"
                        href="#"
                        @click.prevent="activeTab = 'table'"
                    >
                        <i class="fa fa-table mr-1"></i>Tableau
                    </a>
                </li>
            </ul>

            <!-- ── Contenu ─────────────────────────────────────────────── -->
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>

            <template v-else-if="stats.length">
                <!-- Vue Graphique -->
                <div v-show="activeTab === 'chart'">
                    <BarChart :chart-data="chartData" />
                </div>

                <!-- Vue Tableau -->
                <div v-show="activeTab === 'table'" class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Période</th>
                                <th class="text-right">Chiffre d'Affaires</th>
                                <th class="text-right">Coût de revient</th>
                                <th class="text-right">Bénéfice</th>
                                <th class="text-right">Marge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in stats" :key="`${row.year}-${row.month}`">
                                <td class="font-weight-bold">{{ monthName(row.month) }} {{ row.year }}</td>
                                <td class="text-right text-primary">{{ formatPrice(row.total_revenue) }}</td>
                                <td class="text-right text-secondary">{{ formatPrice(row.cost_base) }}</td>
                                <td class="text-right" :class="row.profit >= 0 ? 'text-success' : 'text-danger'">
                                    {{ formatPrice(row.profit) }}
                                </td>
                                <td class="text-right">
                                    <span
                                        class="badge"
                                        :class="margeClass(row)"
                                    >
                                        {{ margePercent(row) }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td>Total</td>
                                <td class="text-right text-primary">{{ formatPrice(totaux.revenue) }}</td>
                                <td class="text-right text-secondary">{{ formatPrice(totaux.cost) }}</td>
                                <td class="text-right" :class="totaux.profit >= 0 ? 'text-success' : 'text-danger'">
                                    {{ formatPrice(totaux.profit) }}
                                </td>
                                <td class="text-right">
                                    <span class="badge" :class="totaux.revenue > 0 ? 'badge-success' : 'badge-secondary'">
                                        {{ totaux.revenue > 0 ? Math.round((totaux.profit / totaux.revenue) * 100) : 0 }}%
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>

            <div v-else class="alert alert-warning text-center mb-0">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                Aucune donnée disponible pour cette période et ces filtres.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import BarChart from "@/Components/stats/BarChart.vue";

const stats = ref([]);
const clients = ref([]);
const fournisseurs = ref([]);
const essences = ["Ayous", "Frake", "Dibetou", "Bois Rouge"];
const loading = ref(false);
const activeTab = ref("chart");

const filters = ref({
    client_id: "",
    essence: "",
    epaisseur: "",
    fournisseur_id: "",
    contract_number: "",
});

const MONTHS = [
    "", "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
    "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre",
];

const monthName = (n) => MONTHS[n] ?? n;

const formatPrice = (value) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(value ?? 0);

const margePercent = (row) =>
    row.total_revenue > 0 ? Math.round((row.profit / row.total_revenue) * 100) : 0;

const margeClass = (row) => {
    const m = margePercent(row);
    if (m >= 20) return "badge-success";
    if (m >= 10) return "badge-warning";
    return "badge-danger";
};

const totaux = computed(() => ({
    revenue: stats.value.reduce((s, r) => s + Number(r.total_revenue), 0),
    cost: stats.value.reduce((s, r) => s + Number(r.cost_base), 0),
    profit: stats.value.reduce((s, r) => s + Number(r.profit), 0),
}));

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
            {
                label: "Bénéfice",
                data: stats.value.map((s) => s.profit),
                backgroundColor: "#28a745",
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
        console.error("Erreur CA/Bénéfice:", e);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.value = { client_id: "", essence: "", epaisseur: "", fournisseur_id: "", contract_number: "" };
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
    const [{ data: c }, { data: f }] = await Promise.all([
        axios.get("/admin/clients/liste-clients"),
        axios.get("/admin/suppliers/liste-suppliers"),
    ]);
    clients.value = c.data ?? c;
    fournisseurs.value = f;
});
</script>

<style scoped>
.nav-link { cursor: pointer; }
</style>
