<template>
    <Head :title="`Dashboard | ${appName}`" />

    <AuthenticatedLayout>
        <div class="container-fluid">
            <BreadcrumbsAndActions
                :title="'Tableau de bord — Planches'"
                :breadcrumbs="breadcrumbs"
            />

            <!-- KPIs généraux -->
            <StatGeneral />

            <div class="row mt-3">
                <!-- Évolution CA 6 mois -->
                <div class="col-lg-8 col-md-12 mb-3">
                    <EvolutionCA />
                </div>

                <!-- Top 5 Clients -->
                <div class="col-lg-4 col-md-12 mb-3">
                    <TopClients />
                </div>
            </div>

            <!-- CA par mois -->
            <div class="row">
                <div class="col-12 mb-3">
                    <StatsCaBenefice />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import StatGeneral from '@/Pages/Dashboard/StatGeneral.vue';
import StatsCaBenefice from '@/Pages/Dashboard/StatsCaBenefice.vue';
import EvolutionCA from '@/Pages/Dashboard/EvolutionCA.vue';
import TopClients from '@/Pages/Dashboard/TopClients.vue';

const appName = import.meta.env.VITE_APP_NAME;

const breadcrumbs = [
    { label: 'Tableau de bord', link: '', icon: 'fa fa-dashboard' },
];
</script>
