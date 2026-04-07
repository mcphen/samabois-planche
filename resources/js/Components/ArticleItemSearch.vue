<template>
    <div>
        <div class="row">
            <div class="col-md-4">
                <label>Numéro de Contrat</label>
                <select v-model="search.contract_number" class="form-control">
                    <option value=""></option>
                    <option v-for="art in articles.data" :key="art.id" :value="art.contract_number">{{ art.contract_number }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>Numéro de Colis</label>
                <input v-model="search.numero_colis" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
                <label>Essence</label>
                <select v-model="search.essence" class="form-control">
                    <option value="">Toutes</option>
                    <option v-for="ess in essences" :key="ess" :value="ess">{{ ess }}</option>
                </select>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-4">
                <label>Longueur (Min - Max)</label>
                <div class="d-flex">
                    <input v-model="search.longueur_min" type="number" class="form-control" placeholder="Min" />
                    <input v-model="search.longueur_max" type="number" class="form-control ms-2" placeholder="Max" />
                </div>
            </div>
            <div class="col-md-4">
                <label>Épaisseur </label>
                <select v-model="search.epaisseur_min"
                        class="form-control">
                    <option value="">toutes les epaisseurs</option>
                    <option value="27">27</option>
                    <option value="40">40</option>
                    <option value="6">6</option>
                </select>
                <!--div class="d-flex">
                    <input  type="number" class="form-control" placeholder="Min" />
                    <input v-model="search.epaisseur_max" type="number" class="form-control ms-2" placeholder="Max" />
                </div-->
            </div>
            <div class="col-md-4">
                <label>Fournisseur</label>
                <select v-model="search.supplier_id" class="form-control">
                    <option value=""></option>
                    <option v-for="f in suppliers" :key="f.id" :value="f.id">{{ f.name }}</option>
                </select>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-4">
                <label>Disponibilité</label>
                <select v-model="search.indisponible" class="form-control">
                    <option value="all">Tout voir</option>
                    <option :value="0">Disponible</option>
                    <option :value="1">Indisponible</option>
                </select>
            </div>
            <!--div class="col-md-4">
                <label>Volume (Min - Max)</label>
                <div class="d-flex">
                    <input v-model="search.volume_min" type="number" class="form-control" placeholder="Min" />
                    <input v-model="search.volume_max" type="number" class="form-control ms-2" placeholder="Max" />
                </div>
            </div-->
        </div>

        <button type="button" class="btn btn-primary mt-3" @click="searchItems">🔎 Rechercher</button>
        <button type="button" class="btn btn-primary mt-3 ml-3" @click="downloadPDF"><i class="fa fa-print"></i> Imprimer</button>

    </div>
</template>

<script setup>
import { ref, defineProps, defineEmits, onMounted } from 'vue';
import axios from "axios";


// Liste des essences disponibles
const essences = ref(["Ayous", "Frake", "Dibetou", "Bois Rouge","Dabema"]);
const articles = ref({ data: [] });
const suppliers = ref({ data: [] });
// Définition des données du formulaire de recherche
const search = ref({
    contract_number: '',
    numero_colis: '',
    supplier_id: '',
    essence: '',
    longueur_min: '',
    longueur_max: '',
    epaisseur_min: '',
    epaisseur_max: '',
    volume_min: '',
    volume_max: '',
    indisponible: 0
});

function fetchArticles() {
    axios.get('/admin/articles/fetch').then(response => {
        articles.value = response.data;
    });
}

function fetchSuppliers(page = 1) {
    axios.get(`/admin/suppliers/liste-suppliers?page=${page}`)
        .then(response => {
            suppliers.value = response.data.data;
        });
}


onMounted(() => {
    fetchSuppliers();
    fetchArticles();
});

// Événement pour transmettre la recherche au parent
const emit = defineEmits(['search']);

function searchItems() {
    emit('search', search.value);
}

function downloadPDF() {
    const url ="/admin/article-items/generate-pdf-article";
    const params = new URLSearchParams(search.value);
    const fullUrl = `${url}?${params.toString()}`;
    window.open(fullUrl, '_blank');
}
</script>
