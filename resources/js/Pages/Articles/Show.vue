<template>
    <Head :title="`Consultation stock | ${appName}`" />
    <AuthenticatedLayout>
        <!-- Utilisation du composant BreadcrumbsAndActions avec des breadcrumbs dynamiques -->
        <BreadcrumbsAndActions
            :title="'📦 Détails du stock'"
            :breadcrumbs="breadcrumbs"
        >
            <!-- Contenu du slot action : le bouton -->
            <template #action>
                <button type="button" class="btn btn-success btn-sm m-1" @click="showModalAddMontant = true">
                    <i class="fa fa-plus"></i>
                    Montant par m³
                </button>
                <button type="button" class="btn btn-success btn-sm m-1" @click="showModalAddColis = true">
                    <i class="fa fa-plus"></i>
                    Ajouter des colis
                </button>
                <button type="button" class="btn btn-warning btn-sm m-1" @click="showModalEdit = true">
                    <i class="fa fa-edit"></i>
                    Modifier
                </button>
                <button type="button" class="btn btn-danger btn-sm m-1" @click="deleteArticle">
                    <i class="fa fa-trash"></i>
                    Supprimer
                </button>
                <button type="button" class="btn btn-primary btn-sm m-1" @click="goBack">
                    <i class="fa fa-arrow-left"></i>
                    Retour à la Liste
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix row-deck">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card primary-bg">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{ formatTotalPrice(montantPerM3 ) }} FCFA</h3>
                            <span>Montant par  m³ </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card secondary-bg">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{formatVolume(volumeTotalVendu)}} m³</h3>
                            <span>Volume Total vendu</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card bg-info">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{formatTotalPrice(chiffreAffaire)}} FCFA</h3>
                            <span>Chiffre d'affaire</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card bg-info">
                    <div class="body">
                        <div class="p-15 text-light">
                            <h3>{{formatTotalPrice(benefice)}} FCFA</h3>
                            <span>Bénéfice</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="">
            <div class="card">
                <div class="header">
                    <h5>Essence : {{ article.essence }}</h5>
                    <h5>Fournisseur : {{ article.supplier.name }}</h5>
                    <h5><strong>Numéro du Contrat :</strong> {{ article.contract_number }}</h5>
                </div>
                <div class="body">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active show"
                               data-toggle="tab" href="#Home">Liste des colisages ({{articleItems.data.length}})
                            </a>
                        </li>
                        <!--li class="nav-item"><a class="nav-link" data-toggle="tab" href="#Profile">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#Contact">Contact</a></li-->
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane show active" id="Home">
                            <div class="card">
                                <div class="header">
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label>Longueur (Min - Max)</label>
                                            <div class="d-flex">
                                                <input v-model="search.longueur_min" type="number" class="form-control" placeholder="Min" />
                                                <input v-model="search.longueur_max" type="number" class="form-control ms-2" placeholder="Max" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Épaisseur </label>

                                            <select v-model="search.epaisseur_min"
                                                    class="form-control">
                                                <option value="27">27</option>
                                                <option value="40">40</option>
                                                <option value="6">6</option>
                                            </select>

                                            <!--div class="d-flex">
                                                <input v-model="search.epaisseur_min" type="number" class="form-control" placeholder="Min" />
                                                <input v-model="search.epaisseur_max" type="number" class="form-control ms-2" placeholder="Max" />
                                            </div-->
                                        </div>
                                        <div class="col-md-4">
                                            <label>Volume (Min - Max)</label>
                                            <div class="d-flex">
                                                <input v-model="search.volume_min" type="number" class="form-control" placeholder="Min" />
                                                <input v-model="search.volume_max" type="number" class="form-control ms-2" placeholder="Max" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label>Disponibilité</label>
                                            <select v-model="search.indisponible" class="form-control">
                                                <option value="all">Tout voir</option>
                                                <option :value="0">Disponible</option>
                                                <option :value="1">Indisponible</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-primary mt-3" @click="searchArticleItems">🔎 Rechercher</button>
                                    <button type="button" class="btn btn-primary mt-3 ml-3" @click="downloadPDF"><i class="fa fa-print"></i> Imprimer</button>

                                </div>

                                <div class="body table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                        <tr>
                                            <!--th>
                                                <label class="fancy-checkbox">
                                                    <input class="select-all" type="checkbox" name="checkbox">
                                                    <span></span>
                                                </label>
                                            </th-->
                                            <th>N-COLIS</th>
                                            <th>L0NGUEUR</th>
                                            <th>EPAISSEUR</th>
                                            <th>LARGEUR</th>
                                            <th>NMBRE PCS</th>
                                            <th>VOLUME</th>
                                            <th>Statut</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr  v-for="article in articleItems.data">
                                            <!--td style="width: 50px;">
                                                <label class="fancy-checkbox" v-if="article.indisponible==0">
                                                    <input class="checkbox-tick" type="checkbox" name="checkbox">
                                                    <span></span>
                                                </label>
                                            </td-->
                                            <td>
                                                <span>{{article.numero_colis}}</span>
                                            </td>
                                            <td>
                                                <span>{{article.longueur}}</span>
                                            </td>
                                            <td>
                                                <span>{{article.epaisseur}}</span>
                                            </td>
                                            <td>
                                                <span>{{article.largeur}}</span>

                                            </td>
                                            <td>
                                                <span>{{article.nombre_piece}}</span>

                                            </td>
                                            <td>
                                                <span>{{formatVolume(article.volume)}}</span>

                                            </td>
                                            <td>
                                                <span v-if="article.indisponible === 1" class="badge badge-danger">Indisponible</span>
                                            </td>
                                            <td>
                                                <template v-if="article.indisponible === 0">
                                                    <button @click="updateArticleItem(article)"
                                                            class="btn btn-warning btn-sm btn_colis">
                                                        Modifier
                                                    </button>
                                                    <button @click="deleteColis(article.id)"
                                                            class="btn btn-danger btn-sm btn_colis">
                                                        Supprimer
                                                    </button>
                                                </template>
                                                <template v-else>
                                                    <span class="text-muted small">Actions indisponibles</span>
                                                </template>
                                            </td>
                                        </tr>

                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-right font-weight-bold">Volume Total :</td>
                                            <td class="font-weight-bold">{{ formatVolume(totalVolume) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-right font-weight-bold"> Total des colis:</td>
                                            <td class="font-weight-bold">{{articleItems.data.length}}</td>
                                            <td></td>
                                        </tr>
                                        </tfoot>

                                    </table>

                                    <!-- Utilisation du composant Pagination -->
                                    <Pagination :pagination="articleItems" @pageChanged="fetchArticles" />
                                </div>
                            </div>
                        </div>
                        <!--div class="tab-pane" id="Profile">
                            <h6>Profile</h6>
                            <p>Etsy mixtape wayfarers, ethical wes anderson tofu before they sold out mcsweeney's organic lomo retro fanny pack lo-fi farm-to-table readymade. Messenger bag gentrify pitchfork tattooed craft beer, iphone skateboard locavore carles etsy salvia banksy hoodie helvetica. DIY synth PBR banksy irony. Leggings gentrify squid 8-bit cred pitchfork. Williamsburg banh mi whatever gluten-free, carles pitchfork biodiesel fixie.</p>
                        </div>
                        <div class="tab-pane" id="Contact">
                            <h6>Contact</h6>
                            <p>Food truck fixie locavore, accusamus mcsweeney's marfa nulla single-origin coffee squid. Exercitation +1 labore velit, blog sartorial PBR leggings next level wes anderson artisan four loko farm-to-table craft beer twee. Qui photo booth letterpress, commodo enim craft beer mlkshk aliquip jean shorts ullamco ad vinyl cillum PBR. Homo nostrud organic, assumenda labore aesthetic magna delectus mollit. Keytar helvetica VHS.</p>
                        </div-->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal d'ajout de client -->
        <div v-if="showModalEdit" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier un stock</h5>
                        <button type="button" class="close" @click="showModalEdit = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="editArticle">
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

                            <button type="submit" class="btn btn-success">Modifier</button>
                            <button type="button" class="btn btn-secondary ml-2" @click="showModalEdit = false">Annuler</button>
                        </form>
                    </div>
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


        <!-- Modal d'ajout de fournisseur -->
        <div v-if="showModalAddColis" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter colis</h5>
                        <button type="button" class="close" @click="showModalAddColis = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addColis">
                            <div class="form-group">
                                 <textarea
                                     class="form-control" cols="30" rows="10"
                                     ref="textareaRef"
                                     @paste="handlePaste"
                                     placeholder="Collez ici les données copiées depuis Excel"
                                 ></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Ajouter</button>
                            <button type="button" class="btn btn-secondary ml-2" @click="showModalAddColis = false">Annuler</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>



        <!-- Modal d'ajout de fournisseur -->
        <div v-if="showModalAddMontant" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">💰 Montant par m³ </h5>
                        <button type="button" class="close" @click="showModalAddMontant = false">&times;</button>
                    </div>
                    <div class="modal-body">

                        <div class="form-group">
                            <input class="form-control" v-model="pricePerM3" type="number"  />

                        </div>
                        <button @click="updatePricePerM3" type="submit" class="btn btn-success">Enregistrer</button>
                        <button type="button" class="btn btn-secondary ml-2" @click="showModalAddMontant = false">Annuler</button>

                    </div>
                </div>
            </div>
        </div>



        <!-- Modal EDit article item -->
        <div v-if="showModalEditArticleItem" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modification colis</h5>
                        <button type="button" class="close" @click="showModalEditArticleItem = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="updateColis">
                            <div class="form-group">
                                <label>Numéro du colis *</label>
                                <input v-model="newArticle.numero_colis" type="text" class="form-control" required />
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Longueur *</label>
                                    <input v-model="newArticle.longueur" type="text" class="form-control" required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Largeur *</label>
                                    <input v-model="newArticle.largeur" type="text" class="form-control" required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Epaisseur *</label>
                                    <input v-model="newArticle.epaisseur" type="text" class="form-control" required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Nombre de pièce *</label>
                                    <input v-model="newArticle.nombre_piece" type="text" class="form-control" required />
                                </div>
                            </div>


                            <div class="form-group">
                                <label>Volume *</label>
                                <input v-model="newArticle.volume" type="text" class="form-control" required />
                            </div>
                            <button type="submit" class="btn btn-success">Modifier</button>
                            <button type="button" class="btn btn-secondary ml-2" @click="showModalEditArticleItem = false">Annuler</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


    </AuthenticatedLayout>

</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import {ref, onMounted, reactive,computed} from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { usePage } from '@inertiajs/inertia-vue3';
import axios from "axios";
import Pagination from '@/Components/Pagination.vue';
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import Swal from "sweetalert2"; // Importation du composant
const props = defineProps({
    article: Array,
});
const showModalEdit = ref(false);
const showModalAddColis = ref(false);
const showModalAddMontant = ref(false);
const showModal = ref(false);
const pricePerM3 = ref(null);
const montantPerM3 = ref(0);
const volumeTotalVendu = ref(0);
const chiffreAffaire = ref(0);
const benefice = ref(0);
const articleItems = ref({ data: [] });
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des stocks', link: '/admin/articles', icon: 'fa fa-database' },
    { label: '📦 Détails du stock' }
];

const essences = ['Ayous', 'Frake', 'Dibetou', 'Bois Rouge','Dabema'];
const form = ref({
    essence: props.article.essence,
    supplier_id: props.article.supplier_id,
    contract_number: props.article.contract_number,
    excelData: '',
    file: null
});
const textareaRef = ref(null);
const parsedData = ref([]);
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

function updatePricePerM3() {
    axios.post(`/admin/articles/${props.article.id}/update-price`, {price_per_m3:pricePerM3.value})
        .then(response => {

            fetchChiffreAffaire();
            showModalAddMontant.value = false;
        })
        .catch(error => {
            console.error("Erreur lors de l'ajout du client", error);
            alert("Erreur : Le fournisseur existe peut-être déjà !");
        });
}
const fetchChiffreAffaire = async () => {
    try {
        const response = await axios.get(`/admin/articles/${props.article.id}/details-price`);

        // Vérifie la structure des données renvoyées par l'API
        console.log("Données reçues :", response.data);

        montantPerM3.value = response.data.price_per_m3 || 0;
        volumeTotalVendu.value = response.data.total_volume || 0;
        chiffreAffaire.value = response.data.total_revenue || 0; // Vérifie si c'est bien "total_revenue"
        benefice.value = response.data.profit || 0;
    } catch (error) {
        console.error("Erreur lors de la récupération des données :", error);
    }
};

function addColis() {
    const dataToSend = {
        data: parsedData.value.map(row => JSON.stringify(row)),
    };

    Inertia.post('/admin/articles/'+props.article.id+'/add-colis', dataToSend, {
        onSuccess: (page) => {
            Swal.fire('Succès', 'Les colis ont été ajoutés avec succès.', 'success').then(() => {
                const articleId = page.props.article.id;
                Inertia.visit(`/admin/articles/${articleId}`);
            });
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('\n') || 'Une erreur est survenue lors de l\'ajout des colis.';
            Swal.fire('Erreur', msg, 'error');
        }
    });
}

function editArticle() {
    axios.put(`/admin/articles/${props.article.id}/update`, form.value)
        .then(response => {

            location.reload();
        })
        .catch(error => {
            console.error("Erreur lors de l'ajout du client", error);
            alert("Erreur : Le fournisseur existe peut-être déjà !");
        });
}

function deleteArticle() {

    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Tous les articles seront supprimés ",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, annuler",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/admin/articles/${props.article.id}/destroy`)
                .then(response => {
                    location.href = "/admin/articles";
                })
                .catch(error => {
                    Swal.fire("Erreur", error.response.data.error || "Impossible d'annuler la facture.", "error");
                });
        }
    });


}

const suppliers = ref([]);
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

function fetchSuppliers() {
    axios.get('/admin/suppliers/liste-suppliers').then(response => {
        suppliers.value = response.data.data;
    });
}

// Propriété calculée pour le volume total
const totalVolume = computed(() => {
    return articleItems.value.data?.reduce((sum, article) => sum + (article.volume || 0), 0) || 0;
});

const visiblePages = computed(() => {
    const currentPage = articleItems.value.current_page;
    const lastPage = articleItems.value.last_page;
    let startPage = Math.max(currentPage - 1, 1);
    let endPage = Math.min(startPage + 2, lastPage);

    if (endPage - startPage < 2) {
        startPage = Math.max(endPage - 2, 1);
    }

    const pages = [];
    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    return pages;
});

function openModal() {
    showModal.value = true;

    console.log(showModal.value)
}

const showModalEditArticleItem = ref(false);

const newArticle = ref({
    id: '',
    numero_colis: '',
    longueur: '',
    largeur: '',
    epaisseur: '',
    nombre_piece: '',
    volume: '',
    article_id: props.article.id,
});
function updateArticleItem(articleItem){
    newArticle.value.numero_colis = articleItem.numero_colis;
    newArticle.value.longueur = articleItem.longueur;
    newArticle.value.largeur = articleItem.largeur;
    newArticle.value.epaisseur = articleItem.epaisseur;
    newArticle.value.nombre_piece = articleItem.nombre_piece;
    newArticle.value.volume = articleItem.volume;
    newArticle.value.id = articleItem.id;
    showModalEditArticleItem.value = true
}

function updateColis(){
    axios.post('/admin/article-items/'+newArticle.value.id+'/update', newArticle.value)
        .then(response => {

            fetchArticles()
            showModalEditArticleItem.value = false
        })
        .catch(error => {
            const msg = error?.response?.data?.message || error?.response?.data?.error || "Impossible de modifier ce colis.";
            Swal.fire("Erreur", msg, "error");
        });
}

function deleteColis(id) {

    Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Le colis sera supprimé ",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, annuler",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/admin/article-items/${id}/destroy`)
                .then(response => {
                    fetchArticles();
                })
                .catch(error => {
                    const msg = error?.response?.data?.message || error?.response?.data?.error || "Action impossible.";
                    Swal.fire("Erreur", msg, "error");
                });
        }
    });


}

const newSupplier = ref({
    name: '',
    address: '',
    phone: '',
    email: ''
});
function closeModal() {
    showModal.value = false;
    newSupplier.value = { name: '', address: '', phone: '', email: '' };
}

const search = reactive({
    contract_number: '',
    numero_colis: '',
    essence: '',
    longueur_min: '',
    longueur_max: '',
    epaisseur_min: '',
    epaisseur_max: '',
    volume_min: '',
    volume_max: '',
    indisponible: 0,
    article_id:props.article.id
});
function fetchArticles(page = 1) {
    axios.get('/admin/article-items/search',{params:{
            article_id:props.article.id,
            page:page
        }}).then(response => {
        articleItems.value = response.data;
    });
}

function formatVolume(price) {
    return price ? `${parseFloat(price).toFixed(3)} ` : '0.00';
}


function searchArticleItems() {
    axios.get('/admin/article-items/search', { params: search })
        .then(response => {
            articleItems.value = response.data;
        });
}

function goBack() {
    Inertia.visit('/admin/articles');
}

function openEditModal(){

}

function getPageNumber(url) {
    console.log(url);
    if (!url) return 1;
    const match = url.match(/page=(\d+)/);
    console.log(match[1]);
    return match ? parseInt(match[1]) : 1;
}

onMounted(() => {
    fetchArticles();
    fetchSuppliers();
    fetchChiffreAffaire()
});

function formatTotalPrice(price) {
    let priceTotal =  price ? parseInt(price) : 0;
    return  new Intl.NumberFormat('de-DE').format(priceTotal); //.toLocaleString('fr-FR');
}

function downloadPDF(id) {
    const url ="/admin/article-items/generate-pdf-article";
    const params = new URLSearchParams(search);
    const fullUrl = `${url}?${params.toString()}`;
    window.open(fullUrl, '_blank');
}
</script>

<style >
.btn_colis{
    margin :2px !important;
}
</style>
