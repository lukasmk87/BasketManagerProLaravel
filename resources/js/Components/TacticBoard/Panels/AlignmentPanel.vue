<template>
    <div class="alignment-panel">
        <div class="panel-header">
            <span>Ausrichtung</span>
            <button class="close-btn" @click="$emit('close')">×</button>
        </div>

        <div class="panel-content">
            <!-- Horizontal Alignment -->
            <div class="section">
                <span class="section-label">Horizontal</span>
                <div class="button-row">
                    <button
                        class="align-btn"
                        :disabled="!canAlign"
                        @click="$emit('align-left')"
                        title="Links ausrichten"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2 3.75A.75.75 0 012.75 3h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 3.75zm0 4.167a.75.75 0 01.75-.75h5.5a.75.75 0 010 1.5h-5.5a.75.75 0 01-.75-.75zm0 4.166a.75.75 0 01.75-.75h9.5a.75.75 0 010 1.5h-9.5a.75.75 0 01-.75-.75zm0 4.167a.75.75 0 01.75-.75h3.5a.75.75 0 010 1.5h-3.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button
                        class="align-btn"
                        :disabled="!canAlign"
                        @click="$emit('align-center')"
                        title="Horizontal zentrieren"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2 3.75A.75.75 0 012.75 3h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 3.75zm3 4.167a.75.75 0 01.75-.75h8.5a.75.75 0 010 1.5h-8.5a.75.75 0 01-.75-.75zm-1 4.166a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H4.75a.75.75 0 01-.75-.75zm2 4.167a.75.75 0 01.75-.75h6.5a.75.75 0 010 1.5h-6.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button
                        class="align-btn"
                        :disabled="!canAlign"
                        @click="$emit('align-right')"
                        title="Rechts ausrichten"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 3.75a.75.75 0 00-.75-.75H2.75a.75.75 0 000 1.5h14.5a.75.75 0 00.75-.75zm0 4.167a.75.75 0 00-.75-.75h-5.5a.75.75 0 000 1.5h5.5a.75.75 0 00.75-.75zm0 4.166a.75.75 0 00-.75-.75h-9.5a.75.75 0 000 1.5h9.5a.75.75 0 00.75-.75zm0 4.167a.75.75 0 00-.75-.75h-3.5a.75.75 0 000 1.5h3.5a.75.75 0 00.75-.75z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Vertical Alignment -->
            <div class="section">
                <span class="section-label">Vertikal</span>
                <div class="button-row">
                    <button
                        class="align-btn"
                        :disabled="!canAlign"
                        @click="$emit('align-top')"
                        title="Oben ausrichten"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3.75 2a.75.75 0 00-.75.75v14.5a.75.75 0 001.5 0V2.75a.75.75 0 00-.75-.75zm4.167 0a.75.75 0 00-.75.75v5.5a.75.75 0 001.5 0v-5.5a.75.75 0 00-.75-.75zm4.166 0a.75.75 0 00-.75.75v9.5a.75.75 0 001.5 0v-9.5a.75.75 0 00-.75-.75zm4.167 0a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5a.75.75 0 00-.75-.75z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button
                        class="align-btn"
                        :disabled="!canAlign"
                        @click="$emit('align-middle')"
                        title="Vertikal zentrieren"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3.75 5a.75.75 0 00-.75.75v8.5a.75.75 0 001.5 0v-8.5a.75.75 0 00-.75-.75zm4.167 3a.75.75 0 00-.75.75v2.5a.75.75 0 001.5 0v-2.5a.75.75 0 00-.75-.75zm4.166-1a.75.75 0 00-.75.75v4.5a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75zm4.167 2a.75.75 0 00-.75.75v.5a.75.75 0 001.5 0v-.5a.75.75 0 00-.75-.75z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button
                        class="align-btn"
                        :disabled="!canAlign"
                        @click="$emit('align-bottom')"
                        title="Unten ausrichten"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3.75 18a.75.75 0 01-.75-.75V2.75a.75.75 0 011.5 0v14.5a.75.75 0 01-.75.75zm4.167 0a.75.75 0 01-.75-.75v-5.5a.75.75 0 011.5 0v5.5a.75.75 0 01-.75.75zm4.166 0a.75.75 0 01-.75-.75v-9.5a.75.75 0 011.5 0v9.5a.75.75 0 01-.75.75zm4.167 0a.75.75 0 01-.75-.75v-3.5a.75.75 0 011.5 0v3.5a.75.75 0 01-.75.75z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Divider -->
            <div class="divider"></div>

            <!-- Distribution -->
            <div class="section">
                <span class="section-label">Verteilen</span>
                <div class="button-row">
                    <button
                        class="align-btn wide"
                        :disabled="!canDistribute"
                        @click="$emit('distribute-horizontally')"
                        title="Horizontal verteilen (mind. 3 Elemente)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3.5 2A1.5 1.5 0 002 3.5v13A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-13A1.5 1.5 0 004.5 2h-1zM9.5 2A1.5 1.5 0 008 3.5v13A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0010.5 2h-1zM15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1z" />
                        </svg>
                        <span>Horizontal</span>
                    </button>
                    <button
                        class="align-btn wide"
                        :disabled="!canDistribute"
                        @click="$emit('distribute-vertically')"
                        title="Vertikal verteilen (mind. 3 Elemente)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="transform: rotate(90deg)">
                            <path d="M3.5 2A1.5 1.5 0 002 3.5v13A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-13A1.5 1.5 0 004.5 2h-1zM9.5 2A1.5 1.5 0 008 3.5v13A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0010.5 2h-1zM15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1z" />
                        </svg>
                        <span>Vertikal</span>
                    </button>
                </div>
            </div>

            <div v-if="!canAlign" class="hint">
                Wähle mindestens 2 Elemente aus (Shift+Klick).
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    canAlign: {
        type: Boolean,
        default: false,
    },
    canDistribute: {
        type: Boolean,
        default: false,
    },
});

defineEmits([
    'close',
    'align-left',
    'align-center',
    'align-right',
    'align-top',
    'align-middle',
    'align-bottom',
    'distribute-horizontally',
    'distribute-vertically',
]);
</script>

<style scoped>
.alignment-panel {
    position: absolute;
    top: 60px;
    right: 250px;
    width: 220px;
    background: var(--tb-bg-panel);
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 100;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid var(--tb-border);
    color: var(--tb-text-heading);
    font-weight: 500;
}

.close-btn {
    background: none;
    border: none;
    color: var(--tb-text-secondary);
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close-btn:hover {
    color: var(--tb-text-heading);
}

.panel-content {
    padding: 12px;
}

.section {
    margin-bottom: 12px;
}

.section-label {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: var(--tb-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.button-row {
    display: flex;
    gap: 6px;
}

.align-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex: 1;
    padding: 8px;
    background: var(--tb-bg-button);
    border: none;
    border-radius: 6px;
    color: var(--tb-text-primary);
    cursor: pointer;
    transition: all 0.15s ease;
}

.align-btn.wide {
    padding: 8px 12px;
    font-size: 12px;
}

.align-btn:hover:not(:disabled) {
    background: var(--tb-bg-hover);
    color: var(--tb-text-heading);
}

.align-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.align-btn svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.align-btn.wide svg {
    width: 16px;
    height: 16px;
}

.divider {
    height: 1px;
    background: var(--tb-border);
    margin: 12px 0;
}

.hint {
    margin-top: 12px;
    padding: 8px;
    background: var(--tb-bg-button);
    border-radius: 6px;
    font-size: 12px;
    color: var(--tb-text-secondary);
    text-align: center;
}
</style>
