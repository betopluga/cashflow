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
    description: string | null;
    type: 'income' | 'expense';
    parent_id: number | null;
}

interface ParentCandidate {
    id: number;
    name: string;
    type: 'income' | 'expense';
    parent_id: number | null;
}

interface ParentTreeNode extends ParentCandidate {
    depth: number;
}

const props = defineProps<{
    open: boolean;
    category?: Category | null;
    parentCandidates: ParentCandidate[];
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
    parent_id: null as number | null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Build indented tree from candidates, excluding self in edit mode
const parentTree = computed((): ParentTreeNode[] => {
    const selfId = props.category?.id;
    const candidates = props.parentCandidates.filter((c) => c.id !== selfId);

    const childrenMap = new Map<number | null, ParentCandidate[]>();
    candidates.forEach((c) => {
        const pid = c.parent_id ?? null;
        if (!childrenMap.has(pid)) childrenMap.set(pid, []);
        childrenMap.get(pid)!.push(c);
    });

    const result: ParentTreeNode[] = [];

    function traverse(parentId: number | null, depth: number) {
        (childrenMap.get(parentId) ?? []).forEach((c) => {
            result.push({ ...c, depth });
            traverse(c.id, depth + 1);
        });
    }

    traverse(null, 0);
    return result;
});

// When a parent is selected, inherit its type
watch(
    () => form.value.parent_id,
    (newParentId) => {
        if (newParentId) {
            const parent = props.parentCandidates.find((c) => c.id === newParentId);
            if (parent) form.value.type = parent.type;
        }
    },
);

// Watch for category changes to populate form
watch(() => props.category, (newCategory) => {
    if (newCategory) {
        form.value = {
            name: newCategory.name,
            description: newCategory.description ?? '',
            type: newCategory.type,
            parent_id: newCategory.parent_id,
        };
    } else {
        resetForm();
    }}, { immediate: true });

function resetForm() {
    form.value = {
        name: '',
        description: '',
        type: 'income',
        parent_id: null,
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
                    <Label for="parent_id">Parent Category <span class="text-muted-foreground text-xs">(optional)</span></Label>
                    <Select v-model="form.parent_id">
                        <SelectTrigger id="parent_id">
                            <SelectValue placeholder="None (root category)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">None (root category)</SelectItem>
                            <SelectItem
                                v-for="node in parentTree"
                                :key="node.id"
                                :value="node.id"
                                :style="{ paddingLeft: `${0.5 + node.depth * 1.25}rem` }"
                            >
                                {{ node.name }}
                                <span class="text-muted-foreground text-xs ml-1">({{ node.type }})</span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.parent_id" />
                </div>

                <div class="space-y-2">
                    <Label for="type">Type</Label>
                    <Select v-model="form.type" :disabled="!!form.parent_id" required>
                        <SelectTrigger id="type">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="expense">Expense</SelectItem>
                            <SelectItem value="income">Income</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.parent_id" class="text-xs text-muted-foreground">
                        Inherited from parent category.
                    </p>
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
