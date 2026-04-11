<template>
    <Head :title="`Facture ${bonLivraison.numero_bl} | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="`Facture ${bonLivraison.numero_bl}`" :breadcrumbs="breadcrumbs">
            <template #action>
                <Link class="btn btn-primary mr-2" href="/admin/planche-bons-livraison">
                    <i class="fa fa-arrow-left"></i> Retour a la liste
                </Link>
               
                <button type="button" class="btn btn-secondary" @click="generatePDF">
                    <i class="fa fa-print"></i> Imprimer
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix row-deck">
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card primary-bg"><div class="body"><div class="p-15 text-light"><h3>{{ bonLivraison.numero_bl }}</h3><span>Numero facture</span></div></div></div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card secondary-bg"><div class="body"><div class="p-15 text-light"><h3>{{ bonLivraison.client_name || '-' }}</h3><span>Client</span></div></div></div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-info"><div class="body"><div class="p-15 text-light"><h3>{{ bonLivraison.lignes_count }}</h3><span>Lignes</span></div></div></div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-success"><div class="body"><div class="p-15 text-light"><h3>{{ bonLivraison.invoice_matricule || '-' }}</h3><span>Facture</span></div></div></div>
            </div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="header"><h2>Informations generales</h2></div>
                    <div class="body">
                        <div class="mb-3"><strong>Client :</strong> {{ bonLivraison.client_name || '-' }}</div>
                        <div class="mb-3"><strong>Numero facture :</strong> {{ bonLivraison.numero_bl || '-' }}</div>
                        <div class="mb-3"><strong>Date de livraison :</strong> {{ bonLivraison.date_livraison || '-' }}</div>
                        <div class="mb-3">
                            <strong>Statut :</strong>
                            <span class="badge badge-success ml-2">valide</span>
                        </div>
                        <div class="mb-3">
                            <strong>Facture client :</strong>
                            <Link
                                v-if="bonLivraison.invoice_id"
                                :href="`/admin/invoices/${bonLivraison.invoice_id}/consultation`"
                            >
                                {{ bonLivraison.invoice_matricule }}
                            </Link>
                            <span v-else>-</span>
                        </div>
                        <div class="mt-3 mb-3"><strong>Contrats concernes :</strong> {{ bonLivraison.contrats.join(', ') || '-' }}</div>
                        <div class="mb-0"><strong>Fournisseurs concernes :</strong> {{ bonLivraison.fournisseurs.join(', ') || '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="header"><h2>Lignes de la facture</h2></div>
                    <div class="body table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Fournisseur</th>
                                    <th>Contrat</th>
                                    <th>Code couleur</th>
                                    <th>Categorie</th>
                                    <th>Epaisseur</th>
                                    <th>Prevue</th>
                                    <th>Livree</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!bonLivraison.lignes.length">
                                    <td colspan="9" class="text-center">Aucune ligne.</td>
                                </tr>
                                <tr v-for="ligne in bonLivraison.lignes" :key="ligne.id">
                                    <td>{{ ligne.supplier_name || '-' }}</td>
                                    <td>{{ ligne.numero_contrat || '-' }}</td>
                                    <td><span class="badge badge-info">{{ ligne.code_couleur || '-' }}</span></td>
                                    <td>
                                        <span class="badge" :class="categorieBadgeClass(ligne.categorie)">
                                            {{ categorieLabel(ligne.categorie) }}
                                        </span>
                                    </td>
                                    <td>{{ formatDecimal(ligne.epaisseur) }}</td>
                                    <td>{{ ligne.quantite_prevue }}</td>
                                    <td>{{ ligne.quantite_livree }}</td>
                                    <td>{{ formatCurrency(ligne.prix_unitaire) }}</td>
                                    <td>{{ formatCurrency(ligne.prix_total) }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="bonLivraison.lignes.length">
                                <tr>
                                    <th colspan="6" class="text-right">Totaux</th>
                                    <th>{{ bonLivraison.quantite_totale_livree }}</th>
                                    <th></th>
                                    <th>{{ formatCurrency(bonLivraison.montant_total) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
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
    return Number(value || 0).toFixed(2);
}
</script>
