<template>
    <div class="row clearfix">
        <div
            v-for="stat in stats"
            :key="stat.key"
            class="col-lg-2 col-md-4 col-sm-6"
        >
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

const fmtVol = (v) =>
    new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 2 }).format(v ?? 0) + " m³";

const stats = computed(() => {
    const k = props.kpi ?? {};
    return [
        {
            key: "colis",
            label: "Colis disponibles",
            icon: "fa fa-cubes",
            color: "#28a745",
            value: (k.total_colis ?? 0) + " colis",
        },
        {
            key: "indispo",
            label: "Colis vendus",
            icon: "fa fa-check-square",
            color: "#6c757d",
            value: (k.total_indisponible ?? 0) + " colis",
        },
        {
            key: "volume",
            label: "Volume total",
            icon: "fa fa-cube",
            color: "#007bff",
            value: fmtVol(k.volume_total),
        },
        {
            key: "valeur",
            label: "Valeur estimée",
            icon: "fa fa-money",
            color: "#fd7e14",
            value: fmt(k.valeur_stock),
        },
        {
            key: "essences",
            label: "Essences",
            icon: "fa fa-leaf",
            color: "#20c997",
            value: (k.nb_essences ?? 0) + " essences",
        },
        {
            key: "fournisseurs",
            label: "Fournisseurs",
            icon: "fa fa-truck",
            color: "#6f42c1",
            value: (k.nb_fournisseurs ?? 0) + " fournisseurs",
        },
    ];
});
</script>

<style scoped>
.stat-card .body { padding: 14px 10px; }
.stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2; }
.stat-value { font-size: 14px; word-break: break-word; overflow-wrap: break-word; line-height: 1.3; }
</style>
