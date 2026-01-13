<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { PiggyBank } from 'lucide-vue-next';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <PiggyBank :class="className" v-bind="$attrs" />
</template>
