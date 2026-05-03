<script setup lang="ts">
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const PER_PAGE = 10;

// --- Controls ---
const years = ref<number[]>([]);
const selectedYear = ref(new Date().getFullYear());
const type = ref<'revenue' | 'expense'>('expense');

const now = new Date();
const monthA = ref(now.getMonth() === 0 ? 0 : now.getMonth() - 1); // 0-based, older month
const monthB = ref(now.getMonth()); // 0-based, newer month

// --- Data ---
interface CategoryRow { id: number; name: string; months: number[]; parent_id: number | null; }

interface DisplayRow {
    id: number;
    name: string;
    parent_id: number | null;
    depth: number;
    hasChildren: boolean;
    valA: number;
    valB: number;
    diff: { label: string; color: string; pct: number };
}

const allCategories = ref<CategoryRow[]>([]);
const loading = ref(false);
const page = ref(1);
const expanded = reactive(new Set<number>());

// --- Per-month totals (sum of all categories for that month index) ---
const monthTotals = computed(() =>
    MONTHS.map((_, i) => allCategories.value.reduce((sum, c) => sum + c.months[i], 0))
);

const fmtShort = (n: number) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- Month select options (enforcing A < B) ---
const monthAOptions = computed(() =>
    MONTHS.map((label, i) => ({
        label: monthTotals.value[i] > 0 ? `${label} (${fmtShort(monthTotals.value[i])})` : label,
        value: i,
        disabled: i >= monthB.value,
    }))
);
const monthBOptions = computed(() =>
    MONTHS.map((label, i) => ({
        label: monthTotals.value[i] > 0 ? `${label} (${fmtShort(monthTotals.value[i])})` : label,
        value: i,
        disabled: i <= monthA.value,
    }))
);

// --- Derived rows ---
const fmt = (n: number) => n.toLocaleString('en-US', { style: 'currency', currency: 'USD' });

function diffInfo(a: number, b: number, catType: 'revenue' | 'expense') {
    if (a === 0 && b === 0) return { label: '—', color: 'text-neutral-400', pct: 0 };
    if (a === 0) return { label: '↑ new', color: catType === 'revenue' ? 'text-green-500' : 'text-red-500', pct: Infinity };
    if (b === 0) return { label: '↓ -100%', color: catType === 'revenue' ? 'text-red-500' : 'text-green-500', pct: 100 };
    const pct = ((b - a) / a) * 100;
    const up  = pct > 0;
    const arrow = up ? '↑' : '↓';
    const color = (up && catType === 'revenue') || (!up && catType === 'expense')
        ? 'text-green-500'
        : 'text-red-500';
    return { label: `${arrow} ${Math.abs(pct).toFixed(1)}%`, color, pct: Math.abs(pct) };
}

// Build a collapsible tree sorted by biggest diff at each level
const visibleRows = computed((): DisplayRow[] => {
    const childrenMap = new Map<number | null, CategoryRow[]>();
    allCategories.value.forEach(c => {
        const pid = c.parent_id ?? null;
        if (!childrenMap.has(pid)) childrenMap.set(pid, []);
        childrenMap.get(pid)!.push(c);
    });

    // Recursively sum own + all descendants for a given month index
    function summaryOf(cat: CategoryRow): number[] {
        const s = [...cat.months];
        (childrenMap.get(cat.id) ?? []).forEach(child => {
            summaryOf(child).forEach((v, i) => { s[i] += v; });
        });
        return s;
    }

    // Build [parentRow, ...childRows] for a category recursively
    function buildGroup(cat: CategoryRow, depth: number): DisplayRow[] {
        const children = childrenMap.get(cat.id) ?? [];
        const hasChildren = children.length > 0;
        const display = hasChildren ? summaryOf(cat) : cat.months;
        const valA = display[monthA.value];
        const valB = display[monthB.value];
        if (valA === 0 && valB === 0) return [];

        const row: DisplayRow = {
            id: cat.id, name: cat.name, parent_id: cat.parent_id,
            depth, hasChildren, valA, valB,
            diff: diffInfo(valA, valB, type.value),
        };

        if (!hasChildren || !expanded.has(cat.id)) return [row];

        const childGroups = children
            .map(c => buildGroup(c, depth + 1))
            .filter(g => g.length > 0)
            .sort((a, b) => b[0].diff.pct - a[0].diff.pct);

        return [row, ...childGroups.flat()];
    }

    const allIds = new Set(allCategories.value.map(c => c.id));
    const roots = allCategories.value.filter(c => c.parent_id === null || !allIds.has(c.parent_id));

    return roots
        .map(root => buildGroup(root, 0))
        .filter(g => g.length > 0)
        .sort((a, b) => b[0].diff.pct - a[0].diff.pct)
        .flat();
});

function toggleExpand(id: number) {
    if (expanded.has(id)) expanded.delete(id);
    else expanded.add(id);
    page.value = 1;
}

const totalPages = computed(() => Math.max(1, Math.ceil(visibleRows.value.length / PER_PAGE)));

const pagedRows = computed(() => {
    const start = (page.value - 1) * PER_PAGE;
    return visibleRows.value.slice(start, start + PER_PAGE);
});

// --- Fetch ---
async function fetchYears() {
    const res = await fetch('/transactions/years', { headers: { Accept: 'application/json' } });
    years.value = await res.json();
    if (years.value.length && !years.value.includes(selectedYear.value)) {
        selectedYear.value = years.value[0];
    }
}

async function fetchBreakdown() {
    loading.value = true;
    try {
        const res = await fetch(
            `/transactions/category-breakdown?year=${selectedYear.value}&type=${type.value}`,
            { headers: { Accept: 'application/json' } },
        );
        allCategories.value = await res.json();
    } catch {
        allCategories.value = [];
    } finally {
        loading.value = false;
    }
}

function resetAndFetch() {
    page.value = 1;
    expanded.clear();
    fetchBreakdown();
}

function onMonthChange() {
    page.value = 1;
}

watch([selectedYear, type], resetAndFetch);
watch([monthA, monthB], onMonthChange);

onMounted(async () => {
    await fetchYears();
    await fetchBreakdown();
});
</script>

<template>
    <div class="flex h-full flex-col p-4">
        <!-- Header -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h3 class="text-sm font-medium text-neutral-700 dark:text-neutral-200">Monthly Comparison</h3>

            <!-- Toggle -->
            <div class="flex overflow-hidden rounded-md border border-neutral-200 text-sm dark:border-neutral-700">
                <button
                    class="px-3 py-1 transition-colors"
                    :class="type === 'expense'
                        ? 'bg-neutral-200 font-medium text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100'
                        : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200'"
                    @click="type = 'expense'"
                >Expense</button>
                <button
                    class="px-3 py-1 transition-colors"
                    :class="type === 'revenue'
                        ? 'bg-neutral-200 font-medium text-neutral-900 dark:bg-neutral-700 dark:text-neutral-100'
                        : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200'"
                    @click="type = 'revenue'"
                >Revenue</button>
            </div>

            <!-- Year -->
            <select
                v-model="selectedYear"
                class="rounded-md border border-neutral-200 bg-transparent px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-neutral-400 dark:border-neutral-700 dark:text-neutral-100"
            >
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <!-- Spinner -->
        <div v-if="loading" class="flex flex-1 items-center justify-center">
            <svg class="size-6 animate-spin text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
        </div>

        <template v-else>
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 dark:border-neutral-700">
                            <th class="pb-2 text-left font-medium text-neutral-500 dark:text-neutral-400">Category</th>
                            <th class="pb-2 text-right font-medium text-neutral-500 dark:text-neutral-400">
                                <select
                                    v-model="monthA"
                                    class="rounded border border-neutral-200 bg-transparent px-1 py-0.5 text-xs focus:outline-none dark:border-neutral-700 dark:text-neutral-300"
                                >
                                    <option
                                        v-for="opt in monthAOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        :disabled="opt.disabled"
                                    >{{ opt.label }}</option>
                                </select>
                            </th>
                            <th class="pb-2 text-right font-medium text-neutral-500 dark:text-neutral-400">
                                <select
                                    v-model="monthB"
                                    class="rounded border border-neutral-200 bg-transparent px-1 py-0.5 text-xs focus:outline-none dark:border-neutral-700 dark:text-neutral-300"
                                >
                                    <option
                                        v-for="opt in monthBOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        :disabled="opt.disabled"
                                    >{{ opt.label }}</option>
                                </select>
                            </th>
                            <th class="pb-2 text-right font-medium text-neutral-500 dark:text-neutral-400">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- No data -->
                        <tr v-if="visibleRows.length === 0">
                            <td colspan="4" class="py-8 text-center text-neutral-400 dark:text-neutral-500">
                                No data for the selected period.
                            </td>
                        </tr>

                        <!-- Rows -->
                        <tr
                            v-for="row in pagedRows"
                            :key="row.id"
                            class="border-b border-neutral-100 last:border-0 dark:border-neutral-800"
                        >
                            <td class="py-2 text-neutral-700 dark:text-neutral-200">
                                <div
                                    class="flex items-center gap-1"
                                    :style="{ paddingLeft: `${row.depth * 1.25}rem` }"
                                >
                                    <button
                                        v-if="row.hasChildren"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        @click="toggleExpand(row.id)"
                                    >
                                        <ChevronDown v-if="expanded.has(row.id)" class="h-3.5 w-3.5 text-neutral-400" />
                                        <ChevronRight v-else class="h-3.5 w-3.5 text-neutral-400" />
                                    </button>
                                    <span v-else class="w-5 shrink-0" />
                                    <span :class="row.hasChildren ? 'font-medium' : 'text-neutral-600 dark:text-neutral-300'">{{ row.name }}</span>
                                </div>
                            </td>
                            <td class="py-2 text-right text-neutral-600 dark:text-neutral-300">{{ fmt(row.valA) }}</td>
                            <td class="py-2 text-right text-neutral-600 dark:text-neutral-300">{{ fmt(row.valB) }}</td>
                            <td class="py-2 text-right font-medium" :class="row.diff.color">{{ row.diff.label }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="mt-3 flex items-center justify-between text-sm text-neutral-500 dark:text-neutral-400">
                <button
                    class="rounded px-2 py-1 hover:text-neutral-900 disabled:opacity-30 dark:hover:text-neutral-100"
                    :disabled="page === 1"
                    @click="page--"
                >&larr; Prev</button>
                <span>Page {{ page }} of {{ totalPages }}</span>
                <button
                    class="rounded px-2 py-1 hover:text-neutral-900 disabled:opacity-30 dark:hover:text-neutral-100"
                    :disabled="page === totalPages"
                    @click="page++"
                >Next &rarr;</button>
            </div>
        </template>
    </div>
</template>
