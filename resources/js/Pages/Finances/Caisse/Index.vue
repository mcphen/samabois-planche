<template>
    <Head :title="`💼 Caisses | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="'💼 Gestion des caisses'" :breadcrumbs="breadcrumbs">
            <template #action>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="text-muted small">Total caisses</div>
                        <div class="h3 m-0 fw-bold text-primary">{{ formatPrice(totalBalance) }}</div>
                    </div>
                    <button class="btn btn-primary btn-sm" @click="openCreate">➕ Nouvelle caisse</button>
                </div>
            </template>
        </BreadcrumbsAndActions>

        <div class="card">
            <div class="body">
                <div class="row g-2 mb-3 align-items-center filter-bar p-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                            <input v-model="filters.search" type="text" class="form-control" placeholder="Rechercher (nom, type, devise)" @keyup.enter="fetchCaisses" />
                            <button v-if="filters.search" class="btn btn-outline-secondary" @click="clearSearch" title="Effacer">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select v-model="filters.active" class="form-control" @change="fetchCaisses">
                            <option :value="''">Toutes</option>
                            <option :value="true">Actives</option>
                            <option :value="false">Inactives</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" @click="fetchCaisses"><i class="fa fa-filter me-1"></i>Filtrer</button>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="btn-group view-toggle">
                            <button class="btn btn-light" :class="{ active: viewMode==='card' }" @click="viewMode='card'">
                                <i class="fa fa-th-large"></i> Cartes
                            </button>
                            <button class="btn btn-light" :class="{ active: viewMode==='table' }" @click="viewMode='table'">
                                <i class="fa fa-table"></i> Tableau
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vue en cartes (par défaut) -->
                <div v-if="viewMode==='card'">
                    <div class="row">
                        <!-- Loading skeletons -->
                        <template v-if="loading">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3" v-for="n in 6" :key="'sk'+n">
                                <div class="card caisse-card h-100 skeleton">
                                    <div class="body d-flex align-items-center">
                                        <div class="me-3 icon-wrap sk-box"></div>
                                        <div class="flex-fill">
                                            <div class="sk-line w-75 mb-2"></div>
                                            <div class="sk-line w-50 mb-1"></div>
                                            <div class="sk-line w-25"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Cards -->
                        <template v-else>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3" v-for="c in caisses" :key="c.id">
                                <div class="card caisse-card h-100 hoverable" role="button" @click="goToDetail(c)">
                                    <div class="body d-flex align-items-center">
                                        <div class="me-3 icon-wrap" :class="iconBgClass(c.type)">
                                            <i :class="iconClass(c.type)"></i>
                                        </div>
                                        <div class="flex-fill">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="mb-1 text-truncate" :title="c.name">{{ c.name }}</h6>
                                                <span class="badge badge-pill" :class="c.active ? 'badge-success' : 'badge-secondary'"><small>{{ c.active ? 'ACTIVE' : 'INACTIVE' }}</small></span>
                                            </div>
                                            <div class="text-muted small">Type: {{ labelType(c.type) }} • Devise: {{ c.currency_code || '-' }}</div>
                                            <div class="fw-bold mt-1">Solde: {{ formatPrice(c.balance_with_initial ?? 0) }}</div>
                                        </div>
                                    </div>
                                    <div class="body pt-0">
                                        <div class="d-flex justify-content-end gap-1 mt-2 actions" @click.stop>
                                            <button class="btn btn-xs btn-warning" @click.stop="openEdit(c)" title="Modifier"><i class="fa fa-edit"></i></button>
                                            <button class="btn btn-xs" :class="c.active ? 'btn-outline-secondary' : 'btn-outline-success'" @click.stop="toggleActive(c)" :title="c.active ? 'Désactiver' : 'Activer'">
                                                <i :class="c.active ? 'fa fa-ban' : 'fa fa-check'"></i>
                                            </button>
                                            <button class="btn btn-xs btn-danger" @click.stop="confirmDelete(c)" title="Supprimer"><i class="fa fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!loading && caisses.length===0" class="col-12 text-center text-muted py-4">
                                <i class="fa fa-wallet fa-2x d-block mb-2"></i>
                                Aucune caisse trouvée
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Vue en tableau -->
                <div v-else class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Devise</th>
                            <th>Solde</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="c in caisses" :key="c.id">
                            <td>{{ c.id }}</td>
                            <td class="text-primary" role="button" @click="goToDetail(c)"><i class="fa fa-external-link-alt me-1"></i>{{ c.name }}</td>
                            <td>{{ labelType(c.type) }}</td>
                            <td>{{ c.currency_code || '-' }}</td>
                            <td>{{ formatPrice(c.balance_with_initial ?? 0) }}</td>
                            <td>
                                <span class="badge" :class="c.active ? 'badge-success' : 'badge-secondary'">{{ c.active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-warning me-1" @click="openEdit(c)"><i class="fa fa-edit" /> Modifier</button>
                                <button class="btn btn-sm me-1" :class="c.active ? 'btn-outline-secondary' : 'btn-outline-success'" @click="toggleActive(c)">
                                    <i :class="c.active ? 'fa fa-ban' : 'fa fa-check'" /> {{ c.active ? 'Désactiver' : 'Activer' }}
                                </button>
                                <button class="btn btn-sm btn-danger" @click="confirmDelete(c)"><i class="fa fa-trash" /> Supprimer</button>
                            </td>
                        </tr>
                        <tr v-if="!loading && caisses.length === 0">
                            <td colspan="7" class="text-center text-muted">Aucune caisse trouvée</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal création/édition -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ isEdit ? 'Modifier la caisse' : 'Nouvelle caisse' }}</h5>
                        <button type="button" class="close" @click="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submit">
                            <div class="form-group mb-2">
                                <label>Nom</label>
                                <input v-model="form.name" type="text" class="form-control" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Type</label>
                                <select v-model="form.type" class="form-control">
                                    <option :value="''">Sélectionner un type</option>
                                    <option value="especes">Espèces</option>
                                    <option value="banque">Banque</option>
                                    <option value="mobile_money">Mobile money</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label>Devise</label>
                                <select v-model="form.currency_code" class="form-control">
                                    <option value="XOF">XOF (par défaut)</option>
                                    <option value="XAF">XAF</option>
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label>Solde</label>
                                <input v-model.number="form.initial_balance" type="number" min="0" class="form-control" />
                            </div>
                            <div class="form-group mb-2">
                                <label>
                                    <input type="checkbox" v-model="form.active" /> Active
                                </label>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-light me-2" @click="closeModal">Annuler</button>
                                <button type="submit" class="btn btn-primary" :disabled="submitting">
                                    {{ submitting ? 'En cours...' : (isEdit ? 'Enregistrer' : 'Créer') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>

<script setup>
const appName = import.meta.env.VITE_APP_NAME;
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const breadcrumbs = [
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: '💼 Caisses' },
];

const caisses = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEdit = ref(false);
const submitting = ref(false);
const currentId = ref(null);
const viewMode = ref('card');

const filters = ref({
    search: '',
    active: '',
});

const form = ref({
    name: '',
    type: '',
    currency_code: 'XOF',
    initial_balance: 0,
    active: true,
});

function formatPrice(n) {
    const v = n ? Number(n) : 0;
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(v);
}

// Total des soldes (balance_with_initial) de toutes les caisses
const totalBalance = computed(() => {
    try {
        return (caisses.value || []).reduce((sum, c) => {
            const val = c && c.balance_with_initial != null ? Number(c.balance_with_initial) : 0;
            return sum + (isNaN(val) ? 0 : val);
        }, 0);
    } catch (_) {
        return 0;
    }
});

function labelType(t) {
    if (t === 'especes') return 'Espèces';
    if (t === 'banque') return 'Banque';
    if (t === 'mobile_money') return 'Mobile money';
    return t || '-';
}

function iconClass(t) {
    if (t === 'especes') return 'fa fa-money-bill-wave';
    if (t === 'banque') return 'fa fa-building-columns';
    if (t === 'mobile_money') return 'fa fa-mobile-alt';
    return 'fa fa-wallet';
}

function iconBgClass(t) {
    if (t === 'especes') return 'bg-success text-white';
    if (t === 'banque') return 'bg-primary text-white';
    if (t === 'mobile_money') return 'bg-warning text-dark';
    return 'bg-secondary text-white';
}

function goToDetail(c) {
    router.visit(`/admin/finances/${c.id}/caisses`);
}

async function fetchCaisses() {
    loading.value = true;
    try {
        const params = {};
        if (filters.value.search) params.search = filters.value.search;
        if (filters.value.active !== '') params.active = filters.value.active;
        const { data } = await axios.get('/admin/finances/caisses/listes', { params });
        caisses.value = data;
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', "Impossible de charger les caisses", 'error');
    } finally {
        loading.value = false;
    }
}

function clearSearch() {
    filters.value.search = '';
    fetchCaisses();
}

function openCreate() {
    isEdit.value = false;
    currentId.value = null;
    form.value = { name: '', type: '', currency_code: 'XOF', initial_balance: 0, active: true };
    showModal.value = true;
}

function openEdit(c) {
    isEdit.value = true;
    currentId.value = c.id;
    form.value = {
        name: c.name,
        type: c.type,
        currency_code: c.currency_code,
        initial_balance: c.initial_balance ?? 0,
        active: !!c.active,
    };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submit() {
    submitting.value = true;
    try {
        if (isEdit.value && currentId.value) {
            await axios.post(`/admin/finances/caisses/${currentId.value}/update`, form.value);
            Swal.fire('Succès', 'Caisse mise à jour', 'success');
        } else {
            await axios.post('/admin/finances/caisses/store', form.value);
            Swal.fire('Succès', 'Caisse créée', 'success');
        }
        await fetchCaisses();
        closeModal();
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', "Vérifiez les champs du formulaire", 'error');
    } finally {
        submitting.value = false;
    }
}

function confirmDelete(c) {
    Swal.fire({
        title: 'Supprimer ?',
        text: `Voulez-vous supprimer la caisse "${c.name}" ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
    }).then(async (res) => {
        if (res.isConfirmed) {
            try {
                await axios.delete(`/admin/finances/caisses/${c.id}/destroy`);
                Swal.fire('Supprimée', 'La caisse a été supprimée', 'success');
                await fetchCaisses();
            } catch (e) {
                console.error(e);
                Swal.fire('Erreur', "Suppression impossible", 'error');
            }
        }
    });
}

onMounted(fetchCaisses);

async function toggleActive(c) {
    const next = !c.active;
    const actionLabel = next ? 'activer' : 'désactiver';
    const result = await Swal.fire({
        title: `${next ? 'Activer' : 'Désactiver'} la caisse ?`,
        text: `Voulez-vous ${actionLabel} la caisse "${c.name}" ?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Oui',
        cancelButtonText: 'Annuler',
    });
    if (!result.isConfirmed) return;

    try {
        await axios.post(`/admin/finances/caisses/${c.id}/update`, { active: next });
        Swal.fire('Succès', `Caisse ${next ? 'activée' : 'désactivée'}`, 'success');
        await fetchCaisses();
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', `Impossible de ${actionLabel} cette caisse`, 'error');
    }
}
</script>

<style scoped>
.modal {
    background-color: rgba(0,0,0,0.4);
}
.modal-dialog { max-width: 520px; }

.filter-bar {
    background: #f8fafc;
    border-radius: 8px;
}

.caisse-card .icon-wrap {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 18px;
}

.caisse-card.hoverable {
    transition: box-shadow .2s ease, transform .1s ease;
}
.caisse-card.hoverable:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,.08);
    transform: translateY(-1px);
}

.badge-success {
    background-color: #28a745;
    color: #fff;
}
.badge-secondary {
    background-color: #adb5bd;
    color: #fff;
}

.view-toggle .btn.active {
    border-color: #0d6efd;
    color: #0d6efd;
}
.btn-group .btn.active {
    border-color: #0d6efd;
    color: #0d6efd;
}

/* Skeleton loader */
.skeleton {
    position: relative;
    overflow: hidden;
}
.sk-box { background: #e9ecef; border-radius: 8px; }
.sk-line { height: 10px; background: #e9ecef; border-radius: 6px; }
.skeleton::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.6) 50%, rgba(255,255,255,0) 100%);
    transform: translateX(-100%);
    animation: shimmer 1.4s infinite;
}
@keyframes shimmer { 100% { transform: translateX(100%); } }

/* Table compact */
table.table tr td, table.table tr th { vertical-align: middle; }
</style>
