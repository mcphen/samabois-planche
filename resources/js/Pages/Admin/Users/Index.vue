

<template>
<div>
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12">
            <h2>Liste des utilisateurs</h2>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#"><i class="fa fa-dashboard"></i></a></li>
                <li class="breadcrumb-item">Liste des utilisateurs</li>

            </ul>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="d-flex flex-row-reverse">
                <div class="page_action">
                    <button type="button" class="btn btn-primary"  @click="showModal = true"><i class="fa fa-plus"></i> Ajouter utilisateur</button>

                </div>
                <div class="p-2 d-flex">

                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">

            <div class="body">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-dark">
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date de création</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td>{{ user.name }}</td>
                            <td>{{ user.email }}</td>
                            <td>{{ new Date(user.created_at).toLocaleDateString() }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal pour Ajouter un Utilisateur -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un Utilisateur</h5>
                        <button type="button" class="close" @click="showModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addUser">
                            <div class="form-group">
                                <label>Nom</label>
                                <input v-model="form.name" type="text" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input v-model="form.email" type="email" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label>Mot de passe</label>
                                <input v-model="form.password" type="password" class="form-control" required />
                            </div>
                            <button type="submit" class="btn btn-success">Ajouter</button>
                            <button type="button" class="btn btn-secondary ml-2" @click="showModal = false">Annuler</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
<script setup>
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { usePage } from '@inertiajs/inertia-vue3';

const props = defineProps({
    users: Array,
});

const showModal = ref(false);
const form = ref({
    name: '',
    email: '',
    password: '',
});

function addUser() {
    Inertia.post('/admin/users', form.value, {
        onSuccess: () => {
            form.value = { name: '', email: '', password: '' };
            showModal.value = false;
        }
    });
}
</script>

<style>
.modal {
    background: rgba(0, 0, 0, 0.6);
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
