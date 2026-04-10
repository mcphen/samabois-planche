<template>
    <Head :title="`Nouvelle facture planche | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="'Nouvelle facture planche'" :breadcrumbs="breadcrumbs">
            <template #action>
                <Link class="btn btn-primary" href="/admin/planche-bons-livraison">
                    <i class="fa fa-arrow-left"></i> Retour a la liste
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="card mb-4">
            <div class="body">
                <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 12px;">
                    <div class="d-flex flex-wrap" style="gap: 10px;">
                        <button
                            type="button"
                            class="btn"
                            :class="currentStep === 1 ? 'btn-primary' : 'btn-outline-primary'"
                            @click="goToStep(1)"
                        >
                            1. Recherche et panier
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="currentStep === 2 ? 'btn-primary' : 'btn-outline-primary'"
                            :disabled="!form.lignes.length"
                            @click="goToStep(2)"
                        >
                            2. Lignes et validation
                        </button>
                    </div>

                    <div class="text-muted small">
                        {{ selectedCount }} ligne(s) selectionnee(s)
                    </div>
                </div>

                <div class="mt-3 text-muted small">
                    Recherchez les details, ajoutez-les au panier, puis passez a la saisie des quantites et des prix.
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="header">
                <h2>Informations de la facture</h2>
            </div>
            <div class="body">
                <div v-if="formError" class="alert alert-danger mb-3">
                    {{ formError }}
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label>
                            Client *
                            <button type="button" class="btn btn-success btn-sm ms-2" @click="showModal = true">
                                <i class="fa fa-plus"></i> Ajouter client
                            </button>
                        </label>
                        <select v-model="form.client_id" class="form-control" @change="clearErrors(['client_id'])">
                            <option value="">Selectionner un client</option>
                            <option v-for="client in clients" :key="client.id" :value="String(client.id)">
                                {{ client.name }}
                            </option>
                        </select>
                        <small v-if="errors.client_id" class="text-danger">{{ errors.client_id[0] }}</small>
                    </div>

                    <div class="col-md-6">
                        <label>Date livraison *</label>
                        <input v-model="form.date_livraison" type="date" class="form-control" @change="clearErrors(['date_livraison'])" />
                        <small v-if="errors.date_livraison" class="text-danger">{{ errors.date_livraison[0] }}</small>
                    </div>

                </div>
            </div>
        </div>

        <template v-if="currentStep === 1">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="header">
                            <h2>Etape 1 - Rechercher les details disponibles</h2>
                        </div>
                        <div class="body">
                            <div class="row mb-3">
                                <div class="col-md-4 col-lg-3">
                                    <label class="small font-weight-bold">Fournisseur</label>
                                    <select v-model="filters.supplier_id" class="form-control">
                                        <option value="">Tous</option>
                                        <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">
                                            {{ supplier.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-3">
                                    <label class="small font-weight-bold">Contrat</label>
                                    <input v-model="filters.numero_contrat" type="text" class="form-control" />
                                </div>

                                <div class="col-md-4 col-lg-3">
                                    <label class="small font-weight-bold">Code couleur</label>
                                    <PlancheColorInput
                                        :model-value="filters.code_couleur"
                                        placeholder="Cliquez puis tapez..."
                                        @update:modelValue="filters.code_couleur = $event"
                                    />
                                </div>

                                <div class="col-md-4 col-lg-3">
                                    <label class="small font-weight-bold">Categorie</label>
                                    <select v-model="filters.categorie" class="form-control">
                                        <option value="">Toutes</option>
                                        <option value="mate">Mate</option>
                                        <option value="semi_brillant">Semi-brillant</option>
                                        <option value="brillant">Brillant</option>
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-3 mt-3">
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

                                <div class="col-md-8 col-lg-9 mt-3 d-flex align-items-end justify-content-end flex-wrap" style="gap: 8px;">
                                    <button type="button" class="btn btn-outline-secondary" @click="resetFilters">Reinitialiser</button>
                                    <button type="button" class="btn btn-primary" :disabled="detailsLoading" @click="fetchAvailableDetails">
                                        {{ detailsLoading ? 'Recherche...' : 'Rechercher' }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="!hasSearched" class="alert alert-light border mb-0">
                                Lancez une recherche pour afficher seulement les details utiles et ajouter vos selections au panier.
                            </div>

                            <div v-else class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fournisseur</th>
                                            <th>Contrat</th>
                                            <th>Code couleur</th>
                                            <th>Categorie</th>
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
                                            <td colspan="9" class="text-center py-4">Aucun detail disponible pour cette recherche.</td>
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
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4" style="position: sticky; top: 24px;">
                        <div class="header">
                            <h2>Panier</h2>
                        </div>
                        <div class="body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <strong>{{ selectedCount }} ligne(s)</strong>
                                <span class="badge badge-light border">{{ totalQuantiteDisponibleSelectionnee }} disponible(s)</span>
                            </div>

                            <div v-if="!form.lignes.length" class="text-muted">
                                Aucune ligne selectionnee pour le moment.
                            </div>

                            <div v-else>
                                <div
                                    v-for="ligne in form.lignes"
                                    :key="ligne.planche_detail_id"
                                    class="border rounded p-2 mb-2"
                                    style="background: #fbfcfe;"
                                >
                                    <div class="font-weight-bold">{{ ligne.code_couleur || '-' }}</div>
                                    <div class="small text-muted">{{ ligne.supplier_name || '-' }} | {{ ligne.numero_contrat || '-' }}</div>
                                    <div class="small text-muted">
                                        {{ categorieLabel(ligne.categorie) }} | {{ formatDecimal(ligne.epaisseur) }} mm | dispo {{ ligne.quantite_disponible }}
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm text-danger px-0 mt-1" @click="removeLineByDetailId(ligne.planche_detail_id)">
                                        Retirer du panier
                                    </button>
                                </div>
                            </div>

                            <small v-if="errors.lignes" class="text-danger d-block mb-3">{{ errors.lignes[0] }}</small>

                            <div v-if="stepOneRequirements.length" class="alert alert-warning mb-3">
                                <strong>Encore a completer :</strong>
                                <div class="mt-2" v-for="requirement in stepOneRequirements" :key="requirement">
                                    - {{ requirement }}
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-success btn-block"
                                :disabled="!canMoveToStepTwo"
                                :title="stepOneRequirements.length ? `Il manque: ${stepOneRequirements.join(', ')}` : 'Passer a l etape 2'"
                                @click="goToStep(2)"
                            >
                                Passer a l etape 2
                            </button>
                            <small class="text-muted d-block mt-2">
                                L etape suivante vous permettra de saisir les quantites livrees, les prix unitaires et les totaux.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="card mb-4">
                <div class="header">
                    <h2>Etape 2 - Lignes de la facture</h2>
                </div>
                <div class="body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap: 12px;">
                        <div class="text-muted">
                            Ajustez les quantites et les prix des lignes selectionnees avant l enregistrement final.
                        </div>
                        <div class="d-flex flex-wrap" style="gap: 8px;">
                            <span class="badge badge-light border px-3 py-2">{{ selectedCount }} ligne(s)</span>
                            <span class="badge badge-light border px-3 py-2">Quantite totale: {{ totalQuantite }}</span>
                            <span class="badge badge-light border px-3 py-2">Montant total: {{ formatCurrency(totalMontant) }}</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Code couleur</th>
                                    <th>Categorie</th>
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
                                    <td colspan="8" class="text-center py-4">Aucune ligne ajoutee.</td>
                                </tr>
                                <tr v-for="(ligne, index) in form.lignes" :key="ligne.planche_detail_id">
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
                                            @input="clearLineQuantityError(index)"
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
                                    <th colspan="4" class="text-right">Totaux</th>
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
                <button type="button" class="btn btn-outline-primary" @click="goToStep(1)">
                    Retour a la selection
                </button>
                <button type="button" class="btn btn-success ml-2" :disabled="submitting || !form.lignes.length" @click="submitForm">
                    <span v-if="submitting">Enregistrement...</span>
                    <span v-else>Enregistrer la facture</span>
                </button>
                <Link href="/admin/planche-bons-livraison" class="btn btn-secondary ml-2">
                    Annuler
                </Link>
            </div>
        </template>
        <!-- Modal ajout client -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un Client</h5>
                        <button type="button" class="close" @click="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div v-if="modalError" class="alert alert-danger">{{ modalError }}</div>
                        <form @submit.prevent="storeClient">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input v-model="newClient.name" type="text" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label>Adresse</label>
                                <input v-model="newClient.address" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Telephone</label>
                                <input v-model="newClient.phone" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input v-model="newClient.email" type="email" class="form-control" />
                            </div>
                            <button type="submit" class="btn btn-success" :disabled="modalSubmitting">
                                {{ modalSubmitting ? 'Ajout...' : 'Ajouter' }}
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" @click="closeModal">Annuler</button>
                        </form>
                    </div>
                </div>
            </div>
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
    { label: 'Factures planche', link: '/admin/planche-bons-livraison', icon: 'fa fa-truck' },
    { label: 'Nouvelle facture' },
];

const currentStep = ref(1);
const detailsLoading = ref(false);
const submitting = ref(false);
const hasSearched = ref(props.availableDetails.length > 0);
const formError = ref('');
const errors = ref({});
const availableDetailsState = ref(props.availableDetails);

const showModal = ref(false);
const modalSubmitting = ref(false);
const modalError = ref('');
const newClient = reactive({ name: '', address: '', phone: '', email: '' });

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

const filters = reactive({
    supplier_id: '',
    numero_contrat: '',
    code_couleur: '',
    categorie: '',
    epaisseur: '',
});

const form = reactive({
    client_id: '',
    date_livraison: new Date().toISOString().slice(0, 10),
    statut: 'valide',
    lignes: [],
});

const selectedCount = computed(() => form.lignes.length);

const stepOneRequirements = computed(() => {
    const missing = [];

    if (!form.client_id) {
        missing.push('selectionner un client');
    }

    if (!form.date_livraison) {
        missing.push('renseigner la date de livraison');
    }

    if (!form.lignes.length) {
        missing.push('ajouter au moins une ligne au panier');
    }

    return missing;
});

const canMoveToStepTwo = computed(() => (
    stepOneRequirements.value.length === 0
));

const totalQuantite = computed(() => (
    form.lignes.reduce((total, ligne) => total + Number(ligne.quantite_livree || 0), 0)
));

const totalMontant = computed(() => (
    form.lignes.reduce((total, ligne) => total + lineTotal(ligne), 0)
));

const totalQuantiteDisponibleSelectionnee = computed(() => (
    form.lignes.reduce((total, ligne) => total + Number(ligne.quantite_disponible || 0), 0)
));

function fetchAvailableDetails() {
    detailsLoading.value = true;
    hasSearched.value = true;

    axios.get('/admin/planche-bons-livraison/details-disponibles', {
        params: {
            supplier_id: filters.supplier_id || undefined,
            numero_contrat: filters.numero_contrat || undefined,
            code_couleur: filters.code_couleur || undefined,
            categorie: filters.categorie || undefined,
            epaisseur: filters.epaisseur || undefined,
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
    filters.supplier_id = '';
    filters.numero_contrat = '';
    filters.code_couleur = '';
    filters.categorie = '';
    filters.epaisseur = '';
    hasSearched.value = false;
    availableDetailsState.value = [];
}

function clearErrors(keys = []) {
    if (!keys.length) {
        return;
    }

    const nextErrors = { ...errors.value };
    keys.forEach((key) => {
        delete nextErrors[key];
    });
    errors.value = nextErrors;

    if (!Object.keys(errors.value).length) {
        formError.value = '';
    }
}

function validateStepOne() {
    const nextErrors = { ...errors.value };

    delete nextErrors.client_id;
    delete nextErrors.date_livraison;
    delete nextErrors.lignes;

    if (!form.client_id) {
        nextErrors.client_id = ['Veuillez selectionner un client.'];
    }

    if (!form.date_livraison) {
        nextErrors.date_livraison = ['Veuillez renseigner la date de livraison.'];
    }

    if (!form.lignes.length) {
        nextErrors.lignes = ['Ajoutez au moins une ligne au panier avant de continuer.'];
    }

    errors.value = nextErrors;

    if (nextErrors.client_id || nextErrors.date_livraison || nextErrors.lignes) {
        formError.value = 'Completez les informations obligatoires et ajoutez au moins une ligne avant de passer a l etape 2.';
        return false;
    }

    formError.value = '';
    return true;
}

function goToStep(step) {
    if (step === 1) {
        currentStep.value = 1;
        return;
    }

    if (validateStepOne()) {
        currentStep.value = 2;
    }
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
        quantite_livree: 1,
        prix_unitaire: 0,
        quantite_disponible: detail.quantite_disponible,
        supplier_name: detail.supplier_name,
        numero_contrat: detail.numero_contrat,
        code_couleur: detail.code_couleur,
        categorie: detail.categorie,
        epaisseur: detail.epaisseur,
    });

    clearErrors(['lignes']);
}

function clearLineQuantityError(index) {
    clearErrors([`lignes.${index}.quantite_livree`, 'lignes']);
}

function validateLineQuantities() {
    const nextErrors = { ...errors.value };

    Object.keys(nextErrors)
        .filter((key) => key === 'lignes' || key.match(/^lignes\.\d+\.quantite_livree$/))
        .forEach((key) => delete nextErrors[key]);

    form.lignes.forEach((ligne, index) => {
        const quantity = Number(ligne.quantite_livree);
        const available = Number(ligne.quantite_disponible || 0);

        if (!Number.isFinite(quantity) || quantity < 1) {
            nextErrors[`lignes.${index}.quantite_livree`] = ['La quantite livree doit etre superieure ou egale a 1.'];
            return;
        }

        if (quantity > available) {
            nextErrors[`lignes.${index}.quantite_livree`] = [`La quantite livree ne peut pas depasser le disponible (${available}).`];
        }
    });

    if (Object.keys(nextErrors).some((key) => key === 'lignes' || key.match(/^lignes\.\d+\.quantite_livree$/))) {
        nextErrors.lignes = ['Corrigez les quantites livrees qui depassent le disponible.'];
        errors.value = nextErrors;
        formError.value = 'Certaines quantites livrees depassent le disponible.';
        return false;
    }

    errors.value = nextErrors;
    return true;
}

function categorieLabel(cat) {
    const map = { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' };
    return map[cat] || cat || '-';
}

function removeLine(index) {
    form.lignes.splice(index, 1);

    if (!form.lignes.length && currentStep.value === 2) {
        currentStep.value = 1;
    }
}

function removeLineByDetailId(detailId) {
    const index = form.lignes.findIndex((ligne) => Number(ligne.planche_detail_id) === Number(detailId));

    if (index !== -1) {
        removeLine(index);
    }
}

function submitForm() {
    if (!validateStepOne()) {
        currentStep.value = 1;
        return;
    }

    if (!validateLineQuantities()) {
        currentStep.value = 2;
        return;
    }

    submitting.value = true;
    errors.value = {};
    formError.value = '';

    axios.post('/admin/planche-bons-livraison/store', {
        client_id: form.client_id ? Number(form.client_id) : null,
        date_livraison: form.date_livraison,
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

function closeModal() {
    showModal.value = false;
    modalError.value = '';
    newClient.name = '';
    newClient.address = '';
    newClient.phone = '';
    newClient.email = '';
}

function storeClient() {
    modalError.value = '';
    modalSubmitting.value = true;

    axios.post('/admin/clients/store', newClient)
        .then((response) => {
            const client = response.data.client;
            props.clients.push(client);
            form.client_id = String(client.id);
            closeModal();
        })
        .catch((error) => {
            modalError.value = error.response?.data?.message || 'Erreur lors de l ajout du client.';
        })
        .finally(() => {
            modalSubmitting.value = false;
        });
}
</script>
