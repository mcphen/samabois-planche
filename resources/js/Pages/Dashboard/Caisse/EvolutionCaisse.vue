<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fa fa-line-chart mr-2 text-primary"></i>Flux de trésorerie — 6 derniers mois
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
                <div v-show="tab === 'chart'">
                    <LineChart :chart-data="chartData" />
                </div>
                <div v-show="tab === 'table'" class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Mois</th>
                                <th class="text-right">Entrées</th>
                                <th class="text-right">Sorties</th>
                                <th class="text-right">Balance nette</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in evolution" :key="row.month">
                                <td class="font-weight-bold">{{ row.month }}</td>
                                <td class="text-right text-success">{{ fmt(row.entrees) }}</td>
                                <td class="text-right text-danger">{{ fmt(row.sorties) }}</td>
                                <td class="text-right font-weight-bold"
                                    :class="(row.entrees - row.sorties) >= 0 ? 'text-primary' : 'text-warning'">
                                    {{ fmt(row.entrees - row.sorties) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td>Total 6 mois</td>
                                <td class="text-right text-success">{{ fmt(totEntrees) }}</td>
                                <td class="text-right text-danger">{{ fmt(totSorties) }}</td>
                                <td class="text-right" :class="(totEntrees - totSorties) >= 0 ? 'text-primary' : 'text-warning'">
                                    {{ fmt(totEntrees - totSorties) }}
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

const totEntrees = computed(() => props.evolution.reduce((s, r) => s + r.entrees, 0));
const totSorties = computed(() => props.evolution.reduce((s, r) => s + r.sorties, 0));

const chartData = computed(() => ({
    labels: props.evolution.map((r) => r.month),
    datasets: [
        {
            label: "Entrées",
            data: props.evolution.map((r) => r.entrees),
            borderColor: "#28a745",
            backgroundColor: "rgba(40,167,69,0.08)",
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: "#28a745",
        },
        {
            label: "Sorties",
            data: props.evolution.map((r) => r.sorties),
            borderColor: "#dc3545",
            backgroundColor: "rgba(220,53,69,0.08)",
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: "#dc3545",
        },
        {
            label: "Balance nette",
            data: props.evolution.map((r) => r.entrees - r.sorties),
            borderColor: "#007bff",
            backgroundColor: "rgba(0,123,255,0.06)",
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: "#007bff",
            borderDash: [5, 3],
        },
    ],
}));
</script>
