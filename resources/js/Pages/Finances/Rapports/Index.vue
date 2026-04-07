<template>
  <Head title="📊 Rapports financiers" />
  <AuthenticatedLayout>
    <BreadcrumbsAndActions :title="'📊 Rapports financiers'" :breadcrumbs="breadcrumbs">
      <template #action>
        <button class="btn btn-primary btn-sm m-1" @click="exportPdf">Exporter PDF</button>
        <button class="btn btn-success btn-sm m-1" @click="exportExcel">Exporter Excel</button>
        <button class="btn btn-default btn-sm m-1" @click="printPage">Imprimer</button>
      </template>
    </BreadcrumbsAndActions>

    <div class="card">
      <div class="body">
        <div class="row">
          <div class="col-md-3 mb-2">
            <label class="form-label">Type de rapport</label>
            <select v-model="filters.report" class="form-control" @change="fetchData">
              <option value="journal_all">Journal de caisse (toutes)</option>
              <option value="journal_caisse">Journal d’une caisse</option>
              <option value="entrees">Rapport d’entrées</option>
              <option value="sorties">Rapport de sorties</option>
              <option value="transferts">Rapport des transferts</option>
              <option value="par_client">Rapport par client</option>
              <option value="par_type_caisse">Rapport par type de caisse</option>
              <option value="par_periode">Rapport par période</option>
              <option value="comparatif">Comparatif (période vs période)</option>
              <option value="soldes">Solde par caisse</option>
            </select>
          </div>
          <div class="col-md-2 mb-2">
            <label class="form-label">Du</label>
            <input type="date" v-model="filters.start_date" class="form-control" @change="fetchData" />
          </div>
          <div class="col-md-2 mb-2">
            <label class="form-label">Au</label>
            <input type="date" v-model="filters.end_date" class="form-control" @change="fetchData" />
          </div>

          <div class="col-md-2 mb-2" v-if="filters.report==='journal_caisse' || showCaisseFilter">
            <label class="form-label">Caisse</label>
            <select v-model="filters.caisse_id" class="form-control" @change="fetchData">
              <option :value="null">Toutes</option>
              <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="col-md-2 mb-2" v-if="filters.report==='par_client'">
            <label class="form-label">Client</label>
            <select v-model="filters.client_id" class="form-control" @change="fetchData">
              <option :value="null">Tous</option>
              <option v-for="cl in clients" :key="cl.id" :value="cl.id">{{ cl.name }}</option>
            </select>
          </div>

          <div class="col-md-2 mb-2" v-if="filters.report==='par_type_caisse' || showTypeFilter">
            <label class="form-label">Type de caisse</label>
            <select v-model="filters.caisse_type" class="form-control" @change="fetchData">
              <option :value="null">Tous</option>
              <option v-for="t in caisseTypes" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>

          <div class="col-md-2 mb-2" v-if="filters.report==='par_periode'">
            <label class="form-label">Période</label>
            <select v-model="filters.period" class="form-control" @change="fetchData">
              <option value="day">Jour</option>
              <option value="week">Semaine</option>
              <option value="month">Mois</option>
              <option value="quarter">Trimestre</option>
            </select>
          </div>
        </div>

        <div class="text-right mt-2">
          <button class="btn btn-primary btn-sm" @click="fetchData" :disabled="loading">
            <span v-if="loading">Chargement...</span>
            <span v-else>Actualiser</span>
          </button>
        </div>
      </div>
    </div>

    <div class="card" v-if="rows.length">
      <div class="body">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="thead-dark">
              <tr>
                <th v-for="col in columns" :key="col">{{ col }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, idx) in rows" :key="idx">
                <td v-for="col in columns" :key="col">{{ r[col] }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="alert alert-info" v-else>
      Aucune donnée trouvée pour les filtres sélectionnés.
    </div>

  </AuthenticatedLayout>

</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue'
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  caisses: { type: Array, default: () => [] },
  clients: { type: Array, default: () => [] },
  caisseTypes: { type: Array, default: () => [] },
})

const breadcrumbs = [
  { label: 'Dashboard', url: '/admin/dashboard' },
  { label: 'Finances', url: '/admin/finances/caisse' },
  { label: 'Rapports', url: '/admin/finances/rapports' },
]

const caisses = computed(() => props.caisses)
const clients = computed(() => props.clients)
const caisseTypes = computed(() => props.caisseTypes)

const today = new Date()
const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0)

function toInputDate(d) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`
}

const filters = reactive({
  report: 'journal_all',
  start_date: toInputDate(startOfMonth),
  end_date: toInputDate(endOfMonth),
  caisse_id: null,
  client_id: null,
  caisse_type: null,
  period: 'month',
})

const loading = ref(false)
const rows = ref([])
const columns = ref([])

const showCaisseFilter = computed(() => ['entrees','sorties','transferts'].includes(filters.report))
const showTypeFilter = computed(() => ['journal_all','entrees','sorties','transferts'].includes(filters.report))

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get('/admin/finances/rapports/data', { params: filters })
    const arr = data?.data || []
    rows.value = arr
    columns.value = inferColumns(arr)
  } finally {
    loading.value = false
  }
}

function inferColumns(arr) {
  if (!arr || !arr.length) return []
  return Object.keys(arr[0])
}

function queryString() {
  const params = new URLSearchParams()
  for (const [k,v] of Object.entries(filters)) {
    if (v !== null && v !== undefined && v !== '') params.append(k, v)
  }
  return params.toString()
}

function exportPdf() {
  const url = `/admin/finances/rapports/export/pdf?${queryString()}&title=${encodeURIComponent(titleForReport())}`
  window.open(url, '_blank')
}

function exportExcel() {
  const url = `/admin/finances/rapports/export/excel?${queryString()}&title=${encodeURIComponent(titleForReport())}`
  window.open(url, '_blank')
}

function titleForReport() {
  const map = {
    journal_all: 'Journal de caisse (toutes caisses)',
    journal_caisse: 'Journal de caisse (spécifique)',
    entrees: 'Rapport des entrées',
    sorties: 'Rapport des sorties',
    transferts: 'Rapport des transferts',
    par_client: 'Rapport par client',
    par_type_caisse: 'Rapport par type de caisse',
    par_periode: 'Rapport par période',
    comparatif: 'Rapport comparatif',
    soldes: 'Solde par caisse',
  }
  return map[filters.report] || 'Rapport'
}

function printPage() {
  window.print()
}

onMounted(fetchData)

</script>

<style scoped>
.thead-dark th { background-color: #343a40; color: #fff; }
</style>
