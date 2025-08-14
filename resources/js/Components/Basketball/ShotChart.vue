<template>
    <div class="shot-chart bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
            Shot Chart
        </h3>
        
        <div class="relative">
            <!-- Basketball Court Diagram -->
            <svg
                viewBox="0 0 500 470"
                class="w-full max-w-lg mx-auto border rounded-lg bg-orange-50"
                style="aspect-ratio: 500/470;"
            >
                <!-- Court Background -->
                <rect width="500" height="470" fill="#fef7ed" stroke="#ea580c" stroke-width="2"/>
                
                <!-- Free Throw Lane -->
                <rect x="190" y="0" width="120" height="190" fill="none" stroke="#ea580c" stroke-width="2"/>
                
                <!-- Free Throw Circle -->
                <circle cx="250" cy="190" r="60" fill="none" stroke="#ea580c" stroke-width="2"/>
                
                <!-- Three Point Line -->
                <path
                    d="M 50 100 Q 250 50, 450 100 L 450 470 L 50 470 Z"
                    fill="none"
                    stroke="#ea580c"
                    stroke-width="2"
                />
                
                <!-- Basketball Hoop -->
                <circle cx="250" cy="50" r="15" fill="#dc2626" stroke="#991b1b" stroke-width="2"/>
                
                <!-- Shot Markers (example shots) -->
                <g v-if="shots && shots.length">
                    <circle
                        v-for="(shot, index) in shots"
                        :key="index"
                        :cx="shot.x"
                        :cy="shot.y"
                        :r="shot.made ? 6 : 4"
                        :fill="shot.made ? '#22c55e' : '#ef4444'"
                        :stroke="shot.made ? '#15803d' : '#dc2626'"
                        stroke-width="2"
                        class="cursor-pointer"
                        @click="selectShot(shot)"
                    />
                </g>
                
                <!-- Legend -->
                <g transform="translate(20, 400)">
                    <circle cx="10" cy="10" r="6" fill="#22c55e" stroke="#15803d" stroke-width="2"/>
                    <text x="25" y="15" class="text-xs fill-gray-700">Treffer</text>
                    
                    <circle cx="10" cy="30" r="4" fill="#ef4444" stroke="#dc2626" stroke-width="2"/>
                    <text x="25" y="35" class="text-xs fill-gray-700">Fehlwurf</text>
                </g>
            </svg>
        </div>
        
        <!-- Statistics -->
        <div v-if="statistics" class="mt-6 grid grid-cols-2 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ statistics.made }}</div>
                <div class="text-sm text-gray-600">Treffer</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-red-600">{{ statistics.missed }}</div>
                <div class="text-sm text-gray-600">Fehlwürfe</div>
            </div>
        </div>
        
        <!-- Shooting Percentage -->
        <div v-if="statistics && statistics.total > 0" class="mt-4 text-center">
            <div class="text-lg font-semibold text-gray-900">
                {{ Math.round((statistics.made / statistics.total) * 100) }}% Trefferquote
            </div>
            <div class="text-sm text-gray-600">
                {{ statistics.made }} / {{ statistics.total }} Würfe
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    shots: {
        type: Array,
        default: () => []
    },
    playerId: {
        type: Number,
        default: null
    },
    gameId: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['shot-selected'])

const statistics = computed(() => {
    if (!props.shots || props.shots.length === 0) {
        return null
    }
    
    const made = props.shots.filter(shot => shot.made).length
    const missed = props.shots.filter(shot => !shot.made).length
    const total = props.shots.length
    
    return { made, missed, total }
})

const selectShot = (shot) => {
    emit('shot-selected', shot)
}
</script>

<style scoped>
.shot-chart svg text {
    font-family: system-ui, -apple-system, sans-serif;
    font-size: 12px;
}
</style>