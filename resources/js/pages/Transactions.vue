<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import TransactionsDataTable from '@/components/TransactionsDataTable.vue';
import TransactionDialog from '@/components/TransactionDialog.vue';
import { Button } from '@/components/ui/button';
import { index as transactionsRoute } from '@/routes/transactions';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
}

interface Transaction {
    id: number;
    description: string;
    amount: string;
    date: string;
    category_id?: number | null;
    category?: Category;
}

const props = defineProps<{
    transactions: Transaction[];
    categories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: transactionsRoute().url,
    },
];

const dialogOpen = ref(false);
const selectedTransaction = ref<Transaction | null>(null);

function createTransaction() {
    selectedTransaction.value = null;
    dialogOpen.value = true;
}

function editTransaction(transaction: Transaction) {
    selectedTransaction.value = transaction;
    dialogOpen.value = true;
}
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Transactions</h1>
                    <p class="text-muted-foreground">
                        Track your income and expenses
                    </p>
                </div>
                <Button @click="createTransaction">
                    <Plus class="mr-2 h-4 w-4" />
                    New Transaction
                </Button>
            </div>

            <TransactionsDataTable 
                :transactions="props.transactions" 
                @edit="editTransaction"
            />
        </div>

        <TransactionDialog 
            v-model:open="dialogOpen"
            :transaction="selectedTransaction"
            :categories="props.categories"
        />
    </AppLayout>
</template>
