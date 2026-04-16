<template>
    <Head :title="`Contrat ${contrat.numero} | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="`Contrat ${contrat.numero}`" :breadcrumbs="breadcrumbs">
            <template #action>
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <button v-if="isAdmin" type="button" class="btn btn-outline-secondary" @click="openEditModal">
                        <i class="fa fa-pencil"></i> Modifier
                    </button>
                    <Link class="btn btn-outline-info" href="/admin/planche-bons-livraison/create">
                        <i class="fa fa-truck"></i> Nouvelle facture
                    </Link>
                    <button v-if="isAdmin" type="button" class="btn btn-primary" @click="openCreateModal">
                        <i class="fa fa-plus"></i> Ajouter des planches
                    </button>
                </div>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix row-deck">
            
            <div class="col-lg-3 col-md-6 col-sm-12"><div class="card bg-info"><div class="body"><div class="p-15 text-light"><h3>{{ contrat.total_quantite_prevue || 0 }}</h3><span>Feuilles prevues</span></div></div></div></div>
            <div class="col-lg-3 col-md-6 col-sm-12"><div class="card bg-success"><div class="body"><div class="p-15 text-light"><h3>{{ contrat.total_quantite_disponible || 0 }}</h3><span>Disponibles</span></div></div></div></div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="header"><h2>Resume du contrat</h2></div>
                    <div class="body">
                        <div class="row">
                            <div class="col-md-6 mb-3"><strong>Numero :</strong> {{ contrat.numero || '-' }}</div>
                            <div class="col-md-6 mb-3"><strong>Fournisseur :</strong> {{ contrat.supplier?.name || '-' }}</div>
                            <div class="col-md-6 mb-3"><strong>Feuilles prevues :</strong> {{ contrat.total_quantite_prevue || 0 }}</div>
                            <div class="col-md-6 mb-3"><strong>Feuilles livrees :</strong> {{ contrat.total_quantite_livree || 0 }}</div>
                            <div class="col-md-6 mb-3"><strong>Feuilles disponibles :</strong> {{ contrat.total_quantite_disponible || 0 }}</div>
                        </div>
                        <div class="border-top pt-3" v-if="contrat.supplier?.phone || contrat.supplier?.email || contrat.supplier?.address">
                            <h6 class="mb-3">Contact fournisseur</h6>
                            <div class="mb-2" v-if="contrat.supplier?.phone"><strong>Telephone :</strong> {{ contrat.supplier.phone }}</div>
                            <div class="mb-2" v-if="contrat.supplier?.email"><strong>Email :</strong> {{ contrat.supplier.email }}</div>
                            <div class="mb-0" v-if="contrat.supplier?.address"><strong>Adresse :</strong> {{ contrat.supplier.address }}</div>
                        </div>
                    </div>
                </div>
            </div>

        
        </div>

        <div class="card">
            <div class="header"><h2>Details du contrat</h2></div>
            <div class="body table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Code couleur</th><th>Categorie</th><th>Epaisseur</th><th>Prevues</th><th>Livrees</th><th>Disponibles</th><th>Prix de revient</th><th>Total vendu</th><th>Bénéfice total</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!contractDetails.length"><td colspan="10" class="text-center py-4">Aucun detail pour ce contrat.</td></tr>
                        <tr v-for="detail in contractDetails" :key="detail.id">
                            
                            <td>
                                <div class="d-flex align-items-center" style="gap:8px;">
                                    <div v-if="detail.image_url"  class="border rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;background:#fff;overflow:hidden;">
                                        <img :src="detail.image_url" alt="Apercu couleur" style="width:100%;height:100%;object-fit:cover;" />
                                        
                                    </div>
                                    <span class="badge badge-info">{{ detail.code_couleur || '-' }}</span>
                                </div>
                            </td>
                            <td><span class="badge" :class="categorieBadgeClass(detail.categorie)">{{ categorieLabel(detail.categorie) }}</span></td>
                            <td>{{ formatDecimal(detail.epaisseur) }}</td>
                            <td>{{ detail.quantite_prevue || 0 }}</td>
                            <td>{{ detail.total_quantite_livree || 0 }}</td>
                            <td class="font-weight-bold">{{ detail.quantite_disponible || 0 }}</td>
                            <td>{{ detail.prix_de_revient !== null && detail.prix_de_revient !== undefined ? formatCurrency(detail.prix_de_revient) : '-' }}</td>
                            <td>{{ detail.total_prix_total ? formatCurrency(detail.total_prix_total) : '-' }}</td>
                            <td :class="detail.profit_total !== null ? (detail.profit_total >= 0 ? 'text-success font-weight-bold' : 'text-danger font-weight-bold') : 'text-muted'">
                                {{ detail.profit_total !== null ? formatCurrency(detail.profit_total) : '-' }}
                            </td>
                            <td>
                                <div v-if="isAdmin" class="d-flex flex-wrap" style="gap:6px;">
                                    <button type="button" class="btn btn-warning btn-sm" @click="openDetailEditModal(detail)">Modifier</button>
                                    <button type="button" class="btn btn-danger btn-sm" @click="deleteDetail(detail)">Supprimer</button>
                                </div>
                                <span v-else class="text-muted">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row clearfix mt-4">
            <div class="col-12">
                <BenefitHistory :contrat-id="contrat.id" />
            </div>
        </div>
    </AuthenticatedLayout>

    <div v-if="showCreateModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter des planches au contrat {{ contrat.numero }}</h5>
                    <button type="button" class="close" @click="closeCreateModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="createFormError" class="alert alert-danger mb-3"><i class="fa fa-exclamation-circle mr-2"></i>{{ createFormError }}</div>
                    <div class="card mb-3">
                        <div class="body">
                            <div class="row">
                                <div class="col-md-6"><div class="form-group mb-0"><label>Fournisseur</label><input :value="contrat.supplier?.name || '-'" type="text" class="form-control" readonly /></div></div>
                                <div class="col-md-6"><div class="form-group mb-0"><label>Numero du contrat</label><input :value="contrat.numero || '-'" type="text" class="form-control" readonly /></div></div>
                            </div>
                        </div>
                    </div>
                    <small v-if="createErrors.groupes" class="text-danger d-block mb-2"><i class="fa fa-exclamation-triangle mr-1"></i>{{ createErrors.groupes[0] }}</small>
                    <div class="card mb-0">
                        <div class="body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:8px;">
                                <div>
                                    <h5 class="mb-1">Lignes de planche</h5>
                                    <small class="text-muted">Le bouton + ajoute une ligne vide pour recommencer depuis le debut.</small>
                                </div>
                                <span class="badge badge-light border">{{ createForm.rows.length }} ligne(s)</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover mb-0" style="background:#fff;">
                                    <thead style="background:#f0f4ff;">
                                        <tr>
                                            <th style="width:28%;">Code couleur</th><th style="width:15%;">Categorie</th><th style="width:13%;">Epaisseur</th><th style="width:13%;">Quantite prevue</th><th style="width:17%;">Prix de revient</th><th class="text-center" style="width:14%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, index) in createForm.rows" :key="row.localId">
                                            <td>
                                                <div class="d-flex align-items-center" style="gap:8px;">
                                                    <PlancheColorInput
                                                        :model-value="row.code_couleur"
                                                        input-class="form-control form-control-sm"
                                                        placeholder="Cliquez puis tapez pour filtrer..."
                                                        @update:modelValue="updateRowColor(row, $event)"
                                                        @select="selectColorSuggestion(row, $event)"
                                                    />
                                                    <div class="border rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;background:#fff;overflow:hidden;">
                                                        <img v-if="row.existing_image_url" :src="row.existing_image_url" alt="Apercu couleur" style="width:100%;height:100%;object-fit:cover;" />
                                                        <i v-else class="fa fa-image text-muted"></i>
                                                    </div>
                                                </div>
                                                <small v-if="createErrors[`rows.${index}.code_couleur`]" class="text-danger d-block mt-1">{{ createErrors[`rows.${index}.code_couleur`][0] }}</small>
                                            </td>
                                            <td>
                                                <select v-model="row.categorie" class="form-control form-control-sm">
                                                    <option value="">Selectionner...</option>
                                                    <option value="mate">Mate</option>
                                                    <option value="semi_brillant">Semi-brillant</option>
                                                    <option value="brillant">Brillant</option>
                                                </select>
                                                <small v-if="createErrors[`rows.${index}.categorie`]" class="text-danger d-block mt-1">{{ createErrors[`rows.${index}.categorie`][0] }}</small>
                                            </td>
                                            <td>
                                                <select v-model="row.epaisseur" class="form-control form-control-sm">
                                                    <option value="">Selectionner...</option>
                                                    <option v-for="epaisseurOption in epaisseurOptions" :key="epaisseurOption.id" :value="epaisseurOption.value">{{ epaisseurOption.label }}</option>
                                                </select>
                                                <small v-if="createErrors[`rows.${index}.epaisseur`]" class="text-danger d-block mt-1">{{ createErrors[`rows.${index}.epaisseur`][0] }}</small>
                                            </td>
                                            <td>
                                                <input v-model="row.quantite_prevue" type="number" min="1" step="1" class="form-control form-control-sm" placeholder="ex: 100" />
                                                <small v-if="createErrors[`rows.${index}.quantite_prevue`]" class="text-danger d-block mt-1">{{ createErrors[`rows.${index}.quantite_prevue`][0] }}</small>
                                            </td>
                                            <td>
                                                <input v-model="row.prix_de_revient" type="number" min="0" step="1" class="form-control form-control-sm" placeholder="Optionnel" />
                                                <small v-if="createErrors[`rows.${index}.prix_de_revient`]" class="text-danger d-block mt-1">{{ createErrors[`rows.${index}.prix_de_revient`][0] }}</small>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center" style="gap:6px;">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Ajouter une ligne vide" @click="addCreateRow(index)"><i class="fa fa-plus"></i></button>
                                                    <button type="button" class="btn btn-sm" :class="createForm.rows.length === 1 ? 'btn-light text-muted' : 'btn-outline-danger'" :disabled="createForm.rows.length === 1" title="Supprimer la ligne" @click="removeCreateRow(index)"><i class="fa fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm" :disabled="submittingCreate" @click="submitCreateForm">{{ submittingCreate ? 'Enregistrement...' : 'Enregistrer' }}</button>
                    <button type="button" class="btn btn-secondary btn-sm" @click="closeCreateModal">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showEditModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le contrat {{ contrat.numero }}</h5>
                    <button type="button" class="close" @click="closeEditModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="editFormError" class="alert alert-danger mb-3">
                        <i class="fa fa-exclamation-circle mr-2"></i>{{ editFormError }}
                    </div>
                    <div class="form-group">
                        <label>Fournisseur</label>
                        <select v-model="editForm.supplier_id" class="form-control">
                            <option value="">Selectionner un fournisseur</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">
                                {{ supplier.name }}
                            </option>
                        </select>
                        <small v-if="editErrors.supplier_id" class="text-danger d-block mt-1">{{ editErrors.supplier_id[0] }}</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Numero du contrat</label>
                        <input v-model="editForm.numero" type="text" class="form-control" placeholder="Ex: CT-001" />
                        <small v-if="editErrors.numero" class="text-danger d-block mt-1">{{ editErrors.numero[0] }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm" :disabled="submittingEdit" @click="submitEditForm">
                        {{ submittingEdit ? 'Mise a jour...' : 'Enregistrer' }}
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" @click="closeEditModal">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showDetailEditModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le detail</h5>
                    <button type="button" class="close" @click="closeDetailEditModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="detailEditFormError" class="alert alert-danger mb-3">
                        <i class="fa fa-exclamation-circle mr-2"></i>{{ detailEditFormError }}
                    </div>
                    <div class="form-group">
                        <label>Code couleur *</label>
                        <PlancheColorInput
                            :model-value="detailEditForm.code_couleur"
                            @update:modelValue="updateDetailFormColor($event)"
                            @select="selectDetailColorSuggestion"
                        />
                        <small v-if="detailEditErrors.code_couleur" class="text-danger d-block mt-1">{{ detailEditErrors.code_couleur[0] }}</small>
                    </div>
                    <div v-if="detailEditForm.existing_image_url" class="form-group text-center">
                        <img
                            :src="detailEditForm.existing_image_url"
                            alt="Apercu couleur"
                            class="img-fluid rounded border"
                            style="max-height:160px;object-fit:cover;"
                        />
                    </div>
                    <div class="form-group">
                        <label>Categorie *</label>
                        <select v-model="detailEditForm.categorie" class="form-control">
                            <option value="">Selectionner...</option>
                            <option value="mate">Mate</option>
                            <option value="semi_brillant">Semi-brillant</option>
                            <option value="brillant">Brillant</option>
                        </select>
                        <small v-if="detailEditErrors.categorie" class="text-danger d-block mt-1">{{ detailEditErrors.categorie[0] }}</small>
                    </div>
                    <div class="form-group">
                        <label>Epaisseur *</label>
                        <select v-model="detailEditForm.epaisseur" class="form-control">
                            <option value="">Selectionner...</option>
                            <option v-for="epaisseurOption in epaisseurOptions" :key="epaisseurOption.id" :value="epaisseurOption.value">{{ epaisseurOption.label }}</option>
                        </select>
                        <small v-if="detailEditErrors.epaisseur" class="text-danger d-block mt-1">{{ detailEditErrors.epaisseur[0] }}</small>
                    </div>
                    <div class="form-group">
                        <label>Quantite prevue *</label>
                        <input v-model="detailEditForm.quantite_prevue" type="number" min="1" step="1" class="form-control" />
                        <small v-if="detailEditErrors.quantite_prevue" class="text-danger d-block mt-1">{{ detailEditErrors.quantite_prevue[0] }}</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Prix de revient <small class="text-muted">(optionnel)</small></label>
                        <input v-model="detailEditForm.prix_de_revient" type="number" min="0" step="1" class="form-control" placeholder="Ex: 5000" />
                        <small v-if="detailEditErrors.prix_de_revient" class="text-danger d-block mt-1">{{ detailEditErrors.prix_de_revient[0] }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm" :disabled="submittingDetailEdit" @click="submitDetailEditForm">
                        {{ submittingDetailEdit ? 'Mise a jour...' : 'Enregistrer' }}
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" @click="closeDetailEditModal">Annuler</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import PlancheColorInput from '@/Components/PlancheColorInput.vue';
import BenefitHistory from '@/Pages/Contrats/BenefitHistory.vue';

const props = defineProps({
    contrat: { type: Object, required: true },
    epaisseurs: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    userRole: { type: String, default: 'user' },
});

const isAdmin = computed(() => props.userRole === 'admin');

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Gestion des contrats', link: '/admin/contrats', icon: 'fa fa-database' },
    { label: `Contrat ${props.contrat.numero}` },
];

const showEditModal = ref(false);
const submittingEdit = ref(false);
const editFormError = ref('');
const editErrors = ref({});
const showCreateModal = ref(false);
const submittingCreate = ref(false);
const createFormError = ref('');
const createErrors = ref({});
const showDetailEditModal = ref(false);
const submittingDetailEdit = ref(false);
const detailEditFormError = ref('');
const detailEditErrors = ref({});
const selectedDetail = ref(null);
const epaisseurOptions = computed(() => props.epaisseurs.map((item) => {
    const value = extractEpaisseurValue(item);
    return value ? { id: item.id, label: item.intitule, value } : null;
}).filter(Boolean));
const createForm = ref(buildInitialCreateForm());
const editForm = ref(buildInitialEditForm());
const detailEditForm = ref(buildInitialDetailEditForm());
const contractDetails = computed(() => (props.contrat.planches || [])
    .flatMap((planche) => (planche.details || []).map((detail) => ({
        id: detail.id,
        planche_id: planche.id,
        code_couleur: planche.code_couleur || detail.couleur?.code || '',
        code_couleur_from_selection: planche.code_couleur || detail.couleur?.code || '',
        existing_image_url: detail.couleur?.image_url || '',
        image_url: detail.couleur?.image_url || '',
        categorie: detail.categorie,
        epaisseur: normalizeEpaisseurValue(detail.epaisseur),
        quantite_prevue: detail.quantite_prevue,
        total_quantite_livree: detail.total_quantite_livree || 0,
        quantite_disponible: detail.quantite_disponible || 0,
        prix_de_revient: detail.prix_de_revient ?? null,
        total_prix_total: detail.total_prix_total ?? 0,
        cout_total: detail.cout_total ?? null,
        profit_total: detail.profit_total ?? null,
    })))
    .sort((a, b) => {
        const keyA = `${a.code_couleur || ''}|${a.categorie || ''}`;
        const keyB = `${b.code_couleur || ''}|${b.categorie || ''}`;
        if (keyA === keyB) return Number(a.epaisseur || 0) - Number(b.epaisseur || 0);
        return keyA.localeCompare(keyB);
    }));

function buildInitialCreateForm() {
    return {
        supplier_id: props.contrat.supplier_id || props.contrat.supplier?.id || '',
        numero_contrat: props.contrat.numero || '',
        rows: [createRow()],
    };
}

function buildInitialEditForm() {
    return {
        supplier_id: String(props.contrat.supplier_id || props.contrat.supplier?.id || ''),
        numero: props.contrat.numero || '',
    };
}

function buildInitialDetailEditForm() {
    return {
        code_couleur: '',
        code_couleur_from_selection: '',
        existing_image_url: '',
        categorie: '',
        epaisseur: '',
        quantite_prevue: '',
        prix_de_revient: '',
    };
}

function createRow(defaults = {}) {
    return {
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        code_couleur: defaults.code_couleur || '',
        code_couleur_from_selection: defaults.code_couleur_from_selection || '',
        existing_image_url: defaults.existing_image_url || '',
        categorie: defaults.categorie || '',
        epaisseur: normalizeEpaisseurValue(defaults.epaisseur),
        quantite_prevue: defaults.quantite_prevue || '',
        prix_de_revient: defaults.prix_de_revient || '',
    };
}

function resetCreateForm() {
    createForm.value = buildInitialCreateForm();
    createErrors.value = {};
    createFormError.value = '';
}

function resetEditForm() {
    editForm.value = buildInitialEditForm();
    editErrors.value = {};
    editFormError.value = '';
}

function resetDetailEditForm() {
    detailEditForm.value = buildInitialDetailEditForm();
    detailEditErrors.value = {};
    detailEditFormError.value = '';
    selectedDetail.value = null;
}

function openEditModal() { resetEditForm(); showEditModal.value = true; }
function closeEditModal() { showEditModal.value = false; resetEditForm(); }
function openCreateModal() { resetCreateForm(); showCreateModal.value = true; }
function closeCreateModal() { showCreateModal.value = false; resetCreateForm(); }
function addCreateRow(index) { createForm.value.rows.splice(index + 1, 0, createRow()); }
function removeCreateRow(index) { if (createForm.value.rows.length > 1) createForm.value.rows.splice(index, 1); }
function normalizeCode(value) { return String(value || '').trim().toLowerCase(); }
function extractEpaisseurValue(item) {
    for (const source of [item?.slug, item?.intitule]) {
        const match = String(source || '').replace(',', '.').match(/(\d+(?:\.\d+)?)/);
        if (match) return normalizeEpaisseurValue(match[1]);
    }
    return '';
}
function normalizeEpaisseurValue(value) {
    const normalized = String(value ?? '').trim().replace(',', '.');
    if (!normalized) return '';
    const numeric = Number(normalized);
    return Number.isFinite(numeric) ? String(numeric) : normalized;
}
function updateRowColor(row, value) {
    row.code_couleur = value;
    if (normalizeCode(value) !== normalizeCode(row.code_couleur_from_selection)) row.existing_image_url = '';
}
function selectColorSuggestion(row, suggestion) {
    row.code_couleur = suggestion?.code || row.code_couleur;
    row.code_couleur_from_selection = suggestion?.code || '';
    row.existing_image_url = suggestion?.image_url || '';
}
function updateDetailFormColor(value) {
    detailEditForm.value.code_couleur = value;
    if (normalizeCode(value) !== normalizeCode(detailEditForm.value.code_couleur_from_selection)) detailEditForm.value.existing_image_url = '';
}
function selectDetailColorSuggestion(suggestion) {
    detailEditForm.value.code_couleur = suggestion?.code || detailEditForm.value.code_couleur;
    detailEditForm.value.code_couleur_from_selection = suggestion?.code || '';
    detailEditForm.value.existing_image_url = suggestion?.image_url || '';
}
function buildPayload() {
    const groupes = [];
    const keyToGroupIndex = new Map();
    const rowMap = [];
    createForm.value.rows.forEach((row) => {
        const key = `${normalizeCode(row.code_couleur)}|${row.categorie || ''}`;
        let groupIndex = keyToGroupIndex.get(key);
        if (groupIndex === undefined) {
            groupIndex = groupes.length;
            keyToGroupIndex.set(key, groupIndex);
            groupes.push({ code_couleur: row.code_couleur, categorie: row.categorie, epaisseurs: [] });
        }
        const epaisseurIndex = groupes[groupIndex].epaisseurs.length;
        groupes[groupIndex].epaisseurs.push({ epaisseur: row.epaisseur, quantite_prevue: row.quantite_prevue, prix_de_revient: row.prix_de_revient !== '' ? row.prix_de_revient : null });
        rowMap.push({ groupIndex, epaisseurIndex });
    });
    return { payload: { supplier_id: createForm.value.supplier_id, numero_contrat: createForm.value.numero_contrat, groupes }, rowMap };
}
function mapServerErrors(serverErrors, rowMap) {
    const mapped = {};
    const groupRows = {};
    rowMap.forEach((item, rowIndex) => {
        if (!groupRows[item.groupIndex]) groupRows[item.groupIndex] = [];
        groupRows[item.groupIndex].push(rowIndex);
    });
    Object.entries(serverErrors || {}).forEach(([path, messages]) => {
        if (path === 'groupes') return void (mapped[path] = messages);
        let match = path.match(/^groupes\.(\d+)\.(code_couleur|categorie)$/);
        if (match) {
            const groupIndex = Number(match[1]);
            const field = match[2];
            (groupRows[groupIndex] || []).forEach((rowIndex) => { mapped[`rows.${rowIndex}.${field}`] = messages; });
            return;
        }
        match = path.match(/^groupes\.(\d+)\.epaisseurs\.(\d+)\.(epaisseur|quantite_prevue)$/);
        if (match) {
            const groupIndex = Number(match[1]);
            const epaisseurIndex = Number(match[2]);
            const field = match[3];
            const rowIndex = rowMap.findIndex((item) => item.groupIndex === groupIndex && item.epaisseurIndex === epaisseurIndex);
            if (rowIndex !== -1) return void (mapped[`rows.${rowIndex}.${field}`] = messages);
        }
        mapped[path] = messages;
    });
    return mapped;
}
function submitCreateForm() {
    submittingCreate.value = true;
    createErrors.value = {};
    createFormError.value = '';
    const { payload, rowMap } = buildPayload();
    axios.post('/admin/planches/store', payload)
        .then((response) => {
            closeCreateModal();
            Inertia.visit(response?.data?.data?.redirect_to || `/admin/contrats/${props.contrat.id}`);
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                createErrors.value = mapServerErrors(error.response.data.errors || {}, rowMap);
                createFormError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
                return;
            }
            const message = error.response?.data?.message || 'Une erreur est survenue pendant l enregistrement.';
            const detail = error.response?.data?.detail;
            createFormError.value = detail ? `${message} ${detail}` : message;
        })
        .finally(() => { submittingCreate.value = false; });
}
function submitEditForm() {
    submittingEdit.value = true;
    editErrors.value = {};
    editFormError.value = '';

    axios.put(`/admin/contrats/${props.contrat.id}`, {
        supplier_id: editForm.value.supplier_id,
        numero: editForm.value.numero,
    })
        .then((response) => {
            closeEditModal();
            Inertia.visit(response?.data?.data?.redirect_to || `/admin/contrats/${props.contrat.id}`);
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                editErrors.value = error.response.data.errors || {};
                editFormError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
                return;
            }

            editFormError.value = error.response?.data?.message || 'Une erreur est survenue pendant la mise a jour.';
        })
        .finally(() => { submittingEdit.value = false; });
}
function openDetailEditModal(detail) {
    selectedDetail.value = detail;
    detailEditErrors.value = {};
    detailEditFormError.value = '';
    detailEditForm.value = {
        code_couleur: detail.code_couleur || '',
        code_couleur_from_selection: detail.code_couleur || '',
        existing_image_url: detail.image_url || '',
        categorie: detail.categorie || '',
        epaisseur: normalizeEpaisseurValue(detail.epaisseur),
        quantite_prevue: detail.quantite_prevue || '',
        prix_de_revient: detail.prix_de_revient !== null && detail.prix_de_revient !== undefined ? detail.prix_de_revient : '',
    };
    showDetailEditModal.value = true;
}
function closeDetailEditModal() {
    showDetailEditModal.value = false;
    resetDetailEditForm();
}
function reloadContratPage() {
    Inertia.visit(`/admin/contrats/${props.contrat.id}`, { preserveScroll: true });
}
function submitDetailEditForm() {
    if (!selectedDetail.value) return;
    submittingDetailEdit.value = true;
    detailEditErrors.value = {};
    detailEditFormError.value = '';

    axios.put(`/admin/planches/${selectedDetail.value.planche_id}/lignes/${selectedDetail.value.id}`, {
        code_couleur: detailEditForm.value.code_couleur,
        categorie: detailEditForm.value.categorie,
        epaisseur: detailEditForm.value.epaisseur,
        quantite_prevue: detailEditForm.value.quantite_prevue,
        prix_de_revient: detailEditForm.value.prix_de_revient !== '' ? detailEditForm.value.prix_de_revient : null,
    })
        .then(() => {
            closeDetailEditModal();
            reloadContratPage();
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                detailEditErrors.value = error.response.data.errors || {};
                detailEditFormError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
                return;
            }

            detailEditFormError.value = error.response?.data?.message || 'Une erreur est survenue pendant la mise a jour.';
        })
        .finally(() => { submittingDetailEdit.value = false; });
}
function deleteDetail(detail) {
    Swal.fire({
        title: 'Etes-vous sur ?',
        text: 'Cette ligne sera supprimee.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (!result.isConfirmed) return;

        axios.delete(`/admin/planches/${detail.planche_id}/lignes/${detail.id}`)
            .then(() => { reloadContratPage(); })
            .catch((error) => {
                Swal.fire(
                    'Erreur',
                    error.response?.data?.message || 'Impossible de supprimer cette ligne.',
                    'error',
                );
            });
    });
}
function categorieLabel(cat) { return { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' }[cat] || cat || '-'; }
function categorieBadgeClass(cat) { return { mate: 'badge-secondary', semi_brillant: 'badge-warning', brillant: 'badge-success' }[cat] || 'badge-light'; }
function formatDecimal(value) { return value === null || value === undefined || value === '' ? '-' : Number(value).toFixed(2); }
function formatCurrency(value) { const num = Math.round(Number(value || 0)); return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' CFA'; }
</script>
