<script setup>
import { computed } from 'vue';
import { UserGroupIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleIconSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    team: {
        type: Object,
        required: true
    },
    selected: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    showStats: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['toggle']);

const handleToggle = () => {
    if (!props.disabled) {
        emit('toggle', props.team);
    }
};

const statusBadge = computed(() => {
    if (props.team.is_active === false) {
        return { text: 'Inaktiv', color: 'bg-gray-100 text-gray-700' };
    }
    return null;
});

const playerCount = computed(() => {
    return props.team.players_count || props.team.players?.length || 0;
});

const lastSeasonInfo = computed(() => {
    if (props.team.last_season) {
        return props.team.last_season;
    }
    return null;
});
</script>

<template>
    <div
        :class="[
            'relative bg-white border-2 rounded-lg transition-all duration-200',
            selected
                ? 'border-blue-500 bg-blue-50 shadow-md'
                : disabled
                    ? 'border-gray-200 bg-gray-50 opacity-60'
                    : 'border-gray-200 hover:border-blue-300 hover:shadow-sm',
            disabled ? 'cursor-not-allowed' : 'cursor-pointer'
        ]"
        @click="handleToggle"
    >
        <!-- Selection Indicator -->
        <div class="absolute top-3 right-3">
            <CheckCircleIconSolid
                v-if="selected"
                class="h-6 w-6 text-blue-600"
            />
            <div
                v-else
                class="h-6 w-6 rounded-full border-2"
                :class="disabled ? 'border-gray-300' : 'border-gray-400'"
            ></div>
        </div>

        <!-- Card Content -->
        <div class="p-4">
            <!-- Team Header -->
            <div class="flex items-center space-x-3 mb-3">
                <!-- Team Logo -->
                <div v-if="team.logo_url" class="flex-shrink-0">
                    <img
                        :src="team.logo_url"
                        :alt="team.name"
                        class="h-12 w-12 rounded-full object-cover"
                    />
                </div>
                <div v-else class="flex-shrink-0">
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <UserGroupIcon class="h-7 w-7 text-blue-600" />
                    </div>
                </div>

                <!-- Team Info -->
                <div class="flex-1 min-w-0 pr-8">
                    <h4 class="text-sm font-medium text-gray-900 truncate">
                        {{ team.name }}
                    </h4>
                    <p v-if="team.age_group" class="text-xs text-gray-500">
                        {{ team.age_group }}
                    </p>
                    <p v-if="team.league" class="text-xs text-gray-500">
                        {{ team.league }}
                    </p>
                </div>
            </div>

            <!-- Status Badge -->
            <div v-if="statusBadge" class="mb-3">
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="statusBadge.color"
                >
                    {{ statusBadge.text }}
                </span>
            </div>

            <!-- Team Stats -->
            <div v-if="showStats" class="grid grid-cols-3 gap-2 mb-3">
                <div class="text-center bg-gray-50 rounded p-2">
                    <div class="text-lg font-semibold text-gray-900">
                        {{ playerCount }}
                    </div>
                    <div class="text-xs text-gray-500">Spieler</div>
                </div>
                <div class="text-center bg-gray-50 rounded p-2">
                    <div class="text-lg font-semibold text-gray-900">
                        {{ team.games_count || 0 }}
                    </div>
                    <div class="text-xs text-gray-500">Spiele</div>
                </div>
                <div class="text-center bg-gray-50 rounded p-2">
                    <div class="text-lg font-semibold text-gray-900">
                        {{ team.wins_count || 0 }}
                    </div>
                    <div class="text-xs text-gray-500">Siege</div>
                </div>
            </div>

            <!-- Last Season Info -->
            <div v-if="lastSeasonInfo" class="pt-3 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    Letzte Saison: <span class="font-medium text-gray-700">{{ lastSeasonInfo }}</span>
                </p>
            </div>

            <!-- Disabled Message -->
            <div v-if="disabled" class="mt-3 pt-3 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    {{ team.disabledReason || 'Bereits in einer anderen Saison' }}
                </p>
            </div>
        </div>

        <!-- Selected Overlay Effect -->
        <div
            v-if="selected"
            class="absolute inset-0 bg-blue-600 opacity-5 rounded-lg pointer-events-none"
        ></div>
    </div>
</template>
