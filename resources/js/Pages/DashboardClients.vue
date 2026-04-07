<template>
    <Head :title="`Dashboard Clients | ${appName}`" />

    <AuthenticatedLayout>
        <div class="container-fluid">
            <BreadcrumbsAndActions
                title="Tableau de bord — Clients & Comptabilité"
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
            <StatsClients :kpi="data.kpi" :loading="loading" />

            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>

            <template v-else>
                <!-- Évolution Factures vs Paiements -->
                <div class="row clearfix">
                    <div class="col-12">
                        <EvolutionPaiements :evolution="data.evolution" />
                    </div>
                </div>

                <!-- Tableau complet clients -->
                <div class="row clearfix mt-2">
                    <div class="col-12">
                        <TableauClients :clients="data.clients" />
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import StatsClients from "@/Pages/Dashboard/Clients/StatsClients.vue";
import EvolutionPaiements from "@/Pages/Dashboard/Clients/EvolutionPaiements.vue";
import TableauClients from "@/Pages/Dashboard/Clients/TableauClients.vue";

const appName = import.meta.env.VITE_APP_NAME;
const loading = ref(true);

const data = ref({
    kpi: null,
    evolution: [],
    clients: [],
});

const breadcrumbs = [
    { label: "Tableaux de bord", link: "/dashboard", icon: "fa fa-dashboard" },
    { label: "Clients & Comptabilité", link: "", icon: "fa fa-users" },
];

const fetchAll = async () => {
    loading.value = true;
    try {
        const { data: res } = await axios.get("/admin/dashboard/clients-stats");
        data.value = res;
    } catch (e) {
        console.error("Erreur dashboard clients:", e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAll);
</script>
