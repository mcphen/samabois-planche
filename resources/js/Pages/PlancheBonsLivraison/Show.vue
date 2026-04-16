<template>
    <Head :title="`Facture ${bonLivraison.numero_bl} | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="`Facture ${bonLivraison.numero_bl}`" :breadcrumbs="breadcrumbs">
            <template #action>
                <button type="button" class="btn btn-primary m-1" @click="generatePDF">
                    <i class="fa fa-print"></i> Imprimer
                </button>
                <button
                    v-if="bonLivraison.can_edit"
                    type="button"
                    class="btn btn-warning m-1"
                    @click="editBonLivraison"
                >
                    <i class="fa fa-edit"></i> Modifier
                </button>
                <button
                    v-if="bonLivraison.can_cancel"
                    type="button"
                    class="btn btn-danger m-1"
                    @click="cancelBonLivraison"
                >
                    <i class="fa fa-trash"></i> Annuler
                </button>
                <Link class="btn btn-outline-primary m-1" href="/admin/planche-bons-livraison">
                    <i class="fa fa-arrow-left"></i> Retour a la liste
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <div class="card invoice1">
                    <div class="body">
                        <div class="invoice-top clearfix">
                            <div class="info">
                                <h6>Client : <a :href="`/admin/clients/${bonLivraison.client_id}/consultation`">{{ bonLivraison.client_name || '-' }}</a></h6>
                                <p class="mb-0">Date de livraison : {{ bonLivraison.date_livraison || '-' }}</p>
                                
                            </div>
                            <div class="title">
                                <h4>Facture {{ bonLivraison.numero_bl }}</h4>
                                <p>
                                    <span class="badge" :class="bonLivraison.statut === 'valide' ? 'badge-success' : 'badge-warning'">
                                        {{ bonLivraison.statut }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Contrat</th>
                                        <th>Code couleur</th>
                                        <th>Categorie</th>
                                        <th>Epaisseur</th>
                                        <th>Qte livree</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                 
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!bonLivraison.lignes.length">
                                        <td colspan="9" class="text-center py-4">Aucune ligne.</td>
                                    </tr>
                                    <tr v-for="ligne in bonLivraison.lignes" :key="ligne.id">
                                        <td>{{ ligne.numero_contrat || '-' }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ ligne.code_couleur || '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge" :class="categorieBadgeClass(ligne.categorie)">
                                                {{ categorieLabel(ligne.categorie) }}
                                            </span>
                                        </td>
                                        <td>{{ formatDecimal(ligne.epaisseur) }}</td>
                                        <td>{{ ligne.quantite_livree }}</td>
                                        <td>{{ formatCurrency(ligne.prix_unitaire) }}</td>
                                        <td>{{ formatCurrency(ligne.prix_total) }}</td>
                                        
                                    </tr>
                                </tbody>
                                <tfoot v-if="bonLivraison.lignes.length">
                                    <tr>
                                        <td colspan="4">Total lignes : {{ bonLivraison.lignes_count }}</td>
                                        <td>{{ bonLivraison.quantite_totale_livree }}</td>
                                        <td></td>
                                      
                                        <td :class="bonLivraison.benefice_total !== null ? (bonLivraison.benefice_total >= 0 ? 'text-success font-weight-bold' : 'text-danger font-weight-bold') : 'text-muted'">
                                            {{ bonLivraison.benefice_total !== null ? formatCurrency(bonLivraison.benefice_total) : '-' }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <hr>

                        <div class="row clearfix">
                            <div class="col-md-6">
                                <table class="table table-sm" style="max-width: 400px;">
                                    <tbody>
                                        <tr>
                                            <td><strong>Total facture</strong></td>
                                            <td class="text-right">{{ formatCurrency(bonLivraison.montant_total) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Montant payé</strong></td>
                                            <td class="text-right text-success">{{ formatCurrency(bonLivraison.montant_solde) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reste à payer</strong></td>
                                            <td class="text-right text-danger">
                                                <strong>{{ formatCurrency(bonLivraison.montant_total - bonLivraison.montant_solde) }}</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 text-right">
                                <h3 class="mb-0 m-t-10">
                                    Total : {{ formatCurrency(bonLivraison.montant_total) }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const props = defineProps({
    bonLivraison: { type: Object, required: true },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Factures planche', link: '/admin/planche-bons-livraison', icon: 'fa fa-truck' },
    { label: props.bonLivraison.numero_bl },
];

function generatePDF() {
    window.open(`/admin/planche-bons-livraison/${props.bonLivraison.id}/generate-pdf`, '_blank');
}

function editBonLivraison() {
    Inertia.visit(`/admin/planche-bons-livraison/${props.bonLivraison.id}/edit`);
}

function cancelBonLivraison() {
    const confirmationText = 'Les quantites livrees de cette facture seront remises en stock.';

    Swal.fire({
        title: 'Etes-vous sur ?',
        text: confirmationText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, annuler',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        axios.delete(`/admin/planche-bons-livraison/${props.bonLivraison.id}`)
            .then((response) => {
                Swal.fire('Facture annulee', response.data?.message || 'La facture a ete annulee avec succes.', 'success')
                    .then(() => {
                        Inertia.visit(response.data?.data?.redirect_to || '/admin/planche-bons-livraison');
                    });
            })
            .catch((error) => {
                Swal.fire(
                    'Erreur',
                    error.response?.data?.message || error.response?.data?.error || 'Impossible d annuler cette facture.',
                    'error'
                );
            });
    });
}

function categorieBadgeClass(cat) {
    return { mate: 'badge-secondary', semi_brillant: 'badge-warning', brillant: 'badge-success' }[cat] || 'badge-light';
}

function categorieLabel(cat) {
    const map = { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' };
    return map[cat] || cat || '-';
}

function formatDecimal(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }
    return Number(value).toFixed(2);
}

function formatCurrency(value) {
    const num = Math.round(Number(value || 0));
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' CFA';
}
</script>
