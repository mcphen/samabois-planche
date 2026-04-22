<template>
    <div>
        <div v-if="flash.message" class="alert mb-3" :class="flash.type === 'success' ? 'alert-success' : 'alert-danger'">
            {{ flash.message }}
        </div>

        <div class="row">
            <div v-if="canEdit" class="col-lg-4 mb-4">
                <div class="border rounded p-3 h-100" style="background:#fbfcfe;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Ajout rapide</h5>
                            <small class="text-muted">Ajoutez plusieurs codes couleur. L image se gere ensuite dans les actions.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="addRow">
                            <i class="fa fa-plus mr-1"></i> Ligne
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="thead-light">
                                <tr>
                                    <th>Code couleur</th>
                                    <th class="text-center" style="width:70px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in rows" :key="row.localId">
                                    <td>
                                        <input
                                            v-model="row.code"
                                            type="text"
                                            class="form-control form-control-sm"
                                            placeholder="Ex: CHENE-MAT"
                                        />
                                        <small v-if="storeErrors[`rows.${index}.code`]" class="text-danger">
                                            {{ storeErrors[`rows.${index}.code`][0] }}
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

            <div :class="canEdit ? 'col-lg-8' : 'col-lg-12'">
                <div class="border rounded p-3" style="background:#fff;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Couleurs configurees</h5>
                            <small class="text-muted">{{ items.length }} element(s)</small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width:90px;">Apercu</th>
                                    <th>Code</th>
                                    <th v-if="canEdit" class="text-center" style="width:180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="items.length === 0">
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Aucune couleur configuree pour le moment.
                                    </td>
                                </tr>
                                <tr v-for="couleur in items" :key="couleur.id">
                                    <template v-if="canEdit && editingId === couleur.id">
                                        <td class="align-middle text-center">
                                            <img
                                                v-if="editPreviewUrl"
                                                :src="editPreviewUrl"
                                                alt="Apercu couleur"
                                                class="img-fluid rounded border"
                                                style="max-height:60px;object-fit:cover;"
                                            />
                                            <span v-else class="text-muted small">Sans image</span>
                                        </td>
                                        <td class="align-middle">
                                            <input
                                                v-model="editForm.code"
                                                type="text"
                                                class="form-control form-control-sm"
                                            />
                                            <small v-if="editErrors.code" class="text-danger d-block">
                                                {{ editErrors.code[0] }}
                                            </small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <input
                                                type="file"
                                                class="form-control form-control-sm mb-2"
                                                accept="image/*"
                                                @change="handleEditImageChange"
                                            />
                                            <small v-if="editErrors.image" class="text-danger d-block mb-2">
                                                {{ editErrors.image[0] }}
                                            </small>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success mr-1"
                                                :disabled="isUpdating"
                                                @click="updateCouleur(couleur.id)"
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" @click="cancelEdit">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="align-middle text-center">
                                            <img
                                                v-if="couleur.image_url"
                                                :src="couleur.image_url"
                                                alt="Apercu couleur"
                                                class="img-fluid rounded border"
                                                style="max-height:60px;object-fit:cover;"
                                            />
                                            <span v-else class="text-muted small">Sans image</span>
                                        </td>
                                        <td class="align-middle">
                                            <strong>{{ couleur.code }}</strong>
                                            <div v-if="couleur.usage_count" class="text-muted small">
                                                Utilisee dans {{ couleur.usage_count }} ligne(s) de planche
                                            </div>
                                        </td>
                                        <td v-if="canEdit" class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-outline-primary mr-1" @click="startEdit(couleur)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                :disabled="deletingId === couleur.id || !couleur.can_delete"
                                                :title="couleur.can_delete ? 'Supprimer cette couleur' : 'Suppression impossible: couleur deja utilisee'"
                                                @click="deleteCouleur(couleur)"
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
    canEdit: {
        type: Boolean,
        default: true,
    },
});

const items = ref(sortItems(props.initialItems));
const rows = ref([createRow()]);
const storeErrors = ref({});
const flash = ref({ type: 'success', message: '' });
const isSaving = ref(false);
const editingId = ref(null);
const editForm = ref({
    code: '',
    image: null,
});
const editErrors = ref({});
const editPreviewUrl = ref('');
const isUpdating = ref(false);
const deletingId = ref(null);

function sortItems(list) {
    return [...list].sort((a, b) => a.code.localeCompare(b.code, 'fr'));
}

function createRow() {
    return {
        localId: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        code: '',
    };
}

function showFlash(type, message) {
    flash.value = { type, message };
}

function revokeObjectUrl(url) {
    if (url?.startsWith('blob:')) {
        URL.revokeObjectURL(url);
    }
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
        const response = await axios.post('/admin/configuration/planche-couleurs', {
            rows: rows.value.map(({ code }) => ({ code })),
        });

        items.value = sortItems([...items.value, ...response.data.data]);
        rows.value = [createRow()];
        showFlash('success', response.data.message);
    } catch (error) {
        if (error.response?.status === 422) {
            storeErrors.value = error.response.data.errors || {};
            showFlash('danger', error.response.data.message || 'Veuillez corriger les lignes en erreur.');
        } else {
            showFlash('danger', 'Impossible d enregistrer les couleurs.');
        }
    } finally {
        isSaving.value = false;
    }
}

function startEdit(couleur) {
    editingId.value = couleur.id;
    editErrors.value = {};
    revokeObjectUrl(editPreviewUrl.value);
    editForm.value = {
        code: couleur.code,
        image: null,
    };
    editPreviewUrl.value = couleur.image_url || '';
}

function handleEditImageChange(event) {
    const [file] = event.target.files || [];
    revokeObjectUrl(editPreviewUrl.value);
    editForm.value.image = file || null;

    if (file) {
        editPreviewUrl.value = URL.createObjectURL(file);
        return;
    }

    const current = items.value.find((item) => item.id === editingId.value);
    editPreviewUrl.value = current?.image_url || '';
}

function cancelEdit() {
    editingId.value = null;
    editErrors.value = {};
    editForm.value = {
        code: '',
        image: null,
    };
    revokeObjectUrl(editPreviewUrl.value);
    editPreviewUrl.value = '';
}

async function updateCouleur(id) {
    isUpdating.value = true;
    editErrors.value = {};
    showFlash('success', '');

    try {
        const payload = new FormData();
        payload.append('code', editForm.value.code || '');

        if (editForm.value.image) {
            payload.append('image', editForm.value.image);
        }

        const response = await axios.post(`/admin/configuration/planche-couleurs/${id}`, payload);
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
            showFlash('danger', 'Impossible de mettre a jour cette couleur.');
        }
    } finally {
        isUpdating.value = false;
    }
}

async function deleteCouleur(couleur) {
    if (!couleur.can_delete) {
        showFlash('danger', 'Suppression impossible: cette couleur est deja utilisee dans des lignes de planche.');
        return;
    }

    if (!window.confirm(`Supprimer la couleur "${couleur.code}" ?`)) {
        return;
    }

    deletingId.value = couleur.id;
    showFlash('success', '');

    try {
        const response = await axios.delete(`/admin/configuration/planche-couleurs/${couleur.id}`);
        items.value = items.value.filter((item) => item.id !== couleur.id);

        if (editingId.value === couleur.id) {
            cancelEdit();
        }

        showFlash('success', response.data.message);
    } catch (error) {
        showFlash('danger', error.response?.data?.message || 'Impossible de supprimer cette couleur.');
    } finally {
        deletingId.value = null;
    }
}
</script>
