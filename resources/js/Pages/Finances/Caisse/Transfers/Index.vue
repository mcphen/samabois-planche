<template>
  <Head title="⇆ Transferts entre caisses" />
  <AuthenticatedLayout>
    <BreadcrumbsAndActions title="⇆ Transferts entre caisses" :breadcrumbs="breadcrumbs">
      <template #action>
        <Link class="btn btn-info btn-sm" href="/admin/finances/caisses">
          <i class="fa fa-wallet"></i> Gérer les caisses
        </Link>
      </template>
    </BreadcrumbsAndActions>

    <div class="card">
      <div class="body">
        <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
          <div class="me-2">
            <label class="form-label">Caisse source</label>
            <select class="form-control" v-model.number="filters.source_caisse_id">
              <option :value="0">Toutes</option>
              <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.currency_code || '-' }})</option>
            </select>
          </div>
          <div class="me-2">
            <label class="form-label">Caisse destination</label>
            <select class="form-control" v-model.number="filters.destination_caisse_id">
              <option :value="0">Toutes</option>
              <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.currency_code || '-' }})</option>
            </select>
          </div>
          <div class="me-2">
            <label class="form-label">Période</label>
            <select class="form-control" v-model="filters.period">
              <option value="">Toutes</option>
              <option value="today">Aujourd’hui</option>
              <option value="week">Cette semaine</option>
              <option value="month">Ce mois</option>
              <option value="custom">Plage personnalisée</option>
            </select>
          </div>
          <div v-if="filters.period==='custom'" class="me-2">
            <label class="form-label">Du</label>
            <input type="date" class="form-control" v-model="filters.start_date" />
          </div>
          <div v-if="filters.period==='custom'" class="me-2">
            <label class="form-label">Au</label>
            <input type="date" class="form-control" v-model="filters.end_date" />
          </div>
          <div class="me-2">
            <label class="form-label">Type</label>
            <select class="form-control" v-model="filters.type">
              <option value="">Tous</option>
              <option value="entrant">Entrant</option>
              <option value="sortant">Sortant</option>
            </select>
          </div>
          <div class="me-2">
            <label class="form-label">Par page</label>
            <select class="form-control" v-model.number="filters.per_page">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
          <div class="ms-auto">
            <button class="btn btn-secondary me-2" @click="applyFilters"><i class="fa fa-filter"></i> Filtrer</button>
            <button class="btn btn-primary" @click="openNewTransfer">
              <i class="fa fa-exchange-alt"></i> Nouveau transfert
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>Date</th>
                <th>Source</th>
                <th>Destination</th>
                <th class="text-end">Montant source</th>
                <th class="text-end">Montant dest.</th>
                <th class="text-end">Taux</th>
                <th class="text-center">Statut</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center text-muted">Chargement...</td>
              </tr>
              <tr v-for="t in transfers" :key="t.id">
                <td>{{ formatDate(t.transfer_date) }}</td>
                <td>{{ t.source?.name }}</td>
                <td>{{ t.destination?.name }}</td>
                <td class="text-end">{{ formatMoney(t.amount_source, t.source?.currency_code) }}</td>
                <td class="text-end">{{ formatMoney(t.amount_destination, t.destination?.currency_code) }}</td>
                <td class="text-end">{{ t.exchange_rate ? t.exchange_rate : '-' }}</td>
                <td class="text-center">
                  <span class="badge" :class="statusClass(t.status)">{{ t.status }}</span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-secondary" @click="openDetails(t.id)">Détails</button>
                </td>
              </tr>
              <tr v-if="!loading && transfers.length===0">
                <td colspan="7" class="text-center text-muted">Aucun transfert</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="text-muted small">Total: {{ meta.total }}</div>
          <nav>
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: meta.current_page<=1 }">
                <button class="page-link" @click="goToPage(meta.current_page-1)" :disabled="meta.current_page<=1">«</button>
              </li>
              <li v-for="p in pages" :key="p" class="page-item" :class="{ active: p===meta.current_page }">
                <button class="page-link" @click="goToPage(p)">{{ p }}</button>
              </li>
              <li class="page-item" :class="{ disabled: meta.current_page>=meta.last_page }">
                <button class="page-link" @click="goToPage(meta.current_page+1)" :disabled="meta.current_page>=meta.last_page">»</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- POP-UP : Détails d'un transfert -->
    <div v-if="showInfo" class="modal d-block" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Détails du transfert</h5>
            <button type="button" class="close" @click="closeInfo">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="!details">Chargement...</div>
            <div v-else>
              <div class="mb-2"><strong>Référence:</strong> {{ details.reference }}</div>
              <div class="mb-2"><strong>Caisse source:</strong> {{ details.source?.name }}</div>
              <div class="mb-2"><strong>Caisse destination:</strong> {{ details.destination?.name }}</div>
              <div class="mb-2"><strong>Montant source:</strong> {{ formatMoney(details.amount_source) }}</div>
              <div class="mb-2"><strong>Montant destination:</strong> {{ formatMoney(details.amount_destination) }}</div>
              <div class="mb-2"><strong>Taux de change:</strong> {{ details.exchange_rate || '-' }}</div>
              <div class="mb-2">
                <strong>Statut:</strong>
                <span class="badge ms-1" :class="statusClass(details.status)">{{ details.status }}</span>
              </div>
              <div class="mb-2"><strong>Description:</strong> {{ details.description || '-' }}</div>
              <div class="mb-2"><strong>Créé par:</strong> {{ details.created_by || '-' }} — {{ formatDate(details.transfer_date) }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" @click="closeInfo">Fermer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- POP-UP : Nouveau transfert (redirige vers page caisse pour l’instant) -->
    <div v-if="showNew" class="modal d-block" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Nouveau transfert</h5>
            <button type="button" class="close" @click="closeNew">&times;</button>
          </div>
          <div class="modal-body">
            <p>Sélectionnez d’abord la caisse source pour initier un transfert.</p>
            <div class="form-group">
              <label>Caisse source</label>
              <select class="form-control" v-model.number="selectedSource">
                <option :value="0">Sélectionner</option>
                <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" @click="closeNew">Annuler</button>
            <button type="button" class="btn btn-primary" :disabled="!selectedSource" @click="goToSourceCaisse">Continuer</button>
          </div>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>

</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';
import dayjs from 'dayjs';
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';

const breadcrumbs = computed(() => ([
  { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
  { label: '💼 Caisses', link: '/admin/finances/caisses' },
  { label: '⇆ Transferts' },
]));

const caisses = ref([]);
const transfers = ref([]);
const meta = ref({ current_page: 1, per_page: 15, total: 0, last_page: 1 });
const loading = ref(false);
const filters = ref({
  source_caisse_id: 0,
  destination_caisse_id: 0,
  period: '',
  start_date: '',
  end_date: '',
  type: '',
  per_page: 15,
});

const pages = computed(() => {
  const last = meta.value.last_page || 1;
  const curr = meta.value.current_page || 1;
  const span = 5;
  let start = Math.max(1, curr - Math.floor(span / 2));
  let end = Math.min(last, start + span - 1);
  start = Math.max(1, end - span + 1);
  const arr = [];
  for (let p = start; p <= end; p++) arr.push(p);
  return arr;
});

function formatDate(d) {
  return d ? dayjs(d).format('DD/MM/YYYY HH:mm') : '-';
}
function formatMoney(n, currency) {
  const v = Number(n || 0);
  const num = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(v);
  return currency ? `${num} ${currency}` : num;
}

async function fetchCaisses() {
  try {
    const { data } = await axios.get('/admin/finances/caisses/listes');
    caisses.value = data;
  } catch (e) { console.error(e); }
}

async function fetchTransfers(page = 1) {
  loading.value = true;
  try {
    const params = { page, per_page: filters.value.per_page };
    if (filters.value.source_caisse_id) params.source_caisse_id = filters.value.source_caisse_id;
    if (filters.value.destination_caisse_id) params.destination_caisse_id = filters.value.destination_caisse_id;
    if (filters.value.period) params.period = filters.value.period;
    if (filters.value.period === 'custom') {
      if (filters.value.start_date) params.start_date = filters.value.start_date;
      if (filters.value.end_date) params.end_date = filters.value.end_date;
    }
    if (filters.value.type) params.type = filters.value.type;
    // Optionally if filtering type relative to a caisse, pick the selected one
    if (filters.value.type && filters.value.source_caisse_id) params.caisse_id = filters.value.source_caisse_id;

    const { data } = await axios.get('/admin/finances/caisse/transfers/list', { params });
    transfers.value = data.data || [];
    meta.value = data.meta || meta.value;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  goToPage(1);
}
function goToPage(p) {
  const last = meta.value.last_page || 1;
  let page = p;
  if (page < 1) page = 1;
  if (page > last) page = last;
  fetchTransfers(page);
}

// Details modal
function statusClass(status) {
  if (status === 'validé') return 'badge-success';
  if (status === 'annulé') return 'badge-secondary';
  if (status === 'corrigé') return 'badge-warning';
  return 'badge-info';
}

const showInfo = ref(false);
const details = ref(null);
async function openDetails(id) {
  showInfo.value = true;
  details.value = null;
  try {
    const { data } = await axios.get(`/admin/finances/caisse/transfers/${id}`);
    details.value = data;
  } catch (e) { console.error(e); }
}
function closeInfo() { showInfo.value = false; }

// New transfer modal -> redirect to caisse show for now
const showNew = ref(false);
const selectedSource = ref(0);
function openNewTransfer() {
  showNew.value = true;
}
function closeNew() { showNew.value = false; }
function goToSourceCaisse() {
  if (!selectedSource.value) return;
  window.location.href = `/admin/finances/caisses/${selectedSource.value}`;
}

onMounted(() => {
  fetchCaisses();
  fetchTransfers(1);
});

</script>

<style scoped>
.modal { background: rgba(0,0,0,0.4); }
.modal-dialog { max-width: 520px; }
</style>
