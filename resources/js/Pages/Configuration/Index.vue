<template>
    <Head :title="`Configuration | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'Configuration'"
            :breadcrumbs="breadcrumbs"
        />

        <div v-if="flash.message" class="alert" :class="flash.type === 'success' ? 'alert-success' : 'alert-danger'">
            {{ flash.message }}
        </div>

        <div class="card">
            <div class="body">
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a
                            href="#"
                            class="nav-link"
                            :class="{ active: activeTab === 'epaisseurs' }"
                            @click.prevent="activeTab = 'epaisseurs'"
                        >
                            Epaisseurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            href="#"
                            class="nav-link"
                            :class="{ active: activeTab === 'planche-couleurs' }"
                            @click.prevent="activeTab = 'planche-couleurs'"
                        >
                            Couleurs planches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            href="#"
                            class="nav-link"
                            :class="{ active: activeTab === 'suppliers' }"
                            @click.prevent="activeTab = 'suppliers'"
                        >
                            Fournisseurs
                        </a>
                    </li>
                </ul>

                <div v-show="activeTab === 'epaisseurs'">
                    <div class="row">
                        <div class="col-lg-5 mb-4">
                            <div class="border rounded p-3 h-100" style="background:#fbfcfe;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="mb-1">Ajout multiple</h5>
                                        <small class="text-muted">
                                            Ajoutez plusieurs lignes puis enregistrez en une seule fois.
                                        </small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addNewRow">
                                        <i class="fa fa-plus mr-1"></i> Ligne
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-3">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Intitule</th>
                                                <th>Slug</th>
                                                <th class="text-center" style="width:70px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, index) in newRows" :key="row.localId">
                                                <td>
                                                    <input
                                                        v-model="row.intitule"
                                                        type="text"
                                                        class="form-control form-control-sm"
                                                        placeholder="Ex: Epaisseur 18 mm"
                                                        @input="handleNewIntituleInput(row)"
                                                    />
                                                    <small v-if="storeErrors[`rows.${index}.intitule`]" class="text-danger">
                                                        {{ storeErrors[`rows.${index}.intitule`][0] }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <input
                                                        v-model="row.slug"
                                                        type="text"
                                                        class="form-control form-control-sm"
                                                        placeholder="Genere automatiquement"
                                                        @input="handleNewSlugInput(row)"
                                                    />
                                                    <small v-if="storeErrors[`rows.${index}.slug`]" class="text-danger">
                                                        {{ storeErrors[`rows.${index}.slug`][0] }}
                                                    </small>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm"
                                                        :class="newRows.length === 1 ? 'btn-light text-muted' : 'btn-outline-danger'"
                                                        :disabled="newRows.length === 1"
                                                        @click="removeNewRow(index)"
                                                    >
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                                    <small class="text-muted">
                                        Les accents restent dans l'intitule, le slug est nettoye automatiquement.
                                    </small>
                                    <button type="button" class="btn btn-success" :disabled="isSavingBatch" @click="saveBatch">
                                        <i class="fa fa-save mr-1"></i>
                                        {{ isSavingBatch ? 'Enregistrement...' : 'Tout enregistrer' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="border rounded p-3" style="background:#fff;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="mb-1">Liste configuree</h5>
                                        <small class="text-muted">{{ epaisseursState.length }} element(s)</small>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered mb-0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Intitule</th>
                                                <th>Slug</th>
                                                <th class="text-center" style="width:150px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="epaisseursState.length === 0">
                                                <td colspan="3" class="text-center text-muted py-4">
                                                    Aucune epaisseur configuree pour le moment.
                                                </td>
                                            </tr>
                                            <tr v-for="epaisseur in epaisseursState" :key="epaisseur.id">
                                                <template v-if="editingId === epaisseur.id">
                                                    <td>
                                                        <input
                                                            v-model="editForm.intitule"
                                                            type="text"
                                                            class="form-control form-control-sm"
                                                            @input="handleEditIntituleInput"
                                                        />
                                                        <small v-if="editErrors.intitule" class="text-danger">
                                                            {{ editErrors.intitule[0] }}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <input
                                                            v-model="editForm.slug"
                                                            type="text"
                                                            class="form-control form-control-sm"
                                                            @input="handleEditSlugInput"
                                                        />
                                                        <small v-if="editErrors.slug" class="text-danger">
                                                            {{ editErrors.slug[0] }}
                                                        </small>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-success mr-1"
                                                            :disabled="isUpdating"
                                                            @click="updateEpaisseur(epaisseur.id)"
                                                        >
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-secondary" @click="cancelEdit">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </td>
                                                </template>
                                                <template v-else>
                                                    <td>{{ epaisseur.intitule }}</td>
                                                    <td><code>{{ epaisseur.slug }}</code></td>
                                                    <td class="text-center align-middle">
                                                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" @click="startEdit(epaisseur)">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            :disabled="isDeletingId === epaisseur.id"
                                                            @click="deleteEpaisseur(epaisseur)"
                                                        >
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </template>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-show="activeTab === 'planche-couleurs'">
                    <ConfigurationPlancheCouleursTab :initial-items="props.plancheCouleurs" />
                </div>

                <div v-show="activeTab === 'suppliers'">
                    <ConfigurationSuppliersTab :initial-items="props.suppliers" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import ConfigurationPlancheCouleursTab from '@/Components/ConfigurationPlancheCouleursTab.vue';
import ConfigurationSuppliersTab from '@/Components/ConfigurationSuppliersTab.vue';

const props = defineProps({
    epaisseurs: { type: Array, default: () => [] },
    plancheCouleurs: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Configuration' },
];

const activeTab = ref('epaisseurs');
const epaisseursState = ref(sortEpaisseurs(props.epaisseurs));
const newRows = ref([createNewRow()]);
const storeErrors = ref({});
const editErrors = ref({});
const isSavingBatch = ref(false);
const isUpdating = ref(false);
const isDeletingId = ref(null);
const editingId = ref(null);
const editAutoSlug = ref(true);
const flash = ref({ type: 'success', message: '' });
const editForm = ref({
    intitule: '',
    slug: '',
});

function createNewRow() {
    return {
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        intitule: '',
        slug: '',
        autoSlug: true,
    };
}

function sortEpaisseurs(items) {
    return [...items].sort((a, b) => a.intitule.localeCompare(b.intitule, 'fr'));
}

function slugify(value) {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function showFlash(type, message) {
    flash.value = { type, message };
}

function addNewRow() {
    newRows.value.push(createNewRow());
}

function removeNewRow(index) {
    if (newRows.value.length === 1) {
        return;
    }

    newRows.value.splice(index, 1);
}

function handleNewIntituleInput(row) {
    if (row.autoSlug) {
        row.slug = slugify(row.intitule);
    }
}

function handleNewSlugInput(row) {
    row.autoSlug = row.slug.trim() === '';
}

async function saveBatch() {
    isSavingBatch.value = true;
    storeErrors.value = {};
    showFlash('success', '');

    try {
        const payload = {
            rows: newRows.value.map(({ intitule, slug }) => ({ intitule, slug })),
        };

        const response = await axios.post('/admin/configuration/epaisseurs', payload);
        epaisseursState.value = sortEpaisseurs([...epaisseursState.value, ...response.data.data]);
        newRows.value = [createNewRow()];
        showFlash('success', response.data.message);
    } catch (error) {
        if (error.response?.status === 422) {
            storeErrors.value = error.response.data.errors || {};
            showFlash('danger', error.response.data.message || 'Veuillez corriger les lignes en erreur.');
        } else {
            showFlash('danger', 'Impossible d enregistrer les epaisseurs.');
        }
    } finally {
        isSavingBatch.value = false;
    }
}

function startEdit(epaisseur) {
    editingId.value = epaisseur.id;
    editErrors.value = {};
    editAutoSlug.value = epaisseur.slug === slugify(epaisseur.intitule);
    editForm.value = {
        intitule: epaisseur.intitule,
        slug: epaisseur.slug,
    };
}

function cancelEdit() {
    editingId.value = null;
    editErrors.value = {};
    editForm.value = { intitule: '', slug: '' };
}

function handleEditIntituleInput() {
    if (editAutoSlug.value) {
        editForm.value.slug = slugify(editForm.value.intitule);
    }
}

function handleEditSlugInput() {
    editAutoSlug.value = editForm.value.slug.trim() === '';
}

async function updateEpaisseur(id) {
    isUpdating.value = true;
    editErrors.value = {};
    showFlash('success', '');

    try {
        const response = await axios.put(`/admin/configuration/epaisseurs/${id}`, editForm.value);
        epaisseursState.value = sortEpaisseurs(
            epaisseursState.value.map((item) => (item.id === id ? response.data.data : item))
        );
        cancelEdit();
        showFlash('success', response.data.message);
    } catch (error) {
        if (error.response?.status === 422) {
            editErrors.value = error.response.data.errors || {};
            showFlash('danger', error.response.data.message || 'Veuillez corriger le formulaire.');
        } else {
            showFlash('danger', 'Impossible de mettre a jour cette epaisseur.');
        }
    } finally {
        isUpdating.value = false;
    }
}

async function deleteEpaisseur(epaisseur) {
    if (!window.confirm(`Supprimer l epaisseur "${epaisseur.intitule}" ?`)) {
        return;
    }

    isDeletingId.value = epaisseur.id;
    showFlash('success', '');

    try {
        const response = await axios.delete(`/admin/configuration/epaisseurs/${epaisseur.id}`);
        epaisseursState.value = epaisseursState.value.filter((item) => item.id !== epaisseur.id);

        if (editingId.value === epaisseur.id) {
            cancelEdit();
        }

        showFlash('success', response.data.message);
    } catch (error) {
        showFlash('danger', error.response?.data?.message || 'Impossible de supprimer cette epaisseur.');
    } finally {
        isDeletingId.value = null;
    }
}
</script>











