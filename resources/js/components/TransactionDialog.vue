<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
}

interface Transaction {
    id?: number;
    description: string;
    amount: string;
    date: string;
    category_id?: number | null;
}

const props = defineProps<{
    open: boolean;
    transaction?: Transaction | null;
    categories: Category[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isEditMode = computed(() => !!props.transaction?.id);
const dialogTitle = computed(() => isEditMode.value ? 'Edit Transaction' : 'New Transaction');
const dialogDescription = computed(() => 
    isEditMode.value 
        ? 'Make changes to your transaction here.' 
        : 'Add a new transaction to track your finances.'
);

const form = ref({
    description: '',
    amount: '',
    date: '',
    category_id: null as number | null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Watch for transaction changes to populate form
watch(() => props.transaction, (newTransaction) => {
    if (newTransaction) {
        form.value = {
            description: newTransaction.description,
            amount: newTransaction.amount,
            date: newTransaction.date,
            category_id: newTransaction.category_id || null,
        };
    } else {
        resetForm();
    }
}, { immediate: true });

function resetForm() {
    const today = new Date().toISOString().split('T')[0];
    form.value = {
        description: '',
        amount: '',
        date: today,
        category_id: null,
    };
    errors.value = {};
}

function closeDialog() {
    emit('update:open', false);
    setTimeout(resetForm, 300);
}

function handleSubmit() {
    processing.value = true;
    errors.value = {};

    const url = isEditMode.value 
        ? `/transactions/${props.transaction!.id}`
        : '/transactions';

    const method = isEditMode.value ? 'put' : 'post';

    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeDialog();
        },
        onError: (err) => {
            errors.value = err as Record<string, string>;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ dialogTitle }}</DialogTitle>
                <DialogDescription>{{ dialogDescription }}</DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        placeholder="e.g., Coffee at Starbucks"
                        required
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="space-y-2">
                    <Label for="amount">Amount</Label>
                    <Input
                        id="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        v-model="form.amount"
                        placeholder="0.00"
                        required
                    />
                    <InputError :message="errors.amount" />
                </div>

                <div class="space-y-2">
                    <Label for="date">Date</Label>
                    <Input
                        id="date"
                        type="date"
                        v-model="form.date"
                        required
                    />
                    <InputError :message="errors.date" />
                </div>

                <div class="space-y-2">
                    <Label for="category_id">Category (Optional)</Label>
                    <Select v-model="form.category_id">
                        <SelectTrigger id="category_id">
                            <SelectValue placeholder="Select category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">No Category</SelectItem>
                            <SelectItem 
                                v-for="category in categories" 
                                :key="category.id" 
                                :value="category.id"
                            >
                                {{ category.name }} ({{ category.type }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.category_id" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeDialog"
                        :disabled="processing"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : isEditMode ? 'Update' : 'Create' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
