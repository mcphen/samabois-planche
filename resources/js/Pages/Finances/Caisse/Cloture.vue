<template>
  <Head title="Clôture de caisse" />
  <AuthenticatedLayout>
    <div class="container-fluid">
      <h3 class="mb-3">🔒 Clôture de caisse</h3>

      <div class="card">
        <div class="body">
          <div class="row">
            <div class="col-md-4">
              <label class="font-weight-bold">Sélection de la caisse</label>
              <select v-model.number="form.caisse_id" class="form-control" @change="onParamsChanged">
                <option value="" disabled>Sélectionner…</option>
                <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.currency_code || 'XOF' }})</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="font-weight-bold">Date de début</label>
              <input type="date" v-model="form.start_date" class="form-control" @change="onParamsChanged" />
            </div>
            <div class="col-md-3">
              <label class="font-weight-bold">Date de fin</label>
              <input type="date" v-model="form.end_date" class="form-control" @change="onParamsChanged" />
            </div>
          </div>

          <hr />
          <div class="row">
            <div class="col-md-6">
              <h6>Vérifications préalables</h6>
              <ul class="list-unstyled">
                <li>
                  <span :class="checkClass(preview.checks.no_unvalidated)">✔</span>
                  Aucun mouvement non validé
                </li>
                <li>
                  <span :class="checkClass(preview.checks.no_negative_balance)">✔</span>
                  Aucun solde négatif
                </li>
                <li>
                  <span :class="checkClass(preview.checks.transfers_matched)">✔</span>
                  Correspondance des transferts
                </li>
                <li v-if="bankFlag !== null">
                  <span :class="checkClass(preview.checks.bank_reconciled)">✔</span>
                  Rapprochement bancaire effectué
                </li>
              </ul>
              <div v-if="preview.overlap" class="alert alert-warning">
                Une clôture validée chevauche déjà cette période.
              </div>
            </div>
            <div class="col-md-6">
              <h6>Résumé</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <tbody>
                    <tr>
                      <th>Solde initial</th>
                      <td class="text-right">{{ fmt(preview.initial_balance) }}</td>
                    </tr>
                    <tr>
                      <th>Total entrées</th>
                      <td class="text-right">{{ fmt(preview.totals.total_entries) }}</td>
                    </tr>
                    <tr>
                      <th>Transferts entrants</th>
                      <td class="text-right">{{ fmt(preview.totals.total_transfer_in) }}</td>
                    </tr>
                    <tr>
                      <th>Total sorties</th>
                      <td class="text-right">{{ fmt(preview.totals.total_exits) }}</td>
                    </tr>
                    <tr>
                      <th>Transferts sortants</th>
                      <td class="text-right">{{ fmt(preview.totals.total_transfer_out) }}</td>
                    </tr>
                    <tr class="table-secondary">
                      <th>Solde théorique</th>
                      <td class="text-right">{{ fmt(preview.theoretical_balance) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <hr />
          <div class="row">
            <div class="col-md-4">
              <label class="font-weight-bold">Solde réel (saisi)</label>
              <input type="number" step="0.01" v-model.number="form.real_balance" class="form-control" @input="onParamsChanged" />
            </div>
            <div class="col-md-4">
              <label class="font-weight-bold">Écart</label>
              <input type="text" class="form-control" :value="fmt(diff)" disabled />
            </div>
            <div class="col-md-12 mt-2">
              <label class="font-weight-bold">Description (si écart)</label>
              <textarea v-model="form.notes" rows="2" class="form-control" placeholder="Explication de l'écart (facultatif)"></textarea>
            </div>
          </div>

          <div class="mt-3 d-flex justify-content-end">
            <Link class="btn btn-light mr-2" href="/admin/finances/caisse/clotures">Annuler</Link>
            <button class="btn btn-primary" :disabled="submitting || !canSubmit" @click="submitClosure">
              {{ submitting ? 'Clôture en cours…' : 'Clôturer la caisse' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

const caisses = ref([])
const preview = reactive({
  overlap: false,
  initial_balance: 0,
  theoretical_balance: 0,
  totals: { total_entries: 0, total_exits: 0, total_transfer_in: 0, total_transfer_out: 0 },
  checks: { no_unvalidated: true, no_negative_balance: true, transfers_matched: true, bank_reconciled: null },
})
const form = reactive({ caisse_id: '', start_date: '', end_date: '', real_balance: null, notes: '' })
const submitting = ref(false)

const bankFlag = computed(() => preview.checks.bank_reconciled)
const diff = computed(() => (form.real_balance !== null ? round(form.real_balance - (preview.theoretical_balance || 0)) : 0))
const canSubmit = computed(() => form.caisse_id && form.start_date && form.end_date && form.real_balance !== null && !preview.overlap)

function fmt(n) { return Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function round(n) { return Math.round((n + Number.EPSILON) * 100) / 100 }
function checkClass(value) { return value ? 'text-success mr-1' : 'text-danger mr-1' }

async function fetchCaisses() {
  try {
    const { data } = await axios.get('/admin/finances/caisses/listes')
    caisses.value = data
    if (!form.caisse_id && data.length) form.caisse_id = data[0].id
  } catch (e) { /* noop */ }
}

async function onParamsChanged() {
  if (!form.caisse_id || !form.start_date || !form.end_date) return
  try {
    const { data } = await axios.post('/admin/finances/caisse/clotures/preview', {
      caisse_id: form.caisse_id,
      start_date: form.start_date,
      end_date: form.end_date,
      real_balance: form.real_balance,
    })
    Object.assign(preview, data)
  } catch (e) { /* noop */ }
}

async function submitClosure() {
  if (!canSubmit.value) return
  submitting.value = true
  try {
    const { data } = await axios.post('/admin/finances/caisse/clotures/create', form)
    window.location.href = '/admin/finances/caisse/clotures'
  } catch (e) {
    submitting.value = false
    alert(e?.response?.data?.message || 'Erreur lors de la clôture')
  }
}

onMounted(async () => {
  const today = new Date().toISOString().slice(0, 10)
  form.start_date = today
  form.end_date = today
  await fetchCaisses()
  await onParamsChanged()
})
</script>

<style scoped>
.mr-1 { margin-right: 6px; }
</style>
