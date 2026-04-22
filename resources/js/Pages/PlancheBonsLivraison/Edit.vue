<template>
    <Head :title="`Modifier facture ${bonLivraison.numero_bl} | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="`Modifier facture ${bonLivraison.numero_bl}`" :breadcrumbs="breadcrumbs">
            <template #action>
                <Link class="btn btn-primary" :href="`/admin/planche-bons-livraison/${bonLivraison.id}`">
                    <i class="fa fa-arrow-left"></i> Retour a la consultation
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="card mb-4">
            <div class="body">
                <div class="d-flex flex-wrap" style="gap: 10px;">
                    <button
                        type="button"
                        class="btn"
                        :class="currentStep === 1 ? 'btn-primary' : 'btn-outline-primary'"
                        @click="goToStep(1)"
                    >
                        1. Recherche et selection
                    </button>
                    <button
                        type="button"
                        class="btn"
                        :class="currentStep === 2 ? 'btn-primary' : 'btn-outline-primary'"
                        :disabled="!validLignes.length"
                        @click="goToStep(2)"
                    >
                        2. Prix et validation
                        <span v-if="validLignes.length > 0">({{ validLignes.length }})</span>
                    </button>
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

        <!-- Etape 1 : selection des articles ligne par ligne -->
        <template v-if="currentStep === 1">
            <div class="card mb-4">
                <div class="header">
                    <h2 class="mb-0">Etape 1 - Selection des articles</h2>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 36px;" class="text-center">#</th>
                                    <th style="min-width: 140px;">Contrat</th>
                                    <th style="min-width: 160px;">Code couleur</th>
                                    <th style="min-width: 150px;">Categorie</th>
                                    <th style="min-width: 130px;">Epaisseur</th>
                                    <th style="width: 110px;" class="text-center">Disponible</th>
                                    <th style="width: 120px;">Qte livree</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ligne, index) in lignes" :key="index">
                                    <td class="text-center align-middle text-muted small">{{ index + 1 }}</td>

                                    <td class="align-middle">
                                        <select
                                            v-model="ligne.contrat"
                                            class="form-control form-control-sm"
                                            @change="onLigneContratChange(index)"
                                        >
                                            <option value="">-- Contrat --</option>
                                            <option v-for="c in contrats" :key="c.id" :value="c.numero">
                                                {{ c.numero }}
                                            </option>
                                        </select>
                                    </td>

                                    <td class="align-middle">
                                        <input
                                            v-model="ligne.code_couleur"
                                            type="text"
                                            :list="`edit-couleurs-${index}`"
                                            class="form-control form-control-sm"
                                            placeholder="Code couleur..."
                                            :disabled="!ligne.contrat || !ligne.couleurOptions.length"
                                            @input="onCodeCouleurChange(index)"
                                        />
                                        <datalist :id="`edit-couleurs-${index}`">
                                            <option v-for="code in ligne.couleurOptions" :key="code" :value="code" />
                                        </datalist>
                                        <small v-if="ligne.contrat && !ligne.couleurOptions.length" class="text-warning">
                                            Aucune disponibilite
                                        </small>
                                    </td>

                                    <td class="align-middle">
                                        <select
                                            v-model="ligne.epaisseur"
                                            class="form-control form-control-sm"
                                            :disabled="!ligne.epaisseurOptions.length"
                                            @change="onEpaisseurChange(index)"
                                        >
                                            <option value="">-</option>
                                            <option
                                                v-for="ep in ligne.epaisseurOptions"
                                                :key="ep.detail_id"
                                                :value="String(ep.epaisseur)"
                                            >
                                                {{ formatDecimal(ep.epaisseur) }}
                                            </option>
                                        </select>
                                    </td>

                                    <td class="align-middle text-center">
                                        <span v-if="ligne.quantite_disponible !== null">
                                            <span
                                                class="badge"
                                                :class="ligne.quantite_disponible > 0 ? 'badge-success' : 'badge-danger'"
                                            >
                                                {{ ligne.quantite_disponible }}
                                            </span>
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>

                                    <td class="align-middle">
                                        <input
                                            v-model.number="ligne.quantite_livree"
                                            type="number"
                                            min="1"
                                            :max="ligne.quantite_disponible || undefined"
                                            class="form-control form-control-sm"
                                            :disabled="ligne.planche_detail_id === null"
                                        />
                                        <small v-if="ligne.planche_detail_id && ligne.quantite_disponible !== null && ligne.quantite_livree > ligne.quantite_disponible" class="text-danger">
                                            Max: {{ ligne.quantite_disponible }}
                                        </small>
                                    </td>

                                    <td class="align-middle text-center" style="white-space: nowrap;">
                                        <button
                                            type="button"
                                            class="btn btn-outline-success btn-sm mr-1"
                                            title="Ajouter une ligne"
                                            @click="addLigne"
                                        >
                                            <i class="fa fa-plus"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            title="Supprimer cette ligne"
                                            :disabled="lignes.length === 1 && !ligne.code_couleur"
                                            @click="removeLigne(index)"
                                        >
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex align-items-center flex-wrap" style="gap: 10px;">
                        <small v-if="errors.lignes" class="text-danger">{{ errors.lignes[0] }}</small>
                        <button
                            type="button"
                            class="btn btn-success"
                            :disabled="!canMoveToStepTwo"
                            :title="stepOneRequirements.length ? `Il manque: ${stepOneRequirements.join(', ')}` : ''"
                            @click="goToStep(2)"
                        >
                            Passer a l etape suivante
                            <span v-if="validLignes.length > 0">({{ validLignes.length }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Etape 2 : saisie des prix et validation -->
        <template v-else>
            <div class="card mb-4">
                <div class="header">
                    <h2 class="mb-0">Etape 2 - Prix et validation</h2>
                </div>
                <div class="body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap: 12px;">
                        <div class="d-flex flex-wrap" style="gap: 8px;">
                            <span class="badge badge-light border px-3 py-2">{{ validLignes.length }} ligne(s)</span>
                            <span class="badge badge-light border px-3 py-2">Quantite totale: {{ totalQuantite }}</span>
                            <span class="badge badge-light border px-3 py-2">Montant total: {{ formatCurrency(totalMontant) }}</span>
                        </div>
                        <div class="input-group" style="max-width: 320px;">
                            <input
                                v-model="globalPrice"
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control"
                                placeholder="Prix unitaire pour toutes les lignes"
                            />
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" @click="applyGlobalPrice">
                                    Appliquer a tous
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Contrat</th>
                                    <th>Code couleur</th>
                                    <th>Epaisseur</th>
                                    <th>Disponible</th>
                                    <th>Qte livree</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!validLignes.length">
                                    <td colspan="8" class="text-center py-4">Aucune ligne complete.</td>
                                </tr>
                                <tr v-for="(ligne, index) in validLignes" :key="`${ligne.planche_detail_id}-${index}`">
                                    <td class="align-middle">{{ ligne.contrat || '-' }}</td>
                                    <td class="align-middle">
                                        <span class="badge badge-info">{{ ligne.code_couleur || '-' }}</span>
                                    </td>
                                    <td class="align-middle">{{ formatDecimal(ligne.epaisseur) }}</td>
                                    <td class="align-middle text-center">{{ ligne.quantite_disponible }}</td>
                                    <td class="align-middle text-center">{{ ligne.quantite_livree }}</td>
                                    <td class="align-middle">
                                        <input
                                            v-model="ligne.prix_unitaire"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="form-control"
                                            @input="clearErrors([`lignes.${index}.prix_unitaire`, 'lignes'])"
                                        />
                                        <small v-if="errors[`lignes.${index}.prix_unitaire`]" class="text-danger">
                                            {{ errors[`lignes.${index}.prix_unitaire`][0] }}
                                        </small>
                                    </td>
                                    <td class="align-middle">{{ formatCurrency(lineTotal(ligne)) }}</td>
                                    <td class="align-middle">
                                        <button type="button" class="btn btn-outline-danger btn-sm" @click="removeLigneByDetail(ligne.planche_detail_id)">
                                            Retirer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="validLignes.length">
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
                <button type="button" class="btn btn-success ml-2" :disabled="submitting || !validLignes.length" @click="submitForm">
                    <span v-if="submitting">Mise a jour...</span>
                    <span v-else>Mettre a jour la facture</span>
                </button>
                <Link :href="`/admin/planche-bons-livraison/${bonLivraison.id}`" class="btn btn-secondary ml-2">
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
                        <div v-if="modalDuplicateWarning" class="alert alert-warning">
                            Un client similaire existe deja : <strong>{{ modalDuplicateWarning }}</strong>
                        </div>
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
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const props = defineProps({
    bonLivraison: { type: Object, required: true },
    suppliers: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    epaisseurs: { type: Array, default: () => [] },
    availableDetails: { type: Array, default: () => [] },
    contrats: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Factures planche', link: '/admin/planche-bons-livraison', icon: 'fa fa-truck' },
    { label: props.bonLivraison.numero_bl, link: `/admin/planche-bons-livraison/${props.bonLivraison.id}` },
    { label: 'Modifier' },
];

const currentStep = ref(1);
const globalPrice = ref('');
const submitting = ref(false);
const formError = ref('');
const errors = ref({});

const showModal = ref(false);
const modalSubmitting = ref(false);
const modalError = ref('');
const modalDuplicateWarning = ref('');
const newClient = reactive({ name: '', address: '', phone: '', email: '' });

function toSlug(str) {
    return str.toLowerCase().trim()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
}

watch(() => newClient.name, (name) => {
    if (!name || name.trim().length < 2) { modalDuplicateWarning.value = ''; return; }
    const match = props.clients.find((c) => c.slug === toSlug(name));
    modalDuplicateWarning.value = match ? match.name : '';
});

const form = reactive({
    client_id: props.bonLivraison.client_id ? String(props.bonLivraison.client_id) : '',
    date_livraison: props.bonLivraison.date_livraison || new Date().toISOString().slice(0, 10),
    statut: props.bonLivraison.statut || 'valide',
});

// --- Lignes (step 1) ---

function createEmptyLigne() {
    return {
        contrat: '',
        allDetails: [],
        couleurOptions: [],
        code_couleur: '',
        epaisseurOptions: [],
        epaisseur: '',
        planche_detail_id: null,
        quantite_disponible: null,
        quantite_livree: 1,
        prix_unitaire: 0,
    };
}

function detailsForContrat(numeroContrat) {
    return props.availableDetails.filter((d) => d.numero_contrat === numeroContrat);
}

function buildInitialLignes() {
    if (!props.bonLivraison.lignes?.length) {
        return [createEmptyLigne()];
    }

    return props.bonLivraison.lignes.map((ligne) => {
        const allDetails = detailsForContrat(ligne.numero_contrat);

        const seenC = new Set();
        const couleurOptions = allDetails
            .map((d) => d.code_couleur)
            .filter((c) => c && !seenC.has(c) && seenC.add(c));

        const seenEp = new Set();
        const epaisseurOptions = allDetails
            .filter((d) => d.code_couleur === ligne.code_couleur)
            .filter((d) => {
                const key = String(d.epaisseur);
                if (seenEp.has(key)) return false;
                seenEp.add(key);
                return true;
            })
            .map((d) => ({ detail_id: d.id, epaisseur: d.epaisseur, quantite_disponible: d.quantite_disponible }));

        const selectedOption = epaisseurOptions.find((o) => o.detail_id === ligne.detail_id);

        return {
            contrat: ligne.numero_contrat || '',
            allDetails,
            couleurOptions,
            code_couleur: ligne.code_couleur || '',
            epaisseurOptions,
            epaisseur: ligne.epaisseur != null ? String(ligne.epaisseur) : '',
            planche_detail_id: ligne.detail_id,
            quantite_disponible: selectedOption?.quantite_disponible ?? ligne.quantite_prevue ?? null,
            quantite_livree: Number(ligne.quantite_livree || 1),
            prix_unitaire: Number(ligne.prix_unitaire || 0),
        };
    });
}

const lignes = ref(buildInitialLignes());

const validLignes = computed(() =>
    lignes.value.filter((l) => l.planche_detail_id !== null)
);

const totalQuantite = computed(() =>
    validLignes.value.reduce((total, ligne) => total + Number(ligne.quantite_livree || 0), 0)
);

const totalMontant = computed(() =>
    validLignes.value.reduce((total, ligne) => total + lineTotal(ligne), 0)
);

// --- Cascading selection (client-side depuis availableDetails) ---

function onLigneContratChange(index) {
    const ligne = lignes.value[index];
    ligne.allDetails = [];
    ligne.couleurOptions = [];
    ligne.code_couleur = '';
    ligne.epaisseurOptions = [];
    ligne.epaisseur = '';
    ligne.planche_detail_id = null;
    ligne.quantite_disponible = null;

    if (!ligne.contrat) return;

    ligne.allDetails = detailsForContrat(ligne.contrat);

    const seen = new Set();
    ligne.couleurOptions = ligne.allDetails
        .map((d) => d.code_couleur)
        .filter((c) => c && !seen.has(c) && seen.add(c));

    if (ligne.couleurOptions.length === 1) {
        ligne.code_couleur = ligne.couleurOptions[0];
        onCodeCouleurChange(index);
    }
}

function onCodeCouleurChange(index) {
    const ligne = lignes.value[index];
    ligne.epaisseurOptions = [];
    ligne.epaisseur = '';
    ligne.planche_detail_id = null;
    ligne.quantite_disponible = null;

    if (!ligne.code_couleur) return;

    const isExactMatch = ligne.couleurOptions.includes(ligne.code_couleur);
    if (!isExactMatch) return;

    const usedDetailIds = new Set(
        lignes.value.filter((_, i) => i !== index).map((l) => l.planche_detail_id).filter(Boolean)
    );

    const seen = new Set();
    ligne.epaisseurOptions = ligne.allDetails
        .filter((d) => d.code_couleur === ligne.code_couleur && !usedDetailIds.has(d.id))
        .filter((d) => {
            const key = String(d.epaisseur);
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        })
        .map((d) => ({ detail_id: d.id, epaisseur: d.epaisseur, quantite_disponible: d.quantite_disponible }));

    if (ligne.epaisseurOptions.length === 1) {
        ligne.epaisseur = String(ligne.epaisseurOptions[0].epaisseur);
        onEpaisseurChange(index);
    }
}

function onEpaisseurChange(index) {
    const ligne = lignes.value[index];
    ligne.planche_detail_id = null;
    ligne.quantite_disponible = null;

    if (!ligne.epaisseur) return;

    const option = ligne.epaisseurOptions.find((ep) => String(ep.epaisseur) === String(ligne.epaisseur));
    if (option) {
        ligne.planche_detail_id = option.detail_id;
        ligne.quantite_disponible = option.quantite_disponible;
        if (!ligne.quantite_livree || ligne.quantite_livree < 1) {
            ligne.quantite_livree = 1;
        }
        if (ligne.quantite_livree > option.quantite_disponible) {
            ligne.quantite_livree = option.quantite_disponible;
        }
    }
}

function addLigne() {
    lignes.value.push(createEmptyLigne());
}

function removeLigne(index) {
    if (lignes.value.length === 1) {
        lignes.value[0] = createEmptyLigne();
        return;
    }
    lignes.value.splice(index, 1);
}

function removeLigneByDetail(detailId) {
    const index = lignes.value.findIndex((l) => l.planche_detail_id === detailId);
    if (index !== -1) {
        removeLigne(index);
        if (!validLignes.value.length) {
            currentStep.value = 1;
        }
    }
}

// --- Validation ---

const stepOneRequirements = computed(() => {
    const missing = [];
    if (!form.client_id) missing.push('selectionner un client');
    if (!form.date_livraison) missing.push('renseigner la date de livraison');
    if (!validLignes.value.length) missing.push('completer au moins une ligne');
    return missing;
});

const canMoveToStepTwo = computed(() => stepOneRequirements.value.length === 0);

function clearErrors(keys = []) {
    if (!keys.length) return;
    const nextErrors = { ...errors.value };
    keys.forEach((key) => delete nextErrors[key]);
    errors.value = nextErrors;
    if (!Object.keys(errors.value).length) formError.value = '';
}

function validateStepOne() {
    const nextErrors = { ...errors.value };
    delete nextErrors.client_id;
    delete nextErrors.date_livraison;
    delete nextErrors.lignes;

    if (!form.client_id) nextErrors.client_id = ['Veuillez selectionner un client.'];
    if (!form.date_livraison) nextErrors.date_livraison = ['Veuillez renseigner la date de livraison.'];
    if (!validLignes.value.length) nextErrors.lignes = ['Ajoutez au moins une ligne complete avant de continuer.'];

    errors.value = nextErrors;

    if (nextErrors.client_id || nextErrors.date_livraison || nextErrors.lignes) {
        formError.value = 'Completez les informations obligatoires et ajoutez au moins une ligne avant de passer a l etape 2.';
        return false;
    }

    formError.value = '';
    return true;
}

function validateLineQuantities() {
    const nextErrors = { ...errors.value };

    Object.keys(nextErrors)
        .filter((key) => key === 'lignes' || key.match(/^lignes\.\d+\.(quantite_livree|prix_unitaire)$/))
        .forEach((key) => delete nextErrors[key]);

    validLignes.value.forEach((ligne, index) => {
        const quantity = Number(ligne.quantite_livree);
        const available = Number(ligne.quantite_disponible || 0);
        const price = Number(ligne.prix_unitaire);

        if (!Number.isFinite(quantity) || quantity < 1) {
            nextErrors[`lignes.${index}.quantite_livree`] = ['La quantite livree doit etre superieure ou egale a 1.'];
        } else if (quantity > available) {
            nextErrors[`lignes.${index}.quantite_livree`] = [`La quantite livree ne peut pas depasser le disponible (${available}).`];
        }

        if (!Number.isFinite(price) || price <= 0) {
            nextErrors[`lignes.${index}.prix_unitaire`] = ['Le prix unitaire doit etre superieur a 0.'];
        }
    });

    const hasError = Object.keys(nextErrors).some((key) =>
        key === 'lignes' || key.match(/^lignes\.\d+\.(quantite_livree|prix_unitaire)$/)
    );

    if (hasError) {
        nextErrors.lignes = ['Corrigez les erreurs dans les lignes avant d enregistrer.'];
        errors.value = nextErrors;
        formError.value = 'Certaines lignes ont des erreurs. Verifiez les quantites et les prix.';
        return false;
    }

    errors.value = nextErrors;
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

// --- Submit ---

function applyGlobalPrice() {
    if (globalPrice.value === '' || globalPrice.value === null) return;
    lignes.value.forEach((ligne) => {
        ligne.prix_unitaire = globalPrice.value;
    });
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

    axios.put(`/admin/planche-bons-livraison/${props.bonLivraison.id}`, {
        client_id: form.client_id ? Number(form.client_id) : null,
        date_livraison: form.date_livraison,
        statut: form.statut,
        lignes: validLignes.value.map((ligne) => ({
            contrat: ligne.contrat,
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

// --- Helpers ---

function formatDecimal(value) {
    if (value === null || value === undefined || value === '') return '-';
    return Number(value).toFixed(2);
}

function lineTotal(ligne) {
    return Number(ligne.quantite_livree || 0) * Number(ligne.prix_unitaire || 0);
}

function formatCurrency(value) {
    const num = Math.round(Number(value || 0));
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' CFA';
}

// --- Modal client ---

function closeModal() {
    showModal.value = false;
    modalError.value = '';
    modalDuplicateWarning.value = '';
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
            const client = response.data;
            props.clients.push(client);
            form.client_id = String(client.id);
            closeModal();
        })
        .catch((error) => {
            modalError.value = error.response?.data?.errors?.name?.[0]
                || error.response?.data?.message
                || 'Erreur lors de l ajout du client.';
        })
        .finally(() => {
            modalSubmitting.value = false;
        });
}
</script>
