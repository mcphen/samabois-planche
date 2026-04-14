<template>
    <Head :title="`Utilisateurs | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            title="Gestion des utilisateurs"
            :breadcrumbs="breadcrumbs"
        >
            <template #action>
                <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
                    <i class="fa fa-plus"></i> Ajouter utilisateur
                </button>
            </template>
        </BreadcrumbsAndActions>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible">
            {{ $page.props.flash.success }}
            <button type="button" class="close" @click="dismissFlash">&times;</button>
        </div>
        <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible">
            {{ $page.props.flash.error }}
            <button type="button" class="close" @click="dismissFlash">&times;</button>
        </div>

        <div class="card">
            <div class="body">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id">
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <span :class="user.role === 'admin' ? 'badge badge-danger' : 'badge badge-info'">
                                        {{ user.role === 'admin' ? 'Admin' : 'Comptable' }}
                                    </span>
                                </td>
                                <td>{{ new Date(user.created_at).toLocaleDateString('fr-FR') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning mr-1" @click="openEdit(user)">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button
                                        v-if="user.id !== currentUserId"
                                        class="btn btn-sm btn-danger"
                                        @click="confirmDelete(user)"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Ajouter / Éditer -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingUser ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}</h5>
                        <button type="button" class="close" @click="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitForm">
                            <div class="form-group">
                                <label>Nom</label>
                                <input v-model="form.name" type="text" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input v-model="form.email" type="email" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label>Rôle</label>
                                <select v-model="form.role" class="form-control" required>
                                    <option value="admin">Admin</option>
                                    <option value="comptable">Comptable</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ editingUser ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe' }}</label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="form-control"
                                    :required="!editingUser"
                                />
                            </div>
                            <div v-if="errors" class="text-danger mb-2">
                                <div v-for="(msgs, field) in errors" :key="field">
                                    <span v-for="msg in msgs" :key="msg">{{ msg }}</span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                {{ editingUser ? 'Enregistrer' : 'Ajouter' }}
                            </button>
                            <button type="button" class="btn btn-secondary ml-2" @click="closeModal">Annuler</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Confirmation Suppression -->
        <div v-if="showDeleteModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="close" @click="showDeleteModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Supprimer <strong>{{ userToDelete?.name }}</strong> ?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" @click="deleteUser">Supprimer</button>
                        <button class="btn btn-secondary" @click="showDeleteModal = false">Annuler</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const appName = import.meta.env.VITE_APP_NAME;

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Utilisateurs' },
];

const props = defineProps({
    users: Array,
});

const page          = usePage();
const currentUserId = page.props.auth?.user?.id;

const showModal      = ref(false);
const showDeleteModal = ref(false);
const editingUser    = ref(null);
const userToDelete   = ref(null);
const errors         = ref(null);

const emptyForm = () => ({ name: '', email: '', password: '', role: 'admin' });
const form = ref(emptyForm());

function openCreate() {
    editingUser.value = null;
    form.value = emptyForm();
    errors.value = null;
    showModal.value = true;
}

function openEdit(user) {
    editingUser.value = user;
    form.value = { name: user.name, email: user.email, password: '', role: user.role };
    errors.value = null;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingUser.value = null;
}

function submitForm() {
    errors.value = null;
    if (editingUser.value) {
        router.put(`/admin/users/${editingUser.value.id}`, form.value, {
            onError: (e) => { errors.value = e; },
            onSuccess: () => closeModal(),
        });
    } else {
        router.post('/admin/users', form.value, {
            onError: (e) => { errors.value = e; },
            onSuccess: () => closeModal(),
        });
    }
}

function confirmDelete(user) {
    userToDelete.value = user;
    showDeleteModal.value = true;
}

function deleteUser() {
    router.delete(`/admin/users/${userToDelete.value.id}`, {
        onSuccess: () => { showDeleteModal.value = false; userToDelete.value = null; },
    });
}

function dismissFlash() {}
</script>

<style scoped>
.modal {
    background: rgba(0, 0, 0, 0.6);
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
.modal-content {
    animation: fadeIn 0.25s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-16px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
