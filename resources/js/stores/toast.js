import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
    const toasts = ref([]);
    let nextId = 0;

    function show(message, type = 'error', duration = 5000) {
        const id = nextId++;
        toasts.value.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => {
                dismiss(id);
            }, duration);
        }
    }

    function error(message, duration = 5000) {
        show(message, 'error', duration);
    }

    function success(message, duration = 3000) {
        show(message, 'success', duration);
    }

    function dismiss(id) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }

    function clear() {
        toasts.value = [];
    }

    return {
        toasts,
        show,
        error,
        success,
        dismiss,
        clear,
    };
});
