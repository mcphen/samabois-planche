<template>
    <Head :title="`Facture ${bonLivraison.numero_bl} | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="`Facture ${bonLivraison.numero_bl}`" :breadcrumbs="breadcrumbs">
            <template #action>
                <Link class="btn btn-primary mr-2" href="/admin/planche-bons-livraison">
                    <i class="fa fa-arrow-left"></i> Retour a la liste
                </Link>
                <Link
                    v-if="bonLivraison.invoice_id"
                    class="btn btn-info mr-2"
                    :href="`/admin/invoices/${bonLivraison.invoice_id}/consultation`"
                >
                    Voir facture
                </Link>
                <button
                    v-if="bonLivraison.can_edit"
                    type="button"
                    class="btn btn-danger"
                    @click="deleteBon"
                >
                    Supprimer
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

        <div v-if="formError" class="alert alert-danger">
            {{ formError }}
        </div>

        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="header"><h2>Informations generales</h2></div>
                    <div class="body">
                        <template v-if="bonLivraison.can_edit">
                            <div class="form-group">
                                <label>Client *</label>
                                <select v-model="form.client_id" class="form-control">
                                    <option value="">Selectionner un client</option>
                                    <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                                        {{ client.name }}
                                    </option>
                                </select>
                                <small v-if="errors.client_id" class="text-danger">{{ errors.client_id[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label>Numero facture *</label>
                                <input v-model="form.numero_bl" type="text" class="form-control" />
                                <small v-if="errors.numero_bl" class="text-danger">{{ errors.numero_bl[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label>Date de livraison *</label>
                                <input v-model="form.date_livraison" type="date" class="form-control" />
                                <small v-if="errors.date_livraison" class="text-danger">{{ errors.date_livraison[0] }}</small>
                            </div>
                            <div class="form-group mb-0">
                                <label>Statut *</label>
                                <select v-model="form.statut" class="form-control">
                                    <option value="brouillon">Brouillon</option>
                                    <option value="valide">Valide</option>
                                </select>
                                <small v-if="errors.statut" class="text-danger">{{ errors.statut[0] }}</small>
                                <small class="text-muted d-block mt-1">
                                    La validation de cette facture cree automatiquement une facture client.
                                </small>
                            </div>
                        </template>
                        <template v-else>
                            <div class="mb-3"><strong>Client :</strong> {{ bonLivraison.client_name || '-' }}</div>
                            <div class="mb-3"><strong>Date de livraison :</strong> {{ bonLivraison.date_livraison }}</div>
                            <div class="mb-3">
                                <strong>Statut :</strong>
                                <span class="badge" :class="bonLivraison.statut === 'valide' ? 'badge-success' : 'badge-warning'">{{ bonLivraison.statut }}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Facture :</strong>
                                <Link
                                    v-if="bonLivraison.invoice_id"
                                    :href="`/admin/invoices/${bonLivraison.invoice_id}/consultation`"
                                >
                                    {{ bonLivraison.invoice_matricule }}
                                </Link>
                                <span v-else>-</span>
                            </div>
                        </template>
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
                                    <th>Catégorie</th>
                                    <th>Epaisseur</th>
                                    <th>Prevue</th>
                                    <th>Disponible</th>
                                    <th>Livree</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                    <th v-if="bonLivraison.can_edit">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!form.lignes.length">
                                    <td :colspan="bonLivraison.can_edit ? 11 : 10" class="text-center">Aucune ligne.</td>
                                </tr>
                                <tr v-for="(ligne, index) in form.lignes" :key="`${ligne.planche_detail_id}-${index}`">
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
                                    <td>{{ lineAvailability(ligne) }}</td>
                                    <td>
                                        <template v-if="bonLivraison.can_edit">
                                            <input
                                                v-model="ligne.quantite_livree"
                                                type="number"
                                                min="1"
                                                :max="lineAvailability(ligne)"
                                                class="form-control"
                                            />
                                            <small v-if="errors[`lignes.${index}.quantite_livree`]" class="text-danger">
                                                {{ errors[`lignes.${index}.quantite_livree`][0] }}
                                            </small>
                                        </template>
                                        <template v-else>
                                            {{ ligne.quantite_livree }}
                                        </template>
                                    </td>
                                    <td>
                                        <template v-if="bonLivraison.can_edit">
                                            <input
                                                v-model="ligne.prix_unitaire"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="form-control"
                                            />
                                            <small v-if="errors[`lignes.${index}.prix_unitaire`]" class="text-danger">
                                                {{ errors[`lignes.${index}.prix_unitaire`][0] }}
                                            </small>
                                        </template>
                                        <template v-else>
                                            {{ formatCurrency(ligne.prix_unitaire) }}
                                        </template>
                                    </td>
                                    <td>{{ formatCurrency(lineTotal(ligne)) }}</td>
                                    <td v-if="bonLivraison.can_edit">
                                        <button type="button" class="btn btn-outline-danger btn-sm" @click="removeLine(index)">
                                            Retirer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="form.lignes.length">
                                <tr>
                                    <th colspan="7" class="text-right">Totaux</th>
                                    <th>{{ totalQuantite }}</th>
                                    <th></th>
                                    <th>{{ formatCurrency(totalMontant) }}</th>
                                    <th v-if="bonLivraison.can_edit"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <small v-if="errors.lignes" class="text-danger d-block mt-2">{{ errors.lignes[0] }}</small>
                    </div>
                </div>
            </div>
        </div>

        <template v-if="bonLivraison.can_edit">
            <div class="card mb-4">
                <div class="header">
                    <h2>Details disponibles</h2>
                </div>
                <div class="body">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="small font-weight-bold">Fournisseur</label>
                            <select v-model="filters.supplier_id" class="form-control">
                                <option value="">Tous</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">
                                    {{ supplier.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="small font-weight-bold">Contrat</label>
                            <input v-model="filters.numero_contrat" type="text" class="form-control" />
                        </div>

                        <div class="col-md-2">
                            <label class="small font-weight-bold">Code couleur</label>
                            <input v-model="filters.code_couleur" type="text" class="form-control" />
                        </div>

                        <div class="col-md-2">
                            <label class="small font-weight-bold">Catégorie</label>
                            <select v-model="filters.categorie" class="form-control">
                                <option value="">Toutes</option>
                                <option value="mate">Mate</option>
                                <option value="semi_brillant">Semi-brillant</option>
                                <option value="brillant">Brillant</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="small font-weight-bold">Epaisseur</label>
                            <input v-model="filters.epaisseur" type="text" class="form-control" />
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary mr-2" @click="resetFilters">RAZ</button>
                            <button type="button" class="btn btn-primary" @click="fetchAvailableDetails">Chercher</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Fournisseur</th>
                                    <th>Contrat</th>
                                    <th>Code couleur</th>
                                    <th>Catégorie</th>
                                    <th>Epaisseur</th>
                                    <th>Prevue</th>
                                    <th>Deja livree</th>
                                    <th>Disponible</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="detailsLoading">
                                    <td colspan="9" class="text-center py-4">Chargement...</td>
                                </tr>
                                <tr v-else-if="!availableDetailsState.length">
                                    <td colspan="9" class="text-center py-4">Aucun detail disponible.</td>
                                </tr>
                                <tr v-for="detail in availableDetailsState" :key="detail.id">
                                    <td>{{ detail.supplier_name || '-' }}</td>
                                    <td>{{ detail.numero_contrat || '-' }}</td>
                                    <td><span class="badge badge-info">{{ detail.code_couleur || '-' }}</span></td>
                                    <td>
                                        <span class="badge" :class="categorieBadgeClass(detail.categorie)">
                                            {{ categorieLabel(detail.categorie) }}
                                        </span>
                                    </td>
                                    <td>{{ formatDecimal(detail.epaisseur) }}</td>
                                    <td>{{ detail.quantite_prevue }}</td>
                                    <td>{{ detail.quantite_livree_total }}</td>
                                    <td>{{ detail.quantite_disponible }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            :disabled="isAlreadySelected(detail.id) || detail.quantite_disponible < 1"
                                            @click="addLine(detail)"
                                        >
                                            {{ isAlreadySelected(detail.id) ? 'Deja ajoute' : 'Ajouter' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Barre d'action avec recapitulatif -->
            <div class="card border-top border-success" style="border-top-width: 3px !important;">
                <div class="body">
                    <div class="row align-items-center">
                        <div class="col-lg-9 col-md-8">
                            <div class="row text-center">
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <div class="small text-muted mb-1">Client</div>
                                    <div class="font-weight-bold text-truncate">{{ selectedClientName || '-' }}</div>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <div class="small text-muted mb-1">N° facture</div>
                                    <div class="font-weight-bold text-truncate">{{ form.numero_bl || '-' }}</div>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <div class="small text-muted mb-1">Date livraison</div>
                                    <div class="font-weight-bold">{{ form.date_livraison || '-' }}</div>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <div class="small text-muted mb-1">Statut</div>
                                    <span class="badge" :class="form.statut === 'valide' ? 'badge-success' : 'badge-warning'">
                                        {{ form.statut }}
                                    </span>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <div class="small text-muted mb-1">Lignes</div>
                                    <div class="font-weight-bold">{{ form.lignes.length }}</div>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <div class="small text-muted mb-1">Montant total</div>
                                    <div class="font-weight-bold text-success">{{ formatCurrency(totalMontant) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4 text-right mt-3 mt-md-0">
                            <Link href="/admin/planche-bons-livraison" class="btn btn-secondary mr-2">
                                Annuler
                            </Link>
                            <button
                                type="button"
                                class="btn btn-success"
                                :disabled="submitting || !form.lignes.length || !form.client_id || !form.numero_bl"
                                @click="submitForm"
                            >
                                <span v-if="submitting">
                                    <i class="fa fa-spinner fa-spin mr-1"></i> Enregistrement...
                                </span>
                                <span v-else>
                                    <i class="fa fa-check mr-1"></i> Enregistrer
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const props = defineProps({
    bonLivraison: { type: Object, required: true },
    availableDetails: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Factures planche', link: '/admin/planche-bons-livraison', icon: 'fa fa-truck' },
    { label: props.bonLivraison.numero_bl },
];

const detailsLoading = ref(false);
const submitting = ref(false);
const formError = ref('');
const errors = ref({});
const availableDetailsState = ref(props.availableDetails);
const filters = reactive({ supplier_id: '', numero_contrat: '', code_couleur: '', categorie: '', epaisseur: '' });
const form = reactive({
    client_id: props.bonLivraison.client_id ? String(props.bonLivraison.client_id) : '',
    numero_bl: props.bonLivraison.numero_bl,
    date_livraison: props.bonLivraison.date_livraison,
    statut: props.bonLivraison.statut,
    lignes: props.bonLivraison.lignes.map((ligne) => ({
        planche_detail_id: ligne.detail_id,
        quantite_livree:   ligne.quantite_livree,
        prix_unitaire:     ligne.prix_unitaire,
        supplier_name:     ligne.supplier_name,
        numero_contrat:    ligne.numero_contrat,
        code_couleur:      ligne.code_couleur,
        categorie:         ligne.categorie,
        epaisseur:         ligne.epaisseur,
        quantite_prevue:   ligne.quantite_prevue,
    })),
});

function availableDetail(detailId) {
    return availableDetailsState.value.find((detail) => Number(detail.id) === Number(detailId)) || null;
}

function lineAvailability(ligne) {
    return Number(availableDetail(ligne.planche_detail_id)?.quantite_disponible ?? ligne.quantite_livree ?? 0);
}

function fetchAvailableDetails() {
    detailsLoading.value = true;

    axios.get('/admin/planche-bons-livraison/details-disponibles', {
        params: {
            supplier_id:              filters.supplier_id || undefined,
            numero_contrat:           filters.numero_contrat || undefined,
            code_couleur:             filters.code_couleur || undefined,
            categorie:                filters.categorie || undefined,
            epaisseur:                filters.epaisseur || undefined,
            exclude_bon_livraison_id: props.bonLivraison.id,
        },
    }).then((response) => {
        availableDetailsState.value = response.data || [];
    }).finally(() => {
        detailsLoading.value = false;
    });
}

function resetFilters() {
    filters.supplier_id    = '';
    filters.numero_contrat = '';
    filters.code_couleur   = '';
    filters.categorie      = '';
    filters.epaisseur      = '';
    fetchAvailableDetails();
}

function isAlreadySelected(detailId) {
    return form.lignes.some((ligne) => Number(ligne.planche_detail_id) === Number(detailId));
}

function categorieBadgeClass(cat) {
    return { mate: 'badge-secondary', semi_brillant: 'badge-warning', brillant: 'badge-success' }[cat] || 'badge-light';
}

function addLine(detail) {
    if (isAlreadySelected(detail.id)) {
        return;
    }

    form.lignes.push({
        planche_detail_id: detail.id,
        quantite_livree:   1,
        prix_unitaire:     0,
        supplier_name:     detail.supplier_name,
        numero_contrat:    detail.numero_contrat,
        code_couleur:      detail.code_couleur,
        categorie:         detail.categorie,
        epaisseur:         detail.epaisseur,
        quantite_prevue:   detail.quantite_prevue,
    });
}

function categorieLabel(cat) {
    const map = { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' };
    return map[cat] || cat || '-';
}

function removeLine(index) {
    form.lignes.splice(index, 1);
}

function submitForm() {
    submitting.value = true;
    errors.value = {};
    formError.value = '';

    axios.put(`/admin/planche-bons-livraison/${props.bonLivraison.id}`, {
        client_id: form.client_id ? Number(form.client_id) : null,
        numero_bl: form.numero_bl,
        date_livraison: form.date_livraison,
        statut: form.statut,
        lignes: form.lignes.map((ligne) => ({
            planche_detail_id: ligne.planche_detail_id,
            quantite_livree: Number(ligne.quantite_livree),
            prix_unitaire: Number(ligne.prix_unitaire || 0),
        })),
    }).then((response) => {
        Inertia.visit(response.data?.data?.redirect_to || `/admin/planche-bons-livraison/${props.bonLivraison.id}`);
    }).catch((error) => {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            formError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
            return;
        }

        formError.value = 'Une erreur est survenue pendant la mise a jour.';
    }).finally(() => {
        submitting.value = false;
    });
}

function deleteBon() {
    if (!confirm('Etes-vous sur de vouloir supprimer cette facture ?')) {
        return;
    }

    axios.delete(`/admin/planche-bons-livraison/${props.bonLivraison.id}`).then((response) => {
        Inertia.visit(response.data?.data?.redirect_to || '/admin/planche-bons-livraison');
    });
}

function formatDecimal(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return Number(value).toFixed(2);
}

function lineTotal(ligne) {
    return Number(ligne.quantite_livree || 0) * Number(ligne.prix_unitaire || 0);
}

function formatCurrency(value) {
    return Number(value || 0).toFixed(2);
}

const totalQuantite = computed(() => (
    form.lignes.reduce((total, ligne) => total + Number(ligne.quantite_livree || 0), 0)
));

const totalMontant = computed(() => (
    form.lignes.reduce((total, ligne) => total + lineTotal(ligne), 0)
));

const selectedClientName = computed(() => {
    const client = props.clients.find((c) => String(c.id) === String(form.client_id));
    return client?.name || null;
});
</script>
