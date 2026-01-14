<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CategoriesDataTable from '@/components/CategoriesDataTable.vue';
import CategoryDialog from '@/components/CategoryDialog.vue';
import { Button } from '@/components/ui/button';
import { index as categoriesRoute } from '@/routes/categories';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    description: string;
    type: 'income' | 'expense';
    created_at: string;
}

interface PaginatedCategories {
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
    categories: PaginatedCategories;
    filters?: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: categoriesRoute().url,
    },
];

const dialogOpen = ref(false);
const selectedCategory = ref<Category | null>(null);

function createCategory() {
    selectedCategory.value = null;
    dialogOpen.value = true;
}

function editCategory(category: Category) {
    selectedCategory.value = category;
    dialogOpen.value = true;
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Categories</h1>
                    <p class="text-muted-foreground">
                        Manage your income and expense categories
                    </p>
                </div>
                <Button @click="createCategory">
                    <Plus class="mr-2 h-4 w-4" />
                    New Category
                </Button>
            </div>

            <CategoriesDataTable 
                :categories="props.categories" 
                @edit="editCategory"
            />
        </div>

        <CategoryDialog 
            v-model:open="dialogOpen"
            :category="selectedCategory"
        />
    </AppLayout>
</template>
