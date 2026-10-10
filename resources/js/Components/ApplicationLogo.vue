<script setup>
defineProps({
    className: {
        type: String,
        default: 'w-10 h-10',
    },
    variant: {
        type: [Number, String],
        default: 1,
    },
});
</script>

<template>
    <img
        :src="`/icon/${variant}.png`"
        alt="LUNOU Logo"
        :class="[className, 'object-contain select-none transition-transform duration-300']"
    />
</template>
