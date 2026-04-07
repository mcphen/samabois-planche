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
                    <!-- Barre de progression pour le taux de recouvrement -->
                    <div v-if="stat.key === 'taux' && !loading && kpi" class="mt-1">
                        <div class="progress" style="height: 4px;">
                            <div
                                class="progress-bar"
                                :style="{ width: Math.min(kpi?.taux_recouvrement ?? 0, 100) + '%', backgroundColor: stat.color }"
                            ></div>
                        </div>
                    </div>
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
    return [
        {
            key: "clients",
            label: "Total clients",
            icon: "fa fa-users",
            color: "#007bff",
            value: (k.nb_clients ?? 0) + " clients",
        },
        {
            key: "ca",
            label: "CA total facturé",
            icon: "fa fa-bar-chart",
            color: "#6f42c1",
            value: fmt(k.total_ca),
        },
        {
            key: "paye",
            label: "Total encaissé",
            icon: "fa fa-check-circle",
            color: "#28a745",
            value: fmt(k.total_paye),
        },
        {
            key: "du",
            label: "Total des créances",
            icon: "fa fa-exclamation-circle",
            color: "#dc3545",
            value: fmt(k.total_du),
        },
        {
            key: "taux",
            label: "Taux de recouvrement",
            icon: "fa fa-pie-chart",
            color: (k.taux_recouvrement ?? 0) >= 80 ? "#28a745" : (k.taux_recouvrement ?? 0) >= 50 ? "#fd7e14" : "#dc3545",
            value: (k.taux_recouvrement ?? 0) + "%",
        },
        {
            key: "creances",
            label: "Clients avec créances",
            icon: "fa fa-warning",
            color: "#fd7e14",
            value: (k.nb_clients_avec_creances ?? 0) + " clients",
        },
    ];
});
</script>

<style scoped>
.stat-card .body { padding: 14px 10px; }
.stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2; }
.stat-value { font-size: 14px; word-break: break-word; overflow-wrap: break-word; line-height: 1.3; }
</style>
