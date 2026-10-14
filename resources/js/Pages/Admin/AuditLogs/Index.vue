<template>
    <Head :title="`Journal des modifications | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions title="Journal des modifications" :breadcrumbs="breadcrumbs" />

        <div class="card">
            <div class="body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label class="small mb-1">Utilisateur</label>
                        <select v-model="filters.user_id" class="form-control form-control-sm">
                            <option value="">Tous</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ roleLabel(u.role) }})</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small mb-1">Rôle</label>
                        <select v-model="filters.role" class="form-control form-control-sm">
                            <option value="">Tous</option>
                            <option value="admin">Admin</option>
                            <option value="comptable">Comptable</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small mb-1">Action</label>
                        <select v-model="filters.event" class="form-control form-control-sm">
                            <option value="">Toutes</option>
                            <option v-for="(label, key) in eventLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label class="small mb-1">Élément</label>
                        <select v-model="filters.type" class="form-control form-control-sm">
                            <option value="">Tous</option>
                            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small mb-1">Recherche</label>
                        <input v-model="filters.search" type="text" class="form-control form-control-sm" placeholder="Code, n°, nom..." />
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small mb-1">Du</label>
                        <input v-model="filters.date_from" type="date" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small mb-1">Au</label>
                        <input v-model="filters.date_to" type="date" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="resetFilters">
                            <i class="fa fa-times mr-1"></i> Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="body table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th style="white-space:nowrap;">Date / heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Élément</th>
                            <th>Détails (avant → après)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="5" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin"></i> Chargement...</td>
                        </tr>
                        <tr v-else-if="!logs.length">
                            <td colspan="5" class="text-center py-4 text-muted">Aucune modification enregistrée.</td>
                        </tr>
                        <template v-else>
                        <tr v-for="log in logs" :key="log.id">
                            <td style="white-space:nowrap;">{{ log.date }}</td>
                            <td>
                                {{ log.user_name }}
                                <span v-if="log.user_role" class="badge ml-1" :class="log.user_role === 'admin' ? 'badge-danger' : 'badge-info'">
                                    {{ roleLabel(log.user_role) }}
                                </span>
                                <div v-if="log.ip" class="small text-muted">IP {{ log.ip }}</div>
                            </td>
                            <td>
                                <span class="badge" :class="eventClasses[log.event] || 'badge-secondary'">
                                    {{ eventLabels[log.event] || log.event }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ log.type_label }}</strong>
                                <span class="text-muted"> #{{ log.auditable_id }}</span>
                                <div v-if="log.auditable_label" class="small">{{ log.auditable_label }}</div>
                            </td>
                            <td class="small">
                                <div v-for="row in changeRows(log)" :key="row.field">
                                    <span class="text-muted">{{ fieldLabel(row.field) }} :</span>
                                    <template v-if="log.event === 'updated'">
                                        <span class="text-danger" style="text-decoration:line-through;">{{ formatValue(row.old) }}</span>
                                        →
                                        <span class="text-success font-weight-bold">{{ formatValue(row.new) }}</span>
                                    </template>
                                    <span v-else>{{ formatValue(log.event === 'deleted' ? row.old : row.new) }}</span>
                                </div>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                </table>

                <div v-if="pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                    <span class="small text-muted">
                        Page {{ pagination.current_page }} / {{ pagination.last_page }} — {{ pagination.total }} entrées
                    </span>
                    <div>
                        <button class="btn btn-sm btn-outline-primary mr-1" :disabled="pagination.current_page <= 1" @click="fetchLogs(pagination.current_page - 1)">
                            <i class="fa fa-chevron-left"></i> Précédent
                        </button>
                        <button class="btn btn-sm btn-outline-primary" :disabled="pagination.current_page >= pagination.last_page" @click="fetchLogs(pagination.current_page + 1)">
                            Suivant <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

defineProps({
    users: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: 'Journal des modifications' },
];

const eventLabels = {
    created: 'Création',
    updated: 'Modification',
    deleted: 'Suppression',
    restored: 'Restauration',
};

const eventClasses = {
    created: 'badge-success',
    updated: 'badge-warning',
    deleted: 'badge-danger',
    restored: 'badge-info',
};

const fieldLabels = {
    numero: 'Numéro',
    numero_bl: 'N° facture',
    supplier_id: 'Fournisseur (id)',
    contrat_id: 'Contrat (id)',
    planche_id: 'Planche (id)',
    planche_couleur_id: 'Couleur (id)',
    planche_detail_id: 'Ligne de stock (id)',
    planche_bon_livraison_id: 'Facture (id)',
    client_id: 'Client (id)',
    categorie: 'Catégorie',
    epaisseur: 'Épaisseur',
    quantite_prevue: 'Quantité prévue',
    quantite_livree: 'Quantité livrée',
    prix_unitaire: 'Prix unitaire',
    prix_total: 'Prix total',
    prix_de_revient: 'Prix de revient',
    prix: 'Prix',
    montant: 'Montant',
    montant_solde: 'Montant soldé',
    amount: 'Montant',
    type: 'Type',
    isSolde: 'Ajustement de solde',
    status: 'Statut',
    statut: 'Statut',
    date: 'Date',
    date_livraison: 'Date de livraison',
    transaction_date: 'Date',
    code: 'Code',
    image_path: 'Image',
    intitule: 'Intitulé',
    name: 'Nom',
    email: 'Email',
    phone: 'Téléphone',
    address: 'Adresse',
    role: 'Rôle',
};

const logs = ref([]);
const loading = ref(false);
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });

const emptyFilters = () => ({ user_id: '', role: '', event: '', type: '', search: '', date_from: '', date_to: '' });
const filters = reactive(emptyFilters());

function roleLabel(role) {
    return role === 'admin' ? 'Admin' : role === 'comptable' ? 'Comptable' : role;
}

function fieldLabel(field) {
    return fieldLabels[field] || field;
}

function formatValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Oui' : 'Non';
    if (typeof value === 'object') return JSON.stringify(value);
    return value;
}

function changeRows(log) {
    const source = log.event === 'deleted' ? log.old_values : log.new_values;
    return Object.keys(source || {})
        .filter((field) => !['id', 'uuid', 'remote_id', 'deleted_at', 'slug', 'slug_name'].includes(field) || log.event === 'updated')
        .map((field) => ({ field, old: log.old_values?.[field], new: log.new_values?.[field] }));
}

function fetchLogs(page = 1) {
    loading.value = true;
    const params = { page };
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null) params[key] = value;
    });

    axios.get('/admin/journal/list', { params })
        .then(({ data }) => {
            logs.value = data.data;
            pagination.current_page = data.current_page;
            pagination.last_page = data.last_page;
            pagination.total = data.total;
        })
        .finally(() => {
            loading.value = false;
        });
}

function resetFilters() {
    Object.assign(filters, emptyFilters());
}

let debounce = null;
watch(filters, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => fetchLogs(1), 300);
});

onMounted(() => fetchLogs());
</script>
