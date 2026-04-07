<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="mb-0">
                <i class="fa fa-users mr-2 text-primary"></i>Situation comptable par client
            </h6>
            <div class="d-flex align-items-center gap-2 mt-1 mt-sm-0">
                <!-- Filtre créances uniquement -->
                <div class="form-check form-check-inline mb-0 mr-2">
                    <input class="form-check-input" type="checkbox" id="creancesOnly" v-model="creancesOnly" />
                    <label class="form-check-label small" for="creancesOnly">Avec créances uniquement</label>
                </div>
                <!-- Recherche -->
                <input
                    v-model="search"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Rechercher un client..."
                    style="max-width: 200px;"
                />
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th @click="sort('name')" class="sortable">
                                Client <i class="fa" :class="sortIcon('name')"></i>
                            </th>
                            <th @click="sort('total_ca')" class="sortable text-right">
                                CA Facturé <i class="fa" :class="sortIcon('total_ca')"></i>
                            </th>
                            <th @click="sort('total_paye')" class="sortable text-right">
                                Encaissé <i class="fa" :class="sortIcon('total_paye')"></i>
                            </th>
                            <th @click="sort('montant_du')" class="sortable text-right">
                                Créance <i class="fa" :class="sortIcon('montant_du')"></i>
                            </th>
                            <th @click="sort('taux_recouvrement')" class="sortable text-right">
                                Taux <i class="fa" :class="sortIcon('taux_recouvrement')"></i>
                            </th>
                            <th @click="sort('nb_factures')" class="sortable text-center">
                                Factures <i class="fa" :class="sortIcon('nb_factures')"></i>
                            </th>
                            <th @click="sort('derniere_facture')" class="sortable text-center">
                                Dernière facture <i class="fa" :class="sortIcon('derniere_facture')"></i>
                            </th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="client in paginated" :key="client.id">
                            <td class="font-weight-bold">{{ client.name }}</td>
                            <td class="text-right text-secondary">{{ fmt(client.total_ca) }}</td>
                            <td class="text-right text-success">{{ fmt(client.total_paye) }}</td>
                            <td class="text-right font-weight-bold" :class="creanceClass(client.montant_du)">
                                {{ fmt(client.montant_du) }}
                            </td>
                            <td class="text-right">
                                <div class="d-flex align-items-center justify-content-end" style="gap:6px">
                                    <div class="progress flex-grow-1" style="height:6px; min-width:50px; max-width:80px;">
                                        <div
                                            class="progress-bar"
                                            :class="tauxBarClass(client.taux_recouvrement)"
                                            :style="{ width: Math.min(client.taux_recouvrement, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="badge" :class="tauxBadgeClass(client.taux_recouvrement)">
                                        {{ client.taux_recouvrement }}%
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ client.nb_factures }}</span>
                            </td>
                            <td class="text-center text-muted small">
                                {{ fmtDate(client.derniere_facture) }}
                            </td>
                            <td class="text-center">
                                <a
                                    :href="`/admin/clients/${client.id}/consultation`"
                                    class="btn btn-xs btn-outline-primary"
                                    title="Voir la fiche client"
                                >
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <tr v-if="!paginated.length">
                            <td colspan="8" class="text-center text-muted py-4">Aucun client trouvé.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <small class="text-muted">
                    {{ filtered.length }} client(s)
                    <template v-if="search || creancesOnly">filtrés</template>
                    sur {{ clients.length }}
                </small>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" :disabled="page <= 1" @click="page--">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                    <small>Page {{ page }} / {{ totalPages }}</small>
                    <button class="btn btn-sm btn-outline-secondary" :disabled="page >= totalPages" @click="page++">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                    <select v-model="perPage" class="form-control form-control-sm" style="width:auto">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    clients: { type: Array, default: () => [] },
});

const search       = ref("");
const creancesOnly = ref(false);
const page         = ref(1);
const perPage      = ref(25);
const sortKey      = ref("montant_du");
const sortDir      = ref("desc");

// Réinitialiser la page lors d'un changement de filtre
watch([search, creancesOnly, perPage], () => { page.value = 1; });

const fmt = (v) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(v ?? 0);

const fmtDate = (d) => d ? new Date(d).toLocaleDateString("fr-FR") : "—";

const sort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortDir.value = "desc";
    }
};

const sortIcon = (key) => {
    if (sortKey.value !== key) return "fa-sort text-muted";
    return sortDir.value === "asc" ? "fa-sort-asc text-primary" : "fa-sort-desc text-primary";
};

const filtered = computed(() => {
    let list = props.clients;
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter((c) => c.name.toLowerCase().includes(q));
    }
    if (creancesOnly.value) {
        list = list.filter((c) => c.montant_du > 0);
    }
    return [...list].sort((a, b) => {
        const va = a[sortKey.value] ?? 0;
        const vb = b[sortKey.value] ?? 0;
        if (typeof va === "string") return sortDir.value === "asc" ? va.localeCompare(vb) : vb.localeCompare(va);
        return sortDir.value === "asc" ? va - vb : vb - va;
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)));
const paginated  = computed(() => {
    const start = (page.value - 1) * perPage.value;
    return filtered.value.slice(start, start + perPage.value);
});

const creanceClass = (v) => {
    if (v <= 0) return "text-success";
    if (v < 500000) return "text-warning";
    return "text-danger";
};

const tauxBadgeClass = (t) => {
    if (t >= 100) return "badge-success";
    if (t >= 80)  return "badge-info";
    if (t >= 50)  return "badge-warning";
    return "badge-danger";
};

const tauxBarClass = (t) => {
    if (t >= 80) return "bg-success";
    if (t >= 50) return "bg-warning";
    return "bg-danger";
};
</script>

<style scoped>
.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
.sortable:hover { background: #f8f9fa; }
.btn-xs { padding: 2px 7px; font-size: 12px; }
</style>
