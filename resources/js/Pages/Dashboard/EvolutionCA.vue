<template>
    <div class="card">
        <!-- En-tête -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="mb-0"><i class="fa fa-line-chart mr-2 text-primary"></i>Évolution du CA — 6 derniers mois</h6>
            <div class="d-flex align-items-center gap-2 mt-1 mt-sm-0">
                <button @click="fetchEvolution" class="btn btn-primary btn-sm mr-1" :disabled="loading">
                    <i class="fa fa-filter mr-1"></i>Appliquer
                </button>
                <button @click="resetFilters" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-times mr-1"></i>Réinitialiser
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
                        <i class="fa fa-line-chart mr-1"></i>Graphique
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

            <template v-else-if="rawData.length">
                <!-- Vue Graphique -->
                <div v-show="activeTab === 'chart'">
                    <LineChart :chart-data="chartData" />
                </div>

                <!-- Vue Tableau -->
                <div v-show="activeTab === 'table'" class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Mois</th>
                                <th class="text-right">Chiffre d'Affaires</th>
                                <th class="text-right">Évolution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in rawData" :key="row.month">
                                <td class="font-weight-bold">{{ row.month }}</td>
                                <td class="text-right text-primary font-weight-bold">
                                    {{ formatPrice(row.total_revenue) }}
                                </td>
                                <td class="text-right">
                                    <template v-if="i > 0">
                                        <span class="badge" :class="evolutionClass(rawData[i - 1].total_revenue, row.total_revenue)">
                                            <i :class="evolutionIcon(rawData[i - 1].total_revenue, row.total_revenue)" class="mr-1"></i>
                                            {{ evolutionPercent(rawData[i - 1].total_revenue, row.total_revenue) }}%
                                        </span>
                                    </template>
                                    <span v-else class="text-muted small">—</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td>Total 6 mois</td>
                                <td class="text-right text-primary">{{ formatPrice(totalRevenue) }}</td>
                                <td class="text-right text-muted small">Moy. {{ formatPrice(Math.round(totalRevenue / rawData.length)) }}/mois</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>

            <div v-else class="alert alert-warning text-center mb-0">
                <i class="fa fa-exclamation-triangle mr-1"></i> Aucune donnée disponible.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import LineChart from "@/Components/stats/LineChart.vue";

const rawData = ref([]);
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

const formatPrice = (value) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(value ?? 0);

const evolutionPercent = (prev, curr) => {
    if (!prev || prev === 0) return curr > 0 ? 100 : 0;
    return Math.round(((curr - prev) / prev) * 100);
};

const evolutionClass = (prev, curr) => {
    const p = evolutionPercent(prev, curr);
    if (p > 0) return "badge-success";
    if (p < 0) return "badge-danger";
    return "badge-secondary";
};

const evolutionIcon = (prev, curr) => {
    const p = evolutionPercent(prev, curr);
    if (p > 0) return "fa fa-arrow-up";
    if (p < 0) return "fa fa-arrow-down";
    return "fa fa-minus";
};

const totalRevenue = computed(() =>
    rawData.value.reduce((s, r) => s + Number(r.total_revenue), 0)
);

const chartData = computed(() => {
    if (!rawData.value.length) return null;
    return {
        labels: rawData.value.map((d) => d.month),
        datasets: [
            {
                label: "Chiffre d'Affaires",
                data: rawData.value.map((d) => d.total_revenue),
                borderColor: "#007bff",
                backgroundColor: "rgba(0, 123, 255, 0.08)",
                fill: true,
                tension: 0.4,
                pointBackgroundColor: "#007bff",
                pointRadius: 5,
            },
        ],
    };
});

const fetchEvolution = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/admin/dashboard/evolution-ca", { params: filters.value });
        rawData.value = data;
    } catch (e) {
        console.error("Erreur évolution CA:", e);
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.value = { client_id: "", essence: "", epaisseur: "", fournisseur_id: "", contract_number: "" };
    fetchEvolution();
};

onMounted(async () => {
    fetchEvolution();
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
