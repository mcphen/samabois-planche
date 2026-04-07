<template>
    <div class="planche-color-input" ref="containerRef">
        <input
            ref="inputRef"
            :value="modelValue"
            type="text"
            :class="inputClass"
            :placeholder="placeholder"
            autocomplete="off"
            @input="handleInput"
            @focus="handleFocus"
        />

        <Teleport to="body">
            <div
                v-if="showSuggestions && suggestions.length"
                class="planche-color-input__dropdown"
                :style="dropdownStyle"
            >
                <button
                    v-for="suggestion in suggestions"
                    :key="suggestion.id"
                    type="button"
                    class="planche-color-input__option"
                    @click="selectSuggestion(suggestion)"
                >
                    <span
                        v-if="suggestion.image_url"
                        class="planche-color-input__thumb"
                        :style="{ backgroundImage: `url(${suggestion.image_url})` }"
                    ></span>
                    <span>{{ suggestion.code }}</span>
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import axios from 'axios';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    inputClass: {
        type: String,
        default: 'form-control',
    },
    placeholder: {
        type: String,
        default: 'Code couleur',
    },
});

const emit = defineEmits(['update:modelValue', 'select']);

const containerRef = ref(null);
const inputRef = ref(null);
const suggestions = ref([]);
const showSuggestions = ref(false);
const dropdownStyle = ref({});
let debounceTimer = null;

function fetchSuggestions(query = '') {
    axios.get('/admin/planches/couleurs', {
        params: {
            q: query || undefined,
        },
    }).then((response) => {
        suggestions.value = response.data || [];
        showSuggestions.value = true;
        updateDropdownPosition();
    }).catch(() => {
        suggestions.value = [];
        showSuggestions.value = false;
    });
}

function handleInput(event) {
    const value = event.target.value;
    emit('update:modelValue', value);
}

function handleFocus() {
    fetchSuggestions(props.modelValue);
}

function selectSuggestion(suggestion) {
    emit('update:modelValue', suggestion.code);
    emit('select', suggestion);
    showSuggestions.value = false;
}

function updateDropdownPosition() {
    if (!inputRef.value || !showSuggestions.value || !suggestions.value.length) {
        return;
    }

    const rect = inputRef.value.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceBelow = viewportHeight - rect.bottom;
    const spaceAbove = rect.top;
    const placeAbove = spaceBelow < 180 && spaceAbove > spaceBelow;

    dropdownStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        top: placeAbove ? 'auto' : `${rect.bottom + 4}px`,
        bottom: placeAbove ? `${viewportHeight - rect.top + 4}px` : 'auto',
        maxHeight: `${Math.min(Math.max((placeAbove ? spaceAbove : spaceBelow) - 12, 120), 220)}px`,
        zIndex: 2000,
    };
}

function handleDocumentClick(event) {
    if (!containerRef.value?.contains(event.target)) {
        showSuggestions.value = false;
    }
}

watch(
    () => props.modelValue,
    (value) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchSuggestions(value);
        }, 180);
    }
);

watch(
    () => showSuggestions.value,
    async (isVisible) => {
        if (!isVisible) {
            return;
        }

        await nextTick();
        updateDropdownPosition();
    }
);

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
    window.addEventListener('resize', updateDropdownPosition);
    window.addEventListener('scroll', updateDropdownPosition, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick);
    window.removeEventListener('resize', updateDropdownPosition);
    window.removeEventListener('scroll', updateDropdownPosition, true);
    clearTimeout(debounceTimer);
});
</script>

<style scoped>
.planche-color-input {
    position: relative;
}

.planche-color-input__dropdown {
    overflow-y: auto;
    background: #fff;
    border: 1px solid #d7dee3;
    border-radius: 6px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.planche-color-input__option {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 12px;
    text-align: left;
    background: transparent;
    border: 0;
}

.planche-color-input__option:hover {
    background: #f4f7f9;
}

.planche-color-input__thumb {
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    border-radius: 6px;
    border: 1px solid #d7dee3;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
}
</style>
