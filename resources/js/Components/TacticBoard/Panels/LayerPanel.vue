<template>
    <div class="layer-panel">
        <div class="panel-header">
            <span>Ebenen</span>
            <button class="close-btn" @click="$emit('close')">×</button>
        </div>

        <div class="panel-content">
            <div class="layer-actions">
                <button
                    class="layer-btn"
                    :disabled="!hasSelection"
                    @click="$emit('bring-to-front')"
                    title="Ganz nach vorne (Ctrl+Shift+])"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                        <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon second">
                        <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                    </svg>
                    <span>Ganz nach vorne</span>
                </button>

                <button
                    class="layer-btn"
                    :disabled="!hasSelection"
                    @click="$emit('bring-forward')"
                    title="Eine Ebene vor (Ctrl+])"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                        <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                    </svg>
                    <span>Eine Ebene vor</span>
                </button>

                <button
                    class="layer-btn"
                    :disabled="!hasSelection"
                    @click="$emit('send-backward')"
                    title="Eine Ebene zurück (Ctrl+[)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                    </svg>
                    <span>Eine Ebene zurück</span>
                </button>

                <button
                    class="layer-btn"
                    :disabled="!hasSelection"
                    @click="$emit('send-to-back')"
                    title="Ganz nach hinten (Ctrl+Shift+[)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon second">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                    </svg>
                    <span>Ganz nach hinten</span>
                </button>
            </div>

            <div v-if="!hasSelection" class="hint">
                Wähle ein Element aus, um die Ebenen-Reihenfolge zu ändern.
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    hasSelection: {
        type: Boolean,
        default: false,
    },
});

defineEmits([
    'close',
    'bring-to-front',
    'bring-forward',
    'send-backward',
    'send-to-back',
]);
</script>

<style scoped>
.layer-panel {
    position: absolute;
    top: 60px;
    right: 20px;
    width: 220px;
    background: #1f2937;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 100;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #374151;
    color: #ffffff;
    font-weight: 500;
}

.close-btn {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close-btn:hover {
    color: #ffffff;
}

.panel-content {
    padding: 12px;
}

.layer-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.layer-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px;
    background: #374151;
    border: none;
    border-radius: 6px;
    color: #d1d5db;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.layer-btn:hover:not(:disabled) {
    background: #4b5563;
    color: #ffffff;
}

.layer-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.layer-btn .icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.layer-btn .icon.second {
    margin-left: -12px;
}

.hint {
    margin-top: 12px;
    padding: 8px;
    background: #374151;
    border-radius: 6px;
    font-size: 12px;
    color: #9ca3af;
    text-align: center;
}
</style>
