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
import { ChevronDown, ChevronRight, Dot, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Category {
    id: number;
    name: string;
    description: string | null;
    type: 'income' | 'expense';
    parent_id: number | null;
    created_at: string;
}

interface TreeNode extends Category {
    depth: number;
    hasChildren: boolean;
}

const props = defineProps<{
    categories: Category[];
}>();

const emit = defineEmits<{
    edit: [category: Category];
}>();

const search = ref('');
const collapsed = reactive(new Set<number>());

const flatTree = computed((): TreeNode[] => {
    const childrenMap = new Map<number | null, Category[]>();
    props.categories.forEach((cat) => {
        const pid = cat.parent_id ?? null;
        if (!childrenMap.has(pid)) childrenMap.set(pid, []);
        childrenMap.get(pid)!.push(cat);
    });

    const result: TreeNode[] = [];

    function traverse(parentId: number | null, depth: number) {
        (childrenMap.get(parentId) ?? []).forEach((cat) => {
            const hasChildren = (childrenMap.get(cat.id)?.length ?? 0) > 0;
            result.push({ ...cat, depth, hasChildren });
            traverse(cat.id, depth + 1);
        });
    }

    traverse(null, 0);
    return result;
});

const visibleRows = computed((): TreeNode[] => {
    const q = search.value.trim().toLowerCase();

    if (q) {
        const nodeById = new Map(flatTree.value.map((n) => [n.id, n]));
        const toShow = new Set<number>();

        flatTree.value.forEach((node) => {
            if (
                node.name.toLowerCase().includes(q) ||
                (node.description ?? '').toLowerCase().includes(q)
            ) {
                toShow.add(node.id);
                // Also show all ancestors so the tree context is clear
                let current: TreeNode | undefined = node;
                while (current?.parent_id != null) {
                    toShow.add(current.parent_id);
                    current = nodeById.get(current.parent_id);
                }
            }
        });

        return flatTree.value.filter((n) => toShow.has(n.id));
    }

    // No search — respect collapsed state
    const hiddenSubtrees = new Set<number>();
    return flatTree.value.filter((node) => {
        if (node.parent_id != null && hiddenSubtrees.has(node.parent_id)) {
            hiddenSubtrees.add(node.id);
            return false;
        }
        if (collapsed.has(node.id)) {
            hiddenSubtrees.add(node.id);
        }
        return true;
    });
});

function toggleCollapse(id: number) {
    if (collapsed.has(id)) {
        collapsed.delete(id);
    } else {
        collapsed.add(id);
    }
}

function editCategory(node: TreeNode) {
    emit('edit', node);
}

function deleteCategory(node: TreeNode) {
    if (confirm(`Are you sure you want to delete "${node.name}"?`)) {
        router.delete(`/categories/${node.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="w-full space-y-4">
        <Input
            placeholder="Search categories..."
            v-model="search"
            class="max-w-sm"
        />

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[35%]">Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead class="w-[110px]">Type</TableHead>
                        <TableHead class="w-[100px]">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="visibleRows.length">
                        <TableRow v-for="node in visibleRows" :key="node.id">
                            <TableCell>
                                <div
                                    class="flex items-center gap-1"
                                    :style="{ paddingLeft: `${node.depth * 1.5}rem` }"
                                >
                                    <button
                                        v-if="node.hasChildren"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded hover:bg-muted"
                                        @click="toggleCollapse(node.id)"
                                    >
                                        <ChevronDown
                                            v-if="!collapsed.has(node.id)"
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                        />
                                        <ChevronRight
                                            v-else
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                        />
                                    </button>
                                    <span
                                        v-else
                                        class="flex h-5 w-5 shrink-0 items-center justify-center"
                                    >
                                        <Dot class="h-4 w-4 text-muted-foreground/50" />
                                    </span>
                                    <span :class="node.hasChildren ? 'font-medium' : ''">
                                        {{ node.name }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ node.description ?? '—' }}
                            </TableCell>
                            <TableCell>
                                <div
                                    :class="`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                        node.type === 'income'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                    }`"
                                >
                                    {{ node.type.charAt(0).toUpperCase() + node.type.slice(1) }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex gap-2">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="editCategory(node)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="deleteCategory(node)"
                                    >
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell colspan="4" class="h-24 text-center">
                                No categories found.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

