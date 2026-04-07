<template>
    <div v-if="show" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une couleur</h5>
                    <button type="button" class="close" @click="closeModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="formError" class="alert alert-danger">
                        {{ formError }}
                    </div>

                    <form @submit.prevent="submitForm">
                        <div class="form-group">
                            <label>Code couleur *</label>
                            <input
                                v-model="form.code"
                                type="text"
                                class="form-control"
                                placeholder="Ex: CHENE-MAT"
                            />
                            <small v-if="errors.code" class="text-danger">{{ errors.code[0] }}</small>
                        </div>

                        <div class="form-group">
                            <label>Image</label>
                            <input
                                type="file"
                                class="form-control"
                                accept="image/*"
                                @change="handleImageChange"
                            />
                            <small class="text-muted d-block mt-1">Facultatif</small>
                            <small v-if="errors.image" class="text-danger">{{ errors.image[0] }}</small>
                        </div>

                        <div v-if="previewUrl" class="form-group text-center">
                            <img
                                :src="previewUrl"
                                alt="Apercu couleur"
                                class="img-fluid rounded border"
                                style="max-height:180px;object-fit:cover;"
                            />
                        </div>

                        <div class="mt-3 d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary" @click="closeModal">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-success ml-2" :disabled="submitting">
                                {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    initialCode: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'created']);

const submitting = ref(false);
const formError = ref('');
const errors = ref({});
const form = reactive({
    code: '',
    image: null,
    image_preview_url: '',
});

const previewUrl = computed(() => form.image_preview_url || '');

function revokePreviewUrl() {
    if (form.image_preview_url?.startsWith('blob:')) {
        URL.revokeObjectURL(form.image_preview_url);
    }
}

function resetForm() {
    revokePreviewUrl();
    form.code = props.initialCode || '';
    form.image = null;
    form.image_preview_url = '';
    formError.value = '';
    errors.value = {};
    submitting.value = false;
}

function closeModal() {
    resetForm();
    emit('close');
}

function handleImageChange(event) {
    const [file] = event.target.files || [];

    revokePreviewUrl();
    form.image = file || null;
    form.image_preview_url = file ? URL.createObjectURL(file) : '';
}

function submitForm() {
    submitting.value = true;
    formError.value = '';
    errors.value = {};

    const payload = new FormData();
    payload.append('code', form.code || '');

    if (form.image) {
        payload.append('image', form.image);
    }

    axios.post('/admin/planches/couleurs', payload)
        .then((response) => {
            emit('created', response.data?.data || null);
            closeModal();
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {};
                formError.value = error.response.data.message || 'Veuillez corriger le formulaire.';
                return;
            }

            formError.value = 'Une erreur est survenue pendant l enregistrement.';
        })
        .finally(() => {
            submitting.value = false;
        });
}

watch(
    () => props.show,
    (value) => {
        if (value) {
            resetForm();
        }
    }
);

onBeforeUnmount(() => {
    revokePreviewUrl();
});
</script>
