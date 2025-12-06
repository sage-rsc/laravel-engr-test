<script setup>
import { onMounted, ref, computed } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const props = defineProps({
    class: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    max: {
        type: String,
        default: null,
    },
    name: {
        type: String,
        default: '',
    },
});

const input = ref(null);

const inputClasses = computed(() => {
    const baseClasses = 'border rounded-md shadow-sm';
    if (props.class) {
        return `${baseClasses} ${props.class}`;
    }
    return `${baseClasses} border-gray-300 focus:border-indigo-500 focus:ring-indigo-500`;
});

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :class="inputClasses"
        v-model="model"
        :type="type"
        :name="name"
        :placeholder="placeholder"
        :required="required"
        :max="max"
        ref="input"
    />
</template>
