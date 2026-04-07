<template>
    <Head :title="`Consultation client | ${appName}`" />

    <AuthenticatedLayout>
        <div>
            <BreadcrumbsAndActions
                :title="'📝 Détails du Client: '+client.name"
                :breadcrumbs="breadcrumbs"
            >
                <!-- Contenu du slot action : le bouton -->
                <template #action>
                    <!-- Bouton pour ajouter un paiement -->
                    <button @click="showOldPaymentModal = true" class="btn btn-primary m-1 btn-sm">
                        💰 Ajouter un ancien compte
                    </button>
                    <!-- Bouton pour ajouter un paiement -->
                    <button @click="showPaymentModal = true" class="btn btn-success m-1 btn-sm">
                        💰 Ajouter un Paiement
                    </button>
                    <button @click="openCancelClient(client.id)" class="btn btn-warning m-1 btn-sm">
                        <i class="fa fa-money"></i> Solder le compte
                    </button>
                    <button @click="restoreAccounting(client.id)" class="btn btn-info m-1 btn-sm">
                        <i class="fa fa-refresh"></i> Rétablir la comptabilité
                    </button>
                </template>
            </BreadcrumbsAndActions>
        </div>

        <div class="row clearfix row-deck">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card primary-bg">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{ total_invoice }}</h3>
                            <span>Factures clients</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card secondary-bg">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{formatTotalPrice(client.amount_due)}} F</h3>
                            <span>Montant Facture </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card bg-info">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{formatTotalPrice(client.amount_payment)}} F</h3>
                            <span>Montant Payé</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card bg-info">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{formatTotalPrice(client.amount_solde)}} F</h3>
                            <span>Montant A Payé</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row clearfix" >
            <div class="col-lg-12 col-md-12">
                <div class="card">

                    <div class="body">
                        <ul class="nav nav-tabs-new2">
                            <li class="nav-item">
                                <a class="nav-link active show" data-toggle="tab"
                                   href="#Home-new2">📜 Compte du Client
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    data-toggle="tab"
                                    href="#HistoriqueSolde">
                                    📊 Historique des comptes soldés
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    data-toggle="tab"
                                    href="#AccountingHistory">
                                    📈 Historique de comptabilité
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane show active" id="Home-new2">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>
                                            📜 Compte du Client
                                            <button class="btn btn-primary btn-sm m-1" @click="downloadPDF"> Imprimer compte client</button>
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Numéro de Facture</th>
                                                <th>Montant Facture</th>
                                                <th>Paiement</th>
                                                <th>Montant Restant</th>
                                                <th>Action</th>

                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="(invoice,index) in invoices" :key="invoice.id">
                                                <td>{{formatDate(invoice.date)}} </td>
                                                <td>
                                                    <a v-if="invoice.facture"
                                                        :href="'/admin/invoices/'+invoice.facture.id+'/consultation'">
                                                        {{  invoice.facture.matricule }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span>
                                                    {{ formatTotalPrice(invoice.invoice)  }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span v-if="invoice.payment!==0">
                                                    {{ formatTotalPrice(invoice.payment)  }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span>
                                                    {{ index === invoices.length - 1 ? formatTotalPrice(client.amount_solde) : formatTotalPrice(invoice.cumul) }}
                                                    </span>

                                                </td>
                                                <td>
                                                    <div v-if="invoice.type==='invoice' && invoice.facture">
                                                        <a :href="'/admin/invoices/'+invoice.facture.id+'/consultation'"
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fa fa-eye"></i> Voir la facture
                                                        </a>
                                                    </div>
                                                    <div v-else-if="invoice.type==='invoice' && invoice.isSolde" class="text-muted small italic">
                                                        Ajustement de solde (non modifiable)
                                                    </div>
                                                    <div v-else-if="invoice.type==='payment'">
                                                        <div v-if="!invoice.isSolde">
                                                            <button class="btn btn-warning btn-sm m-1" @click="openModalEditPaiement(invoice)">
                                                                <i class="fa fa-edit"></i> Modifier le paiement
                                                            </button>
                                                            <button class="btn btn-danger btn-sm m-1" @click="deletePayment(invoice.id)">
                                                                <i class="fa fa-trash"></i> Supprimer le paiement
                                                            </button>
                                                        </div>
                                                        <div v-else class="text-muted small italic">
                                                            Ajustement de solde (non modifiable)
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane" id="HistoriqueSolde">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>📊 Historique des comptes soldés</h4>
                                    </div>
                                    <div class="card-body">
                                        <div v-if="historiqueLoading" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Chargement...</span>
                                            </div>
                                        </div>
                                        <div v-else-if="historiqueSolde.length === 0" class="alert alert-info">
                                            Aucun historique de compte soldé trouvé pour ce client.
                                        </div>
                                        <table v-else class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Montant soldé</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="historique in historiqueSolde" :key="historique.id">
                                                    <td>{{ formatDate(historique.date) }}</td>
                                                    <td>{{ formatTotalPrice(historique.amount) }} F</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="AccountingHistory">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>📈 Historique de comptabilité</h4>
                                    </div>
                                    <div class="card-body">
                                        <div v-if="accountingLoading" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Chargement...</span>
                                            </div>
                                        </div>
                                        <div v-else-if="accountingHistory.length === 0" class="alert alert-info">
                                            Aucun historique de comptabilité trouvé pour ce client.
                                        </div>
                                        <table v-else class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Utilisateur</th>
                                                    <th>Montant Facture</th>
                                                    <th>Montant Payé</th>
                                                    <th>Montant Restant</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="history in accountingHistory" :key="history.id">
                                                    <td>{{ formatDate(history.created_at) }}</td>
                                                    <td>{{ history.user ? history.user.email : 'N/A' }}</td>
                                                    <td>{{ formatTotalPrice(history.amount_due) }} F</td>
                                                    <td>{{ formatTotalPrice(history.amount_payment) }} F</td>
                                                    <td>{{ formatTotalPrice(history.amount_solde) }} F</td>
                                                    <td>{{ history.notes }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="Profile-new2">
                                <!-- <div class="card-body">
                                        <table class="table table-bordered">
                                            <thead>
                                            <tr>
                                                <th>Numéro de Facture</th>
                                                <th>Montant Total</th>
                                                <th>Montant Payé</th>

                                <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="invoice in invoices" :key="invoice.id">
                                    <td><a :href="'/admin/invoices/'+invoice.invoice_id+'consultation'">{{  invoice.invoice.matricule }}</a> </td>
                                    <td>{{ formatTotalPrice(invoice.invoice.total_price)  }}</td>
                                    <td>{{ formatTotalPrice(invoice.amount_paid)}}</td>

                                    <td>

                                        <button class="btn btn-info btn-sm m-1" @click="viewInvoice(invoice.invoice_id)">👁️ Voir</button>
                                        <button class="btn btn-success btn-sm m-1" @click="downloadPDF(invoice.invoice_id)"><i class="fa fa-print"></i> Imprimer</button>

                                    </td>
                                </tr>
                                </tbody>
                                </table>
                            </div> -->
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ajouter un Paiement -->
        <div v-if="showPaymentModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{editModal?"Modifier un paiement":"Ajouter un Paiement"}}</h5>
                        <button type="button" class="close" @click="showPaymentModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="handlePayment">
                            <div class="form-group">
                                <label>Date *</label>
                                <input v-model="paymentForm.transaction_date" type="date" class="form-control" required />
                            </div>

                            <div class="form-group mt-3">
                                <label for="amount">Montant du Paiement</label>
                                <input type="number" v-model="paymentForm.amount" class="form-control" required />
                            </div>
                            <div class="form-group mt-3">
                                <label for="caisse_id">Caisse</label>
                                <select v-model="paymentForm.caisse_id" class="form-control" required>
                                    <option value="" disabled>-- Sélectionnez une caisse --</option>
                                    <option v-for="c in caisses" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-success m-1" type="submit" :disabled="disabled">
                                    {{editModal?"Modifier":"Ajouter"}}
                                </button>
                                <button class="btn btn-danger m-1" @click="closePaymentModal">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal Ajouter un Paiement -->
        <div v-if="showOldPaymentModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ancien compte</h5>
                        <button type="button" class="close" @click="showOldPaymentModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addOldPayment">


                            <div class="form-group mt-3">
                                <label for="amount">Montant ancien compte</label>
                                <input type="number" v-model="oldPaymentForm.amount" class="form-control" required />
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-success m-1" type="submit" :disabled="disabled">Ajouter</button>
                                <button class="btn btn-danger m-1" @click="showOldPaymentModal = false">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>


</template>


<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import dayjs from 'dayjs';
import Swal from 'sweetalert2';
import {ref, onMounted, reactive,computed} from 'vue';
import {Inertia} from "@inertiajs/inertia";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import axios from "axios";

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des Clients', link: '/admin/clients', icon: 'fa fa-user-plus' },
    { label: 'Details Client' }
];

function formatDate(date) {
    return dayjs(date).format('DD/MM/YYYY');
}

function openCancelClient(id) {
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
            axios.post(`/admin/clients/${id}/cancel-solde`)
                .then(response => {
                    Swal.fire("Compte soldé !", response.data.message, "success")
                        .then(() => location.reload());
                })
                .catch(error => {
                    Swal.fire("Erreur", error.response?.data?.error || "Impossible de solder le compte.", "error");
                });
        }
    });
}

function deletePayment(id) {
    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Le paiement sera supprimé.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/admin/clients/payments/${id}/delete-payments`)
                .then(response => {
                    Swal.fire("le paiement a été supprimé !", response.data.message, "success")
                        .then(() => location.reload());
                })
                .catch(error => {
                    Swal.fire("Erreur", error.response?.data?.error || "Impossible de supprimer le paiement.", "error");
                });
        }
    });
}

const props = defineProps({
    client: Array,
    total_due: Number,
    total_paid: Number,
    total_invoice: Number,
});

const invoices = ref([]);
const historiqueSolde = ref([]);
const historiqueLoading = ref(false);
const accountingHistory = ref([]);
const accountingLoading = ref(false);

const paymentForm = ref({
    amount:'',
    transaction_date: new Date().toISOString().split("T")[0],
    caisse_id: '',

    // client_id:props.client.id,
});

const oldPaymentForm = ref({
    amount:'',
    transaction_date: new Date().toISOString().split("T")[0],

    // client_id:props.client.id,
});

const showPaymentModal = ref(false);
const showOldPaymentModal = ref(false);
const disabled = ref(false);
const editModal = ref(false);
const selectedInvoiceId = ref('');
const caisses = ref([]);

function openModalEditPaiement(invoice) {
    editModal.value = true;
    selectedInvoiceId.value = invoice.id;
    paymentForm.value.amount = invoice.payment
    showPaymentModal.value = true;
}

function handlePayment() {
    if(editModal.value===true){
        editPayment()
    }else{
        addPayment()
    }
}
function formatTotalPrice(price) {
    let priceTotal =  price ? parseInt(price) : 0;
    return  new Intl.NumberFormat('de-DE').format(priceTotal); //.toLocaleString('fr-FR');
}
function formatTotalPriceRestant(price1,price2) {
    let price = parseFloat(price1) - parseFloat(price2);
    let priceTotal =  price ? parseInt(price) : 0;
    return  new Intl.NumberFormat('de-DE').format(priceTotal); //.toLocaleString('fr-FR');
}
async function fetchInvoices() {
    try {
        const response = await axios.get(`/admin/clients/${props.client.id}/transaction-paiements`);

        invoices.value = response.data;

    } catch (error) {
        console.error("Erreur lors de la récupération des données du client :", error);
    }
}

async function fetchHistoriqueSolde() {
    try {
        historiqueLoading.value = true;
        const response = await axios.get(`/admin/clients/${props.client.id}/historique-solde`);
        historiqueSolde.value = response.data;
    } catch (error) {
        console.error("Erreur lors de la récupération de l'historique des comptes soldés :", error);
    } finally {
        historiqueLoading.value = false;
    }
}

async function fetchAccountingHistory() {
    try {
        accountingLoading.value = true;
        const response = await axios.get(`/admin/clients/${props.client.id}/historique-comptabilite`);
        accountingHistory.value = response.data;
    } catch (error) {
        console.error("Erreur lors de la récupération de l'historique de comptabilité :", error);
    } finally {
        accountingLoading.value = false;
    }
}

async function fetchCaisses() {
    try {
        const response = await axios.get(`/admin/finances/caisses/listes`, { params: { active: 1 } });
        caisses.value = response.data || [];
        if (!paymentForm.value.caisse_id && caisses.value.length > 0) {
            paymentForm.value.caisse_id = caisses.value[0].id;
        }
    } catch (error) {
        console.error("Erreur lors de la récupération des caisses :", error);
    }
}

onMounted(() => {
    fetchInvoices();
    fetchHistoriqueSolde();
    fetchAccountingHistory();
    fetchCaisses();
});
const editPayment = () => {
    disabled.value=true
    axios
        .post(`/admin/clients/payments/${selectedInvoiceId.value}/update-payments`, paymentForm.value)
        .then((response) => {
            Swal.fire(
                "Succès",
                "Paiement modifié avec succès",
                "success").then(() => {
                closePaymentModal()
                // Redirection vers l'étape 2
                location.reload()

            });
            //emit("paymentAdded", response.data);
            //closeModal();
        })
        .catch((error) => {
            disabled.value=false
            Swal.fire("Erreur", "Une erreur est survenue", "error");
            console.error("Erreur d'ajout du paiement :", error);
        });
}
const addPayment = () => {
    disabled.value=true
    axios
        .post(`/admin/clients/${props.client.id}/payments`, paymentForm.value)
        .then((response) => {
            Swal.fire(
                "Succès",
                "Paiement enregistré avec succès",
                "success").then(() => {
                    closePaymentModal()
                // Redirection vers l'étape 2
                location.reload()

            });
            //emit("paymentAdded", response.data);
            //closeModal();
        })
        .catch((error) => {
            disabled.value=false
            Swal.fire("Erreur", "Une erreur est survenue", "error");
            console.error("Erreur d'ajout du paiement :", error);
        });
};

const addOldPayment = () => {
    disabled.value=true
    axios
        .post(`/admin/clients/${props.client.id}/old-payments`, oldPaymentForm.value)
        .then((response) => {
            Swal.fire(
                "Succès",
                "Ancien compte enregistré avec succès",
                "success").then(() => {
                    closePaymentModal()
                // Redirection vers l'étape 2
                location.reload()

            });
            //emit("paymentAdded", response.data);
            //closeModal();
        })
        .catch((error) => {
            disabled.value=false
            Swal.fire("Erreur", "Une erreur est survenue", "error");
            console.error("Erreur d'ajout du paiement :", error);
        });
};

function closePaymentModal() {
    showPaymentModal.value = false;
}


function viewInvoice(id) {
    Inertia.visit(`/admin/invoices/${id}/consultation`);
}

function downloadPDF() {
    window.open(`/admin/clients/${props.client.id}/generate-pdf-facture-paiement`, '_blank');
}

function restoreAccounting(id) {
    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "La comptabilité du client sera rétablie.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Oui, rétablir",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/clients/${id}/update-amount/${id}`)
                .then(response => {
                    Swal.fire("Succès !", "La comptabilité du client a été rétablie avec succès.", "success")
                        .then(() => location.reload());
                })
                .catch(error => {
                    Swal.fire("Erreur", error.response?.data?.error || "Impossible de rétablir la comptabilité.", "error");
                });
        }
    });
}
</script>



