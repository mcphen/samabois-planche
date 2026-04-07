<template>
    <Head :title="`Historique de rétablissement de comptabilité | ${appName}`" />

    <AuthenticatedLayout>
        <div>
            <BreadcrumbsAndActions
                title="📊 Historique de rétablissement de comptabilité"
                :breadcrumbs="breadcrumbs"
            >
            </BreadcrumbsAndActions>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>📜 Historique des rétablissements de comptabilité</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Utilisateur</th>
                                    <th>Montant Facture</th>
                                    <th>Montant Payé</th>
                                    <th>Montant Restant</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in history.data" :key="entry.id">
                                    <td>{{ formatDate(entry.created_at) }}</td>
                                    <td>
                                        <a :href="'/admin/clients/' + entry.client.id + '/consultation'">
                                            {{ entry.client.name }}
                                        </a>
                                    </td>
                                    <td>{{ entry.user.name }}</td>
                                    <td>{{ formatTotalPrice(entry.amount_due) }} F</td>
                                    <td>{{ formatTotalPrice(entry.amount_payment) }} F</td>
                                    <td>{{ formatTotalPrice(entry.amount_solde) }} F</td>
                                    <td>{{ entry.notes }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item" :class="{ disabled: !history.prev_page_url }">
                                        <a class="page-link" href="#" @click.prevent="changePage(history.current_page - 1)">Précédent</a>
                                    </li>
                                    <li v-for="page in getPageNumbers()" :key="page" class="page-item" :class="{ active: page === history.current_page }">
                                        <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                                    </li>
                                    <li class="page-item" :class="{ disabled: !history.next_page_url }">
                                        <a class="page-link" href="#" @click.prevent="changePage(history.current_page + 1)">Suivant</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import dayjs from 'dayjs';
import { ref, onMounted } from 'vue';
import { Inertia } from "@inertiajs/inertia";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BreadcrumbsAndActions from "@/Components/Nav/BreadcrumbsAndActions.vue";

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Gestion des Clients', link: '/admin/clients', icon: 'fa fa-user-plus' },
    { label: 'Historique de rétablissement de comptabilité' }
];

const props = defineProps({
    history: Object,
});

function formatDate(date) {
    return dayjs(date).format('DD/MM/YYYY HH:mm:ss');
}

function formatTotalPrice(price) {
    let priceTotal = price ? parseInt(price) : 0;
    return new Intl.NumberFormat('de-DE').format(priceTotal);
}

function changePage(page) {
    if (page < 1 || page > props.history.last_page) return;

    Inertia.get(route('clients.accounting-history'), { page }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function getPageNumbers() {
    const pages = [];
    const currentPage = props.history.current_page;
    const lastPage = props.history.last_page;

    // Always show first page, last page, current page, and 1 page before and after current
    const pagesToShow = new Set([1, lastPage, currentPage, currentPage - 1, currentPage + 1]);

    // Filter out invalid pages
    for (let page of pagesToShow) {
        if (page >= 1 && page <= lastPage) {
            pages.push(page);
        }
    }

    // Sort pages
    return [...pages].sort((a, b) => a - b);
}
</script>



