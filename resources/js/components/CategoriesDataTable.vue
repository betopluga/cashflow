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
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    type ColumnDef,
    type SortingState,
    type VisibilityState,
} from '@tanstack/vue-table';
import { ArrowUpDown, ChevronDown, Pencil, Trash2 } from 'lucide-vue-next';
import { h, ref, computed } from 'vue';

interface Category {
    id: number;
    name: string;
    description: string;
    type: 'income' | 'expense';
    created_at: string;
}

const props = defineProps<{
    categories: Category[];
}>();

const emit = defineEmits<{
    edit: [category: Category];
}>();

// Make data reactive by using computed
const data = computed(() => props.categories);

const sorting = ref<SortingState>([]);
const columnVisibility = ref<VisibilityState>({});
const globalFilter = ref('');

const columns: ColumnDef<Category>[] = [
    {
        accessorKey: 'name',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        accessorKey: 'description',
        header: 'Description',
        cell: ({ row }) => h('div', { class: 'text-muted-foreground' }, row.getValue('description')),
    },
    {
        accessorKey: 'type',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Type', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            const type = row.getValue('type') as string;
            return h(
                'div',
                {
                    class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                        type === 'income'
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                    }`,
                },
                type.charAt(0).toUpperCase() + type.slice(1),
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => {
            const category = row.original;
            return h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        onClick: () => editCategory(category),
                    },
                    () => h(Pencil, { class: 'h-4 w-4' }),
                ),
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        onClick: () => deleteCategory(category),
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
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => {
        sorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(sorting.value)
                : updaterOrValue;
    },
    onColumnVisibilityChange: (updaterOrValue) => {
        columnVisibility.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(columnVisibility.value)
                : updaterOrValue;
    },
    onGlobalFilterChange: (updaterOrValue) => {
        globalFilter.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(globalFilter.value)
                : updaterOrValue;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get globalFilter() {
            return globalFilter.value;
        },
    },
});

function editCategory(category: Category) {
    emit('edit', category);
}

function deleteCategory(category: Category) {
    if (confirm(`Are you sure you want to delete "${category.name}"?`)) {
        router.delete(`/categories/${category.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="w-full space-y-4">
        <div class="flex items-center justify-between gap-4">
            <Input
                placeholder="Search categories..."
                v-model="globalFilter"
                class="max-w-sm"
            />
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
                                No categories found.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <div class="flex items-center justify-end space-x-2">
            <div class="flex-1 text-sm text-muted-foreground">
                {{ table.getFilteredRowModel().rows.length }} category(ies)
            </div>
            <div class="space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanNextPage()"
                    @click="table.nextPage()"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>
