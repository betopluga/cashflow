<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { onMounted, ref, watch } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
);

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const years = ref<number[]>([]);
const selectedYear = ref(new Date().getFullYear());
const loading = ref(false);
const balanceData = ref<number[]>(Array(12).fill(0));

const chartData = ref({
    labels: MONTHS,
    datasets: [
        {
            label: 'Balance',
            data: balanceData.value,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ],
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: any) =>
                    ` $${ctx.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2 })}`,
            },
        },
    },
    scales: {
        x: {
            grid: { color: 'rgba(128,128,128,0.15)' },
            ticks: { color: '#9ca3af' },
        },
        y: {
            grid: { color: 'rgba(128,128,128,0.15)' },
            ticks: {
                color: '#9ca3af',
                callback: (v: any) => `$${Number(v).toLocaleString('en-US')}`,
            },
        },
    },
};

async function fetchYears() {
    const res = await fetch('/transactions/years', { headers: { Accept: 'application/json' } });
    years.value = await res.json();
    if (years.value.length && !years.value.includes(selectedYear.value)) {
        selectedYear.value = years.value[0];
    }
}

async function fetchGraph() {
    loading.value = true;
    try {
        const res = await fetch(`/transactions/graph?year=${selectedYear.value}`, {
            headers: { Accept: 'application/json' },
        });
        const data: { month: number; revenue: number; expenses: number }[] = await res.json();
        const newBalances = data.map((d) => parseFloat((d.revenue - d.expenses).toFixed(2)));
        balanceData.value = newBalances;
        chartData.value = {
            labels: MONTHS,
            datasets: [
                {
                    ...chartData.value.datasets[0],
                    data: newBalances,
                    borderColor: newBalances.every((b) => b >= 0)
                        ? '#22c55e'
                        : newBalances.every((b) => b <= 0)
                          ? '#ef4444'
                          : '#3b82f6',
                    backgroundColor: newBalances.every((b) => b >= 0)
                        ? 'rgba(34,197,94,0.1)'
                        : newBalances.every((b) => b <= 0)
                          ? 'rgba(239,68,68,0.1)'
                          : 'rgba(59,130,246,0.1)',
                },
            ],
        };
    } catch {
        balanceData.value = Array(12).fill(0);
    } finally {
        loading.value = false;
    }
}

watch(selectedYear, fetchGraph);
onMounted(async () => {
    await fetchYears();
    await fetchGraph();
});
</script>

<template>
    <div class="flex h-full flex-col p-4">
        <!-- Header -->
        <div class="mb-3 flex items-center gap-3">
            <h3 class="text-sm font-medium text-neutral-700 dark:text-neutral-200">Yearly Balance</h3>
            <select
                v-model="selectedYear"
                class="rounded-md border border-neutral-200 bg-transparent px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-neutral-400 dark:border-neutral-700 dark:text-neutral-100"
            >
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <!-- Spinner -->
        <div v-if="loading" class="flex flex-1 items-center justify-center">
            <svg
                class="size-6 animate-spin text-neutral-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
        </div>

        <!-- Chart -->
        <div v-else class="relative min-h-0 flex-1">
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
