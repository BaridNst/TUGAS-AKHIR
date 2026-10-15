<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        ref="input"
        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-base shadow-sm placeholder:text-gray-400 focus:border-orange-300 focus:bg-white focus:ring-2 focus:ring-orange-200/50 sm:rounded-2xl sm:py-3.5 sm:text-[15px]"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
