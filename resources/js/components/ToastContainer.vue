<template>
    <div class="toast-container">
        <div
            v-for="toast in toastStore.toasts"
            :key="toast.id"
            class="toast"
            :class="toast.type"
        >
            <span class="toast-message">{{ toast.message }}</span>
            <button class="toast-dismiss" @click="toastStore.dismiss(toast.id)">&times;</button>
        </div>
    </div>
</template>

<script setup>
import { useToastStore } from '../stores/toast';

const toastStore = useToastStore();
</script>

<style scoped>
.toast-container {
    position: fixed;
    top: var(--spacing-lg);
    right: var(--spacing-lg);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
    max-width: 400px;
}

.toast {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.toast.error {
    background-color: var(--color-error, #ef4444);
    color: white;
}

.toast.success {
    background-color: var(--color-success, #22c55e);
    color: white;
}

.toast-message {
    flex: 1;
    font-size: 0.875rem;
}

.toast-dismiss {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.25rem;
    cursor: pointer;
    opacity: 0.7;
    padding: 0;
    line-height: 1;
}

.toast-dismiss:hover {
    opacity: 1;
}
</style>
