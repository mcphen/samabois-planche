<template>
    <Head :title="`Nouveau BL planche | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="'Nouveau bon de livraison planche'" :breadcrumbs="breadcrumbs">
            <template #action>
                <Link class="btn btn-primary" href="/admin/planche-bons-livraison">
                    <i class="fa fa-arrow-left"></i> Retour a la liste
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="card mb-4">
            <div class="body">
                <div v-if="formError" class="alert alert-danger">
                    {{ formError }}
                </div>

                <form @submit.prevent="submitForm">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Client *</label>
                            <select v-model="form.client_id" class="form-control">
                                <option value="">Selectionner un client</option>
                                <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                                    {{ client.name }}
                                </option>
                            </select>
                            <small v-if="errors.client_id" class="text-danger">{{ errors.client_id[0] }}</small>
                        </div>

                        <div class="col-md-4">
                            <label>Numero BL *</label>
                            <input v-model="form.numero_bl" type="text" class="form-control" />
                            <small v-if="errors.numero_bl" class="text-danger">{{ errors.numero_bl[0] }}</small>
                        </div>

                        <div class="col-md-4">
                            <label>Date livraison *</label>
                            <input v-model="form.date_livraison" type="date" class="form-control" />
                            <small v-if="errors.date_livraison" class="text-danger">{{ errors.date_livraison[0] }}</small>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label>Statut *</label>
                            <select v-model="form.statut" class="form-control">
                                <option value="brouillon">Brouillon</option>
                                <option value="valide">Valide</option>
                            </select>
                            <small v-if="errors.statut" class="text-danger">{{ errors.statut[0] }}</small>
                            <small class="text-muted d-block mt-1">
                                Si vous validez ce BL, une facture client sera creee automatiquement.
                            </small>
                        </div>
                    </div>

                </form>
            </div>
        </div>

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
                        <PlancheColorInput
                            :model-value="filters.code_couleur"
                            placeholder="Cliquez puis tapez..."
                            @update:modelValue="filters.code_couleur = $event"
                        />
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
                        <select v-model="filters.epaisseur" class="form-control">
                            <option value="">Toutes</option>
                            <option
                                v-for="epaisseurOption in epaisseurOptions"
                                :key="epaisseurOption.id"
                                :value="epaisseurOption.value"
                            >
                                {{ epaisseurOption.label }}
                            </option>
                        </select>
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

        <div class="card">
            <div class="header">
                <h2>Lignes du bon</h2>
            </div>
            <div class="body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fournisseur</th>
                                <th>Contrat</th>
                                <th>Code couleur</th>
                                <th>Catégorie</th>
                                <th>Epaisseur</th>
                                <th>Disponible</th>
                                <th>Quantite livree</th>
                                <th>Prix unitaire</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!form.lignes.length">
                                <td colspan="10" class="text-center py-4">Aucune ligne ajoutee.</td>
                            </tr>
                            <tr v-for="(ligne, index) in form.lignes" :key="ligne.planche_detail_id">
                                <td>{{ ligne.supplier_name || '-' }}</td>
                                <td>{{ ligne.numero_contrat || '-' }}</td>
                                <td><span class="badge badge-info">{{ ligne.code_couleur || '-' }}</span></td>
                                <td>
                                    <span class="badge" :class="categorieBadgeClass(ligne.categorie)">
                                        {{ categorieLabel(ligne.categorie) }}
                                    </span>
                                </td>
                                <td>{{ formatDecimal(ligne.epaisseur) }}</td>
                                <td>{{ ligne.quantite_disponible }}</td>
                                <td>
                                    <input
                                        v-model="ligne.quantite_livree"
                                        type="number"
                                        min="1"
                                        :max="ligne.quantite_disponible"
                                        class="form-control"
                                    />
                                    <small v-if="errors[`lignes.${index}.quantite_livree`]" class="text-danger">
                                        {{ errors[`lignes.${index}.quantite_livree`][0] }}
                                    </small>
                                </td>
                                <td>
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
                                </td>
                                <td>{{ formatCurrency(lineTotal(ligne)) }}</td>
                                <td>
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeLine(index)">
                                        Retirer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="form.lignes.length">
                            <tr>
                                <th colspan="6" class="text-right">Totaux</th>
                                <th>{{ totalQuantite }}</th>
                                <th></th>
                                <th>{{ formatCurrency(totalMontant) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <small v-if="errors.lignes" class="text-danger d-block mt-2">{{ errors.lignes[0] }}</small>
            </div>
        </div>

        <div class="mt-4 mb-4">
            <button type="button" class="btn btn-success" :disabled="submitting || !form.lignes.length || !form.client_id || !form.numero_bl" @click="submitForm">
                <span v-if="submitting">Enregistrement...</span>
                <span v-else>
                    Suivant
                    <span v-if="form.lignes.length > 0">({{ form.lignes.length }})</span>
                </span>
            </button>
            <Link href="/admin/planche-bons-livraison" class="btn btn-secondary ml-2">
                Annuler
            </Link>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import PlancheColorInput from '@/Components/PlancheColorInput.vue';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    epaisseurs: { type: Array, default: () => [] },
    availableDetails: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Bons de livraison planche', link: '/admin/planche-bons-livraison', icon: 'fa fa-truck' },
    { label: 'Nouveau BL' },
];

const detailsLoading = ref(false);
const submitting = ref(false);
const formError = ref('');
const errors = ref({});
const availableDetailsState = ref(props.availableDetails);
const epaisseurOptions = computed(() => {
    return props.epaisseurs
        .map((item) => {
            const value = extractEpaisseurValue(item);

            if (!value) {
                return null;
            }

            return {
                id: item.id,
                label: item.intitule,
                value,
            };
        })
        .filter(Boolean);
});
const filters = reactive({ supplier_id: '', numero_contrat: '', code_couleur: '', categorie: '', epaisseur: '' });
const form = reactive({
    client_id: '',
    numero_bl: '',
    date_livraison: new Date().toISOString().slice(0, 10),
    statut: 'brouillon',
    lignes: [],
});

function fetchAvailableDetails() {
    detailsLoading.value = true;
    axios.get('/admin/planche-bons-livraison/details-disponibles', {
        params: {
            supplier_id:    filters.supplier_id || undefined,
            numero_contrat: filters.numero_contrat || undefined,
            code_couleur:   filters.code_couleur || undefined,
            categorie:      filters.categorie || undefined,
            epaisseur:      filters.epaisseur || undefined,
        },
    }).then((response) => {
        availableDetailsState.value = response.data || [];
    }).finally(() => {
        detailsLoading.value = false;
    });
}

function extractEpaisseurValue(item) {
    const sources = [item?.slug, item?.intitule];

    for (const source of sources) {
        const match = String(source || '').replace(',', '.').match(/(\d+(?:\.\d+)?)/);

        if (match) {
            return match[1];
        }
    }

    return '';
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
        planche_detail_id:   detail.id,
        quantite_livree:     1,
        prix_unitaire:       0,
        quantite_disponible: detail.quantite_disponible,
        supplier_name:       detail.supplier_name,
        numero_contrat:      detail.numero_contrat,
        code_couleur:        detail.code_couleur,
        categorie:           detail.categorie,
        epaisseur:           detail.epaisseur,
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

    axios.post('/admin/planche-bons-livraison/store', {
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
        Inertia.visit(response.data?.data?.redirect_to || '/admin/planche-bons-livraison');
    }).catch((error) => {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
            formError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
            return;
        }

        formError.value = 'Une erreur est survenue pendant l enregistrement.';
    }).finally(() => {
        submitting.value = false;
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
</script>
