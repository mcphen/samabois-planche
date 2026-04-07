<template>
    <div class="chart-container">
        <canvas ref="canvas"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const props = defineProps({
    chartData: Object,
});

const canvas = ref(null);
let instance = null;

const render = () => {
    if (!props.chartData) return;
    if (instance) instance.destroy();

    instance = new Chart(canvas.value, {
        type: "doughnut",
        data: props.chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "right" },
                tooltip: {
                    callbacks: {
                        label: (item) => ` ${item.label} : ${item.raw} colis`,
                    },
                },
            },
        },
    });
};

watch(() => props.chartData, render, { deep: true });
onMounted(render);
</script>

<style scoped>
.chart-container {
    width: 100%;
    height: 260px;
    position: relative;
}
canvas {
    width: 100% !important;
    height: 100% !important;
}
</style>
