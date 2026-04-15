<template>
    <Head :title="`Consultation facture | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="`Facture #${invoice.matricule}`"
            :breadcrumbs="breadcrumbs"
        >
            <template #action>
                <button type="button" class="btn btn-primary m-1" @click="generatePDF">
                    <i class="fa fa-print"></i>
                    Imprimer la facture
                </button>

                <button
                    v-if="!isPlancheInvoice && invoice.status !== 'canceled'"
                    class="btn btn-warning m-1"
                    @click="editInvoice"
                >
                    <i class="fa fa-edit"></i>
                    Modifier la facture
                </button>

                <button
                    v-if="!isPlancheInvoice && invoice.status !== 'canceled'"
                    class="btn btn-danger m-1"
                    @click="cancelInvoice"
                >
                    <i class="fa fa-trash"></i>
                    Annuler la facture
                </button>
                <button type="button" class="btn btn-outline-primary" @click="goBack">
                    <i class="fa fa-arrow-left"></i>
                    Retour a la liste
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <div class="card invoice1">
                    <div class="body">
                        <div class="invoice-top clearfix">
                            <div class="info">
                                <h6>Client : <a :href="`/admin/clients/${invoice.client.id}/consultation`">{{ invoice.client.name }}</a></h6>
                                <p v-if="isPlancheInvoice" class="mb-0">
                                    BL source : {{ invoice.planche_bon_livraison?.numero_bl || '-' }}
                                </p>
                            </div>
                            <div class="title">
                                <h4>Facture #{{ invoice.matricule }}</h4>
                                <p>Date : {{ formattedDate }}</p>
                            </div>
                        </div>
                        <hr>

                        <div v-if="isPlancheInvoice" class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-dark">
                                <tr>
                                    <th>Fournisseur</th>
                                    <th>Contrat</th>
                                    <th>Code couleur</th>
                                    <th>Epaisseur</th>
                                    <th>Qte prevue</th>
                                    <th>Qte livree</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="ligne in plancheLines" :key="ligne.id">
                                    <td>{{ ligne.supplier_name || '-' }}</td>
                                    <td>{{ ligne.numero_contrat || '-' }}</td>
                                    <td>{{ ligne.code_couleur || '-' }}</td>
                                    <td>{{ formatDecimal(ligne.epaisseur) }}</td>
                                    <td>{{ ligne.quantite_prevue }}</td>
                                    <td>{{ ligne.quantite_livree }}</td>
                                </tr>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="5">Total lignes :</td>
                                    <td>{{ plancheLines.length }}</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div v-else class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-dark">
                                <tr>
                                    <th>Essence</th>
                                    <th>Numero Colis</th>
                                    <th>Longueur</th>
                                    <th>Epaisseur</th>
                                    <th>Nb P</th>
                                    <th>Volume (m3)</th>
                                    <th>Prix/m3</th>
                                    <th>Prix Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="item in sortedItems" :key="item.id">
                                    <td>{{ item.article_item.article.essence }}</td>
                                    <td>{{ item.article_item.numero_colis }}</td>
                                    <td>{{ item.article_item.longueur }}</td>
                                    <td>{{ item.article_item.epaisseur }}</td>
                                    <td>{{ item.article_item.nombre_piece }}</td>
                                    <td>{{ formatVolume(item.article_item.volume) }} m3</td>
                                    <td>{{ formatTotalPrice(item.price) }} FCFA</td>
                                    <td>{{ formatTotalPrice(item.total_price_item) }} FCFA</td>
                                </tr>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="5">Total colis :</td>
                                    <td colspan="3">{{ invoice.items.length }}</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                        <hr>
                        <div class="row clearfix">
                            <div class="col-md-12 text-right">
                                <h3 class="mb-0 m-t-10">Total : {{ formatTotalPrice(invoice.total_price) }} FCFA</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import dayjs from 'dayjs';
import Swal from 'sweetalert2';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des factures', link: '/admin/invoices', icon: 'fa fa-plus-square' },
    { label: 'Consultation facture' }
];

const props = defineProps({
    invoice: Object
});

const isPlancheInvoice = computed(() => Boolean(props.invoice.planche_bon_livraison));

const formattedDate = computed(() => {
    return dayjs(props.invoice.date).format('DD/MM/YYYY');
});

const sortedItems = computed(() => {
    return [...props.invoice.items].sort((a, b) => {
        if (a.article_item.longueur === b.article_item.longueur) {
            return a.article_item.epaisseur - b.article_item.epaisseur;
        }
        return a.article_item.longueur - b.article_item.longueur;
    });
});

const plancheLines = computed(() => {
    const lignes = props.invoice.planche_bon_livraison?.lignes || [];

    return lignes.map((ligne) => ({
        id: ligne.id,
        supplier_name: ligne.planche_detail?.planche?.contrat?.supplier?.name,
        numero_contrat: ligne.planche_detail?.planche?.contrat?.numero,
        code_couleur: ligne.planche_detail?.couleur?.code,
        epaisseur: ligne.planche_detail?.epaisseur,
        quantite_prevue: ligne.planche_detail?.quantite_prevue || 0,
        quantite_livree: ligne.quantite_livree,
    }));
});

function formatVolume(value) {
    return value ? `${parseFloat(value).toFixed(3)}` : '0.000';
}

function formatTotalPrice(value) {
    const amount = value ? parseInt(value, 10) : 0;
    return new Intl.NumberFormat('de-DE').format(amount);
}

function formatDecimal(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return Number(value).toFixed(2);
}

function cancelInvoice() {
    Swal.fire({
        title: 'Etes-vous sur ?',
        text: 'Tous les articles de cette facture seront remis en disponible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, annuler',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/invoices/${props.invoice.id}/cancel`)
                .then((response) => {
                    Swal.fire('Facture annulee', response.data.message, 'success')
                        .then(() => location.reload());
                })
                .catch((error) => {
                    Swal.fire('Erreur', error.response?.data?.error || 'Impossible d annuler la facture.', 'error');
                });
        }
    });
}

function generatePDF() {
    window.open(`/admin/invoices/${props.invoice.id}/generate-pdf`, '_blank');
}

function editInvoice() {
    Inertia.visit(`/admin/invoices/${props.invoice.id}/edit`);
}

function goBack() {
    Inertia.visit('/admin/invoices');
}
</script>
