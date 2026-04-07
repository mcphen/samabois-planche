<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fa fa-line-chart mr-2 text-primary"></i>Factures vs Paiements — 6 derniers mois
            </h6>
            <ul class="nav nav-tabs border-0 mb-0">
                <li class="nav-item">
                    <a class="nav-link py-1 px-2" :class="{ active: tab === 'chart' }"
                       href="#" @click.prevent="tab = 'chart'">
                        <i class="fa fa-line-chart"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2" :class="{ active: tab === 'table' }"
                       href="#" @click.prevent="tab = 'table'">
                        <i class="fa fa-table"></i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <template v-if="evolution.length">
                <!-- Graphique -->
                <div v-show="tab === 'chart'">
                    <LineChart :chart-data="chartData" />
                </div>

                <!-- Tableau -->
                <div v-show="tab === 'table'" class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Mois</th>
                                <th class="text-right">Facturé</th>
                                <th class="text-right">Encaissé</th>
                                <th class="text-right">Écart</th>
                                <th class="text-right">Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in evolution" :key="row.month">
                                <td class="font-weight-bold">{{ row.month }}</td>
                                <td class="text-right text-secondary">{{ fmt(row.total_facture) }}</td>
                                <td class="text-right text-success">{{ fmt(row.total_paye) }}</td>
                                <td class="text-right" :class="ecart(row) <= 0 ? 'text-success' : 'text-danger'">
                                    {{ fmt(ecart(row)) }}
                                </td>
                                <td class="text-right">
                                    <span class="badge" :class="tauxClass(row)">{{ taux(row) }}%</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td>Total</td>
                                <td class="text-right text-secondary">{{ fmt(totFacture) }}</td>
                                <td class="text-right text-success">{{ fmt(totPaye) }}</td>
                                <td class="text-right" :class="totFacture - totPaye <= 0 ? 'text-success' : 'text-danger'">
                                    {{ fmt(totFacture - totPaye) }}
                                </td>
                                <td class="text-right">
                                    <span class="badge" :class="totFacture > 0 ? (totPaye/totFacture >= 0.8 ? 'badge-success' : 'badge-warning') : 'badge-secondary'">
                                        {{ totFacture > 0 ? Math.round((totPaye / totFacture) * 100) : 0 }}%
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>
            <div v-else class="alert alert-warning text-center mb-0">
                <i class="fa fa-exclamation-triangle mr-1"></i> Aucune donnée sur 6 mois.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import LineChart from "@/Components/stats/LineChart.vue";

const props = defineProps({
    evolution: { type: Array, default: () => [] },
});

const tab = ref("chart");

const fmt = (v) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(v ?? 0);

const ecart = (row) => row.total_facture - row.total_paye;
const taux = (row) =>
    row.total_facture > 0 ? Math.round((row.total_paye / row.total_facture) * 100) : 100;
const tauxClass = (row) => {
    const t = taux(row);
    if (t >= 80) return "badge-success";
    if (t >= 50) return "badge-warning";
    return "badge-danger";
};

const totFacture = computed(() => props.evolution.reduce((s, r) => s + r.total_facture, 0));
const totPaye    = computed(() => props.evolution.reduce((s, r) => s + r.total_paye, 0));

const chartData = computed(() => ({
    labels: props.evolution.map((r) => r.month),
    datasets: [
        {
            label: "Facturé",
            data: props.evolution.map((r) => r.total_facture),
            borderColor: "#6f42c1",
            backgroundColor: "rgba(111,66,193,0.07)",
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: "#6f42c1",
        },
        {
            label: "Encaissé",
            data: props.evolution.map((r) => r.total_paye),
            borderColor: "#28a745",
            backgroundColor: "rgba(40,167,69,0.07)",
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: "#28a745",
        },
    ],
}));
</script>
