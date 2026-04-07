<template>
    <div>
        <div v-if="flash.message" class="alert mb-3" :class="flash.type === 'success' ? 'alert-success' : 'alert-danger'">
            {{ flash.message }}
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="border rounded p-3 h-100" style="background:#fbfcfe;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Ajout rapide</h5>
                            <small class="text-muted">Ajoutez plusieurs fournisseurs en ne saisissant que le nom.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="addRow">
                            <i class="fa fa-plus mr-1"></i> Ligne
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nom</th>
                                    <th class="text-center" style="width:70px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in rows" :key="row.localId">
                                    <td>
                                        <input
                                            v-model="row.name"
                                            type="text"
                                            class="form-control form-control-sm"
                                            placeholder="Ex: Bois du Sahel"
                                        />
                                        <small v-if="storeErrors[`rows.${index}.name`]" class="text-danger">
                                            {{ storeErrors[`rows.${index}.name`][0] }}
                                        </small>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button
                                            type="button"
                                            class="btn btn-sm"
                                            :class="rows.length === 1 ? 'btn-light text-muted' : 'btn-outline-danger'"
                                            :disabled="rows.length === 1"
                                            @click="removeRow(index)"
                                        >
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-success" :disabled="isSaving" @click="saveBatch">
                            <i class="fa fa-save mr-1"></i>
                            {{ isSaving ? 'Enregistrement...' : 'Tout enregistrer' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="border rounded p-3" style="background:#fff;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Fournisseurs configures</h5>
                            <small class="text-muted">{{ items.length }} element(s)</small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Nom</th>
                                    <th>Adresse</th>
                                    <th>Telephone</th>
                                    <th>Email</th>
                                    <th class="text-center" style="width:150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="items.length === 0">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Aucun fournisseur configure pour le moment.
                                    </td>
                                </tr>
                                <tr v-for="supplier in items" :key="supplier.id">
                                    <template v-if="editingId === supplier.id">
                                        <td>
                                            <input v-model="editForm.name" type="text" class="form-control form-control-sm" />
                                            <small v-if="editErrors.name" class="text-danger">{{ editErrors.name[0] }}</small>
                                        </td>
                                        <td>
                                            <input v-model="editForm.address" type="text" class="form-control form-control-sm" />
                                            <small v-if="editErrors.address" class="text-danger">{{ editErrors.address[0] }}</small>
                                        </td>
                                        <td>
                                            <input v-model="editForm.phone" type="text" class="form-control form-control-sm" />
                                            <small v-if="editErrors.phone" class="text-danger">{{ editErrors.phone[0] }}</small>
                                        </td>
                                        <td>
                                            <input v-model="editForm.email" type="email" class="form-control form-control-sm" />
                                            <small v-if="editErrors.email" class="text-danger">{{ editErrors.email[0] }}</small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success mr-1"
                                                :disabled="isUpdating"
                                                @click="updateSupplier(supplier.id)"
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" @click="cancelEdit">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td>{{ supplier.name }}</td>
                                        <td>{{ supplier.address || '-' }}</td>
                                        <td>{{ supplier.phone || '-' }}</td>
                                        <td>{{ supplier.email || '-' }}</td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-outline-primary mr-1" @click="startEdit(supplier)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                :disabled="deletingId === supplier.id"
                                                @click="deleteSupplier(supplier)"
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
</template>

<script setup>
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps({
    initialItems: {
        type: Array,
        default: () => [],
    },
});

const items = ref(sortItems(props.initialItems));
const rows = ref([createRow()]);
const storeErrors = ref({});
const editErrors = ref({});
const flash = ref({ type: 'success', message: '' });
const isSaving = ref(false);
const editingId = ref(null);
const isUpdating = ref(false);
const deletingId = ref(null);
const editForm = ref({
    name: '',
    address: '',
    phone: '',
    email: '',
});

function createRow() {
    return {
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        name: '',
    };
}

function sortItems(list) {
    return [...list].sort((a, b) => a.name.localeCompare(b.name, 'fr'));
}

function showFlash(type, message) {
    flash.value = { type, message };
}

function addRow() {
    rows.value.push(createRow());
}

function removeRow(index) {
    if (rows.value.length === 1) {
        return;
    }

    rows.value.splice(index, 1);
}

async function saveBatch() {
    isSaving.value = true;
    storeErrors.value = {};
    showFlash('success', '');

    try {
        const response = await axios.post('/admin/configuration/suppliers', {
            rows: rows.value.map(({ name }) => ({ name })),
        });

        items.value = sortItems([...items.value, ...response.data.data]);
        rows.value = [createRow()];
        showFlash('success', response.data.message);
    } catch (error) {
        if (error.response?.status === 422) {
            storeErrors.value = error.response.data.errors || {};
            showFlash('danger', error.response.data.message || 'Veuillez corriger les lignes en erreur.');
        } else {
            showFlash('danger', 'Impossible d enregistrer les fournisseurs.');
        }
    } finally {
        isSaving.value = false;
    }
}

function startEdit(supplier) {
    editingId.value = supplier.id;
    editErrors.value = {};
    editForm.value = {
        name: supplier.name || '',
        address: supplier.address || '',
        phone: supplier.phone || '',
        email: supplier.email || '',
    };
}

function cancelEdit() {
    editingId.value = null;
    editErrors.value = {};
    editForm.value = {
        name: '',
        address: '',
        phone: '',
        email: '',
    };
}

async function updateSupplier(id) {
    isUpdating.value = true;
    editErrors.value = {};
    showFlash('success', '');

    try {
        const response = await axios.put(`/admin/configuration/suppliers/${id}`, editForm.value);
        items.value = sortItems(
            items.value.map((item) => (item.id === id ? response.data.data : item))
        );
        cancelEdit();
        showFlash('success', response.data.message);
    } catch (error) {
        if (error.response?.status === 422) {
            editErrors.value = error.response.data.errors || {};
            showFlash('danger', error.response.data.message || 'Veuillez corriger le formulaire.');
        } else {
            showFlash('danger', 'Impossible de mettre a jour ce fournisseur.');
        }
    } finally {
        isUpdating.value = false;
    }
}

async function deleteSupplier(supplier) {
    if (!window.confirm(`Supprimer le fournisseur "${supplier.name}" ?`)) {
        return;
    }

    deletingId.value = supplier.id;
    showFlash('success', '');

    try {
        const response = await axios.delete(`/admin/configuration/suppliers/${supplier.id}`);
        items.value = items.value.filter((item) => item.id !== supplier.id);

        if (editingId.value === supplier.id) {
            cancelEdit();
        }

        showFlash('success', response.data.message);
    } catch (error) {
        showFlash('danger', error.response?.data?.message || 'Impossible de supprimer ce fournisseur.');
    } finally {
        deletingId.value = null;
    }
}
</script>
