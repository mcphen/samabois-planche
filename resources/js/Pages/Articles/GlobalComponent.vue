

<template>
    <Head :title="`Vue globale du stock | ${appName}`" />
    <AuthenticatedLayout>
        <!-- Utilisation du composant BreadcrumbsAndActions avec des breadcrumbs dynamiques -->
        <BreadcrumbsAndActions
            :title="'Vue globale du stock'"
            :breadcrumbs="breadcrumbs"
        >
        </BreadcrumbsAndActions>
        <div>
            <div class="card">
                <div class="header">
                    <h4 class="mt-3">🔍 Rechercher </h4>

                    <ArticleItemSearch @search="searchArticleItems" />
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
                            <th>N-CONTRAT</th>
                            <th>ESSENCE</th>

                            <th>N-COLIS</th>
                            <th>L0NGUEUR</th>
                            <th>EPAISSEUR</th>

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
                                <span>{{article.article?.contract_number}}</span>
                            </td>
                            <td>
                                <span>{{article.article?.essence}}</span>
                            </td>

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
                                <span>{{article.nombre_piece}}</span>

                            </td>
                            <td>
                                <span>{{formatVolume(article.volume)}}</span>

                            </td>
                            <td>
                                <span v-if="article.indisponible === 1" class="badge badge-danger">Indisponible</span>
                            </td>
                            <td>
                                <template v-if="article.indisponible !== 1">
                                    <button @click="updateArticleItem(article)"
                                            class="btn btn-warning btn-sm btn_colis me-1">
                                        Modifier
                                    </button>
                                    <button @click="deleteColis(article.id)"
                                            class="btn btn-danger btn-sm btn_colis">
                                        Supprimer
                                    </button>
                                </template>
                                <template v-else>

                                </template>
                            </td>
                        </tr>

                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">Volume Total :</td>
                            <td class="font-weight-bold">{{ formatVolume(totalVolume) }}</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold"> Total des colis:</td>
                            <td class="font-weight-bold">{{articleItems.data.length}}</td>
                            <td></td>
                        </tr>
                        </tfoot>

                    </table>

                    </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import {ref, onMounted, reactive,computed} from 'vue';
import axios from "axios";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";
import Pagination from "@/Components/Pagination.vue";
import ArticleItemSearch from "@/Components/ArticleItemSearch.vue";
import Swal from 'sweetalert2';
const articleItems = ref({ data: [] });



const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des articles' }
];
function searchArticleItems(search) {
    axios.get('/admin/article-items/search', { params: search })
        .then(response => {
            articleItems.value = response.data;

            console.log(response.data);
        });
}

onMounted(() => {
    searchArticleItems();

});

function formatVolume(price) {
    return price ? `${parseFloat(price).toFixed(3)} ` : '0.00';
}

// Propriété calculée pour le volume total
const totalVolume = computed(() => {
    return articleItems.value.data?.reduce((sum, article) => sum + (article.volume || 0), 0) || 0;
});

// Actions similaires à Show.vue (confirmation, feedback) et seulement si disponible
async function deleteColis(id) {
    try {
        const result = await Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: 'Cette action est irréversible !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer !'
        });
        if (!result.isConfirmed) return;

        await axios.delete(`/admin/article-items/${id}/destroy`);
        await Swal.fire('Supprimé !', 'Le colis a été supprimé.', 'success');
        // Rafraîchir la liste
        await searchArticleItems();
    } catch (e) {
        const msg = e?.response?.data?.message || "Une erreur est survenue lors de la suppression.";
        Swal.fire('Erreur', msg, 'error');
    }
}

function updateArticleItem(article) {
    if (article?.indisponible === 1) {
        Swal.fire('Indisponible', 'Cet article est indisponible et ne peut pas être modifié.', 'info');
        return;
    }
    // Rediriger vers la page de l\'article pour édition détaillée (UX cohérente)
    const articleId = article?.article?.id || article?.article_id;
    if (articleId) {
        window.location.href = `/admin/articles/${articleId}`;
        return;
    }
    // Si impossible de déterminer l\'article parent, informer l\'utilisateur
    Swal.fire('Information', "Impossible d'ouvrir la modification car l'article parent est introuvable.", 'info');
}

</script>
<style scoped>

</style>



