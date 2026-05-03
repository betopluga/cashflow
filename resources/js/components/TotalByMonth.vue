<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

const now = new Date();
const monthValue = ref(
    `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`,
);

const revenue = ref(0);
const expenses = ref(0);
const loading = ref(false);

const balance = () => revenue.value - expenses.value;

const fmt = (n: number) =>
    n.toLocaleString('en-US', { style: 'currency', currency: 'USD' });

async function fetchTotals() {
    loading.value = true;
    const [year, month] = monthValue.value.split('-');
    try {
        const res = await fetch(`/transactions/totals?month=${month}&year=${year}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        revenue.value = data.revenue;
        expenses.value = data.expenses;
    } catch {
        revenue.value = 0;
        expenses.value = 0;
    } finally {
        loading.value = false;
    }
}

watch(monthValue, fetchTotals);
onMounted(fetchTotals);
</script>

<template>
    <div class="flex h-full flex-col p-4">
        <!-- Title + Month picker -->
        <div class="mb-3 flex items-center gap-3">
            <h3 class="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                Monthly Summary
            </h3>
            <input
                v-model="monthValue"
                type="month"
                class="rounded-md border border-neutral-200 bg-transparent px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-neutral-400 dark:border-neutral-700 dark:text-neutral-100"
            />
        </div>

        <!-- Spinner -->
        <div v-if="loading" class="flex flex-1 items-center justify-center">
            <svg
                class="h-6 w-6 animate-spin text-neutral-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                />
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                />
            </svg>
        </div>

        <!-- Totals -->
        <div v-else class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-sm text-neutral-500 dark:text-neutral-400">Revenue</span>
                <span class="font-semibold text-green-600 dark:text-green-400">{{ fmt(revenue) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-neutral-500 dark:text-neutral-400">Expenses</span>
                <span class="font-semibold text-red-600 dark:text-red-400">{{ fmt(expenses) }}</span>
            </div>
            <div class="mt-1 border-t border-neutral-200 pt-2 dark:border-neutral-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-lg text-neutral-700 dark:text-neutral-200">Balance</span>
                    <span
                        class="font-bold text-lg"
                        :class="balance() >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                    >{{ fmt(balance()) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
