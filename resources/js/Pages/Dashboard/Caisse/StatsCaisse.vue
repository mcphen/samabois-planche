<template>
    <div class="row clearfix">
        <div v-for="stat in stats" :key="stat.key" class="col-lg-2 col-md-4 col-sm-6">
            <div class="card stat-card">
                <div class="body text-center">
                    <div
                        class="stat-icon mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle"
                        :style="{ backgroundColor: stat.color + '1a', width: '48px', height: '48px' }"
                    >
                        <i :class="stat.icon" :style="{ color: stat.color, fontSize: '20px' }"></i>
                    </div>
                    <p class="stat-label text-muted mb-1">{{ stat.label }}</p>
                    <p class="stat-value mb-0 font-weight-bold" :style="{ color: stat.color }">
                        <span v-if="loading">—</span>
                        <span v-else>{{ stat.value }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    kpi: Object,
    loading: Boolean,
});

const fmt = (v) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(v ?? 0);

const stats = computed(() => {
    const k = props.kpi ?? {};
    const balance = k.balance_nette_mois ?? 0;
    return [
        {
            key: "solde",
            label: "Solde total caisses",
            icon: "fa fa-university",
            color: "#17a2b8",
            value: fmt(k.solde_total),
        },
        {
            key: "entrees",
            label: "Entrées du mois",
            icon: "fa fa-arrow-circle-down",
            color: "#28a745",
            value: fmt(k.entrees_mois),
        },
        {
            key: "sorties",
            label: "Sorties du mois",
            icon: "fa fa-arrow-circle-up",
            color: "#dc3545",
            value: fmt(k.sorties_mois),
        },
        {
            key: "balance",
            label: "Balance nette du mois",
            icon: balance >= 0 ? "fa fa-trending-up" : "fa fa-trending-down",
            color: balance >= 0 ? "#007bff" : "#fd7e14",
            value: fmt(balance),
        },
        {
            key: "clients",
            label: "Encaissements clients",
            icon: "fa fa-users",
            color: "#6f42c1",
            value: fmt(k.entrees_clients_mois),
        },
        {
            key: "caisses",
            label: "Caisses actives",
            icon: "fa fa-archive",
            color: "#fd7e14",
            value: (k.nb_caisses ?? 0) + " caisses",
        },
    ];
});
</script>

<style scoped>
.stat-card .body { padding: 14px 10px; }
.stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2; }
.stat-value { font-size: 14px; word-break: break-word; overflow-wrap: break-word; line-height: 1.3; }
</style>
