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
                        <span v-else>{{ stat.formatted }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";

const data = ref(null);
const loading = ref(false);

const fetchDashboardStats = async () => {
    loading.value = true;
    try {
        const { data: res } = await axios.get("/admin/dashboard/stats-general");
        data.value = res;
    } catch (e) {
        console.error("Erreur stats générales:", e);
    } finally {
        loading.value = false;
    }
};

const formatPrice = (value) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(value ?? 0);

const stats = computed(() => {
    const d = data.value;
    return [
        {
            key: "ca",
            label: "Chiffre d'affaires",
            icon: "fa fa-bar-chart",
            color: "#007bff",
            formatted: formatPrice(d?.chiffre_affaires),
        },
        {
            key: "ca_old",
            label: "Ancien compte",
            icon: "fa fa-history",
            color: "#6c757d",
            formatted: formatPrice(d?.chiffre_affaire_old),
        },
        {
            key: "paye",
            label: "Montant payé",
            icon: "fa fa-check-circle",
            color: "#28a745",
            formatted: formatPrice(d?.montant_paye),
        },
        {
            key: "du",
            label: "Montant dû",
            icon: "fa fa-exclamation-triangle",
            color: "#dc3545",
            formatted: formatPrice(d?.montant_du),
        },
        {
            key: "stock",
            label: "Colis en stock",
            icon: "fa fa-cubes",
            color: "#fd7e14",
            formatted: d ? (d.stock_disponible ?? 0) + " colis" : "—",
        },
        {
            key: "caisse",
            label: "Solde caisse",
            icon: "fa fa-money",
            color: "#17a2b8",
            formatted: formatPrice(d?.soldeCaisse),
        },
    ];
});

onMounted(fetchDashboardStats);
</script>

<style scoped>
.stat-card .body {
    padding: 14px 10px;
}
.stat-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
}
.stat-value {
    font-size: 14px;
    word-break: break-word;
    overflow-wrap: break-word;
    line-height: 1.3;
}
</style>
