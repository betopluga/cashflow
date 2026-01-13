<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CategoriesDataTable from '@/components/CategoriesDataTable.vue';
import { Button } from '@/components/ui/button';
import { index as categoriesRoute } from '@/routes/categories';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: categoriesRoute().url,
    },
];

function createCategory() {
    // TODO: Open create dialog
    console.log('Create new category');
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

            <CategoriesDataTable :categories="props.categories" />
        </div>
    </AppLayout>
</template>
