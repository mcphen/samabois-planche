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
                    <li class="nav-item">
                        <a
                            href="#"
                            class="nav-link"
                            :class="{ active: activeTab === 'clients' }"
                            @click.prevent="onClientsTabOpen"
                        >
                            Clients
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
                                                    <td>
                                                        <div>{{ epaisseur.intitule }}</div>
                                                        <small v-if="epaisseur.usage_count" class="text-muted">
                                                            Utilisee dans {{ epaisseur.usage_count }} ligne(s) de planche
                                                        </small>
                                                    </td>
                                                    <td><code>{{ epaisseur.slug }}</code></td>
                                                    <td class="text-center align-middle">
                                                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" @click="startEdit(epaisseur)">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            :disabled="isDeletingId === epaisseur.id || !epaisseur.can_delete"
                                                            :title="epaisseur.can_delete ? 'Supprimer cette epaisseur' : 'Suppression impossible: epaisseur deja utilisee'"
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

                <div v-show="activeTab === 'clients'">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Liste des clients</h5>
                            <small class="text-muted">{{ clientsState.length }} client(s)</small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" @click="openClientAddModal">
                            <i class="fa fa-plus mr-1"></i> Ajouter un client
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Nom</th>
                                    <th>Adresse</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th class="text-center" style="width:160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="clientsState.length === 0">
                                    <td colspan="5" class="text-center text-muted py-4">Aucun client enregistré.</td>
                                </tr>
                                <tr v-for="client in clientsState" :key="client.id">
                                    <td>{{ client.name }}</td>
                                    <td>{{ client.address || '-' }}</td>
                                    <td>{{ client.phone || '-' }}</td>
                                    <td>{{ client.email || '-' }}</td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-outline-warning mr-1" @click="openClientEditModal(client)">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button v-if="isAdmin" type="button" class="btn btn-sm btn-outline-danger" @click="deleteClient(client)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal Ajouter / Modifier client -->
    <div v-if="showClientModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ clientEditId ? 'Modifier le client' : 'Ajouter un client' }}</h5>
                    <button type="button" class="close" @click="closeClientModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nom *</label>
                        <input v-model="clientForm.name" type="text" class="form-control" />
                        <small v-if="clientErrors.name" class="text-danger">{{ clientErrors.name[0] }}</small>
                    </div>
                    <div class="form-group">
                        <label>Adresse</label>
                        <input v-model="clientForm.address" type="text" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input v-model="clientForm.phone" type="text" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input v-model="clientForm.email" type="email" class="form-control" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" :disabled="isSavingClient" @click="saveClient">
                        {{ isSavingClient ? 'Enregistrement...' : (clientEditId ? 'Modifier' : 'Ajouter') }}
                    </button>
                    <button type="button" class="btn btn-secondary" @click="closeClientModal">Annuler</button>
                </div>
            </div>
        </div>
    </div>
    </AuthenticatedLayout>
</template>

<script setup>
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import ConfigurationPlancheCouleursTab from '@/Components/ConfigurationPlancheCouleursTab.vue';
import ConfigurationSuppliersTab from '@/Components/ConfigurationSuppliersTab.vue';

const props = defineProps({
    epaisseurs: { type: Array, default: () => [] },
    plancheCouleurs: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    userRole: { type: String, default: 'user' },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Configuration' },
];

const isAdmin = computed(() => props.userRole === 'admin');

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

// ─── Clients tab ───────────────────────────────────────────────────────────
const clientsState = ref([...props.clients]);
const showClientModal = ref(false);
const clientEditId = ref(null);
const isSavingClient = ref(false);
const clientErrors = ref({});
const clientForm = ref({ name: '', address: '', phone: '', email: '' });

function onClientsTabOpen() {
    activeTab.value = 'clients';
}

function openClientAddModal() {
    clientEditId.value = null;
    clientErrors.value = {};
    clientForm.value = { name: '', address: '', phone: '', email: '' };
    showClientModal.value = true;
}

function openClientEditModal(client) {
    clientEditId.value = client.id;
    clientErrors.value = {};
    clientForm.value = { name: client.name, address: client.address || '', phone: client.phone || '', email: client.email || '' };
    showClientModal.value = true;
}

function closeClientModal() {
    showClientModal.value = false;
    clientEditId.value = null;
    clientErrors.value = {};
    clientForm.value = { name: '', address: '', phone: '', email: '' };
}

async function saveClient() {
    isSavingClient.value = true;
    clientErrors.value = {};
    showFlash('success', '');

    try {
        if (clientEditId.value) {
            const response = await axios.post(`/admin/clients/${clientEditId.value}/update`, clientForm.value);
            const updated = response.data.data ?? response.data.client ?? response.data;
            clientsState.value = clientsState.value.map((c) => (c.id === clientEditId.value ? { ...c, ...clientForm.value } : c));
            showFlash('success', response.data.message || 'Client modifié avec succès.');
        } else {
            const response = await axios.post('/admin/clients/store', clientForm.value);
            const created = response.data.data ?? response.data.client ?? response.data;
            if (created && created.id) {
                clientsState.value = [...clientsState.value, created].sort((a, b) => a.name.localeCompare(b.name, 'fr'));
            }
            showFlash('success', response.data.message || 'Client ajouté avec succès.');
        }
        closeClientModal();
    } catch (error) {
        if (error.response?.status === 422) {
            clientErrors.value = error.response.data.errors || {};
            showFlash('danger', error.response.data.message || 'Veuillez corriger le formulaire.');
        } else {
            showFlash('danger', 'Impossible d\'enregistrer le client.');
        }
    } finally {
        isSavingClient.value = false;
    }
}

async function deleteClient(client) {
    if (!window.confirm(`Supprimer le client "${client.name}" ?`)) {
        return;
    }

    showFlash('success', '');

    try {
        const response = await axios.delete(`/admin/clients/destroy/${client.id}`);
        clientsState.value = clientsState.value.filter((c) => c.id !== client.id);
        showFlash('success', response.data.message || 'Client supprimé.');
    } catch (error) {
        showFlash('danger', error.response?.data?.message || 'Impossible de supprimer ce client.');
    }
}
// ─────────────────────────────────────────────────────────────────────────────

async function deleteEpaisseur(epaisseur) {
    if (!epaisseur.can_delete) {
        showFlash('danger', 'Suppression impossible: cette epaisseur est deja utilisee dans des lignes de planche.');
        return;
    }

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











