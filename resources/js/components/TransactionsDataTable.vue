<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { router } from '@inertiajs/vue3';
import {
    FlexRender,
    getCoreRowModel,
    useVueTable,
    type ColumnDef,
} from '@tanstack/vue-table';
import { ArrowUpDown, Pencil, Trash2 } from 'lucide-vue-next';
import { h, ref, computed, watch } from 'vue';

interface Transaction {
    id: number;
    description: string;
    amount: string;
    date: string;
    category?: {
        id: number;
        name: string;
        type: 'income' | 'expense';
    };
}

interface PaginatedData {
    data: Transaction[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
}

interface Filters {
    search?: string;
    sort?: string;
    direction?: string;
    per_page?: number;
    date_from?: string;
    date_to?: string;
}

const props = defineProps<{
    transactions: PaginatedData;
    filters?: Filters;
}>();

const emit = defineEmits<{
    edit: [transaction: Transaction];
}>();

// Helper functions to get current month's first and last day
function getFirstDayOfMonth() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
}

function getLastDayOfMonth() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
}

const data = computed(() => props.transactions.data);
const search = ref(props.filters?.search || '');
const sortBy = ref(props.filters?.sort || 'date');
const sortDirection = ref(props.filters?.direction || 'desc');
const perPage = ref(props.filters?.per_page || 10);
const dateFrom = ref(props.filters?.date_from || getFirstDayOfMonth());
const dateTo = ref(props.filters?.date_to || getLastDayOfMonth());

// Debounced search function
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 300);
});

// Watch date filters
watch(dateFrom, () => updateFilters());
watch(dateTo, () => updateFilters());

function updateFilters() {
    router.get('/transactions', {
        search: search.value || undefined,
        sort: sortBy.value,
        direction: sortDirection.value,
        per_page: perPage.value,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function toggleSort(column: string) {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
    updateFilters();
}

function changePage(page: number) {
    router.get('/transactions', {
        page,
        search: search.value || undefined,
        sort: sortBy.value,
        direction: sortDirection.value,
        per_page: perPage.value,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

const columns: ColumnDef<Transaction>[] = [
    {
        accessorKey: 'date',
        header: () => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => toggleSort('date'),
                },
                () => ['Date', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            let dateString = row.getValue('date') as string;
            dateString = dateString.split('T')[0];
            return h('div', { class: 'font-medium' }, dateString);
        },
    },
    {
        accessorKey: 'description',
        header: 'Description',
        cell: ({ row }) => h('div', row.getValue('description')),
    },
    {
        accessorKey: 'category',
        header: 'Category',
        cell: ({ row }) => {
            const category = row.original.category;
            if (!category) return h('div', { class: 'text-muted-foreground' }, 'Uncategorized');
            
            return h(
                'div',
                {
                    class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                        category.type === 'income'
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                    }`,
                },
                category.name,
            );
        },
    },
    {
        accessorKey: 'amount',
        header: () => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => toggleSort('amount'),
                },
                () => ['Amount', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const amount = parseFloat(row.getValue('amount'));
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(amount);
            
            const type = row.original.category?.type;
            return h(
                'div',
                {
                    class: `font-semibold ${
                        type === 'income' ? 'text-green-600 dark:text-green-400' : 
                        type === 'expense' ? 'text-red-600 dark:text-red-400' : 
                        ''
                    }`,
                },
                formatted,
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => {
            const transaction = row.original;
            return h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        onClick: () => editTransaction(transaction),
                    },
                    () => h(Pencil, { class: 'h-4 w-4' }),
                ),
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        onClick: () => deleteTransaction(transaction),
                    },
                    () => h(Trash2, { class: 'h-4 w-4 text-destructive' }),
                ),
            ]);
        },
    },
];

const table = useVueTable({
    get data() {
        return data.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    pageCount: props.transactions.last_page,
});

function editTransaction(transaction: Transaction) {
    emit('edit', transaction);
}

function deleteTransaction(transaction: Transaction) {
    if (confirm(`Are you sure you want to delete this transaction?`)) {
        router.delete(`/transactions/${transaction.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="w-full space-y-4">
        <div class="flex items-center gap-4">
            <Input
                placeholder="Search transactions..."
                v-model="search"
                class="max-w-sm"
            />
            <div class="flex items-center gap-2">
                <Input
                    type="date"
                    v-model="dateFrom"
                    placeholder="From date"
                    class="w-40"
                />
                <span class="text-muted-foreground">to</span>
                <Input
                    type="date"
                    v-model="dateTo"
                    placeholder="To date"
                    class="w-40"
                />
            </div>
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="
                                row.getIsSelected() ? 'selected' : undefined
                            "
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell
                                :colspan="columns.length"
                                class="h-24 text-center"
                            >
                                No transactions found.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <div class="flex items-center justify-between space-x-2">
            <div class="flex-1 text-sm text-muted-foreground">
                Showing {{ props.transactions.from || 0 }} to {{ props.transactions.to || 0 }} of {{ props.transactions.total }} transaction(s)
            </div>
            <div class="space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="props.transactions.current_page === 1"
                    @click="changePage(props.transactions.current_page - 1)"
                >
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground">
                    Page {{ props.transactions.current_page }} of {{ props.transactions.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="props.transactions.current_page === props.transactions.last_page"
                    @click="changePage(props.transactions.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>
