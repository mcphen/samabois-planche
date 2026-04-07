<template>
    <Head :title="showSettledClients ? `Liste des comptes clients soldés | ${appName}` : `Liste des comptes clients | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="showSettledClients ? 'Comptes Clients Soldés' : 'Comptes Clients'"
            :breadcrumbs="breadcrumbs"
        >

            <!-- Contenu du slot action : les boutons -->
            <template #action>
                <button @click="downloadPDF"
                        class="btn btn-primary m-1">
                    <i class="fa fa-print"></i>
                    Imprimer compte client
                </button>
                <button @click="toggleClientType"
                        class="btn btn-success m-1">
                    <i class="fa fa-users"></i>
                    {{ showSettledClients ? 'Afficher clients avec dette' : 'Afficher clients sans dette' }}
                </button>
            </template>

        </BreadcrumbsAndActions>


        <div class="card">
            <div class="body">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-dark">
                        <tr>
                            <th>Nom du Client</th>
                            <th>Total Facturé (FCFA)</th>
                            <th>Montant Versé (FCFA)</th>
                            <th>{{ showSettledClients ? 'Solde (FCFA)' : 'Montant Restant (FCFA)' }}</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="client in clients" :key="client.id">
                            <td>{{ client.name }}</td>
                            <td>{{ formatPrice(client.total_invoices) }}</td>
                            <td>{{ formatPrice(client.total_paid) }}</td>
                            <td :class="{'text-danger': client.remaining_due > 0, 'text-success': client.remaining_due === 0}">
                                {{ formatPrice(client.remaining_due) }}
                            </td>
                            <td>
                                <!--button @click="openCancelClient(client.uuid)" class="btn btn-warning m-1 btn-sm">
                                    <i class="fa fa-money"></i> Solder le compte
                                </button-->
                                <button @click="showClient(client.id)" class="btn btn-primary m-1 btn-sm">
                                    <i class="fa fa-eye"></i> Consulter le compte
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>


                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { ref, onMounted } from "vue";
import axios from "axios";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import {Inertia} from "@inertiajs/inertia";
import Swal from "sweetalert2";

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Liste des comptes clients' }
];
const clients = ref([]);
const showCancelSolde = ref(false);
const selectedClient = ref("");
const showSettledClients = ref(false);

function openCancelClient(uuid) {
    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Le compte sera soldé.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/clients/${uuid}/cancel-solde`)
                .then(response => {
                    Swal.fire("Compte soldé !", response.data.message, "success")
                        .then(() => location.reload());
                })
                .catch(error => {
                    Swal.fire("Erreur", error.response.data.error || "Impossible de solder le compte.", "error");
                });
        }
    });
}

const toggleClientType = () => {
    showSettledClients.value = !showSettledClients.value;
    fetchClientAccounts();
};

const fetchClientAccounts = async () => {
    const endpoint = showSettledClients.value
        ? "/admin/clients/liste-comptes-soldes"
        : "/admin/clients/liste-comptes";

    try {
        const { data } = await axios.get(endpoint);
        clients.value = data;
    } catch (error) {
        console.error("Erreur lors de la récupération des comptes clients:", error);
        Swal.fire("Erreur", "Impossible de récupérer les données des clients.", "error");
    }
};

//const formatPrice = (value) => new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF" }).format(value);

function formatPrice(price) {
    let priceTotal =  price ? parseInt(price) : 0;
    return  new Intl.NumberFormat('de-DE').format(priceTotal); //.toLocaleString('fr-FR');
}
function showClient(id) { 
    Inertia.visit('/admin/clients/'+id+'/consultation');
}
onMounted(fetchClientAccounts);

function downloadPDF() {
    const url ="/admin/clients/generate-pdf-account";
    const params = new URLSearchParams({});
    const fullUrl = `${url}?${params.toString()}`;
    window.open(fullUrl, '_blank');
}
</script>



<style scoped>

</style>


