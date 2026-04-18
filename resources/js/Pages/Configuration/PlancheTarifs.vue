<template>
    <Head :title="`Tarifs planches | ${appName}`" />

    <AuthenticatedLayout>
        <BreadcrumbsAndActions
            title="Tarifs prix de revient"
            :breadcrumbs="breadcrumbs"
        />

        <div class="row clearfix">
            <!-- Liste des tarifs -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="header d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
                        <div>
                            <h2>Grille tarifaire <span class="badge badge-secondary ml-2">{{ tarifs.length }}</span></h2>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                Le prix de revient est déterminé automatiquement selon la catégorie et l'épaisseur de chaque planche.
                            </p>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" @click="openAddModal">
                            <i class="fa fa-plus mr-1"></i> Ajouter un tarif
                        </button>
                    </div>
                    <div class="body">
                        <div v-if="tarifs.length === 0" class="text-muted text-center py-3">
                            Aucun tarif défini. Ajoutez le premier tarif depuis le formulaire.
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead style="background:#f0f4ff;">
                                    <tr>
                                        <th>Catégorie</th>
                                        <th class="text-center">Épaisseur</th>
                                        <th>Contrat</th>
                                        <th class="text-right">Prix de revient</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="tarif in sortedTarifs" :key="tarif.id">
                                        <td>
                                            <span class="badge" :class="categorieBadgeClass(tarif.categorie)">
                                                {{ categorieLabel(tarif.categorie) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ formatDecimal(tarif.epaisseur) }}</td>
                                        <td>
                                            <span v-if="tarif.contrat" class="badge badge-info">{{ tarif.contrat.numero }}</span>
                                            <span v-else class="text-muted" style="font-size:0.82rem;">Tous</span>
                                        </td>
                                        <td class="text-right font-weight-bold">{{ formatCurrency(tarif.prix) }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center" style="gap:6px;">
                                                <button type="button" class="btn btn-info btn-sm" title="Voir les bénéfices" @click="openBenefices(tarif)">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm" @click="startEdit(tarif)">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" @click="deleteTarif(tarif)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Modale ajout / modification -->
    <div v-if="showFormModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa mr-2" :class="editingTarif ? 'fa-pencil' : 'fa-plus'"></i>
                        {{ editingTarif ? 'Modifier le tarif' : 'Ajouter un tarif' }}
                    </h5>
                    <button type="button" class="close" @click="cancelEdit">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="formError" class="alert alert-danger mb-3">
                        <i class="fa fa-exclamation-circle mr-2"></i>{{ formError }}
                    </div>
                    <div class="form-group">
                        <label>Contrat *</label>
                        <select v-model="form.contrat_id" class="form-control">
                            <option :value="null">Sélectionner un contrat...</option>
                            <option v-for="c in contrats" :key="c.id" :value="c.id">{{ c.numero }}</option>
                        </select>
                        <small v-if="formErrors.contrat_id" class="text-danger d-block mt-1">{{ formErrors.contrat_id[0] }}</small>
                    </div>
                    <div class="form-group">
                        <label>Catégorie *</label>
                        <select v-model="form.categorie" class="form-control">
                            <option value="">Sélectionner...</option>
                            <option value="mate">Mate</option>
                            <option value="semi_brillant">Semi-brillant</option>
                            <option value="brillant">Brillant</option>
                        </select>
                        <small v-if="formErrors.categorie" class="text-danger d-block mt-1">{{ formErrors.categorie[0] }}</small>
                    </div>
                    <div class="form-group">
                        <label>Épaisseur *</label>
                        <select v-model="form.epaisseur" class="form-control">
                            <option value="">Sélectionner...</option>
                            <option v-for="ep in epaisseurOptions" :key="ep.id" :value="ep.value">{{ ep.label }}</option>
                        </select>
                        <small v-if="formErrors.epaisseur" class="text-danger d-block mt-1">{{ formErrors.epaisseur[0] }}</small>
                    </div>
                    <div class="form-group">
                        <label>Prix (CFA) *</label>
                        <input v-model="form.prix" type="number" min="0" step="1" class="form-control" placeholder="Ex: 5000" />
                        <small v-if="formErrors.prix" class="text-danger d-block mt-1">{{ formErrors.prix[0] }}</small>
                    </div>
                    <div v-if="editingTarif" class="form-group mb-0">
                        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:0.88rem;">
                            <label class="d-flex align-items-start mb-0" style="cursor:pointer;gap:8px;">
                                <input type="checkbox" v-model="form.update_lignes" style="margin-top:3px;flex-shrink:0;" />
                                <span>
                                    <strong>Mettre à jour les prix de revient existants</strong><br>
                                    <span class="text-muted">Si coché, le nouveau prix remplacera le prix de revient de <strong>toutes</strong> les livraisons déjà enregistrées pour ce tarif. Sinon, seules les lignes sans prix de revient seront ignorées.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" @click="cancelEdit">Annuler</button>
                    <button type="button" class="btn btn-success btn-sm" :disabled="submitting" @click="submitForm">
                        {{ submitting ? 'Enregistrement...' : (editingTarif ? 'Mettre à jour' : 'Ajouter') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale bénéfices -->
    <div v-if="showBeneficesModal" class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-bar-chart mr-2 text-info"></i>
                        Bénéfices réalisés —
                        <span class="badge" :class="categorieBadgeClass(beneficesData?.tarif?.categorie)">{{ categorieLabel(beneficesData?.tarif?.categorie) }}</span>
                        {{ formatDecimal(beneficesData?.tarif?.epaisseur) }} mm
                        <span class="ml-2 text-muted" style="font-size:0.85rem;">(Prix revient : {{ formatCurrency(beneficesData?.tarif?.prix) }})</span>
                    </h5>
                    <button type="button" class="close" @click="showBeneficesModal = false">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="beneficesLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"><span class="sr-only">Chargement...</span></div>
                    </div>
                    <template v-else-if="beneficesData">
                        <!-- Totaux -->
                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="card bg-light mb-0"><div class="body p-3 text-center">
                                    <div class="font-weight-bold" style="font-size:1.2rem;">{{ beneficesData.totaux.quantite_totale }}</div>
                                    <small class="text-muted">Feuilles livrées</small>
                                </div></div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="card bg-primary mb-0"><div class="body p-3 text-center text-white">
                                    <div class="font-weight-bold" style="font-size:1.2rem;">{{ formatCurrency(beneficesData.totaux.ca_total) }}</div>
                                    <small>Chiffre d'affaires</small>
                                </div></div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="card bg-secondary mb-0"><div class="body p-3 text-center text-white">
                                    <div class="font-weight-bold" style="font-size:1.2rem;">{{ formatCurrency(beneficesData.totaux.cout_total) }}</div>
                                    <small>Coût de revient</small>
                                </div></div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="card mb-0" :class="(beneficesData.totaux.benefice_total ?? 0) >= 0 ? 'bg-success' : 'bg-danger'"><div class="body p-3 text-center text-white">
                                    <div class="font-weight-bold" style="font-size:1.2rem;">{{ beneficesData.totaux.benefice_total !== null ? formatCurrency(beneficesData.totaux.benefice_total) : '-' }}</div>
                                    <small>Bénéfice total</small>
                                </div></div>
                            </div>
                        </div>

                        <div v-if="!beneficesData.lignes.length" class="alert alert-warning mb-0">
                            <i class="fa fa-info-circle mr-1"></i> Aucune livraison enregistrée pour ce tarif.
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead style="background:#f0f4ff;">
                                    <tr>
                                        <th>N° BL</th>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Contrat</th>
                                        <th>Code couleur</th>
                                        <th class="text-center">Qté</th>
                                        <th class="text-right">Prix vente</th>
                                        <th class="text-right">Prix revient</th>
                                        <th class="text-right">Bén. unitaire</th>
                                        <th class="text-right">Bén. total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="ligne in beneficesData.lignes" :key="ligne.id">
                                        <td><span class="badge badge-light border">{{ ligne.numero_bl }}</span></td>
                                        <td>{{ ligne.date_livraison || '-' }}</td>
                                        <td>{{ ligne.client || '-' }}</td>
                                        <td>{{ ligne.numero_contrat || '-' }}</td>
                                        <td><span class="badge badge-info">{{ ligne.code_couleur || '-' }}</span></td>
                                        <td class="text-center">{{ ligne.quantite_livree }}</td>
                                        <td class="text-right">{{ formatCurrency(ligne.prix_unitaire) }}</td>
                                        <td class="text-right text-muted">{{ ligne.prix_de_revient !== null ? formatCurrency(ligne.prix_de_revient) : '-' }}</td>
                                        <td class="text-right" :class="ligne.benefice_unitaire !== null ? (ligne.benefice_unitaire >= 0 ? 'text-success' : 'text-danger') : 'text-muted'">
                                            {{ ligne.benefice_unitaire !== null ? formatCurrency(ligne.benefice_unitaire) : '-' }}
                                        </td>
                                        <td class="text-right font-weight-bold" :class="ligne.benefice_total !== null ? (ligne.benefice_total >= 0 ? 'text-success' : 'text-danger') : 'text-muted'">
                                            {{ ligne.benefice_total !== null ? formatCurrency(ligne.benefice_total) : '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" @click="showBeneficesModal = false">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BreadcrumbsAndActions from '@/Components/Nav/BreadcrumbsAndActions.vue';

const props = defineProps({
    tarifs:    { type: Array, default: () => [] },
    epaisseurs: { type: Array, default: () => [] },
    contrats:  { type: Array, default: () => [] },
});

const appName = import.meta.env.VITE_APP_NAME;
const breadcrumbs = [
    { label: 'Tableau de bord', link: '/dashboard', icon: 'fa fa-dashboard' },
    { label: 'Configuration', link: '/admin/configuration', icon: 'fa fa-cog' },
    { label: 'Tarifs prix de revient' },
];

const tarifs = ref([...props.tarifs]);
const editingTarif = ref(null);
const submitting = ref(false);
const formError = ref('');
const formErrors = ref({});
const showFormModal = ref(false);

const showBeneficesModal = ref(false);
const beneficesLoading = ref(false);
const beneficesData = ref(null);

function openBenefices(tarif) {
    showBeneficesModal.value = true;
    beneficesLoading.value = true;
    beneficesData.value = null;
    axios.get(`/admin/configuration/planche-tarifs/${tarif.id}/benefices`)
        .then((response) => { beneficesData.value = response.data; })
        .catch(() => { showBeneficesModal.value = false; Swal.fire('Erreur', 'Impossible de charger les données.', 'error'); })
        .finally(() => { beneficesLoading.value = false; });
}

const form = ref(buildEmptyForm());

function buildEmptyForm() {
    return { categorie: '', epaisseur: '', prix: '', contrat_id: null, update_lignes: false };
}

const epaisseurOptions = computed(() =>
    props.epaisseurs
        .map((item) => {
            const match = String(item?.slug || item?.intitule || '').replace(',', '.').match(/(\d+(?:\.\d+)?)/);
            if (!match) return null;
            const value = String(Number(match[1]));
            return { id: item.id, label: item.intitule, value };
        })
        .filter(Boolean)
);

const sortedTarifs = computed(() =>
    [...tarifs.value].sort((a, b) => {
        if (a.categorie !== b.categorie) return a.categorie.localeCompare(b.categorie);
        return Number(a.epaisseur) - Number(b.epaisseur);
    })
);

function openAddModal() {
    editingTarif.value = null;
    form.value = buildEmptyForm();
    formError.value = '';
    formErrors.value = {};
    showFormModal.value = true;
}

function startEdit(tarif) {
    editingTarif.value = tarif;
    form.value = { categorie: tarif.categorie, epaisseur: String(Number(tarif.epaisseur)), prix: tarif.prix, contrat_id: tarif.contrat_id ?? null, update_lignes: false };
    formError.value = '';
    formErrors.value = {};
    showFormModal.value = true;
}

function cancelEdit() {
    editingTarif.value = null;
    form.value = buildEmptyForm();
    formError.value = '';
    formErrors.value = {};
    showFormModal.value = false;
}

function submitForm() {
    submitting.value = true;
    formError.value = '';
    formErrors.value = {};

    const payload = {
        categorie:   form.value.categorie,
        epaisseur:   form.value.epaisseur,
        prix:        form.value.prix,
        contrat_id:  form.value.contrat_id || null,
        ...(editingTarif.value ? { update_lignes: form.value.update_lignes } : {}),
    };

    const request = editingTarif.value
        ? axios.put(`/admin/configuration/planche-tarifs/${editingTarif.value.id}`, payload)
        : axios.post('/admin/configuration/planche-tarifs', payload);

    request
        .then((response) => {
            const saved = response.data.data;
            if (editingTarif.value) {
                const idx = tarifs.value.findIndex((t) => t.id === editingTarif.value.id);
                if (idx !== -1) tarifs.value[idx] = saved;
            } else {
                tarifs.value.push(saved);
            }
            cancelEdit();
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                formErrors.value = error.response.data.errors || {};
                formError.value = error.response.data.message || 'Veuillez corriger les erreurs.';
                return;
            }
            formError.value = error.response?.data?.message || 'Une erreur est survenue.';
        })
        .finally(() => { submitting.value = false; });
}

function deleteTarif(tarif) {
    Swal.fire({
        title: 'Supprimer ce tarif ?',
        text: `${categorieLabel(tarif.categorie)} — ${formatDecimal(tarif.epaisseur)} → ${formatCurrency(tarif.prix)}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
    }).then((result) => {
        if (!result.isConfirmed) return;
        axios.delete(`/admin/configuration/planche-tarifs/${tarif.id}`)
            .then(() => {
                tarifs.value = tarifs.value.filter((t) => t.id !== tarif.id);
            })
            .catch((error) => {
                Swal.fire('Erreur', error.response?.data?.message || 'Impossible de supprimer.', 'error');
            });
    });
}

function categorieLabel(cat) {
    return { mate: 'Mate', semi_brillant: 'Semi-brillant', brillant: 'Brillant' }[cat] || cat || '-';
}
function categorieBadgeClass(cat) {
    return { mate: 'badge-secondary', semi_brillant: 'badge-warning', brillant: 'badge-success' }[cat] || 'badge-light';
}
function formatDecimal(value) {
    return value === null || value === undefined ? '-' : Number(value).toFixed(2);
}
function formatCurrency(value) {
    const num = Math.round(Number(value || 0));
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' CFA';
}
</script>
