<template>
    <Head :title="`Ajouter des planches | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Ajouter des planches'"
            :breadcrumbs="breadcrumbs"
        >
            <template #action>
                <Link class="btn btn-primary" href="/admin/contrats">
                    <i class="fa fa-arrow-left"></i> Retour a la liste
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="card mb-3">
            <div class="body">
                <div v-if="formError" class="alert alert-danger mb-3">
                    <i class="fa fa-exclamation-circle mr-2"></i>{{ formError }}
                </div>

                <form @submit.prevent="submitForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>
                                    <i class="fa fa-building-o mr-1 text-muted"></i> Fournisseur *
                                    <button type="button" class="btn btn-success btn-sm ms-2" @click="showModal = true">
                                        <i class="fa fa-plus"></i> Ajouter fournisseur
                                    </button>
                                </label>
                                <select v-model="form.supplier_id" class="form-control">
                                    <option value="">Selectionner un fournisseur</option>
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                        {{ supplier.name }}
                                    </option>
                                </select>
                                <small v-if="errors.supplier_id" class="text-danger">{{ errors.supplier_id[0] }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label><i class="fa fa-file-text-o mr-1 text-muted"></i> Numero du contrat *</label>
                                <input v-model="form.numero_contrat" type="text" class="form-control" placeholder="Ex: C-2026-001" />
                                <small v-if="errors.numero_contrat" class="text-danger">{{ errors.numero_contrat[0] }}</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <small v-if="errors.groupes" class="text-danger d-block mb-2">
            <i class="fa fa-exclamation-triangle mr-1"></i>{{ errors.groupes[0] }}
        </small>

        <div class="card mb-3">
            <div class="body">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:8px;">
                    <div>
                        <h5 class="mb-1">Lignes de planche</h5>
                        <small class="text-muted">
                            Couleur, categorie, epaisseur et quantite prevue sont saisis sur la meme ligne. Le bouton + reprend la ligne courante.
                        </small>
                    </div>
                    <span class="badge badge-light border">{{ form.rows.length }} ligne(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0" style="background:#fff;">
                        <thead style="background:#f0f4ff;">
                            <tr>
                                <th style="width:30%;">Code couleur</th>
                                <th style="width:15%;">Categorie</th>
                                <th style="width:13%;">Epaisseur</th>
                                <th style="width:13%;">Quantite prevue</th>
                                <th style="width:15%;">Prix de revient</th>
                                <th class="text-center" style="width:14%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in form.rows" :key="row.localId">
                                <td>
                                    <div class="d-flex align-items-center" style="gap:8px;">
                                        <PlancheColorInput
                                            :model-value="row.code_couleur"
                                            input-class="form-control form-control-sm"
                                            placeholder="Cliquez puis tapez pour filtrer..."
                                            @update:modelValue="updateRowColor(row, $event)"
                                            @select="selectColorSuggestion(row, $event)"
                                        />
                                        <div
                                            class="border rounded d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width:42px;height:42px;background:#fff;overflow:hidden;"
                                        >
                                            <img
                                                v-if="row.existing_image_url"
                                                :src="row.existing_image_url"
                                                alt="Apercu couleur"
                                                style="width:100%;height:100%;object-fit:cover;"
                                            />
                                            <i v-else class="fa fa-image text-muted"></i>
                                        </div>
                                    </div>
                                    <small v-if="errors[`rows.${index}.code_couleur`]" class="text-danger d-block mt-1">
                                        {{ errors[`rows.${index}.code_couleur`][0] }}
                                    </small>
                                </td>
                                <td>
                                    <select v-model="row.categorie" class="form-control form-control-sm">
                                        <option value="">Selectionner...</option>
                                        <option value="mate">Mate</option>
                                        <option value="semi_brillant">Semi-brillant</option>
                                        <option value="brillant">Brillant</option>
                                    </select>
                                    <small v-if="errors[`rows.${index}.categorie`]" class="text-danger d-block mt-1">
                                        {{ errors[`rows.${index}.categorie`][0] }}
                                    </small>
                                </td>
                                <td>
                                    <select v-model="row.epaisseur" class="form-control form-control-sm">
                                        <option value="">Selectionner...</option>
                                        <option
                                            v-for="epaisseurOption in epaisseurOptions"
                                            :key="epaisseurOption.id"
                                            :value="epaisseurOption.value"
                                        >
                                            {{ epaisseurOption.label }}
                                        </option>
                                    </select>
                                    <small v-if="errors[`rows.${index}.epaisseur`]" class="text-danger d-block mt-1">
                                        {{ errors[`rows.${index}.epaisseur`][0] }}
                                    </small>
                                </td>
                                <td>
                                    <input
                                        v-model="row.quantite_prevue"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="form-control form-control-sm"
                                        placeholder="ex: 100"
                                    />
                                    <small v-if="errors[`rows.${index}.quantite_prevue`]" class="text-danger d-block mt-1">
                                        {{ errors[`rows.${index}.quantite_prevue`][0] }}
                                    </small>
                                </td>
                                <td>
                                    <input
                                        v-model="row.prix_de_revient"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="form-control form-control-sm"
                                        placeholder="Optionnel"
                                    />
                                    <small v-if="errors[`rows.${index}.prix_de_revient`]" class="text-danger d-block mt-1">
                                        {{ errors[`rows.${index}.prix_de_revient`][0] }}
                                    </small>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-center" style="gap:6px;">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Dupliquer la ligne"
                                            @click="addRow(index)"
                                        >
                                            <i class="fa fa-plus"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm"
                                            :class="form.rows.length === 1 ? 'btn-light text-muted' : 'btn-outline-danger'"
                                            :disabled="form.rows.length === 1"
                                            title="Supprimer la ligne"
                                            @click="removeRow(index)"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="body d-flex justify-content-end align-items-center flex-wrap" style="gap:8px;">
                <div>
                    <button type="button" class="btn btn-success" :disabled="submitting" @click="submitForm">
                        <i class="fa fa-save mr-1"></i>
                        {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                    <Link href="/admin/contrats" class="btn btn-secondary ml-2">
                        Annuler
                    </Link>
                </div>
            </div>
        </div>
        <!-- Modal ajout fournisseur -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un fournisseur</h5>
                        <button type="button" class="close" @click="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div v-if="modalError" class="alert alert-danger">{{ modalError }}</div>
                        <form @submit.prevent="storeSupplier">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input v-model="newSupplier.name" type="text" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label>Adresse</label>
                                <input v-model="newSupplier.address" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Telephone</label>
                                <input v-model="newSupplier.phone" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input v-model="newSupplier.email" type="email" class="form-control" />
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
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { computed, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import PlancheColorInput from '@/Components/PlancheColorInput.vue';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    epaisseurs: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Gestion des contrats', link: '/admin/contrats', icon: 'fa fa-database' },
    { label: 'Ajouter des planches' },
];

const submitting = ref(false);
const formError = ref('');
const errors = ref({});

const showModal = ref(false);
const modalSubmitting = ref(false);
const modalError = ref('');
const newSupplier = reactive({ name: '', address: '', phone: '', email: '' });

function closeModal() {
    showModal.value = false;
    modalError.value = '';
    newSupplier.name = '';
    newSupplier.address = '';
    newSupplier.phone = '';
    newSupplier.email = '';
}

function storeSupplier() {
    modalError.value = '';
    modalSubmitting.value = true;

    axios.post('/admin/suppliers/store', newSupplier)
        .then((response) => {
            const supplier = response.data.supplier;
            props.suppliers.push(supplier);
            form.value.supplier_id = supplier.id;
            closeModal();
        })
        .catch((error) => {
            modalError.value = error.response?.data?.message || 'Erreur lors de l ajout du fournisseur.';
        })
        .finally(() => {
            modalSubmitting.value = false;
        });
}
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

function createRow(defaults = {}) {
    return {
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        code_couleur: defaults.code_couleur || '',
        code_couleur_from_selection: defaults.code_couleur_from_selection || '',
        existing_image_url: defaults.existing_image_url || '',
        categorie: defaults.categorie || '',
        epaisseur: defaults.epaisseur || '',
        quantite_prevue: defaults.quantite_prevue || '',
        prix_de_revient: defaults.prix_de_revient || '',
    };
}

const form = ref({
    supplier_id: '',
    numero_contrat: '',
    rows: [createRow()],
});

function addRow(index) {
    const source = form.value.rows[index];

    form.value.rows.splice(index + 1, 0, createRow({
        code_couleur: source?.code_couleur || '',
        code_couleur_from_selection: source?.code_couleur_from_selection || '',
        existing_image_url: source?.existing_image_url || '',
        categorie: source?.categorie || '',
        epaisseur: source?.epaisseur || '',
        quantite_prevue: source?.quantite_prevue || '',
        prix_de_revient: source?.prix_de_revient || '',
    }));
}

function removeRow(index) {
    if (form.value.rows.length === 1) {
        return;
    }

    form.value.rows.splice(index, 1);
}

function normalizeCode(value) {
    return String(value || '').trim().toLowerCase();
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

function updateRowColor(row, value) {
    row.code_couleur = value;

    if (normalizeCode(value) !== normalizeCode(row.code_couleur_from_selection)) {
        row.existing_image_url = '';
    }
}

function selectColorSuggestion(row, suggestion) {
    row.code_couleur = suggestion?.code || row.code_couleur;
    row.code_couleur_from_selection = suggestion?.code || '';
    row.existing_image_url = suggestion?.image_url || '';
}

function buildPayload() {
    const groupes = [];
    const keyToGroupIndex = new Map();
    const rowMap = [];

    form.value.rows.forEach((row) => {
        const key = `${normalizeCode(row.code_couleur)}|${row.categorie || ''}`;
        let groupIndex = keyToGroupIndex.get(key);

        if (groupIndex === undefined) {
            groupIndex = groupes.length;
            keyToGroupIndex.set(key, groupIndex);
            groupes.push({
                code_couleur: row.code_couleur,
                categorie: row.categorie,
                epaisseurs: [],
            });
        }

        const epaisseurIndex = groupes[groupIndex].epaisseurs.length;
        groupes[groupIndex].epaisseurs.push({
            epaisseur: row.epaisseur,
            quantite_prevue: row.quantite_prevue,
            prix_de_revient: row.prix_de_revient !== '' ? row.prix_de_revient : null,
        });

        rowMap.push({ groupIndex, epaisseurIndex });
    });

    return {
        payload: {
            supplier_id: form.value.supplier_id,
            numero_contrat: form.value.numero_contrat,
            groupes,
        },
        rowMap,
    };
}

function mapServerErrors(serverErrors, rowMap) {
    const mapped = {};
    const groupRows = {};

    rowMap.forEach((item, rowIndex) => {
        if (!groupRows[item.groupIndex]) {
            groupRows[item.groupIndex] = [];
        }

        groupRows[item.groupIndex].push(rowIndex);
    });

    Object.entries(serverErrors || {}).forEach(([path, messages]) => {
        if (path === 'groupes') {
            mapped[path] = messages;
            return;
        }

        let match = path.match(/^groupes\.(\d+)\.(code_couleur|categorie)$/);
        if (match) {
            const groupIndex = Number(match[1]);
            const field = match[2];

            (groupRows[groupIndex] || []).forEach((rowIndex) => {
                mapped[`rows.${rowIndex}.${field}`] = messages;
            });

            return;
        }

        match = path.match(/^groupes\.(\d+)\.epaisseurs\.(\d+)\.(epaisseur|quantite_prevue)$/);
        if (match) {
            const groupIndex = Number(match[1]);
            const epaisseurIndex = Number(match[2]);
            const field = match[3];
            const rowIndex = rowMap.findIndex((item) => item.groupIndex === groupIndex && item.epaisseurIndex === epaisseurIndex);

            if (rowIndex !== -1) {
                mapped[`rows.${rowIndex}.${field}`] = messages;
                return;
            }
        }

        mapped[path] = messages;
    });

    return mapped;
}

function submitForm() {
    submitting.value = true;
    errors.value = {};
    formError.value = '';

    const { payload, rowMap } = buildPayload();

    axios.post('/admin/planches/store', payload)
        .then((response) => {
            const redirectTo = response?.data?.data?.redirect_to;
            Inertia.visit(redirectTo || '/admin/contrats');
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                errors.value = mapServerErrors(error.response.data.errors || {}, rowMap);
                formError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
                return;
            }

            const serverMessage = error.response?.data?.message || 'Une erreur est survenue pendant l enregistrement.';
            const serverDetail = error.response?.data?.detail;
            formError.value = serverDetail ? `${serverMessage} ${serverDetail}` : serverMessage;
        })
        .finally(() => { submitting.value = false; });
}
</script>
