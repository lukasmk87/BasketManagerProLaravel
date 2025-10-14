<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        required: true,
    },
    featureKey: {
        type: String,
        required: true,
    },
    featureName: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
    badge: {
        type: String,
        default: null, // 'new', 'pro', 'beta', etc.
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

// Check if this feature is currently selected
const isSelected = computed(() => {
    return props.modelValue.includes(props.featureKey);
});

// Toggle feature in array
const toggleFeature = () => {
    if (props.disabled) return;

    const newValue = [...props.modelValue];
    const index = newValue.indexOf(props.featureKey);

    if (index > -1) {
        // Remove feature
        newValue.splice(index, 1);
    } else {
        // Add feature
        newValue.push(props.featureKey);
    }

    emit('update:modelValue', newValue);
};

// Badge styles
const badgeStyles = computed(() => {
    const styles = {
        new: 'bg-green-100 text-green-800',
        pro: 'bg-purple-100 text-purple-800',
        beta: 'bg-blue-100 text-blue-800',
        enterprise: 'bg-indigo-100 text-indigo-800',
        premium: 'bg-yellow-100 text-yellow-800',
    };
    return styles[props.badge] || 'bg-gray-100 text-gray-800';
});

// Feature icons mapping (same as FeatureToggle)
const featureIcons = {
    'basic_team_management': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    'basic_player_profiles': 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    'game_scheduling': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'basic_statistics': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'mobile_web_access': 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    'advanced_statistics': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'live_scoring': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    'training_management': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    'emergency_contacts': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    'email_notifications': 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'push_notifications': 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    'basic_analytics': 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    'advanced_analytics': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'tournament_management': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    'video_analysis': 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    'ai_insights': 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    'custom_reports': 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'api_access': 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
    'data_export': 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'custom_branding': 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    'federation_integration': 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
    'white_label': 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
    'dedicated_support': 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
    'sla_guarantee': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'custom_integrations': 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
    'unlimited_api': 'M13 10V3L4 14h7v7l9-11h-7z',
    'multi_club_management': 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    'advanced_security': 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    'audit_logs': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'compliance_tools': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
};

const displayIcon = computed(() => {
    return featureIcons[props.featureKey] || 'M5 13l4 4L19 7';
});
</script>

<template>
    <div
        class="rounded-lg border transition-all duration-200 cursor-pointer"
        :class="[
            'p-4',
            isSelected
                ? 'bg-indigo-50 border-indigo-200 shadow-sm'
                : 'bg-white border-gray-200 hover:border-gray-300',
            disabled ? 'opacity-60 cursor-not-allowed' : 'hover:shadow-md'
        ]"
        @click="toggleFeature"
    >
        <div class="flex items-start justify-between">
            <!-- Left side: Icon + Label -->
            <div class="flex items-start space-x-3 flex-1">
                <!-- Icon -->
                <div
                    class="flex-shrink-0 rounded-lg p-2"
                    :class="isSelected ? 'bg-indigo-100' : 'bg-gray-100'"
                >
                    <svg
                        class="w-5 h-5"
                        :class="isSelected ? 'text-indigo-600' : 'text-gray-400'"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="displayIcon"
                        ></path>
                    </svg>
                </div>

                <!-- Label & Description -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <p
                            class="text-base font-medium"
                            :class="isSelected ? 'text-indigo-900' : 'text-gray-900'"
                        >
                            {{ featureName }}
                        </p>
                        <span
                            v-if="badge"
                            :class="[badgeStyles, 'px-2 py-0.5 rounded-full text-xs font-medium uppercase']"
                        >
                            {{ badge }}
                        </span>
                    </div>
                    <p
                        v-if="description"
                        class="text-sm mt-1"
                        :class="isSelected ? 'text-indigo-700' : 'text-gray-500'"
                    >
                        {{ description }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1 font-mono">
                        {{ featureKey }}
                    </p>
                </div>
            </div>

            <!-- Right side: Checkbox -->
            <div class="flex-shrink-0 ml-4">
                <input
                    type="checkbox"
                    :checked="isSelected"
                    :disabled="disabled"
                    class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    :class="disabled ? 'cursor-not-allowed' : 'cursor-pointer'"
                    @click.stop="toggleFeature"
                />
            </div>
        </div>

        <!-- Status indicator -->
        <div v-if="isSelected && !disabled" class="mt-3 flex items-center space-x-2">
            <div class="flex-1">
                <div class="h-1 bg-indigo-200 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full" style="width: 100%"></div>
                </div>
            </div>
            <span class="text-xs font-medium text-indigo-600">Ausgewählt</span>
        </div>
    </div>
</template>
