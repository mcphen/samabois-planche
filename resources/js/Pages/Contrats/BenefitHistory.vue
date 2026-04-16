<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="mb-0"><i class="fa fa-history mr-2 text-primary"></i>Historique des bénéfices</h6>
            <button class="btn btn-sm btn-outline-primary" :disabled="loading" @click="fetchHistory">
                <i class="fa fa-sync mr-1"></i>Rafraîchir
            </button>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>

            <div v-else>
                <div v-if="error" class="alert alert-danger mb-3">
                    {{ error }}
                </div>

                <div v-if="!history.length" class="alert alert-warning mb-0 text-center">
                    <i class="fa fa-exclamation-triangle mr-1"></i>
                    Aucun historique de bénéfices trouvé.
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Utilisateur</th>
                                <th>Détail</th>
                                <th>Bon</th>
                                <th>Ancienne valeur</th>
                                <th>Nouvelle valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in history" :key="entry.id">
                                <td>{{ formatDate(entry.created_at) }}</td>
                                <td>{{ actionLabel(entry.action) }}</td>
                                <td>{{ entry.user || '-' }}</td>
                                <td>
                                    <span v-if="entry.planche_detail">
                                        {{ entry.planche_detail.code_couleur || '-' }}
                                        {{ entry.planche_detail.categorie || '-' }}
                                        {{ formatDecimal(entry.planche_detail.epaisseur) }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td>{{ entry.bon_livraison?.numero_bl || '-' }}</td>
                                <td v-html="renderData(entry.old_data)"></td>
                                <td v-html="renderData(entry.new_data)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    contratId: { type: Number, required: true },
});

const history = ref([]);
const loading = ref(false);
const error = ref('');

const actionLabels = {
    detail_prix_de_revient_set: 'Prix de revient renseigné',
    detail_prix_de_revient_changed: 'Prix de revient modifié',
    detail_quantite_prevue_changed: 'Quantité prévue modifiée',
    bon_livraison_created: 'Bon de livraison créé',
    bon_livraison_updated: 'Bon de livraison mis à jour',
};

const fetchHistory = async () => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get(`/admin/contrats/${props.contratId}/benefit-history`);
        history.value = data;
    } catch (e) {
        console.error(e);
        error.value = 'Impossible de charger l historique des bénéfices.';
    } finally {
        loading.value = false;
    }
};

const actionLabel = (action) => actionLabels[action] || action.replace(/_/g, ' ');

const formatDecimal = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    return Number(value).toFixed(2);
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    const num = Math.round(Number(value || 0));
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' CFA';
};

const formatDate = (value) => value || '-';

const renderData = (data) => {
    if (!data || typeof data !== 'object') return '-';

    const parts = [];
    const entries = Object.entries(data);

    entries.forEach(([key, value]) => {
        if (key === 'lines' && Array.isArray(value)) {
            parts.push(`Lignes : ${value.length}`);
            return;
        }

        if (['prix_de_revient', 'total_prix_total', 'profit', 'montant'].includes(key)) {
            parts.push(`${labelFor(key)} : ${formatCurrency(value)}`);
            return;
        }

        if (['quantite_livree', 'quantite_prevue'].includes(key)) {
            parts.push(`${labelFor(key)} : ${value}`);
            return;
        }

        parts.push(`${labelFor(key)} : ${String(value)}`);
    });

    return parts.length ? parts.join('<br>') : '-';
};

const labelFor = (key) => {
    return {
        prix_de_revient: 'Prix de revient',
        total_prix_total: 'Total vendu',
        profit: 'Bénéfice',
        montant: 'Montant',
        quantite_livree: 'Qté livrée',
        quantite_prevue: 'Qté prévue',
        lines: 'Lignes',
    }[key] || key.replace(/_/g, ' ');
};

onMounted(fetchHistory);
</script>

<style scoped>
.table td {
    vertical-align: middle;
}
</style>
