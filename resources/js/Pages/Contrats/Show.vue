<template>
    <Head :title="`Contrat ${contrat.numero} | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="`Contrat ${contrat.numero}`" :breadcrumbs="breadcrumbs">
            <template #action>
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <Link class="btn btn-outline-info" href="/admin/planche-bons-livraison/create">
                        <i class="fa fa-truck"></i> Nouvelle facture
                    </Link>
                    <button type="button" class="btn btn-primary" @click="openCreateModal">
                        <i class="fa fa-plus"></i> Ajouter des planches
                    </button>
                </div>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix row-deck">
            <div class="col-lg-3 col-md-6 col-sm-12"><div class="card primary-bg"><div class="body"><div class="p-15 text-light"><h3>{{ contrat.total_planches || 0 }}</h3><span>Planches</span></div></div></div></div>
            <div class="col-lg-3 col-md-6 col-sm-12"><div class="card secondary-bg"><div class="body"><div class="p-15 text-light"><h3>{{ contrat.total_details || 0 }}</h3><span>Lignes</span></div></div></div></div>
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
                            <div class="col-md-6 mb-3"><strong>Planches :</strong> {{ contrat.total_planches || 0 }}</div>
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

            <div class="col-lg-6 col-md-12" v-if="activePlanche">
                <div class="card">
                    <div class="header"><h2>Planche selectionnee</h2></div>
                    <div class="body">
                        <div class="mb-3"><strong>ID planche :</strong> #{{ activePlanche.id }}</div>
                        <div class="mb-3"><strong>Code couleur :</strong> <span class="badge badge-info ml-2">{{ activePlanche.code_couleur || '-' }}</span></div>
                        <div class="mb-3">
                            <strong>Categorie :</strong>
                            <span class="badge ml-2" :class="categorieBadgeClass(activePlanche.categorie)">{{ categorieLabel(activePlanche.categorie) }}</span>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3"><div class="border rounded p-3 text-center"><div class="font-weight-bold" style="font-size: 18px;">{{ activePlanche.details?.length || 0 }}</div><small class="text-muted">Lignes</small></div></div>
                            <div class="col-md-4 mb-3"><div class="border rounded p-3 text-center"><div class="font-weight-bold" style="font-size: 18px;">{{ activePlanche.total_quantite_prevue || 0 }}</div><small class="text-muted">Prevues</small></div></div>
                            <div class="col-md-4 mb-3"><div class="border rounded p-3 text-center"><div class="font-weight-bold" style="font-size: 18px;">{{ activePlanche.total_quantite_disponible || 0 }}</div><small class="text-muted">Disponibles</small></div></div>
                        </div>
                        <div class="mt-2"><Link :href="`/admin/planches/${activePlanche.id}`" class="btn btn-info btn-sm">Ouvrir la page planche</Link></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="header"><h2>Planches du contrat</h2></div>
            <div class="body table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Planche</th><th>Code couleur</th><th>Categorie</th><th>Prevues</th><th>Livrees</th><th>Disponibles</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!contrat.planches?.length"><td colspan="7" class="text-center py-4">Aucune planche pour ce contrat.</td></tr>
                        <tr v-for="planche in contrat.planches" :key="planche.id" :class="{ 'table-primary': planche.id === activePlanche?.id }">
                            <td><span v-if="planche.id === activePlanche?.id" class="badge badge-primary mr-2">Selectionnee</span>#{{ planche.id }}</td>
                            <td><span class="badge badge-info">{{ planche.code_couleur || '-' }}</span></td>
                            <td><span class="badge" :class="categorieBadgeClass(planche.categorie)">{{ categorieLabel(planche.categorie) }}</span></td>
                            <td>{{ planche.total_quantite_prevue || 0 }}</td>
                            <td>{{ planche.total_quantite_livree || 0 }}</td>
                            <td class="font-weight-bold">{{ planche.total_quantite_disponible || 0 }}</td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" @click="openPlancheModal(planche)">Voir details</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>

    <div v-if="showCreateModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
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
                                            <th style="width:36%;">Code couleur</th><th style="width:18%;">Categorie</th><th style="width:16%;">Epaisseur</th><th style="width:16%;">Quantite prevue</th><th class="text-center" style="width:14%;">Actions</th>
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

    <div v-if="modalPlanche" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Planche #{{ modalPlanche.id }} - {{ modalPlanche.code_couleur || '-' }}</h5>
                    <button type="button" class="close" @click="closePlancheModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Categorie :</strong>
                        <span class="badge ml-2" :class="categorieBadgeClass(modalPlanche.categorie)">{{ categorieLabel(modalPlanche.categorie) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead><tr><th>ID detail</th><th>Categorie</th><th>Epaisseur</th><th>Prevues</th><th>Livrees</th><th>Disponibles</th></tr></thead>
                            <tbody>
                                <tr v-if="!modalPlanche.details?.length"><td colspan="6" class="text-center">Aucun detail pour cette planche.</td></tr>
                                <tr v-for="detail in modalPlanche.details" :key="detail.id">
                                    <td>#{{ detail.id }}</td>
                                    <td><span class="badge" :class="categorieBadgeClass(detail.categorie)">{{ categorieLabel(detail.categorie) }}</span></td>
                                    <td>{{ formatDecimal(detail.epaisseur) }} mm</td>
                                    <td>{{ detail.quantite_prevue || 0 }}</td>
                                    <td>{{ detail.total_quantite_livree || 0 }}</td>
                                    <td class="font-weight-bold">{{ detail.quantite_disponible || 0 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" @click="closePlancheModal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import PlancheColorInput from '@/Components/PlancheColorInput.vue';

const props = defineProps({
    contrat: { type: Object, required: true },
    active_planche_id: { type: Number, default: null },
    epaisseurs: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Gestion des contrats', link: '/admin/contrats', icon: 'fa fa-database' },
    { label: `Contrat ${props.contrat.numero}` },
];

const modalPlanche = ref(null);
const showCreateModal = ref(false);
const submittingCreate = ref(false);
const createFormError = ref('');
const createErrors = ref({});
const activePlanche = computed(() => props.contrat.planches.find((item) => item.id === props.active_planche_id) || props.contrat.planches[0] || null);
const epaisseurOptions = computed(() => props.epaisseurs.map((item) => {
    const value = extractEpaisseurValue(item);
    return value ? { id: item.id, label: item.intitule, value } : null;
}).filter(Boolean));
const createForm = ref(buildInitialCreateForm());

function buildInitialCreateForm() {
    return {
        supplier_id: props.contrat.supplier_id || props.contrat.supplier?.id || '',
        numero_contrat: props.contrat.numero || '',
        rows: [createRow()],
    };
}

function createRow(defaults = {}) {
    return {
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        code_couleur: defaults.code_couleur || '',
        code_couleur_from_selection: defaults.code_couleur_from_selection || '',
        existing_image_url: defaults.existing_image_url || '',
        categorie: defaults.categorie || '',
        epaisseur: defaults.epaisseur || '',
        quantite_prevue: defaults.quantite_prevue || '',
    };
}

function resetCreateForm() {
    createForm.value = buildInitialCreateForm();
    createErrors.value = {};
    createFormError.value = '';
}

function openCreateModal() { resetCreateForm(); showCreateModal.value = true; }
function closeCreateModal() { showCreateModal.value = false; resetCreateForm(); }
function addCreateRow(index) { createForm.value.rows.splice(index + 1, 0, createRow()); }
function removeCreateRow(index) { if (createForm.value.rows.length > 1) createForm.value.rows.splice(index, 1); }
function normalizeCode(value) { return String(value || '').trim().toLowerCase(); }
function extractEpaisseurValue(item) {
    for (const source of [item?.slug, item?.intitule]) {
        const match = String(source || '').replace(',', '.').match(/(\d+(?:\.\d+)?)/);
        if (match) return match[1];
    }
    return '';
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
        groupes[groupIndex].epaisseurs.push({ epaisseur: row.epaisseur, quantite_prevue: row.quantite_prevue });
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
function categorieLabel(cat) { return { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' }[cat] || cat || '-'; }
function categorieBadgeClass(cat) { return { mate: 'badge-secondary', semi_brillant: 'badge-warning', brillant: 'badge-success' }[cat] || 'badge-light'; }
function formatDecimal(value) { return value === null || value === undefined || value === '' ? '-' : Number(value).toFixed(2); }
function openPlancheModal(planche) { modalPlanche.value = planche; }
function closePlancheModal() { modalPlanche.value = null; }
</script>
