<template>
  <Head title="Historique des clôtures" />
  <AuthenticatedLayout>
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h3>📐 Historique des clôtures</h3>
        <Link class="btn btn-primary" href="/admin/finances/caisse/cloture">Nouvelle clôture</Link>
      </div>

      <div class="card mb-3">
        <div class="body">
          <div class="row">
            <div class="col-md-4">
              <label>Caisse</label>
              <select v-model.number="filters.caisse_id" class="form-control" @change="fetchClosures(1)">
                <option :value="undefined">Toutes</option>
                <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Du</label>
              <input type="date" v-model="filters.from" class="form-control" @change="fetchClosures(1)" />
            </div>
            <div class="col-md-3">
              <label>Au</label>
              <input type="date" v-model="filters.to" class="form-control" @change="fetchClosures(1)" />
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Caisse</th>
                  <th>Période</th>
                  <th>Solde initial</th>
                  <th>Solde final (théorique)</th>
                  <th>Écart</th>
                  <th>Validé par</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="cl in closures" :key="cl.id">
                  <td>{{ cl.caisse?.name }}</td>
                  <td>{{ formatPeriod(cl.start_date, cl.end_date) }}</td>
                  <td>{{ fmt(cl.initial_balance) }}</td>
                  <td>{{ fmt(cl.theoretical_balance) }}</td>
                  <td :class="{ 'text-danger': cl.difference < 0, 'text-success': cl.difference >= 0 }">{{ fmt(cl.difference) }}</td>
                  <td>{{ cl.validator?.name || '-' }}</td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary mr-2" @click="openDetails(cl)">Voir</button>
                    <a class="btn btn-sm btn-outline-secondary" :href="`/admin/finances/caisse/clotures/${cl.id}/download`">Télécharger</a>
                  </td>
                </tr>
                <tr v-if="!loading && closures.length === 0">
                  <td colspan="7" class="text-center text-muted">Aucune clôture</td>
                </tr>
              </tbody>
            </table>
          </div>

          <Pagination :pagination="meta" @pageChanged="fetchClosures" />
        </div>
      </div>

      <!-- Modal détails -->
      <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Détails de la clôture</h5>
              <button type="button" class="close" @click="closeModal">&times;</button>
            </div>
            <div class="modal-body" v-if="selected">
              <p>
                <strong>Période:</strong> {{ formatPeriod(selected.start_date, selected.end_date) }}
              </p>
              <p>
                <strong>Écart:</strong>
                <span :class="{ 'text-danger': selected.difference < 0, 'text-success': selected.difference >= 0 }">
                  {{ fmt(selected.difference) }} XOF
                </span>
                <span v-if="selected.notes"> ({{ selected.notes }})</span>
              </p>
              <p><strong>Validé par:</strong> {{ selected.validator?.name || '-' }}</p>
              <div class="mt-2">
                <a class="btn btn-sm btn-outline-secondary" :href="`/admin/finances/caisse/clotures/${selected.id}/download`">Télécharger le PDF</a>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-light" @click="closeModal">Fermer</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>

</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import Pagination from '@/Components/Pagination.vue'

const closures = ref([])
const meta = reactive({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const loading = ref(false)
const caisses = ref([])
const filters = reactive({ caisse_id: undefined, from: '', to: '' })

const showModal = ref(false)
const selected = ref(null)

function fmt(n) { return Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function formatPeriod(a, b) {
  const dd = (s) => new Date(s).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
  return `${dd(a)} → ${dd(b)}`
}

async function fetchCaisses() {
  try { const { data } = await axios.get('/admin/finances/caisses/listes'); caisses.value = data } catch(e) {}
}

async function fetchClosures(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/admin/finances/caisse/clotures/list', {
      params: { page, caisse_id: filters.caisse_id, from: filters.from, to: filters.to }
    })
    closures.value = data.data
    Object.assign(meta, data.meta)
  } finally { loading.value = false }
}

async function openDetails(cl) {
  const { data } = await axios.get(`/admin/finances/caisse/clotures/${cl.id}`)
  selected.value = data
  showModal.value = true
}
function closeModal() { showModal.value = false; selected.value = null }

onMounted(async () => { await fetchCaisses(); await fetchClosures(1) })
</script>
