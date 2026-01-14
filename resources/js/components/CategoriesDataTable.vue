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

interface Category {
    id: number;
    name: string;
    description: string;
    type: 'income' | 'expense';
    created_at: string;
}

interface PaginatedData {
    data: Category[];
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
}

const props = defineProps<{
    categories: PaginatedData;
    filters?: Filters;
}>();

const emit = defineEmits<{
    edit: [category: Category];
}>();

const data = computed(() => props.categories.data);
const search = ref(props.filters?.search || '');
const sortBy = ref(props.filters?.sort || 'created_at');
const sortDirection = ref(props.filters?.direction || 'desc');
const perPage = ref(props.filters?.per_page || 10);

// Debounced search function
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 300);
});

function updateFilters() {
    router.get('/categories', {
        search: search.value || undefined,
        sort: sortBy.value,
        direction: sortDirection.value,
        per_page: perPage.value,
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
    router.get('/categories', {
        page,
        search: search.value || undefined,
        sort: sortBy.value,
        direction: sortDirection.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

const columns: ColumnDef<Category>[] = [
    {
        accessorKey: 'name',
        header: () => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => toggleSort('name'),
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
        header: () => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => toggleSort('type'),
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
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    pageCount: props.categories.last_page,
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
                v-model="search"
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

        <div class="flex items-center justify-between space-x-2">
            <div class="flex-1 text-sm text-muted-foreground">
                Showing {{ props.categories.from || 0 }} to {{ props.categories.to || 0 }} of {{ props.categories.total }} category(ies)
            </div>
            <div class="space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="props.categories.current_page === 1"
                    @click="changePage(props.categories.current_page - 1)"
                >
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground">
                    Page {{ props.categories.current_page }} of {{ props.categories.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="props.categories.current_page === props.categories.last_page"
                    @click="changePage(props.categories.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>
