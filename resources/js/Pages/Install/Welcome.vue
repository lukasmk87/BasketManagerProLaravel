<template>
    <Layout :app-name="appName" :subtitle="$t('installation_wizard')" :current-step="1" :language="language">
        <div class="text-center">
            <div class="text-6xl mb-6">🏀</div>
            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                {{ $t('welcome_title') }}
            </h2>
            <p class="text-xl text-gray-600 mb-8">
                {{ $t('welcome_subtitle') }}
            </p>
            <p class="text-gray-700 mb-8">
                {{ $t('welcome_description') }}
            </p>
        </div>

        <!-- Features -->
        <div class="my-10">
            <h3 class="text-xl font-semibold text-gray-900 mb-6 text-center">
                {{ $t('welcome_features') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="(feature, index) in features" :key="index" class="flex items-start space-x-3 p-4 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0 text-2xl">{{ feature.icon }}</div>
                    <div>
                        <h4 class="font-semibold text-gray-900">{{ $t(feature.title) }}</h4>
                        <p class="text-sm text-gray-600">{{ $t(feature.description) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="text-center mt-10">
            <Link
                :href="route('install.requirements')"
                class="inline-flex items-center px-8 py-4 bg-orange-600 text-white text-lg font-semibold rounded-lg hover:bg-orange-700 transition-colors shadow-lg hover:shadow-xl"
            >
                {{ $t('start_installation') }}
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </Link>
        </div>
    </Layout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Layout from './Layout.vue';

const props = defineProps({
    appName: {
        type: String,
        default: 'BasketManager Pro'
    },
    language: {
        type: String,
        default: 'de'
    }
});

const features = [
    { icon: '⚡', title: 'feature_live_scoring', description: 'Live game updates and statistics' },
    { icon: '👥', title: 'feature_team_management', description: 'Manage teams and players' },
    { icon: '📊', title: 'feature_training', description: 'Training sessions and drills' },
    { icon: '🏆', title: 'feature_tournaments', description: 'Tournament brackets' },
    { icon: '💳', title: 'feature_subscriptions', description: 'Multi-tenant subscriptions' },
    { icon: '🔒', title: 'feature_gdpr', description: 'GDPR compliance' }
];

// Simple translation helper (until we have proper i18n loaded)
const $t = (key) => {
    const translations = {
        de: {
            installation_wizard: 'Installations-Assistent',
            welcome_title: 'Willkommen zu BasketManager Pro',
            welcome_subtitle: 'Professionelles Basketball Club Management System',
            welcome_description: 'Dieser Installations-Assistent führt Sie durch die Einrichtung von BasketManager Pro. Der Prozess dauert etwa 5-10 Minuten.',
            welcome_features: 'Features:',
            feature_live_scoring: 'Live-Spielverfolgung',
            feature_team_management: 'Team-Management',
            feature_training: 'Trainingsverwaltung',
            feature_tournaments: 'Turnierverwaltung',
            feature_subscriptions: 'Subscription-System',
            feature_gdpr: 'GDPR-Konformität',
            start_installation: 'Installation starten'
        },
        en: {
            installation_wizard: 'Installation Wizard',
            welcome_title: 'Welcome to BasketManager Pro',
            welcome_subtitle: 'Professional Basketball Club Management System',
            welcome_description: 'This installation wizard will guide you through setting up BasketManager Pro. The process takes about 5-10 minutes.',
            welcome_features: 'Features:',
            feature_live_scoring: 'Live Game Scoring',
            feature_team_management: 'Team Management',
            feature_training: 'Training Management',
            feature_tournaments: 'Tournament Management',
            feature_subscriptions: 'Subscription System',
            feature_gdpr: 'GDPR Compliance',
            start_installation: 'Start Installation'
        }
    };

    return translations[props.language]?.[key] || key;
};
</script>
