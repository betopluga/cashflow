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
    id?: number;
    name: string;
    description: string;
    type: 'income' | 'expense';
}

const props = defineProps<{
    open: boolean;
    category?: Category | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isEditMode = computed(() => !!props.category?.id);
const dialogTitle = computed(() => isEditMode.value ? 'Edit Category' : 'Create Category');
const dialogDescription = computed(() => 
    isEditMode.value 
        ? 'Make changes to your category here.' 
        : 'Add a new category for your transactions.'
);

const form = ref({
    name: '',
    description: '',
    type: 'income' as 'income' | 'expense',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Watch for category changes to populate form
watch(() => props.category, (newCategory) => {
    if (newCategory) {
        form.value = {
            name: newCategory.name,
            description: newCategory.description,
            type: newCategory.type,
        };
    } else {
        resetForm();
    }
}, { immediate: true });

function resetForm() {
    form.value = {
        name: '',
        description: '',
        type: 'income',
    };
    errors.value = {};
}

function closeDialog() {
    emit('update:open', false);
    setTimeout(resetForm, 300); // Wait for dialog animation
}

function handleSubmit() {
    processing.value = true;
    errors.value = {};

    const url = isEditMode.value 
        ? `/categories/${props.category!.id}`
        : '/categories';

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
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g., Groceries, Salary"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Optional description..."
                        rows="3"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="space-y-2">
                    <Label for="type">Type</Label>
                    <Select v-model="form.type" required>
                        <SelectTrigger id="type">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="income">Income</SelectItem>
                            <SelectItem value="expense">Expense</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.type" />
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
