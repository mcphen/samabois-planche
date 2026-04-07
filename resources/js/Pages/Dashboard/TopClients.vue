<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fa fa-trophy mr-2 text-warning"></i>Top 5 Clients</h6>
            <a href="/admin/clients" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-list mr-1"></i>Tous
            </a>
        </div>
        <div class="card-body p-0">
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>
            <table v-else class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Client</th>
                        <th class="text-right">CA Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(client, index) in clients" :key="client.id">
                        <td>
                            <span class="badge" :class="medalClass(index)">{{ index + 1 }}</span>
                        </td>
                        <td>
                            <a :href="`/admin/clients/${client.id}/consultation`" class="text-dark font-weight-bold">
                                {{ client.name }}
                            </a>
                        </td>
                        <td class="text-right font-weight-bold text-primary">
                            {{ formatPrice(client.total_revenue) }}
                        </td>
                    </tr>
                    <tr v-if="!clients.length && !loading">
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="fa fa-info-circle mr-1"></i> Aucun client avec des ventes.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const clients = ref([]);
const loading = ref(false);

const fetchTopClients = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/admin/dashboard/top-clients");
        clients.value = data;
    } catch (e) {
        console.error("Erreur top clients:", e);
    } finally {
        loading.value = false;
    }
};

const formatPrice = (value) =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: "XOF" }).format(value ?? 0);

const medalClass = (index) => {
    const classes = ["badge-warning", "badge-secondary", "badge-info", "badge-light", "badge-light"];
    return classes[index] ?? "badge-light";
};

onMounted(fetchTopClients);
</script>
