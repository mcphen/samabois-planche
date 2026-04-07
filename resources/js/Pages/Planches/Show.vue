<template>
    <Head :title="`Detail planche | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="`Detail de la planche ${planche.couleur?.code || '-'}`"
            :breadcrumbs="breadcrumbs"
        >
            <template #action>
                <Link class="btn btn-primary" href="/admin/planches">
                    <i class="fa fa-arrow-left"></i> Retour a la liste
                </Link>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix row-deck">
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card primary-bg">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{ planche.couleur?.code || '-' }}</h3>
                            <span>Code couleur</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-12">
                <div
                    class="card"
                    :style="{ background: categorieBgColor(planche.categorie) }"
                >
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{ categorieLabel(planche.categorie) }}</h3>
                            <span>Categorie</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card secondary-bg">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{ planche.details.length }}</h3>
                            <span>Epaisseurs</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card bg-info">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{ totalQuantite }}</h3>
                            <span>Total feuilles</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-5 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2>Informations generales</h2>
                    </div>
                    <div class="body">
                        <div v-if="planche.couleur?.image_url" class="mb-3 text-center">
                            <img
                                :src="planche.couleur.image_url"
                                alt="Image couleur"
                                class="img-fluid rounded border"
                                style="max-height:180px;object-fit:cover;"
                            />
                        </div>
                        <div class="mb-3">
                            <strong>Fournisseur :</strong>
                            {{ planche.contrat?.supplier?.name || '-' }}
                        </div>
                        <div class="mb-3">
                            <strong>Numero du contrat :</strong>
                            {{ planche.contrat?.numero || '-' }}
                        </div>
                        <div class="mb-3">
                            <strong>Code couleur :</strong>
                            {{ planche.couleur?.code || '-' }}
                        </div>
                        <div class="mb-3" v-if="planche.contrat?.supplier?.phone">
                            <strong>Telephone :</strong>
                            {{ planche.contrat.supplier.phone }}
                        </div>
                        <div class="mb-3" v-if="planche.contrat?.supplier?.email">
                            <strong>Email :</strong>
                            {{ planche.contrat.supplier.email }}
                        </div>
                        <div class="mb-0" v-if="planche.contrat?.supplier?.address">
                            <strong>Adresse :</strong>
                            {{ planche.contrat.supplier.address }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2>Details des epaisseurs</h2>
                    </div>
                    <div class="body table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Categorie</th>
                                    <th>Epaisseur (mm)</th>
                                    <th>Nb feuilles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!planche.details.length">
                                    <td colspan="3" class="text-center">Aucun detail enregistre.</td>
                                </tr>
                                <tr v-for="detail in sortedDetails" :key="detail.id">
                                    <td>
                                        <span class="badge" :class="categorieBadgeClass(detail.categorie)">
                                            {{ categorieLabel(detail.categorie) }}
                                        </span>
                                    </td>
                                    <td>{{ formatDecimal(detail.epaisseur) }} mm</td>
                                    <td class="font-weight-bold">{{ detail.quantite_prevue }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="planche.details.length">
                                <tr>
                                    <th colspan="2">Total feuilles</th>
                                    <th>{{ totalQuantite }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" v-if="autres_planches.length">
            <div class="header">
                <h2>Autres planches du meme contrat</h2>
            </div>
            <div class="body">
                <div class="d-flex flex-wrap">
                    <Link
                        v-for="autre in autres_planches"
                        :key="autre.id"
                        :href="`/admin/planches/${autre.id}`"
                        class="btn btn-outline-secondary m-1"
                    >
                        {{ autre.couleur?.code || '-' }} - {{ categorieLabel(autre.categorie) }}
                    </Link>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="header">
                <h2>Gestion des lignes du contrat</h2>
            </div>
            <div class="body">
                <div v-if="formError" class="alert alert-danger">
                    {{ formError }}
                </div>

                <form @submit.prevent="addLine" class="mb-4 p-3 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
                    <div class="row mb-2">
                        <div class="col-md-5">
                            <label class="small font-weight-bold">Code couleur *</label>
                            <PlancheColorInput v-model="createForm.code_couleur" @select="applySelectedColor(createForm, $event)" />
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="openColorModal(createForm)">
                                    <i class="fa fa-plus mr-1"></i> Ajouter une couleur
                                </button>
                            </div>
                            <small v-if="errors.code_couleur" class="text-danger d-block mt-1">{{ errors.code_couleur[0] }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="small font-weight-bold">Categorie *</label>
                            <select v-model="createForm.categorie" class="form-control">
                                <option value="">Selectionner...</option>
                                <option value="mate">Mate</option>
                                <option value="semi_brillant">Semi-brillant</option>
                                <option value="brillant">Brillant</option>
                            </select>
                            <small v-if="errors.categorie" class="text-danger">{{ errors.categorie[0] }}</small>
                        </div>
                        <div class="col-md-3">
                            <div
                                v-if="createForm.existing_image_url"
                                class="border rounded p-2 text-center mb-2"
                                style="background:#fff;"
                            >
                                <img
                                    :src="createForm.existing_image_url"
                                    alt="Apercu couleur"
                                    class="img-fluid rounded"
                                    style="max-height:78px;object-fit:cover;margin:0 auto;"
                                />
                            </div>
                            <span
                                v-if="createForm.categorie"
                                class="badge w-100 py-2"
                                style="font-size:13px;"
                                :class="categorieBadgeClass(createForm.categorie)"
                            >
                                {{ categorieLabel(createForm.categorie) }}
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="small font-weight-bold">Epaisseur (mm) *</label>
                            <input v-model="createForm.epaisseur" type="number" min="0.01" step="0.01" class="form-control" placeholder="ex: 1.50" />
                            <small v-if="errors.epaisseur" class="text-danger">{{ errors.epaisseur[0] }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="small font-weight-bold">Nb feuilles *</label>
                            <input v-model="createForm.quantite_prevue" type="number" min="1" step="1" class="form-control" placeholder="ex: 100" />
                            <small v-if="errors.quantite_prevue" class="text-danger">{{ errors.quantite_prevue[0] }}</small>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100" :disabled="submitting">
                                <i class="fa fa-plus mr-1"></i> Ajouter la ligne
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Code couleur</th>
                                <th>Categorie</th>
                                <th>Epaisseur</th>
                                <th>Nb feuilles</th>
                                <th>Planche</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!contractRows.length">
                                <td colspan="6" class="text-center py-4">Aucune ligne disponible.</td>
                            </tr>
                            <tr v-for="row in contractRows" :key="row.detail_id">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img
                                            v-if="row.image_url"
                                            :src="row.image_url"
                                            alt="Couleur"
                                            class="rounded border mr-2"
                                            style="width:32px;height:32px;object-fit:cover;"
                                        />
                                        <span class="badge badge-info">{{ row.code_couleur }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="categorieBadgeClass(row.categorie)">
                                        {{ categorieLabel(row.categorie) }}
                                    </span>
                                </td>
                                <td>{{ formatDecimal(row.epaisseur) }}</td>
                                <td>{{ row.quantite_prevue }}</td>
                                <td>
                                    <Link :href="`/admin/planches/${row.planche_id}`" class="badge badge-info">
                                        {{ row.planche_id === planche.id ? 'Courante' : 'Voir planche' }}
                                    </Link>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm mr-1" @click="openEditModal(row)">
                                        Modifier
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" @click="deleteLine(row)">
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <div v-if="showEditModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier une ligne</h5>
                    <button type="button" class="close" @click="closeEditModal">&times;</button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="updateLine">
                        <div class="form-group">
                            <label>Code couleur *</label>
                            <PlancheColorInput v-model="editForm.code_couleur" @select="applySelectedColor(editForm, $event)" />
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="openColorModal(editForm)">
                                    <i class="fa fa-plus mr-1"></i> Ajouter une couleur
                                </button>
                            </div>
                        </div>
                        <div v-if="editForm.existing_image_url" class="form-group text-center">
                            <img
                                :src="editForm.existing_image_url"
                                alt="Apercu couleur"
                                class="img-fluid rounded border"
                                style="max-height:160px;object-fit:cover;"
                            />
                        </div>
                        <div class="form-group">
                            <label>Categorie *</label>
                            <select v-model="editForm.categorie" class="form-control">
                                <option value="mate">Mate</option>
                                <option value="semi_brillant">Semi-brillant</option>
                                <option value="brillant">Brillant</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Epaisseur *</label>
                            <input v-model="editForm.epaisseur" type="number" min="0.01" step="0.01" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label>Nb feuilles *</label>
                            <input v-model="editForm.quantite_prevue" type="number" min="1" step="1" class="form-control" />
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success" :disabled="submitting">
                                Enregistrer
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" @click="closeEditModal">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <PlancheColorModal
        :show="showColorModal"
        :initial-code="colorModalInitialCode"
        @close="closeColorModal"
        @created="handleColorCreated"
    />
</template>

<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import { Inertia } from '@inertiajs/inertia';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import PlancheColorInput from '@/Components/PlancheColorInput.vue';
import PlancheColorModal from '@/Components/PlancheColorModal.vue';

const props = defineProps({
    planche: {
        type: Object,
        required: true,
    },
    autres_planches: {
        type: Array,
        default: () => [],
    },
    contrat_planches: {
        type: Array,
        default: () => [],
    },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Gestion des planches', link: '/admin/planches', icon: 'fa fa-database' },
    { label: `Planche ${props.planche.couleur?.code || ''}` },
];

const submitting = ref(false);
const showEditModal = ref(false);
const formError = ref('');
const errors = ref({});
const selectedDetailId = ref(null);
const showColorModal = ref(false);
const colorModalInitialCode = ref('');
const colorTarget = ref(null);

function createLineForm(defaults = {}) {
    return {
        code_couleur: defaults.code_couleur || '',
        existing_image_url: defaults.image_url || '',
        categorie: defaults.categorie || '',
        epaisseur: defaults.epaisseur || '',
        quantite_prevue: defaults.quantite_prevue || '',
    };
}

const createForm = ref(createLineForm({
    code_couleur: props.planche.couleur?.code || '',
    image_url: props.planche.couleur?.image_url || '',
    categorie: props.planche.categorie || '',
}));

const editForm = ref(createLineForm());

const sortedDetails = computed(() => {
    return [...props.planche.details].sort((a, b) => Number(a.epaisseur) - Number(b.epaisseur));
});

const totalQuantite = computed(() => {
    return props.planche.details.reduce((total, detail) => total + Number(detail.quantite_prevue || 0), 0);
});

const contractRows = computed(() => {
    return props.contrat_planches
        .flatMap((item) => item.details.map((detail) => ({
            detail_id: detail.id,
            planche_id: item.id,
            code_couleur: item.couleur?.code || '',
            image_url: item.couleur?.image_url || '',
            categorie: detail.categorie,
            epaisseur: detail.epaisseur,
            quantite_prevue: detail.quantite_prevue,
        })))
        .sort((a, b) => {
            const keyA = a.code_couleur + '|' + (a.categorie || '');
            const keyB = b.code_couleur + '|' + (b.categorie || '');

            if (keyA === keyB) {
                return Number(a.epaisseur) - Number(b.epaisseur);
            }

            return keyA.localeCompare(keyB);
        });
});

function categorieLabel(cat) {
    return { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' }[cat] || cat || '-';
}

function categorieBadgeClass(cat) {
    return { mate: 'badge-secondary', semi_brillant: 'badge-warning', brillant: 'badge-success' }[cat] || 'badge-light';
}

function categorieBgColor(cat) {
    return { mate: '#6c757d', semi_brillant: '#fd7e14', brillant: '#28a745' }[cat] || '#adb5bd';
}

function formatDecimal(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return Number(value).toFixed(2);
}

function resetErrors() {
    errors.value = {};
    formError.value = '';
}

function reloadPage() {
    Inertia.reload({ preserveScroll: true });
}

function handleRedirect(response) {
    const redirectTo = response?.data?.data?.redirect_to;

    if (redirectTo) {
        Inertia.visit(redirectTo);
        return true;
    }

    return false;
}

function applySelectedColor(form, suggestion) {
    form.existing_image_url = suggestion?.image_url || '';
}

function openColorModal(target) {
    colorTarget.value = target;
    colorModalInitialCode.value = target?.code_couleur || '';
    showColorModal.value = true;
}

function closeColorModal() {
    showColorModal.value = false;
    colorModalInitialCode.value = '';
    colorTarget.value = null;
}

function handleColorCreated(couleur) {
    if (!colorTarget.value || !couleur) {
        closeColorModal();
        return;
    }

    colorTarget.value.code_couleur = couleur.code || '';
    colorTarget.value.existing_image_url = couleur.image_url || '';
    closeColorModal();
}

function addLine() {
    submitting.value = true;
    resetErrors();

    axios.post(`/admin/planches/${props.planche.id}/lignes`, createForm.value)
        .then((response) => {
            if (response.data?.data?.planche_id && response.data.data.planche_id !== props.planche.id) {
                Inertia.visit(`/admin/planches/${response.data.data.planche_id}`);
                return;
            }

            createForm.value = createLineForm({
                code_couleur: createForm.value.code_couleur,
                image_url: createForm.value.existing_image_url,
                categorie: createForm.value.categorie,
            });
            reloadPage();
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {};
                formError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
                return;
            }

            formError.value = 'Une erreur est survenue pendant l ajout.';
        })
        .finally(() => {
            submitting.value = false;
        });
}

function openEditModal(row) {
    resetErrors();
    selectedDetailId.value = row.detail_id;
    editForm.value = createLineForm({
        code_couleur: row.code_couleur,
        image_url: row.image_url,
        categorie: row.categorie,
        epaisseur: row.epaisseur,
        quantite_prevue: row.quantite_prevue,
    });
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    selectedDetailId.value = null;
    editForm.value = createLineForm();
}

function updateLine() {
    if (!selectedDetailId.value) {
        return;
    }

    submitting.value = true;
    resetErrors();

    axios.put(`/admin/planches/${props.planche.id}/lignes/${selectedDetailId.value}`, editForm.value)
        .then((response) => {
            closeEditModal();

            if (handleRedirect(response)) {
                return;
            }

            if (response.data?.data?.planche_id && response.data.data.planche_id !== props.planche.id) {
                Inertia.visit(`/admin/planches/${response.data.data.planche_id}`);
                return;
            }

            reloadPage();
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {};
                formError.value = error.response.data.message || 'Veuillez corriger les erreurs du formulaire.';
                return;
            }

            formError.value = 'Une erreur est survenue pendant la modification.';
        })
        .finally(() => {
            submitting.value = false;
        });
}

function deleteLine(row) {
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
        if (!result.isConfirmed) {
            return;
        }

        axios.delete(`/admin/planches/${props.planche.id}/lignes/${row.detail_id}`)
            .then((response) => {
                if (handleRedirect(response)) {
                    return;
                }

                reloadPage();
            });
    });
}
</script>
