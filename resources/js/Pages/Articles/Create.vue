<template>
    <Head :title="`Ajouter un stock | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            :title="'🆕 Ajouter un nouveau stock'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button type="button" class="btn btn-primary" @click="goBack">
                    <i class="fa fa-arrow-left"></i>
                    Retour à la Liste
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div>
            <div class="card">
                <div class="body">
                    <form @submit.prevent="addArticle" enctype="multipart/form-data">


                        <div class="form-group">
                            <label>Fournisseur *
                                <button type="button" class="btn btn-success ms-3" @click="openModal">➕ Ajouter fournisseur</button>
                            </label>
                            <select v-model="form.supplier_id" class="form-control" required>
                                <option value="" disabled>Sélectionner un fournisseur</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Essence *</label>
                            <select v-model="form.essence" class="form-control" required>
                                <option value="" disabled>Sélectionner l'essence</option>
                                <option v-for="ess in essences" :key="ess" :value="ess">{{ ess }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Numéro du Contrat *</label>
                            <input v-model="form.contract_number" type="text" class="form-control" required />
                        </div>

                        <!--div class="form-group">
                            <label>Importer le fichier Excel *</label>
                            <input type="file" @change="handleFileUpload" class="form-control" accept=".xlsx,.xls" required />
                        </div-->

                        <div class="form-group">
                             <textarea
                                 class="form-control" cols="30" rows="10"
                                 ref="textareaRef"
                                 @paste="handlePaste"
                                 placeholder="Collez ici les données copiées depuis Excel"
                             ></textarea>

                            <div class="table-responsive">
                                <table class="table  mt-1" v-if="parsedData.length">

                                    <tbody>
                                    <tr v-for="(row, rowIndex) in parsedData" :key="rowIndex">
                                        <td v-for="(cell, cellIndex) in row" :key="cellIndex">{{ cell }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <button type="submit" class="btn btn-success mt-3">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal d'ajout de fournisseur -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un fournisseur</h5>
                        <button type="button" class="close" @click="showModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="storeSupplier">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input v-model="newSupplier.name" type="text" class="form-control" required />
                            </div>
                            <div class="form-group">
                                <label>Adresse</label>
                                <input v-model="newSupplier.address" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input v-model="newSupplier.phone" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input v-model="newSupplier.email" type="email" class="form-control" />
                            </div>
                            <button type="submit" class="btn btn-success">Ajouter</button>
                            <button type="button" class="btn btn-secondary ml-2" @click="showModal = false">Annuler</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
    <div>



    </div>
</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { ref, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';
const suppliers = ref([]);

const textareaRef = ref(null);
const parsedData = ref([]);

const essences = ['Ayous', 'Frake', 'Dibetou', 'Bois Rouge','Dabema'];
import axios from 'axios';
import Swal from 'sweetalert2';
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des stocks', link: '/admin/articles', icon: 'fa fa-database' },
    { label: '🆕 Ajouter un Nouvel Article' }
];
const form = ref({
    essence: '',
    supplier_id: '',
    contract_number: '',
    excelData: '',
    file: null
});

const showModal = ref(false);

const newSupplier = ref({
    name: '',
    address: '',
    phone: '',
    email: ''
});

function storeSupplier() {
    axios.post('/admin/suppliers/store', newSupplier.value)
        .then(response => {

            suppliers.value.push(response.data.supplier);
            form.value.supplier_id = response.data.supplier.id;
            closeModal();
        })
        .catch(error => {
            console.error("Erreur lors de l'ajout du client", error);
            alert("Erreur : Le fournisseur existe peut-être déjà !");
        });
}

const handlePaste = (event) => {
    let clipboardData = event.clipboardData || window.clipboardData;
    let pastedData = clipboardData.getData("Text");

    // Convertir les données en tableau, en gérant \r\n et \n
    let rows = pastedData.split(/\r?\n/).map(row => row.split("\t"));

    // Supprimer les lignes vides et nettoyer chaque cellule
    parsedData.value = rows
        .filter(row => row.some(cell => cell.trim() !== ""))
        .map(row => row.map(cell => cell.trim()));
};

function fetchSuppliers() {
    axios.get('/admin/suppliers/liste-suppliers').then(response => {
        suppliers.value = response.data.data;
    });
}

function handleFileUpload(event) {
    form.value.file = event.target.files[0];
}

function openModal() {
    showModal.value = true;

    console.log(showModal.value)
}

function closeModal() {
    showModal.value = false;
    newSupplier.value = { name: '', address: '', phone: '', email: '' };
}


function addArticle() {
    const dataToSend = {
        essence: form.value.essence,
        supplier_id: form.value.supplier_id,
        contract_number: form.value.contract_number,
        data: parsedData.value.map(row => JSON.stringify(row)),
        file: form.value.file
    };

    Inertia.post('/admin/articles/store', dataToSend, {
        onSuccess: (page) => {
            Swal.fire('Succès', 'Le stock a été enregistré avec succès.', 'success').then(() => {
                const articleId = page.props.article.id;
                Inertia.visit(`/admin/articles/${articleId}`);
            });
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('\n') || 'Une erreur est survenue lors de l\'enregistrement.';
            Swal.fire('Erreur', msg, 'error');
        }
    });
}

function goBack() {
    Inertia.visit('/admin/articles');
}

onMounted(() => {

    fetchSuppliers();
});

</script>
<style scoped>

</style>



