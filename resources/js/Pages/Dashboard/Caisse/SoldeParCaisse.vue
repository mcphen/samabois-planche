<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fa fa-archive mr-2 text-info"></i>Solde par caisse
            </h6>
            <ul class="nav nav-tabs border-0 mb-0">
                <li class="nav-item">
                    <a class="nav-link py-1 px-2" :class="{ active: tab === 'chart' }"
                       href="#" @click.prevent="tab = 'chart'">
                        <i class="fa fa-bar-chart"></i>
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
            <template v-if="parCaisse.length">
                <div v-show="tab === 'chart'">
                    <BarChart :chart-data="chartData" />
                </div>
                <div v-show="tab === 'table'" class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Caisse</th>
                                <th>Devise</th>
                                <th class="text-right">Solde initial</th>
                                <th class="text-right">Total entrées</th>
                                <th class="text-right">Total sorties</th>
                                <th class="text-right font-weight-bold">Solde actuel</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in parCaisse" :key="c.id">
                                <td class="font-weight-bold">{{ c.name }}</td>
                                <td><span class="badge badge-secondary">{{ c.currency_code || 'XOF' }}</span></td>
                                <td class="text-right text-muted">{{ fmt(c.initial_balance, c.currency_code) }}</td>
                                <td class="text-right text-success">{{ fmt(c.total_entrees, c.currency_code) }}</td>
                                <td class="text-right text-danger">{{ fmt(c.total_sorties, c.currency_code) }}</td>
                                <td class="text-right font-weight-bold" :class="c.solde >= 0 ? 'text-primary' : 'text-danger'">
                                    {{ fmt(c.solde, c.currency_code) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge" :class="c.active ? 'badge-success' : 'badge-secondary'">
                                        {{ c.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="3">Total</td>
                                <td class="text-right text-success">{{ fmtXof(totEntrees) }}</td>
                                <td class="text-right text-danger">{{ fmtXof(totSorties) }}</td>
                                <td class="text-right text-primary">{{ fmtXof(totSolde) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>
            <div v-else class="alert alert-warning text-center mb-0">
                <i class="fa fa-exclamation-triangle mr-1"></i> Aucune caisse active.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import BarChart from "@/Components/stats/BarChart.vue";

const props = defineProps({
    parCaisse: { type: Array, default: () => [] },
});

const tab = ref("table");

const fmtXof = (v) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(v ?? 0);

const fmt = (v, currency) => {
    const cur = currency || "XOF";
    try {
        return new Intl.NumberFormat("fr-FR", { style: "currency", currency: cur, maximumFractionDigits: 0 }).format(v ?? 0);
    } catch {
        return fmtXof(v);
    }
};

const totEntrees = computed(() => props.parCaisse.reduce((s, c) => s + Number(c.total_entrees), 0));
const totSorties = computed(() => props.parCaisse.reduce((s, c) => s + Number(c.total_sorties), 0));
const totSolde   = computed(() => props.parCaisse.reduce((s, c) => s + Number(c.solde), 0));

const chartData = computed(() => {
    if (!props.parCaisse.length) return null;
    return {
        labels: props.parCaisse.map((c) => c.name),
        datasets: [
            {
                label: "Entrées totales",
                data: props.parCaisse.map((c) => c.total_entrees),
                backgroundColor: "#28a745",
            },
            {
                label: "Sorties totales",
                data: props.parCaisse.map((c) => c.total_sorties),
                backgroundColor: "#dc3545",
            },
            {
                label: "Solde actuel",
                data: props.parCaisse.map((c) => c.solde),
                backgroundColor: "#007bff",
            },
        ],
    };
});
</script>
