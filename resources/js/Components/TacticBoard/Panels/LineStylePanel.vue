<template>
    <div class="line-style-panel">
        <div class="panel-header">
            <span>Linienstil</span>
        </div>
        <div class="panel-content">
            <!-- Stroke Width -->
            <div class="property-row">
                <label>Liniendicke</label>
                <div class="slider-row">
                    <input
                        type="range"
                        min="1"
                        max="10"
                        :value="strokeWidth"
                        @input="$emit('update:strokeWidth', parseInt($event.target.value))"
                    />
                    <span class="value">{{ strokeWidth }}px</span>
                </div>
            </div>

            <!-- Line Style -->
            <div class="property-row">
                <label>Linienart</label>
                <div class="style-buttons">
                    <button
                        :class="['style-btn', { active: lineStyle === 'solid' }]"
                        @click="$emit('update:lineStyle', 'solid')"
                        title="Durchgezogen"
                    >
                        <svg class="line-icon" viewBox="0 0 40 10">
                            <line x1="0" y1="5" x2="40" y2="5" stroke="currentColor" stroke-width="3" />
                        </svg>
                    </button>
                    <button
                        :class="['style-btn', { active: lineStyle === 'dashed' }]"
                        @click="$emit('update:lineStyle', 'dashed')"
                        title="Gestrichelt"
                    >
                        <svg class="line-icon" viewBox="0 0 40 10">
                            <line x1="0" y1="5" x2="40" y2="5" stroke="currentColor" stroke-width="3" stroke-dasharray="8,4" />
                        </svg>
                    </button>
                    <button
                        :class="['style-btn', { active: lineStyle === 'dotted' }]"
                        @click="$emit('update:lineStyle', 'dotted')"
                        title="Gepunktet"
                    >
                        <svg class="line-icon" viewBox="0 0 40 10">
                            <line x1="0" y1="5" x2="40" y2="5" stroke="currentColor" stroke-width="3" stroke-dasharray="3,3" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Color -->
            <div class="property-row">
                <label>Farbe</label>
                <div class="color-row">
                    <input
                        type="color"
                        :value="color"
                        @input="$emit('update:color', $event.target.value)"
                    />
                    <div class="color-presets">
                        <button
                            v-for="preset in colorPresets"
                            :key="preset"
                            :class="['color-preset', { active: color === preset }]"
                            :style="{ backgroundColor: preset }"
                            @click="$emit('update:color', preset)"
                        ></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    strokeWidth: {
        type: Number,
        default: 3,
    },
    lineStyle: {
        type: String,
        default: 'solid',
    },
    color: {
        type: String,
        default: '#ffffff',
    },
});

defineEmits(['update:strokeWidth', 'update:lineStyle', 'update:color']);

const colorPresets = [
    '#ffffff', // White
    '#22c55e', // Green (pass)
    '#f59e0b', // Orange (dribble)
    '#ef4444', // Red
    '#3b82f6', // Blue
    '#a855f7', // Purple
    '#fbbf24', // Yellow
    '#000000', // Black
];
</script>

<style scoped>
.line-style-panel {
    background: #1f2937;
    border-radius: 8px;
    overflow: hidden;
    width: 220px;
}

.panel-header {
    padding: 10px 12px;
    border-bottom: 1px solid #374151;
    color: #ffffff;
    font-weight: 500;
    font-size: 13px;
}

.panel-content {
    padding: 12px;
}

.property-row {
    margin-bottom: 14px;
}

.property-row:last-child {
    margin-bottom: 0;
}

.property-row label {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.slider-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.slider-row input[type="range"] {
    flex: 1;
    height: 4px;
    -webkit-appearance: none;
    background: #374151;
    border-radius: 2px;
    cursor: pointer;
}

.slider-row input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    background: #2563eb;
    border-radius: 50%;
    cursor: pointer;
}

.slider-row .value {
    font-size: 12px;
    color: #d1d5db;
    min-width: 35px;
    text-align: right;
}

.style-buttons {
    display: flex;
    gap: 6px;
}

.style-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    background: #374151;
    border: 2px solid transparent;
    border-radius: 6px;
    color: #d1d5db;
    cursor: pointer;
    transition: all 0.15s ease;
}

.style-btn:hover {
    background: #4b5563;
    color: #ffffff;
}

.style-btn.active {
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.2);
    color: #ffffff;
}

.line-icon {
    width: 32px;
    height: 10px;
}

.color-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-row input[type="color"] {
    width: 36px;
    height: 36px;
    padding: 0;
    border: 2px solid #374151;
    border-radius: 6px;
    cursor: pointer;
    background: transparent;
}

.color-row input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 2px;
}

.color-row input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

.color-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    flex: 1;
}

.color-preset {
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.color-preset:hover {
    transform: scale(1.15);
}

.color-preset.active {
    border-color: #ffffff;
}
</style>
