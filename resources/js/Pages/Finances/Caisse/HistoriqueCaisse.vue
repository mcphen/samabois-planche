<template>
    <Head :title="`💰 Historique de la caisse | ${appName}`" />
    <AuthenticatedLayout>
        <!-- Utilisation du composant BreadcrumbsAndActions avec des breadcrumbs dynamiques -->
        <BreadcrumbsAndActions
            :title="'💰 Historique de la caisse'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button class="btn btn-danger btn-sm m-1" @click="openSortieModal">➖ Enregistrer une Sortie</button>
                <button class="btn btn-primary btn-sm m-1" @click="openFiltreModal"> Imprimer caisse</button>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix row-deck">
            <div class="col-lg-4 col-md-6 col-sm-6">
                <div class="card primary-bg">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{ formatTotalPrice(solde) }} FCFA</h3>
                            <span>Solde actuel</span>
                        </div>
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
                                <th>Date</th>
                                <th>Type</th>
                                <th>Objet</th>
                                <th>Montant</th>
                                <th>Solde</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="c in caisses" :key="c.id" :class="{'text-decoration-line-through text-muted': c.transfer_status === 'annulé', 'text-italic text-muted': c.transfer_status === 'corrigé'}">
                                <td>{{ formatDate(c.date) }}</td>
                                <td>
                                        <span class="badge" :class="c.type==='sortie' ? 'badge-danger' : 'badge-success'">
                                           {{ c.type }}
                                        </span>
                                </td>
                                <td>
                                    <div v-if="c.type==='sortie'">
                                    {{ c.objet }}
                                    <div v-if="c.caisse_transfer_id && c.transfer_dest_name" class="small text-muted">
                                        Vers: {{ c.transfer_dest_name }}
                                    </div>
                                    <span v-if="c.transfer_status === 'annulé'" class="badge badge-secondary ms-1">Annulé</span>
                                    <span v-if="c.transfer_status === 'corrigé'" class="badge badge-warning ms-1">Corrigé</span>
                                    </div>
                                    <div v-else>
                                        {{ c.client ? 'Paiement : ' + c.client : c.objet }}
                                        <div v-if="c.caisse_transfer_id && c.transfer_source_name" class="small text-muted">
                                            De: {{ c.transfer_source_name }}
                                        </div>
                                        <span v-if="c.transfer_status === 'annulé'" class="badge badge-secondary ms-1">Annulé</span>
                                        <span v-if="c.transfer_status === 'corrigé'" class="badge badge-warning ms-1">Corrigé</span>
                                    </div>
                                </td>
                                <td>{{ formatTotalPrice(c.amount) }}</td>
                                <td>{{ formatTotalPrice(c.cumul) }}</td>
                                <td>
                                    <div v-if="c.type==='sortie' && c.transfer_status !== 'annulé' && c.transfer_status !== 'corrigé'">
                                        <button class="btn btn-sm btn-warning m-1" @click="openEditSortieModal(c)">
                                            <i class="fa fa-edit"></i> Modifier
                                        </button>
                                        <button class="btn btn-sm btn-danger m-1" @click="deleteSortie(c.id)">
                                            <i class="fa fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                    <div v-else-if="c.transfer_status === 'annulé' || c.transfer_status === 'corrigé'">
                                        <span class="badge" :class="c.transfer_status === 'annulé' ? 'badge-secondary' : 'badge-warning'">
                                            {{ c.transfer_status }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr id="bottom">
                                <td><strong>Solde actuel</strong></td>
                                <td colspan="5"><strong>{{ formatTotalPrice(solde) }} FCFA</strong></td>
                            </tr>
                            </tfoot>
                        </table>
                        <!-- Pagination -->

                        <Pagination :pagination="paginationData" @pageChanged="fetchCaisse" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ajouter/Modifier une Sortie -->
        <div v-if="showSortieModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ editSortie ? 'Modifier la Sortie' : '➖ Nouvelle Sortie' }}
                        </h5>
                        <button type="button" class="close" @click="closeSortieModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitSortie">
                            <div class="form-group">
                                <label>Objet</label>
                                <input v-model="sortie.objet" type="text" required class="form-control" />
                            </div>
                            <div class="form-group mt-1">
                                <label>Montant</label>
                                <input v-model="sortie.amount" type="number" required class="form-control" />
                            </div>
                            <div class="form-group mt-1">
                                <label>Date</label>
                                <input v-model="sortie.date" type="date" required class="form-control" />
                            </div>
                            <div class="form-group mt-1">
                                <label>Description</label>
                                <textarea v-model="sortie.description" class="form-control"></textarea>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-success m-1" type="submit" :disabled="disabled">
                                    {{ editSortie ? 'Modifier' : 'Enregistrer' }}
                                </button>
                                <button class="btn btn-danger m-1" @click="closeSortieModal">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Historique de la Caisse -->
        <div v-if="showFiltreModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">📜 Historique de la Caisse</h5>
                        <button type="button" class="close" @click="showFiltreModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Date de début :</label>
                            <input type="date" v-model="filters.start_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Date de fin :</label>
                            <input type="date" v-model="filters.end_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Type :</label>
                            <select v-model="filters.type" class="form-control">
                                <option value="">Tous</option>
                                <option value="entrée">Entrée</option>
                                <option value="sortie">Sortie</option>
                            </select>
                        </div>
                        <button @click="downloadPDF" class="btn btn-primary">Imprimer PDF</button>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import { onMounted, ref, computed } from "vue";
import axios from "axios";
import dayjs from "dayjs";
import Swal from "sweetalert2";
import Pagination from "@/Components/Pagination.vue";

const breadcrumbs = [
    { label: "Tableau de bord", link: "/", icon: "fa fa-dashboard" },
    { label: "💰 Historique de la caisse" }
];

const solde = ref(0);
const caisses = ref([]);
const allCaisses = ref([]); // Store all data from backend
const currentPage = ref(1);
const perPage = ref(25); // 25 items per page
const showSortieModal = ref(false);
const showFiltreModal = ref(false);
const disabled = ref(false);

// Pour différencier ajout vs modification de sortie
const editSortie = ref(false);
const currentSortieId = ref(null);

const sortie = ref({
    objet: "",
    description: "",
    amount: "",
    date: ""
});

const filters = ref({
    start_date: "",
    end_date: "",
    type: ""
});

// Formats
function formatTotalPrice(price) {
    let priceTotal = price ? parseInt(price) : 0;
    return new Intl.NumberFormat("de-DE").format(priceTotal);
}

function formatDate(date) {
    return dayjs(date).format("DD/MM/YYYY");
}

// Ouvre le modal en mode ajout
function openSortieModal() {
    editSortie.value = false;
    // Réinitialiser le formulaire
    sortie.value = { objet: "", description: "", amount: "", date: "" };
    showSortieModal.value = true;
}

// Ouvre le modal en mode modification et charge les données existantes
function openEditSortieModal(caisse) {
    editSortie.value = true;
    currentSortieId.value = caisse.id;
    // Charger les valeurs existantes dans le formulaire
    sortie.value = {
        objet: caisse.objet,
        description: caisse.description || "",
        amount: caisse.amount,
        date: dayjs(caisse.date).format("YYYY-MM-DD")
    };
    showSortieModal.value = true;
}

// Ferme le modal sortie
function closeSortieModal() {
    showSortieModal.value = false;
    editSortie.value = false;
    currentSortieId.value = null;
}

// Fonction pour soumettre le formulaire (ajout ou modification)
const submitSortie = async () => {
    disabled.value = true;
    try {
        if (editSortie.value && currentSortieId.value) {
            // Mise à jour
            await axios.post(`/admin/finances/caisse/sortie/${currentSortieId.value}/update`, sortie.value);
            Swal.fire("Modifié !", "La sortie a bien été modifiée.", "success");
        } else {
            // Création
            await axios.post("/admin/finances/caisse/sortie", sortie.value);
            Swal.fire("Enregistré !", "La sortie a bien été enregistrée.", "success");
        }
        await getSolde();
        await fetchCaisse();
        closeSortieModal();
    } catch (error) {
        Swal.fire("Erreur", "Une erreur est survenue lors de l'opération.", "error");
    } finally {
        disabled.value = false;
    }
};

// Suppression d'une sortie avec confirmation swal
function deleteSortie(id) {
    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Cette action est irréversible !",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Oui, supprimer !"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/admin/finances/caisse/sortie/${id}`);
                Swal.fire("Supprimé !", "La sortie a été supprimée.", "success");
                await getSolde();
                await fetchCaisse();
            } catch (error) {
                Swal.fire("Erreur", "Une erreur est survenue lors de la suppression.", "error");
            }
        }
    });
}

const getSolde = async () => {
    const { data } = await axios.get("/admin/finances/caisse/solde");
    solde.value = data.solde;
};

const fetchCaisse = async (page = null) => {
    try {
        const { data } = await axios.get("/admin/finances/caisse/fetch-caisse-old");
        allCaisses.value = data;

        // If page is provided, use it, otherwise use the last page
        if (page) {
            currentPage.value = page;
        } else {
            // Calculate total pages and set to last page on initial load
            const totalPages = Math.ceil(allCaisses.value.length / perPage.value);
            currentPage.value = totalPages > 0 ? totalPages : 1;
        }

        // Apply pagination to the data
        const startIndex = (currentPage.value - 1) * perPage.value;
        const endIndex = startIndex + perPage.value;
        caisses.value = allCaisses.value.slice(startIndex, endIndex);

        console.log('Current page:', currentPage.value, 'Total items:', allCaisses.value.length);
    } catch (error) {
        console.error('Error fetching caisse data:', error);
    }
};

// Create pagination object for the Pagination component
const paginationData = computed(() => {
    const totalItems = allCaisses.value.length;
    const totalPages = Math.ceil(totalItems / perPage.value);

    return {
        current_page: currentPage.value,
        last_page: totalPages,
        prev_page_url: currentPage.value > 1 ? '#' : null,
        next_page_url: currentPage.value < totalPages ? '#' : null,
        links: Array.from({ length: totalPages }, (_, i) => ({
            url: '#',
            label: i + 1,
            active: i + 1 === currentPage.value
        }))
    };
});

const downloadPDF = () => {
    let params = new URLSearchParams(filters.value).toString();
    window.open(`/admin/finances/caisse/historique/pdf?${params}`, "_blank");
    showFiltreModal.value = false;
};

function openFiltreModal() {
    showFiltreModal.value = true;
}

onMounted(() => {
    getSolde();
    fetchCaisse();
    const element = document.getElementById("bottom");
    if (element) {
        element.scrollIntoView({ behavior: "smooth" });
    }
});
</script>

<style scoped>
/* Ajoutez vos styles ici */
</style>



