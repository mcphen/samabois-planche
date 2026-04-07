<template>
    <Head :title="`Dashboard Caisse | ${appName}`" />

    <AuthenticatedLayout>
        <div class="container-fluid">
            <BreadcrumbsAndActions
                title="Tableau de bord — Caisse & Trésorerie"
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
            <StatsCaisse :kpi="data.kpi" :loading="loading" />

            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>

            <template v-else>
                <!-- Solde par caisse -->
                <div class="row clearfix mt-2">
                    <div class="col-12">
                        <SoldeParCaisse :par-caisse="data.par_caisse" />
                    </div>
                </div>

                <!-- Évolution trésorerie 6 mois -->
                <div class="row clearfix mt-2">
                    <div class="col-12">
                        <EvolutionCaisse :evolution="data.evolution" />
                    </div>
                </div>

                <!-- Dernières transactions -->
                <div class="row clearfix mt-2">
                    <div class="col-12">
                        <DernieresTransactions :dernieres="data.dernieres" />
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
import StatsCaisse from "@/Pages/Dashboard/Caisse/StatsCaisse.vue";
import SoldeParCaisse from "@/Pages/Dashboard/Caisse/SoldeParCaisse.vue";
import EvolutionCaisse from "@/Pages/Dashboard/Caisse/EvolutionCaisse.vue";
import DernieresTransactions from "@/Pages/Dashboard/Caisse/DernieresTransactions.vue";

const appName = import.meta.env.VITE_APP_NAME;
const loading = ref(true);

const data = ref({
    kpi: null,
    par_caisse: [],
    evolution: [],
    dernieres: [],
});

const breadcrumbs = [
    { label: "Tableaux de bord", link: "/dashboard", icon: "fa fa-dashboard" },
    { label: "Caisse & Trésorerie", link: "", icon: "fa fa-university" },
];

const fetchAll = async () => {
    loading.value = true;
    try {
        const { data: res } = await axios.get("/admin/dashboard/caisse-stats");
        data.value = res;
    } catch (e) {
        console.error("Erreur dashboard caisse:", e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAll);
</script>
