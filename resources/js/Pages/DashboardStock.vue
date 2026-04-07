<template>
    <Head :title="`Dashboard Stock | ${appName}`" />

    <AuthenticatedLayout>
        <div class="container-fluid">
            <BreadcrumbsAndActions
                title="Tableau de bord — Stock"
                :breadcrumbs="breadcrumbs"
            >
                <template #action>
                    <button @click="fetchAll" class="btn btn-primary btn-sm" :disabled="loading">
                        <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loading }"></i>
                        Actualiser
                    </button>
                </template>
            </BreadcrumbsAndActions>

            <!-- KPI cards -->
            <StatsStock :kpi="stats.kpi" :loading="loading" />

            <!-- Graphiques / Tableaux -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>

            <GraphiquesStock
                v-else
                :par-essence="stats.par_essence"
                :par-epaisseur="stats.par_epaisseur"
                :par-fournisseur="stats.par_fournisseur"
            />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import StatsStock from "@/Pages/Dashboard/Stock/StatsStock.vue";
import GraphiquesStock from "@/Pages/Dashboard/Stock/GraphiquesStock.vue";

const appName = import.meta.env.VITE_APP_NAME;
const loading = ref(true);

const stats = ref({
    kpi: null,
    par_essence: [],
    par_epaisseur: [],
    par_fournisseur: [],
});

const breadcrumbs = [
    { label: "Tableaux de bord", link: "/dashboard", icon: "fa fa-dashboard" },
    { label: "Stock", link: "", icon: "fa fa-cubes" },
];

const fetchAll = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/admin/dashboard/stock-stats");
        stats.value = data;
    } catch (e) {
        console.error("Erreur dashboard stock:", e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAll);
</script>
