<template>
    <div class="team-settings-panel">
        <div class="panel-header">
            <span>Team-Einstellungen</span>
            <button class="close-btn" @click="$emit('close')">×</button>
        </div>

        <div class="panel-content">
            <!-- Offense Team -->
            <div class="team-section">
                <span class="team-label">Offense</span>
                <div class="team-controls">
                    <div class="color-picker-row">
                        <label>Farbe:</label>
                        <input
                            type="color"
                            :value="offenseColor"
                            @input="$emit('update:offenseColor', $event.target.value)"
                        />
                    </div>
                    <div class="color-presets">
                        <button
                            v-for="color in offensePresets"
                            :key="color"
                            class="preset-btn"
                            :style="{ backgroundColor: color }"
                            :class="{ active: offenseColor === color }"
                            @click="$emit('update:offenseColor', color)"
                        ></button>
                    </div>
                </div>
            </div>

            <!-- Defense Team -->
            <div class="team-section">
                <span class="team-label">Defense</span>
                <div class="team-controls">
                    <div class="color-picker-row">
                        <label>Farbe:</label>
                        <input
                            type="color"
                            :value="defenseColor"
                            @input="$emit('update:defenseColor', $event.target.value)"
                        />
                    </div>
                    <div class="color-presets">
                        <button
                            v-for="color in defensePresets"
                            :key="color"
                            class="preset-btn"
                            :style="{ backgroundColor: color }"
                            :class="{ active: defenseColor === color }"
                            @click="$emit('update:defenseColor', color)"
                        ></button>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="divider"></div>

            <!-- Ball Control -->
            <div class="ball-section">
                <span class="team-label">Basketball</span>
                <div class="ball-controls">
                    <button
                        v-if="!hasBall"
                        class="add-ball-btn"
                        @click="$emit('add-ball')"
                    >
                        + Ball hinzufügen
                    </button>
                    <button
                        v-else
                        class="remove-ball-btn"
                        @click="$emit('remove-ball')"
                    >
                        Ball entfernen
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    offenseColor: {
        type: String,
        default: '#2563eb',
    },
    defenseColor: {
        type: String,
        default: '#dc2626',
    },
    hasBall: {
        type: Boolean,
        default: false,
    },
});

defineEmits([
    'close',
    'update:offenseColor',
    'update:defenseColor',
    'add-ball',
    'remove-ball',
]);

// Color presets for offense
const offensePresets = [
    '#2563eb', // Blue
    '#0ea5e9', // Light Blue
    '#22c55e', // Green
    '#eab308', // Yellow
    '#f97316', // Orange
];

// Color presets for defense
const defensePresets = [
    '#dc2626', // Red
    '#e11d48', // Rose
    '#7c3aed', // Violet
    '#db2777', // Pink
    '#9333ea', // Purple
];
</script>

<style scoped>
.team-settings-panel {
    position: absolute;
    top: 60px;
    left: 20px;
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

.team-section,
.ball-section {
    margin-bottom: 16px;
}

.team-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #d1d5db;
    margin-bottom: 8px;
}

.team-controls {
    background: #374151;
    border-radius: 6px;
    padding: 8px;
}

.color-picker-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.color-picker-row label {
    font-size: 12px;
    color: #9ca3af;
}

.color-picker-row input[type="color"] {
    width: 32px;
    height: 32px;
    padding: 0;
    border: 2px solid #4b5563;
    border-radius: 6px;
    cursor: pointer;
}

.color-picker-row input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 2px;
}

.color-picker-row input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

.color-presets {
    display: flex;
    gap: 6px;
}

.preset-btn {
    width: 28px;
    height: 28px;
    border: 2px solid transparent;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.15s ease;
}

.preset-btn:hover {
    transform: scale(1.1);
}

.preset-btn.active {
    border-color: #ffffff;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

.divider {
    height: 1px;
    background: #374151;
    margin: 12px 0;
}

.ball-controls {
    display: flex;
    gap: 8px;
}

.add-ball-btn,
.remove-ball-btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.add-ball-btn {
    background: #22c55e;
    color: white;
}

.add-ball-btn:hover {
    background: #16a34a;
}

.remove-ball-btn {
    background: #dc2626;
    color: white;
}

.remove-ball-btn:hover {
    background: #b91c1c;
}
</style>
