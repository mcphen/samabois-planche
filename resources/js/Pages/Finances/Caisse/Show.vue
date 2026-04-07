<template>
    <Head :title="`🧾 ${caisse.name} | ${appName}`" />
    <AuthenticatedLayout>
        <BreadcrumbsAndActions :title="`🧾 Détail caisse — ${caisse.name}`" :breadcrumbs="breadcrumbs">
            <template #action>
                <button class="btn btn-light btn-sm me-2" @click="goBack"><i class="fa fa-arrow-left"></i> Retour</button>
                <button class="btn btn-success btn-sm me-1" @click="openEntreeModal"><i class="fa fa-plus"></i> Nouvelle entrée</button>
                <button class="btn btn-danger btn-sm me-1" @click="openSortieModal"><i class="fa fa-minus"></i> Nouvelle sortie</button>
                <button class="btn btn-info btn-sm me-1" @click="openTransferModal"><i class="fa fa-exchange-alt"></i> Transférer</button>
                <button class="btn btn-primary btn-sm me-2" @click="printHistorique"><i class="fa fa-print"></i> Imprimer</button>
                <button class="btn btn-warning btn-sm me-1" @click="openEdit"><i class="fa fa-edit"></i> Modifier</button>
                <button class="btn btn-sm" :class="caisse.active ? 'btn-outline-secondary' : 'btn-outline-success'" @click="toggleActive">
                    <i :class="caisse.active ? 'fa fa-ban' : 'fa fa-check'" /> {{ caisse.active ? 'Désactiver' : 'Activer' }}
                </button>
            </template>
        </BreadcrumbsAndActions>

        <div class="row clearfix">
            <div class="col-lg-6">
                <div class="card">
                    <div class="body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3 icon-wrap" :class="iconBgClass(caisse.type)">
                                <i :class="iconClass(caisse.type)"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ caisse.name }}</h5>
                                <div class="text-muted small">Type: {{ labelType(caisse.type) }} • Devise: {{ caisse.currency_code || '-' }}</div>
                            </div>
                            <div class="ms-auto">
                                <span class="badge" :class="caisse.active ? 'badge-success' : 'badge-secondary'">{{ caisse.active ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>

                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><strong>Solde initial: </strong>{{ formatPrice(caisse.initial_balance) }}</li>
                            <li class="mb-2"><strong>ID: </strong>#{{ caisse.id }}</li>
                            <li class="mb-2"><strong>Créée le: </strong>{{ formatDate(caisse.created_at) }}</li>
                            <li class="mb-2"><strong>Modifiée le: </strong>{{ formatDate(caisse.updated_at) }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloc Résumé -->
        <div class="card mb-3">
            <div class="body">
                <h6 class="mb-3">Résumé</h6>
                <div class="row g-3">
                    <div class="col-md-4 col-lg-2">
                        <div class="small text-muted">Solde initial</div>
                        <div class="fw-bold">{{ formatPrice(summary.period_opening_balance ?? 0) }}</div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="small text-muted">Total entrées</div>
                        <div class="fw-bold text-success">{{ formatPrice(summary.total_entrees ?? 0) }}</div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="small text-muted">Total sorties</div>
                        <div class="fw-bold text-danger">{{ formatPrice(summary.total_sorties ?? 0) }}</div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="small text-muted">Transferts entrants</div>
                        <div class="fw-bold text-success">{{ formatPrice(summary.total_transfers_in ?? 0) }}</div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="small text-muted">Transferts sortants</div>
                        <div class="fw-bold text-danger">{{ formatPrice(summary.total_transfers_out ?? 0) }}</div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="small text-muted">Solde final calculé</div>
                        <div class="fw-bold">{{ formatPrice(summary.computed_closing_balance ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions paginées -->
        <div class="card">
            <div class="body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Du</label>
                        <input type="date" class="form-control" v-model="filters.start_date" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Au</label>
                        <input type="date" class="form-control" v-model="filters.end_date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select class="form-control" v-model="filters.type">
                            <option value="">Tous</option>
                            <option value="entree">Entrée</option>
                            <option value="sortie">Sortie</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Par page</label>
                        <select class="form-control" v-model.number="filters.per_page" @change="goToPage(1)">
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-secondary w-100" @click="applyFilters">Filtrer</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Caisse</th>
                            <th>Type</th>
                            <th>Objet</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">Solde</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="loadingTxns">
                            <td colspan="6" class="text-center text-muted">Chargement...</td>
                        </tr>
                        <tr v-for="t in transactions" :key="t.id" :class="{'text-decoration-line-through text-muted': t.transfer_status === 'annulé', 'text-italic text-muted': t.transfer_status === 'corrigé'}">
                            <td>{{ formatDate(t.date) }}</td>
                            <td>{{ caisse.name }}</td>
                            <td>
                                <span class="badge" :class="t.type==='entree' ? 'badge-success' : 'badge-danger'">{{ t.type }}</span>
                            </td>
                            <td>
                  <span v-if="t.type==='sortie'">
                    {{ t.objet || '-' }}
                    <div v-if="t.caisse_transfer_id && t.transfer_dest_name" class="small text-muted">
                      Vers: {{ t.transfer_dest_name }}
                    </div>
                    <span v-if="t.transfer_status === 'annulé'" class="badge badge-secondary ms-1">Annulé</span>
                    <span v-if="t.transfer_status === 'corrigé'" class="badge badge-warning ms-1">Corrigé</span>
                  </span>
                                <span v-else>
                    {{ t.isSolde ? 'Ajustement ' : 'Paiement ' }}
                    {{ t.client ? '• ' + t.client : t.objet }}
                    <div v-if="t.caisse_transfer_id && t.transfer_source_name" class="small text-muted">
                      De: {{ t.transfer_source_name }}
                    </div>
                    <span v-if="t.transfer_status === 'annulé'" class="badge badge-secondary ms-1">Annulé</span>
                    <span v-if="t.transfer_status === 'corrigé'" class="badge badge-warning ms-1">Corrigé</span>
                  </span>
                            </td>
                            <td class="text-end">{{ formatPrice(t.amount) }}</td>
                            <td class="text-end">{{ formatPrice(t.solde) }}</td>

                            <td>
                                <div v-if="t.type==='sortie' && t.transfer_status !== 'annulé' && t.transfer_status !== 'corrigé' && !(t.objet && t.objet.startsWith('Contre-écriture'))">
                                    <button class="btn btn-sm btn-warning m-1" @click="openEditSortieModal(t)">
                                        <i class="fa fa-edit"></i> Modifier
                                    </button>
                                    <button class="btn btn-sm btn-danger m-1" @click="deleteSortie(t.id)">
                                        <i class="fa fa-trash"></i> Supprimer
                                    </button>
                                </div>
                                <div v-else-if="t.type==='entree' && (!t.client && t.objet !== 'Transfert entrant') && t.transfer_status !== 'annulé' && t.transfer_status !== 'corrigé' && !(t.objet && t.objet.startsWith('Contre-écriture'))">
                                    <button class="btn btn-sm btn-warning m-1" @click="openEditEntreeModal(t)">
                                        <i class="fa fa-edit"></i> Modifier
                                    </button>
                                    <button class="btn btn-sm btn-danger m-1" @click="deleteEntree(t.id)">
                                        <i class="fa fa-trash"></i> Supprimer
                                    </button>
                                </div>
                                <div v-else-if="t.type==='entree' && t.client && t.transfer_status !== 'annulé' && t.transfer_status !== 'corrigé'">
                                    <button v-if="!t.isSolde" class="btn btn-sm btn-primary m-1" @click="openCorrectPaymentModal(t)">
                                        <i class="fa fa-wrench"></i> Corriger paiement
                                    </button>
                                    <span v-else class="text-muted small italic">Ajustement non modifiable</span>
                                </div>
                                <div v-else-if="t.type==='entree' && t.objet === 'Transfert entrant'">
                                    <button v-if="t.transfer_status === 'validé'" class="btn btn-sm btn-info m-1" @click="openCorrectTransferModal(t)">
                                        <i class="fa fa-wrench"></i> Corriger / Annuler
                                    </button>
                                    <span v-else class="badge" :class="t.transfer_status === 'annulé' ? 'badge-secondary' : 'badge-warning'">
                            Transfert {{ t.transfer_status }}
                          </span>
                                </div>
                                <div v-else-if="t.transfer_status === 'annulé' || t.transfer_status === 'corrigé' || (t.objet && t.objet.startsWith('Contre-écriture'))">
                                    <span v-if="t.objet && t.objet.startsWith('Contre-écriture')" class="badge badge-info">Contre-écriture</span>
                                    <span v-else class="badge" :class="t.transfer_status === 'annulé' ? 'badge-secondary' : 'badge-warning'">
                            {{ t.transfer_status }}
                          </span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!loadingTxns && transactions.length===0">
                            <td colspan="6" class="text-center text-muted">Aucune transaction</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-muted small">
                        Solde d'ouverture: <strong>{{ formatPrice(summary.opening_balance) }}</strong>
                        • Solde courant (page): <strong>{{ formatPrice(summary.closing_balance) }}</strong>
                    </div>
                    <!-- Pagination component (client-side) -->
                    <Pagination :pagination="paginationData" @pageChanged="goToPage" />
                </div>
            </div>
        </div>

        <!-- POP-UP : Correction Paiement Client -->
        <div v-if="showCorrectPayment" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">✏️ Corriger paiement client</h5>
                        <button type="button" class="close" @click="closeCorrectPayment">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitCorrectPayment">
                            <div class="form-group mb-2">
                                <label>Client</label>
                                <input type="text" class="form-control" :value="correctPayment.client_name" readonly />
                            </div>
                            <div class="form-group mb-2">
                                <label>Montant</label>
                                <input type="number" class="form-control" v-model.number="correctPayment.amount" min="0.01" step="0.01" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Date</label>
                                <input type="date" class="form-control" v-model="correctPayment.transaction_date" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Description (optionnel)</label>
                                <input type="text" class="form-control" v-model="correctPayment.description" />
                            </div>
                            <div class="form-group mb-2">
                                <label>Motif de correction (optionnel)</label>
                                <input type="text" class="form-control" v-model="correctPayment.reason" />
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-light me-2" @click="closeCorrectPayment">Annuler</button>
                                <button type="button" class="btn btn-outline-danger me-2" @click="deleteCorrectPayment" :disabled="submittingCorrectPayment">
                                    <i class="fa fa-trash"></i> Supprimer
                                </button>
                                <button type="submit" class="btn btn-primary" :disabled="submittingCorrectPayment">{{ submittingCorrectPayment ? 'En cours...' : 'Corriger' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- POP-UP : Correction Transfert Entrant -->
        <div v-if="showCorrectTransfer" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">✏️ Corriger transfert entrant</h5>
                        <button type="button" class="close" @click="closeCorrectTransfer">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitCorrectTransfer">
                            <div class="form-group mb-2">
                                <label>Caisse destination</label>
                                <input type="text" class="form-control" :value="caisse.name" readonly />
                            </div>
                            <div class="form-group mb-2">
                                <label>Montant source</label>
                                <input type="number" class="form-control" v-model.number="correctTransfer.amount_source" min="0.01" step="0.01" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Taux de change (si devises différentes)</label>
                                <input type="number" class="form-control" v-model.number="correctTransfer.exchange_rate" min="0.000001" step="0.000001" />
                            </div>
                            <div class="form-group mb-2">
                                <label>Montant destination (optionnel, sinon calculé)</label>
                                <input type="number" class="form-control" v-model.number="correctTransfer.amount_destination" min="0.01" step="0.01" />
                            </div>
                            <div class="form-group mb-2">
                                <label>Date du transfert</label>
                                <input type="date" class="form-control" v-model="correctTransfer.transfer_date" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Description (optionnel)</label>
                                <input type="text" class="form-control" v-model="correctTransfer.description" />
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-light me-2" @click="closeCorrectTransfer">Annuler</button>
                                <button type="button" class="btn btn-outline-danger me-2" @click="cancelCorrectTransfer" :disabled="submittingCorrectTransfer">
                                    <i class="fa fa-ban"></i> Annuler le transfert
                                </button>
                                <button type="submit" class="btn btn-info" :disabled="submittingCorrectTransfer">{{ submittingCorrectTransfer ? 'En cours...' : 'Corriger' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal édition rapide -->
        <div v-if="showModal" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la caisse</h5>
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
                                <label>Solde initial</label>
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
                                    {{ submitting ? 'En cours...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- POP-UP : ENTREE (création / édition) -->
        <div v-if="showEntree" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editEntree ? '✏️ Modifier une entrée' : '➕ Nouvelle entrée' }}</h5>
                        <button type="button" class="close" @click="closeEntree">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitEntree">
                            <div class="form-group mb-2">
                                <label>Type d'entrée</label>
                                <div>
                                    <label class="me-3" :class="{ 'text-muted': editEntree }">
                                        <input type="radio" value="paiement_client" v-model="entree.type" :disabled="editEntree" /> Paiement client
                                    </label>
                                    <label :class="{ 'text-muted': editEntree }">
                                        <input type="radio" value="entree_diverse" v-model="entree.type" :disabled="editEntree" /> Entrée diverse
                                    </label>
                                </div>
                            </div>

                            <div class="form-group mb-2" v-if="entree.type === 'paiement_client'">
                                <label>Client</label>
                                <div class="dropdown-select position-relative">
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Rechercher un client..."
                                        v-model="clientQuery"
                                        :disabled="editEntree"
                                        @focus="onClientInputFocus"
                                        @input="onClientInput"
                                        @blur="onClientInputBlur"
                                    />
                                    <div v-if="showClientDropdown" class="dropdown-menu show w-100" style="max-height: 220px; overflow-y: auto; z-index: 1051;">
                                        <div v-if="filteredClients.length === 0" class="dropdown-item text-muted">Aucun client trouvé</div>
                                        <a
                                            v-for="client in filteredClients"
                                            :key="client.id"
                                            class="dropdown-item"
                                            href="#"
                                            @mousedown.prevent="selectClient(client)"
                                        >
                                            {{ client.name }}
                                        </a>
                                    </div>
                                </div>
                                <small v-if="entree.client_id" class="text-muted">Client sélectionné: {{ selectedClientName }}</small>
                            </div>

                            <div class="form-group mb-2">
                                <label>Montant</label>
                                <input type="number" class="form-control" v-model.number="entree.amount" min="0" required />
                            </div>

                            <div class="form-group mb-2">
                                <label>Description <span class="text-muted small">(obligatoire si entrée diverse ou aucun client sélectionné)</span></label>
                                <input type="text" class="form-control" v-model="entree.description" :required="entreeDescriptionRequired" />
                            </div>

                            <div class="form-group mb-2">
                                <label>Date</label>
                                <input type="date" class="form-control" v-model="entree.date" required />
                            </div>

                            <div class="form-group mb-2">
                                <label>Caisse</label>
                                <input type="text" class="form-control" :value="caisse.name" readonly />
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-light me-2" @click="closeEntree">Annuler</button>
                                <button type="submit" class="btn btn-success" :disabled="submittingEntree">{{ submittingEntree ? 'En cours...' : (editEntree ? 'Mettre à jour' : 'Enregistrer') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- POP-UP : NOUVELLE SORTIE -->
        <div v-if="showSortie" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">➖ Nouvelle sortie</h5>
                        <button type="button" class="close" @click="closeSortie">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitSortie">
                            <div class="form-group mb-2">
                                <label>Montant</label>
                                <input type="number" class="form-control" v-model.number="sortie.amount" min="0" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Description (obligatoire)</label>
                                <input type="text" class="form-control" v-model="sortie.description" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Justificatif (optionnel)</label>
                                <input type="file" class="form-control" @change="onJustificatifChange" />
                            </div>
                            <div class="form-group mb-2">
                                <label>Date</label>
                                <input type="date" class="form-control" v-model="sortie.date" required />
                            </div>
                            <div class="form-group mb-2">
                                <label>Caisse</label>
                                <input type="text" class="form-control" :value="caisse.name" readonly />
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-light me-2" @click="closeSortie">Annuler</button>
                                <button type="submit" class="btn btn-danger" :disabled="submittingSortie">{{ submittingSortie ? 'En cours...' : 'Enregistrer' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- POP-UP : TRANSFERT ENTRE CAISSES -->
        <div v-if="showTransfer" class="modal d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">⇆ Transfert entre caisses</h5>
                        <button type="button" class="close" @click="closeTransfer">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitTransfer">
                            <div class="form-group mb-2">
                                <label>Caisse source</label>
                                <input type="text" class="form-control" :value="`${caisse.name} (${caisse.currency_code || '-'})`" readonly />
                            </div>
                            <div class="form-group mb-2">
                                <label>Caisse destination</label>
                                <select class="form-control" v-model.number="transfer.destination_caisse_id" required>
                                    <option :value="0">Sélectionner une caisse</option>
                                    <option v-for="c in otherCaisses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.currency_code || '-' }})</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label>Montant source</label>
                                <input type="number" class="form-control" v-model.number="transfer.amount_source" min="0.01" step="0.01" required />
                            </div>

                            <div v-if="showFx" class="border rounded p-2 mb-2">
                                <div class="form-group mb-2">
                                    <label>Taux de change</label>
                                    <input type="number" class="form-control" v-model.number="transfer.exchange_rate" min="0.000001" step="0.000001" />
                                </div>
                                <div class="form-group mb-2">
                                    <label>Montant destination</label>
                                    <input type="number" class="form-control" v-model.number="transfer.amount_destination" min="0.01" step="0.01" />
                                </div>
                            </div>

                            <div class="form-group mb-2">
                                <label>Description (facultative)</label>
                                <input type="text" class="form-control" v-model="transfer.description" />
                            </div>
                            <div class="form-group mb-2">
                                <label>Date</label>
                                <input type="date" class="form-control" v-model="transfer.transfer_date" required />
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-light me-2" @click="closeTransfer">Annuler</button>
                                <button type="submit" class="btn btn-info" :disabled="submittingTransfer">{{ submittingTransfer ? 'En cours...' : 'Transférer' }}</button>
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
import dayjs from 'dayjs';
import { computed, ref, onMounted, watch, toRefs } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ caisse: { type: Object, required: true } });

console.log(props.caisse)
// Rendre la prop disponible comme variable réactive locale
const  caisse = props.caisse;

const breadcrumbs = computed(() => ([
    { label: 'Tableau de bord', link: '/', icon: 'fa fa-dashboard' },
    { label: '💼 Caisses', link: '/admin/finances/caisses' },
    { label: props.caisse.name },
]));

const showModal = ref(false);
const submitting = ref(false);
const form = ref({
    name: props.caisse.name || '',
    type: props.caisse.type || '',
    currency_code: props.caisse.currency_code || 'XOF',
    initial_balance: props.caisse.initial_balance ?? 0,
    active: !!props.caisse.active,
});

function goBack() {
    router.visit('/admin/finances/caisses');
}

function formatPrice(n) {
    const v = n ? Number(n) : 0;
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(v);
}

function formatDate(d) {
    return d ? dayjs(d).format('DD/MM/YYYY HH:mm') : '-';
}

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

function openEdit() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submit() {
    submitting.value = true;
    try {
        await axios.post(`/admin/finances/caisses/${props.caisse.id}/update`, form.value);
        Swal.fire('Succès', 'Caisse mise à jour', 'success');
        // refresh current page
        router.visit(`/admin/finances/caisses/${props.caisse.id}`, { replace: true });
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', "Vérifiez les champs du formulaire", 'error');
    } finally {
        submitting.value = false;
    }
}

async function toggleActive() {
    const next = !props.caisse.active;
    const actionLabel = next ? 'activer' : 'désactiver';
    const result = await Swal.fire({
        title: `${next ? 'Activer' : 'Désactiver'} la caisse ?`,
        text: `Voulez-vous ${actionLabel} la caisse "${props.caisse.name}" ?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Oui',
        cancelButtonText: 'Annuler',
    });
    if (!result.isConfirmed) return;

    try {
        await axios.post(`/admin/finances/caisses/${props.caisse.id}/update`, { active: next });
        Swal.fire('Succès', `Caisse ${next ? 'activée' : 'désactivée'}`, 'success');
        router.visit(`/admin/finances/caisses/${props.caisse.id}`, { replace: true });
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', `Impossible de ${actionLabel} cette caisse`, 'error');
    }
}

// ===== Entrée =====
const showEntree = ref(false);
const submittingEntree = ref(false);
const clients = ref([]);
// Recherche client (typeahead)
const clientQuery = ref('');
const showClientDropdown = ref(false);
const highlightIndex = ref(-1);
const filteredClients = computed(() => {
    const q = (clientQuery.value || '').toLowerCase().trim();
    if (!q) return clients.value;
    return clients.value.filter(c => (c.name || '').toLowerCase().includes(q));
});
const selectedClientName = computed(() => {
    const id = entree.value.client_id;
    if (!id) return '';
    const cl = clients.value.find(c => c.id === id);
    return cl ? cl.name : '';
});
const editEntree = ref(false);
const currentEntreeId = ref(null);
const entree = ref({
    type: 'paiement_client',
    client_id: null,
    amount: null,
    description: '',
    date: dayjs().format('YYYY-MM-DD'),
});

const entreeDescriptionRequired = computed(() => {
    if (entree.value.type === 'entree_diverse') return true;
    // paiement client: description optionnelle, sauf si aucun client sélectionné
    return !entree.value.client_id;
});

function openEntreeModal() {
    // mode création
    editEntree.value = false;
    currentEntreeId.value = null;
    entree.value = { type: 'paiement_client', client_id: null, amount: null, description: '', date: dayjs().format('YYYY-MM-DD') };
    showEntree.value = true;
    fetchClientsIfNeeded();
    clientQuery.value = '';
    showClientDropdown.value = false;
    highlightIndex.value = -1;
}
function closeEntree() {
    showEntree.value = false;
    editEntree.value = false;
    currentEntreeId.value = null;
    clientQuery.value = '';
    showClientDropdown.value = false;
    highlightIndex.value = -1;
}

async function fetchClientsIfNeeded() {
    if (clients.value.length) return;
    try {
        const { data } = await axios.get('/admin/clients/liste-clients');
        // API is paginated; support both array or {data: []}
        clients.value = Array.isArray(data) ? data : (data.data || []);
    } catch (e) {
        console.error(e);
    }
}

async function submitEntree() {
    // Validation simple côté client
    if (!entree.value.amount || entree.value.amount <= 0) {
        return Swal.fire('Erreur', 'Montant invalide', 'error');
    }
    if (entreeDescriptionRequired.value && !entree.value.description) {
        return Swal.fire('Erreur', 'La description est obligatoire', 'error');
    }

    submittingEntree.value = true;
    try {
        if (editEntree.value && currentEntreeId.value) {
            // Mise à jour d'une entrée diverse
            await axios.post(`/admin/finances/caisse/entree/${currentEntreeId.value}/update`, {
                amount: entree.value.amount,
                description: entree.value.description,
                objet: entree.value.description,
                date: entree.value.date,
                caisse_id: caisse.id,
            });
            Swal.fire('Succès', 'Entrée modifiée avec succès', 'success');
        } else if (entree.value.type === 'paiement_client') {
            if (!entree.value.client_id) {
                return Swal.fire('Erreur', 'Veuillez sélectionner un client ou choisir "Entrée diverse".', 'error');
            }
            await axios.post(`/admin/clients/${entree.value.client_id}/payments`, {
                amount: entree.value.amount,
                transaction_date: entree.value.date,
                caisse_id: caisse.id,
            });
        } else {
            await axios.post('/admin/finances/caisse/entree-diverse', {
                amount: entree.value.amount,
                description: entree.value.description,
                date: entree.value.date,
                caisse_id: caisse.id,
            });
            Swal.fire('Succès', 'Entrée enregistrée avec succès', 'success');
        }
        // Recharger la liste des transactions et les totaux
        fetchTransactions(currentPage.value || 1);
        showEntree.value = false;
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', "Impossible d'enregistrer l'entrée", 'error');
    } finally {
        submittingEntree.value = false;
    }
}

// Handlers pour le champ de recherche client
function onClientInputFocus() {
    if (editEntree.value) return;
    fetchClientsIfNeeded();
    showClientDropdown.value = true;
}

function onClientInput() {
    if (editEntree.value) return;
    const q = clientQuery.value || '';
    // Si la saisie ne correspond plus exactement au client sélectionné, on réinitialise la sélection
    if (selectedClientName.value && selectedClientName.value !== q) {
        entree.value.client_id = null;
    }
    showClientDropdown.value = true;
    highlightIndex.value = filteredClients.value.length ? 0 : -1;
}

function moveClientHighlight(delta) {
    if (!showClientDropdown.value || !filteredClients.value.length) return;
    const len = filteredClients.value.length;
    highlightIndex.value = (highlightIndex.value + delta + len) % len;
}

function confirmHighlightedClient() {
    if (!filteredClients.value.length) return;
    const idx = highlightIndex.value >= 0 ? highlightIndex.value : 0;
    const cl = filteredClients.value[idx];
    if (cl) selectClient(cl);
}

function selectClient(cl) {
    entree.value.client_id = cl.id;
    clientQuery.value = cl.name || '';
    showClientDropdown.value = false;
}

function onClientInputBlur() {
    // Laisser le temps au clic sur un élément de la liste
    setTimeout(() => {
        showClientDropdown.value = false;
    }, 150);
}

// Edition/Suppression d'entrée diverse
function openEditEntreeModal(t) {
    // On n'autorise l'édition que pour les entrées diverses (pas paiements clients ni transferts)
    editEntree.value = true;
    currentEntreeId.value = t.id;
    entree.value = {
        type: 'entree_diverse',
        client_id: null,
        amount: t.amount,
        // On n'a pas la description dans la liste (backend n'envoie pas), on préremplit avec l'objet au besoin
        description: t.objet || '',
        date: dayjs(t.date).format('YYYY-MM-DD'),
    };
    showEntree.value = true;
}

function deleteEntree(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: 'Cette action est irréversible !',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer !'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/admin/finances/caisse/entree/${id}`);
                Swal.fire('Supprimé !', "L'entrée a été supprimée.", 'success');
                fetchTransactions(currentPage.value || 1);
            } catch (error) {
                const msg = error?.response?.data?.message || "Une erreur est survenue lors de la suppression.";
                Swal.fire('Erreur', msg, 'error');
            }
        }
    });
}

// ===== Sortie =====
const showSortie = ref(false);
const submittingSortie = ref(false);
const sortie = ref({ amount: null, description: '', date: dayjs().format('YYYY-MM-DD'), file: null });
const editSortie = ref(false);
const currentSortieId = ref(null);

function openSortieModal() {
    // mode création
    editSortie.value = false;
    currentSortieId.value = null;
    sortie.value = { amount: null, description: '', date: dayjs().format('YYYY-MM-DD'), file: null };
    showSortie.value = true;
}
function closeSortie() {
    showSortie.value = false;
    editSortie.value = false;
    currentSortieId.value = null;
}
function onJustificatifChange(e) { sortie.value.file = e.target.files?.[0] || null; }

// Ouvre le modal en mode modification et charge les données existantes (même logique que HistoriqueCaisse.vue)
function openEditSortieModal(t) {
    editSortie.value = true;
    currentSortieId.value = t.id;
    sortie.value = {
        amount: t.amount,
        // dans l'historique c'est "objet"; on le mappe sur description du formulaire
        description: t.objet || t.description || '',
        date: dayjs(t.date).format('YYYY-MM-DD'),
        file: null,
    };
    showSortie.value = true;
}

// Suppression d'une sortie (même logique que HistoriqueCaisse.vue)
function deleteSortie(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: 'Cette action est irréversible !',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui, supprimer !'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/admin/finances/caisse/sortie/${id}`);
                Swal.fire('Supprimé !', 'La sortie a été supprimée.', 'success');
                fetchTransactions(currentPage.value || 1);
            } catch (error) {
                Swal.fire('Erreur', "Une erreur est survenue lors de la suppression.", 'error');
            }
        }
    });
}

async function submitSortie() {
    if (!sortie.value.amount || sortie.value.amount <= 0) {
        return Swal.fire('Erreur', 'Montant invalide', 'error');
    }
    if (!sortie.value.description) {
        return Swal.fire('Erreur', 'La description est obligatoire', 'error');
    }
    submittingSortie.value = true;
    try {
        if (editSortie.value && currentSortieId.value) {
            // Mise à jour (comme HistoriqueCaisse.vue)
            await axios.post(`/admin/finances/caisse/sortie/${currentSortieId.value}/update`, {
                amount: sortie.value.amount,
                objet: sortie.value.description,
                description: sortie.value.description,
                date: sortie.value.date,
                caisse_id: caisse.id,
            });
            Swal.fire('Succès', 'Sortie modifiée avec succès', 'success');
        } else {
            // Création
            await axios.post('/admin/finances/caisse/sortie', {
                amount: sortie.value.amount,
                objet: sortie.value.description, // map obligatoire backend
                description: sortie.value.description,
                date: sortie.value.date,
                caisse_id: caisse.id,
            });
            Swal.fire('Succès', 'Sortie enregistrée avec succès', 'success');
        }
        // Recharger la liste des transactions et les totaux
        fetchTransactions(currentPage.value || 1);
        closeSortie();
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', 'Impossible d\'enregistrer la sortie', 'error');
    } finally {
        submittingSortie.value = false;
    }
}

// ===== Transfert =====
const showTransfer = ref(false);
const submittingTransfer = ref(false);
const caisses = ref([]);
const soldeSource = ref(null);
const transfer = ref({
    destination_caisse_id: 0,
    amount_source: null,
    exchange_rate: null,
    amount_destination: null,
    description: '',
    transfer_date: dayjs().format('YYYY-MM-DD'),
});

const otherCaisses = computed(() => caisses.value.filter(c => c.id !== caisse.id));
const showFx = computed(() => {
    const dest = caisses.value.find(c => c.id === transfer.value.destination_caisse_id);
    if (!dest) return false;
    return (dest.currency_code || 'XOF') !== (caisse.currency_code || 'XOF');
});

function openTransferModal() {
    showTransfer.value = true;
    fetchCaissesIfNeeded();
    fetchSoldeSource();
}
function closeTransfer() { showTransfer.value = false; }

async function fetchCaissesIfNeeded() {
    if (caisses.value.length) return;
    try {
        const { data } = await axios.get('/admin/finances/caisses/listes');
        caisses.value = data;
    } catch (e) { console.error(e); }
}

async function submitTransfer() {
    if (!transfer.value.destination_caisse_id) return Swal.fire('Erreur', 'Sélectionnez la caisse destination', 'error');
    if (!transfer.value.amount_source || transfer.value.amount_source <= 0) return Swal.fire('Erreur', 'Montant source invalide', 'error');
    if (transfer.value.destination_caisse_id === caisse.id) return Swal.fire('Erreur', 'La caisse destination doit être différente de la source', 'error');
    if (showFx.value) {
        if (!transfer.value.exchange_rate || transfer.value.exchange_rate <= 0) return Swal.fire('Erreur', 'Le taux de change est obligatoire', 'error');
        if (!transfer.value.amount_destination || transfer.value.amount_destination <= 0) return Swal.fire('Erreur', 'Le montant destination est obligatoire', 'error');
    }
    if (soldeSource.value !== null && transfer.value.amount_source > soldeSource.value) {
        return Swal.fire('Erreur', 'Solde insuffisant dans la caisse source', 'error');
    }
    submittingTransfer.value = true;
    try {
        await axios.post('/admin/finances/caisse/transfer', {
            source_caisse_id: caisse.id,
            destination_caisse_id: transfer.value.destination_caisse_id,
            amount_source: transfer.value.amount_source,
            exchange_rate: showFx.value ? (transfer.value.exchange_rate || 1) : null,
            amount_destination: showFx.value ? transfer.value.amount_destination : null,
            description: transfer.value.description || null,
            transfer_date: transfer.value.transfer_date,
        });
        Swal.fire('Succès', 'Transfert effectué avec succès', 'success');
        // Recharger la liste des transactions et les totaux
        fetchTransactions(currentPage.value || 1);
        showTransfer.value = false;
    } catch (e) {
        console.error(e);
        const msg = e?.response?.data?.message || 'Impossible d\'effectuer le transfert';
        Swal.fire('Erreur', msg, 'error');
    } finally {
        submittingTransfer.value = false;
    }
}

async function fetchSoldeSource() {
    try {
        const { data } = await axios.get('/admin/finances/caisse/solde', { params: { caisse_id: caisse.id } });
        soldeSource.value = data.solde ?? null;
    } catch (e) { console.error(e); }
}

// Auto-calcul du montant destination lorsque FX affiché
watch(() => [showFx.value, transfer.value.amount_source, transfer.value.exchange_rate], ([fx, amt, rate]) => {
    if (fx && amt && rate && amt > 0 && rate > 0) {
        transfer.value.amount_destination = Number((amt * rate).toFixed(2));
    }
});

// Print
function printHistorique() {
    const url = `/admin/finances/caisse/historique/pdf?caisse_id=${caisse.id}`;
    window.open(url, '_blank');
}

// ===== Transactions paginées (liste) - client-side =====
const allTransactions = ref([]); // full dataset from API (chronological ASC)
const transactions = ref([]);    // current page slice
const currentPage = ref(1);
const summary = ref({ opening_balance: 0, closing_balance: 0 });
const loadingTxns = ref(false);
const filters = ref({ start_date: '', end_date: '', type: '', per_page: 15 });

const paginationData = computed(() => {
    const totalItems = allTransactions.value.length;
    const perPage = filters.value.per_page || 15;
    const lastPage = Math.max(1, Math.ceil(totalItems / perPage));
    const links = Array.from({ length: lastPage }, (_, i) => ({
        url: '#',
        label: i + 1,
        active: i + 1 === currentPage.value,
    }));
    return {
        current_page: currentPage.value,
        last_page: lastPage,
        prev_page_url: currentPage.value > 1 ? '#' : null,
        next_page_url: currentPage.value < lastPage ? '#' : null,
        links,
    };
});

function recalcPageSummary() {
    const perPage = filters.value.per_page || 15;
    const startIndex = (currentPage.value - 1) * perPage;
    const endIndex = Math.min(startIndex + perPage, allTransactions.value.length);
    const opening = startIndex > 0
        ? (allTransactions.value[startIndex - 1]?.solde ?? 0)
        : (summary.value.opening_balance ?? 0);
    const closing = endIndex > startIndex
        ? (allTransactions.value[endIndex - 1]?.solde ?? opening)
        : opening;
    summary.value = {
        ...summary.value,
        opening_balance: opening,
        closing_balance: closing,
    };
}

function sliceTransactions() {
    const perPage = filters.value.per_page || 15;
    const startIndex = (currentPage.value - 1) * perPage;
    const endIndex = startIndex + perPage;
    transactions.value = allTransactions.value.slice(startIndex, endIndex);
    recalcPageSummary();
}

async function fetchTransactions(page = null) {
    loadingTxns.value = true;
    try {
        const params = {
            caisse_id: caisse.id,
        };
        if (filters.value.start_date) params.start_date = filters.value.start_date;
        if (filters.value.end_date) params.end_date = filters.value.end_date;
        if (filters.value.type) params.type = filters.value.type;

        const { data } = await axios.get('/admin/finances/caisse/fetch-caisse', { params });
        // On reçoit désormais la liste en ordre chronologique (ASC) avec solde cumulatif côté backend
        allTransactions.value = data.data || [];
        // Conserver la partie résumé globale renvoyée par l'API, puis on recalcule le résumé de page après le slice
        summary.value = data.summary || summary.value;
        // Déterminer la page: si fournie on la borne, sinon démarrer à la page 1
        const perPage = filters.value.per_page || 15;
        const last = Math.max(1, Math.ceil(allTransactions.value.length / perPage));
        if (page) {
            const requested = Number(page) || 1;
            currentPage.value = Math.max(1, Math.min(requested, last));
        } else {
            // Par défaut, démarrer sur la dernière page
            currentPage.value = last;
        }
        sliceTransactions();
    } catch (e) {
        console.error(e);
        Swal.fire('Erreur', "Impossible de charger les transactions de caisse", 'error');
    } finally {
        loadingTxns.value = false;
    }
}

function applyFilters() {
    // Refetch data with new filters and reset to page 1
    fetchTransactions(1);
}

function goToPage(p) {
    const perPage = filters.value.per_page || 15;
    const last = Math.max(1, Math.ceil(allTransactions.value.length / perPage));
    let pageNum = p;
    if (pageNum < 1) pageNum = 1;
    if (pageNum > last) pageNum = last;
    currentPage.value = pageNum;
    sliceTransactions();
}

onMounted(() => {
    // Preload for better UX
    fetchCaissesIfNeeded();
    fetchTransactions();
});

// ===== Correction Paiement Client =====
const showCorrectPayment = ref(false);
const submittingCorrectPayment = ref(false);
const correctPayment = ref({
    transaction_id: null,
    client_name: '',
    amount: null,
    transaction_date: dayjs().format('YYYY-MM-DD'),
    description: '',
    reason: '',
});

function openCorrectPaymentModal(t) {
    correctPayment.value = {
        transaction_id: t.id,
        client_name: t.client || '',
        amount: t.amount,
        transaction_date: dayjs(t.date).format('YYYY-MM-DD'),
        description: t.objet || '',
        reason: '',
    };
    showCorrectPayment.value = true;
}
function closeCorrectPayment() {
    showCorrectPayment.value = false;
    submittingCorrectPayment.value = false;
}
async function submitCorrectPayment() {
    if (!correctPayment.value.amount || correctPayment.value.amount <= 0) {
        return Swal.fire('Erreur', 'Montant invalide', 'error');
    }
    submittingCorrectPayment.value = true;
    try {
        await axios.post(`/admin/finances/caisse/payment/${correctPayment.value.transaction_id}/correct`, {
            amount: correctPayment.value.amount,
            transaction_date: correctPayment.value.transaction_date,
            description: correctPayment.value.description || null,
            reason: correctPayment.value.reason || null,
        });
        Swal.fire('Succès', 'Paiement corrigé avec succès', 'success');
        showCorrectPayment.value = false;
        fetchTransactions(currentPage.value || 1);
    } catch (e) {
        const msg = e?.response?.data?.message || 'Impossible de corriger le paiement';
        Swal.fire('Erreur', msg, 'error');
    } finally {
        submittingCorrectPayment.value = false;
    }
}

// Suppression du paiement client depuis le modal de correction
async function deleteCorrectPayment() {
    const txnId = correctPayment.value.transaction_id;
    if (!txnId) return;
    const result = await Swal.fire({
        title: 'Supprimer ce paiement ?\n',
        text: 'Cette action supprimera définitivement le paiement et ses liens. Continuer ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
    });
    if (!result.isConfirmed) return;
    submittingCorrectPayment.value = true;
    try {
        // envoyer raison en body avec axios.delete via config.data
        await axios.delete(`/admin/finances/caisse/payment/${txnId}`, {
            data: { reason: correctPayment.value.reason || correctPayment.value.description || null },
        });
        Swal.fire('Supprimé', 'Le paiement a été supprimé avec succès', 'success');
        showCorrectPayment.value = false;
        fetchTransactions(currentPage.value || 1);
    } catch (e) {
        const msg = e?.response?.data?.message || "Impossible de supprimer le paiement";
        Swal.fire('Erreur', msg, 'error');
    } finally {
        submittingCorrectPayment.value = false;
    }
}

// ===== Correction Transfert Entrant =====
const showCorrectTransfer = ref(false);
const submittingCorrectTransfer = ref(false);
const correctTransfer = ref({
    transaction_id: null,
    amount_source: null,
    exchange_rate: null,
    amount_destination: null,
    transfer_date: dayjs().format('YYYY-MM-DD'),
    description: '',
});

function openCorrectTransferModal(t) {
    correctTransfer.value = {
        transaction_id: t.id,
        amount_source: null, // l'utilisateur fournit le montant source correct
        exchange_rate: null,
        amount_destination: t.amount, // pré-rempli du côté destination
        transfer_date: dayjs(t.date).format('YYYY-MM-DD'),
        description: t.objet || '',
    };
    showCorrectTransfer.value = true;
}
function closeCorrectTransfer() {
    showCorrectTransfer.value = false;
    submittingCorrectTransfer.value = false;
}
async function submitCorrectTransfer() {
    if (!correctTransfer.value.amount_source || correctTransfer.value.amount_source <= 0) {
        return Swal.fire('Erreur', 'Montant source invalide', 'error');
    }
    if (!correctTransfer.value.transfer_date) {
        return Swal.fire('Erreur', 'Date de transfert requise', 'error');
    }
    submittingCorrectTransfer.value = true;
    try {
        await axios.post(`/admin/finances/caisse/transfer-entry/${correctTransfer.value.transaction_id}/correct`, {
            amount_source: correctTransfer.value.amount_source,
            exchange_rate: correctTransfer.value.exchange_rate || null,
            amount_destination: correctTransfer.value.amount_destination || null,
            transfer_date: correctTransfer.value.transfer_date,
            description: correctTransfer.value.description || null,
        });
        Swal.fire('Succès', 'Transfert corrigé avec succès', 'success');
        showCorrectTransfer.value = false;
        fetchTransactions(currentPage.value || 1);
    } catch (e) {
        const msg = e?.response?.data?.message || "Impossible de corriger le transfert";
        Swal.fire('Erreur', msg, 'error');
    } finally {
        submittingCorrectTransfer.value = false;
    }
}

// Annulation du transfert entrant depuis le modal de correction
async function cancelCorrectTransfer() {
    const txnId = correctTransfer.value.transaction_id;
    if (!txnId) return;
    const result = await Swal.fire({
        title: 'Annuler ce transfert ?',
        text: 'Cette action créera une contre-écriture inverse pour annuler ce transfert sans supprimer l\'historique. Continuer ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, annuler',
        cancelButtonText: 'Fermer',
    });
    if (!result.isConfirmed) return;
    submittingCorrectTransfer.value = true;
    try {
        await axios.delete(`/admin/finances/caisse/transfer-entry/${txnId}`, {
            data: { reason: correctTransfer.value.description || 'Annulation demandée par l\'utilisateur' },
        });
        Swal.fire('Annulé', 'Le transfert a été annulé par contre-écriture avec succès', 'success');
        showCorrectTransfer.value = false;
        fetchTransactions(currentPage.value || 1);
    } catch (e) {
        const msg = e?.response?.data?.message || 'Impossible d\'annuler le transfert';
        Swal.fire('Erreur', msg, 'error');
    } finally {
        submittingCorrectTransfer.value = false;
    }
}
</script>

<style scoped>
.icon-wrap {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 20px;
}
.modal { background-color: rgba(0,0,0,0.4); }
.modal-dialog { max-width: 520px; }
</style>
