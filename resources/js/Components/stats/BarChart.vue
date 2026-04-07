<template>
    <div class="chart-container">
        <canvas ref="barChartCanvas"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { Chart, registerables } from "chart.js";

// Enregistrement des composants nécessaires de Chart.js
Chart.register(...registerables);

const props = defineProps({
    chartData: Object, // Les données du graphique
});

const barChartCanvas = ref(null);
let barChartInstance = null;

const renderChart = () => {
    if (!props.chartData) return;

    if (barChartInstance) {
        barChartInstance.destroy(); // Détruire l'ancien graphique avant d'en créer un nouveau
    }

    barChartInstance = new Chart(barChartCanvas.value, {
        type: "bar",
        data: props.chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false, // Important pour éviter le redimensionnement automatique
            plugins: {
                legend: {
                    position: "top",
                },
                tooltip: {
                    callbacks: {
                        label: (tooltipItem) => {
                            return `${tooltipItem.dataset.label}: ${tooltipItem.raw.toLocaleString()} XOF`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => value.toLocaleString() + " XOF",
                    },
                },
                x: {
                    title: {
                        display: true,
                        text: "Mois",
                        font: { size: 14, weight: "bold" }
                    }
                }
            },
        },
    });
};

// Observer les changements de données et mettre à jour le graphique
watch(() => props.chartData, () => {
    renderChart();
}, { deep: true });

onMounted(() => {
    renderChart();
});
</script>

<style scoped>
.chart-container {
    width: 100%;
    height: 300px; /* Fixe la hauteur à 300px */
    position: relative;
}
canvas {
    width: 100% !important;
    height: 100% !important;
}
</style>
