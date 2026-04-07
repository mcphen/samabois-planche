<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fa fa-list-alt mr-2 text-secondary"></i>20 dernières opérations de caisse
            </h6>
            <a href="/admin/finances/caisse" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-external-link mr-1"></i>Historique complet
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Caisse</th>
                            <th>Type</th>
                            <th>Objet / Description</th>
                            <th class="text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in dernieres" :key="t.id">
                            <td class="text-nowrap text-muted small">{{ fmtDate(t.date) }}</td>
                            <td>
                                <span class="badge badge-light border">{{ t.caisse }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="typeClass(t)">
                                    <i :class="typeIcon(t)" class="mr-1"></i>
                                    {{ typeLabel(t.movement_type) }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ t.objet || '—' }}</td>
                            <td class="text-right font-weight-bold" :class="t.sens === 'entree' ? 'text-success' : 'text-danger'">
                                <i :class="t.sens === 'entree' ? 'fa fa-plus' : 'fa fa-minus'" class="mr-1" style="font-size:10px"></i>
                                {{ fmt(t.amount) }}
                            </td>
                        </tr>
                        <tr v-if="!dernieres.length">
                            <td colspan="5" class="text-center text-muted py-4">Aucune transaction.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    dernieres: { type: Array, default: () => [] },
});

const fmt = (v) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF", maximumFractionDigits: 0 }).format(v ?? 0);

const fmtDate = (d) =>
    d ? new Date(d).toLocaleDateString("fr-FR", { day: "2-digit", month: "short", year: "numeric" }) : "—";

const LABELS = {
    entree_client:     "Paiement client",
    entree_autre:      "Entrée diverse",
    sortie:            "Sortie / Dépense",
    transfert_entrant: "Transfert entrant",
    transfert_sortant: "Transfert sortant",
    entree:            "Entrée",
};

const typeLabel = (mt) => LABELS[mt] ?? mt ?? "—";

const typeClass = (t) => {
    if (t.sens === "entree") return "badge-success";
    return "badge-danger";
};

const typeIcon = (t) => {
    const icons = {
        entree_client:     "fa fa-user",
        entree_autre:      "fa fa-plus-circle",
        sortie:            "fa fa-minus-circle",
        transfert_entrant: "fa fa-arrow-circle-down",
        transfert_sortant: "fa fa-arrow-circle-up",
    };
    return icons[t.movement_type] ?? (t.sens === "entree" ? "fa fa-plus-circle" : "fa fa-minus-circle");
};
</script>
