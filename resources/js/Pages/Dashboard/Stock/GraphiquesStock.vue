<template>
    <div class="row clearfix">
        <!-- ── Par Essence ──────────────────────────────────────────── -->
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fa fa-leaf mr-2 text-success"></i>Répartition par essence
                    </h6>
                    <TabToggle v-model="tabEssence" />
                </div>
                <div class="card-body">
                    <div v-show="tabEssence === 'chart'">
                        <DoughnutChart v-if="doughnutEssence" :chart-data="doughnutEssence" />
                        <p v-else class="text-muted text-center py-4">Aucune donnée</p>
                    </div>
                    <div v-show="tabEssence === 'table'" class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Essence</th>
                                    <th class="text-right">Colis</th>
                                    <th class="text-right">Volume (m³)</th>
                                    <th class="text-right">Valeur estimée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in parEssence" :key="row.essence">
                                    <td class="font-weight-bold">{{ row.essence }}</td>
                                    <td class="text-right">{{ row.nb_colis }}</td>
                                    <td class="text-right">{{ fmtVol(row.volume_total) }}</td>
                                    <td class="text-right text-primary">{{ fmt(row.valeur_estimee) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="font-weight-bold bg-light">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-right">{{ totEssence.colis }}</td>
                                    <td class="text-right">{{ fmtVol(totEssence.volume) }}</td>
                                    <td class="text-right text-primary">{{ fmt(totEssence.valeur) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Par Épaisseur ──────────────────────────────────────── -->
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fa fa-sliders mr-2 text-warning"></i>Répartition par épaisseur
                    </h6>
                    <TabToggle v-model="tabEpaisseur" />
                </div>
                <div class="card-body">
                    <div v-show="tabEpaisseur === 'chart'">
                        <BarChart v-if="barEpaisseur" :chart-data="barEpaisseur" />
                        <p v-else class="text-muted text-center py-4">Aucune donnée</p>
                    </div>
                    <div v-show="tabEpaisseur === 'table'" class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Épaisseur</th>
                                    <th class="text-right">Colis</th>
                                    <th class="text-right">Volume (m³)</th>
                                    <th class="text-right">% colis</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in parEpaisseur" :key="row.epaisseur">
                                    <td class="font-weight-bold">{{ row.epaisseur }} mm</td>
                                    <td class="text-right">{{ row.nb_colis }}</td>
                                    <td class="text-right">{{ fmtVol(row.volume_total) }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-info">
                                            {{ pctColis(row.nb_colis) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="font-weight-bold bg-light">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-right">{{ totEpaisseur.colis }}</td>
                                    <td class="text-right">{{ fmtVol(totEpaisseur.volume) }}</td>
                                    <td class="text-right">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Par Fournisseur ────────────────────────────────────── -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fa fa-truck mr-2 text-info"></i>Répartition par fournisseur
                    </h6>
                    <TabToggle v-model="tabFournisseur" />
                </div>
                <div class="card-body">
                    <div v-show="tabFournisseur === 'chart'">
                        <BarChart v-if="barFournisseur" :chart-data="barFournisseur" />
                        <p v-else class="text-muted text-center py-4">Aucune donnée</p>
                    </div>
                    <div v-show="tabFournisseur === 'table'" class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fournisseur</th>
                                    <th class="text-right">Colis</th>
                                    <th class="text-right">Volume (m³)</th>
                                    <th class="text-right">Valeur estimée</th>
                                    <th class="text-right">% stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in parFournisseur" :key="row.name">
                                    <td class="font-weight-bold">{{ row.name }}</td>
                                    <td class="text-right">{{ row.nb_colis }}</td>
                                    <td class="text-right">{{ fmtVol(row.volume_total) }}</td>
                                    <td class="text-right text-primary">{{ fmt(row.valeur_estimee) }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-primary">
                                            {{ pctColis(row.nb_colis) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="font-weight-bold bg-light">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-right">{{ totFournisseur.colis }}</td>
                                    <td class="text-right">{{ fmtVol(totFournisseur.volume) }}</td>
                                    <td class="text-right text-primary">{{ fmt(totFournisseur.valeur) }}</td>
                                    <td class="text-right">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import DoughnutChart from "@/Components/stats/DoughnutChart.vue";
import BarChart from "@/Components/stats/BarChart.vue";

// Petit composant inline pour le toggle chart/table
const TabToggle = {
    props: ["modelValue"],
    emits: ["update:modelValue"],
    template: `
        <ul class="nav nav-tabs border-0 mb-0">
            <li class="nav-item">
                <a class="nav-link py-1 px-2" :class="{ active: modelValue === 'chart' }"
                   href="#" @click.prevent="$emit('update:modelValue', 'chart')">
                    <i class="fa fa-bar-chart"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-2" :class="{ active: modelValue === 'table' }"
                   href="#" @click.prevent="$emit('update:modelValue', 'table')">
                    <i class="fa fa-table"></i>
                </a>
            </li>
        </ul>`,
};

const props = defineProps({
    parEssence: { type: Array, default: () => [] },
    parEpaisseur: { type: Array, default: () => [] },
    parFournisseur: { type: Array, default: () => [] },
});

const tabEssence    = ref("chart");
const tabEpaisseur  = ref("chart");
const tabFournisseur = ref("chart");

// ── Formatters ───────────────────────────────────────────────────────────
const fmt = (v) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(v ?? 0);
const fmtVol = (v) =>
    new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 2 }).format(v ?? 0);

// ── Totaux ───────────────────────────────────────────────────────────────
const totEssence = computed(() => ({
    colis:  props.parEssence.reduce((s, r) => s + Number(r.nb_colis), 0),
    volume: props.parEssence.reduce((s, r) => s + Number(r.volume_total), 0),
    valeur: props.parEssence.reduce((s, r) => s + Number(r.valeur_estimee), 0),
}));

const totEpaisseur = computed(() => ({
    colis:  props.parEpaisseur.reduce((s, r) => s + Number(r.nb_colis), 0),
    volume: props.parEpaisseur.reduce((s, r) => s + Number(r.volume_total), 0),
}));

const totFournisseur = computed(() => ({
    colis:  props.parFournisseur.reduce((s, r) => s + Number(r.nb_colis), 0),
    volume: props.parFournisseur.reduce((s, r) => s + Number(r.volume_total), 0),
    valeur: props.parFournisseur.reduce((s, r) => s + Number(r.valeur_estimee), 0),
}));

const pctColis = (nb) => {
    const total = totEssence.value.colis || totFournisseur.value.colis || 1;
    // Utilise le total global passé depuis le parent via la prop appropriée
    return Math.round((nb / (totEpaisseur.value.colis || total)) * 100);
};

// ── Chart data ───────────────────────────────────────────────────────────
const COLORS = ["#007bff","#28a745","#fd7e14","#dc3545","#6f42c1","#20c997","#ffc107","#17a2b8"];

const doughnutEssence = computed(() => {
    if (!props.parEssence.length) return null;
    return {
        labels: props.parEssence.map((r) => r.essence),
        datasets: [{
            data: props.parEssence.map((r) => r.nb_colis),
            backgroundColor: props.parEssence.map((_, i) => COLORS[i % COLORS.length]),
        }],
    };
});

const barEpaisseur = computed(() => {
    if (!props.parEpaisseur.length) return null;
    return {
        labels: props.parEpaisseur.map((r) => `${r.epaisseur} mm`),
        datasets: [{
            label: "Nombre de colis",
            data: props.parEpaisseur.map((r) => r.nb_colis),
            backgroundColor: "#fd7e14",
        }],
    };
});

const barFournisseur = computed(() => {
    if (!props.parFournisseur.length) return null;
    return {
        labels: props.parFournisseur.map((r) => r.name),
        datasets: [
            {
                label: "Colis",
                data: props.parFournisseur.map((r) => r.nb_colis),
                backgroundColor: "#007bff",
            },
            {
                label: "Volume (m³)",
                data: props.parFournisseur.map((r) => parseFloat(r.volume_total).toFixed(2)),
                backgroundColor: "#20c997",
            },
        ],
    };
});
</script>
