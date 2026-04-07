<template>
    <Head :title="`Dashboard | ${appName}`" />

    <AuthenticatedLayout>
        <div class="container-fluid">
            <BreadcrumbsAndActions
                :title="'Tableau de bord'"
                :breadcrumbs="breadcrumbs"
            >
                <template #action>
                    
                </template>
            </BreadcrumbsAndActions>

           


            
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import StatGeneral from '@/Pages/Dashboard/StatGeneral.vue';
import StatsCaBenefice from '@/Pages/Dashboard/StatsCaBenefice.vue';
import EvolutionCA from '@/Pages/Dashboard/EvolutionCA.vue';
import TopClients from '@/Pages/Dashboard/TopClients.vue';

const appName = import.meta.env.VITE_APP_NAME;
const isSyncing = ref(false);

const startSync = async () => {
    isSyncing.value = true;
    try {
        const response = await axios.post('/admin/dashboard/sync');
        Swal.fire({
            icon: 'success',
            title: 'Synchronisation terminée',
            text: 'Les données ont été synchronisées avec succès.',
            confirmButtonColor: '#3085d6',
        });
        console.log('Sync details:', response.data.details);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Échec de la synchronisation',
            text: 'Impossible de contacter le serveur. Vérifiez votre connexion.',
            confirmButtonColor: '#d33',
        });
    } finally {
        isSyncing.value = false;
    }
};

const breadcrumbs = [
    { label: 'Tableau de bord', link: '', icon: 'fa fa-dashboard' },
];
</script>
