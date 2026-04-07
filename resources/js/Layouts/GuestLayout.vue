<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="card">
        <div class="header">
            <p class="lead">Login to your account</p>
        </div>
        <div class="body">
            <slot />

        </div>
    </div>

</template>
