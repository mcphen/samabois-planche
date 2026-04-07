<template>
    <div class="chart-container">
        <canvas ref="lineChartCanvas"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const props = defineProps({
    chartData: Object,
});

const lineChartCanvas = ref(null);
let lineChartInstance = null;

const renderChart = () => {
    if (!props.chartData) return;
    if (lineChartInstance) lineChartInstance.destroy();

    lineChartInstance = new Chart(lineChartCanvas.value, {
        type: "line",
        data: props.chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "top" },
                tooltip: {
                    callbacks: {
                        label: (tooltipItem) =>
                            `${tooltipItem.dataset.label}: ${tooltipItem.raw.toLocaleString("fr-FR")} XOF`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => value.toLocaleString("fr-FR") + " XOF",
                    },
                },
                x: {
                    title: { display: true, text: "Mois", font: { size: 13 } },
                },
            },
        },
    });
};

watch(() => props.chartData, () => renderChart(), { deep: true });
onMounted(() => renderChart());
</script>

<style scoped>
.chart-container {
    width: 100%;
    height: 300px;
    position: relative;
}
canvas {
    width: 100% !important;
    height: 100% !important;
}
</style>
