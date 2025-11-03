<template>
    <Layout :app-name="appName" :subtitle="$t('complete_title')" :current-step="7" :language="language">
        <div class="text-center">
            <!-- Success Icon -->
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full">
                    <svg class="w-16 h-16 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                🎉 {{ $t('complete_title') }}
            </h2>
            <p class="text-xl text-gray-600 mb-8">
                {{ $t('complete_subtitle') }}
            </p>
            <p class="text-gray-700 mb-12">
                {{ $t('complete_description') }}
            </p>

            <!-- Credentials Box -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 border-2 border-orange-200 rounded-lg p-6 mb-8 max-w-md mx-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    🔑 {{ $t('your_credentials') }}
                </h3>
                <div class="bg-white rounded-lg p-4 space-y-3 text-left">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $t('email') }}</label>
                        <div class="font-mono text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200">
                            {{ adminEmail }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $t('password') }}</label>
                        <div class="font-mono text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded border border-gray-200">
                            {{ $t('password_as_entered') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8 max-w-md mx-auto text-left">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h4 class="font-semibold text-yellow-900 mb-2">{{ $t('important_note') }}</h4>
                        <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                            <li>{{ $t('change_password_note') }}</li>
                            <li>{{ $t('save_credentials_note') }}</li>
                            <li v-if="$t('backup_recommendation')">{{ $t('backup_recommendation') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a
                    :href="loginUrl"
                    class="inline-flex items-center px-8 py-4 bg-orange-600 text-white text-lg font-semibold rounded-lg hover:bg-orange-700 transition-colors shadow-lg hover:shadow-xl"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    {{ $t('go_to_login') }}
                </a>
            </div>

            <!-- What's Next -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ $t('whats_next') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-3xl mb-2">👥</div>
                        <h4 class="font-semibold text-gray-900 mb-1">{{ $t('step_1_title') }}</h4>
                        <p class="text-sm text-gray-600">{{ $t('step_1_description') }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-3xl mb-2">🏀</div>
                        <h4 class="font-semibold text-gray-900 mb-1">{{ $t('step_2_title') }}</h4>
                        <p class="text-sm text-gray-600">{{ $t('step_2_description') }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-3xl mb-2">⚙️</div>
                        <h4 class="font-semibold text-gray-900 mb-1">{{ $t('step_3_title') }}</h4>
                        <p class="text-sm text-gray-600">{{ $t('step_3_description') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from './Layout.vue';

const props = defineProps({
    appName: {
        type: String,
        default: 'BasketManager Pro'
    },
    adminEmail: {
        type: String,
        required: true
    },
    loginUrl: {
        type: String,
        required: true
    },
    language: {
        type: String,
        default: 'de'
    }
});

const $t = (key) => {
    const translations = {
        de: {
            complete_title: 'Installation abgeschlossen!',
            complete_subtitle: 'BasketManager Pro ist einsatzbereit',
            complete_description: 'Ihre Installation war erfolgreich. Sie können sich jetzt mit Ihren Administrator-Zugangsdaten anmelden.',
            your_credentials: 'Ihre Zugangsdaten',
            email: 'E-Mail',
            password: 'Passwort',
            password_as_entered: '(wie eingegeben)',
            important_note: 'Wichtiger Hinweis',
            change_password_note: 'Bitte ändern Sie nach dem ersten Login Ihr Passwort.',
            save_credentials_note: 'Speichern Sie Ihre Zugangsdaten an einem sicheren Ort.',
            backup_recommendation: 'Erstellen Sie regelmäßige Backups Ihrer Datenbank.',
            go_to_login: 'Zum Login',
            whats_next: 'Nächste Schritte',
            step_1_title: 'Team erstellen',
            step_1_description: 'Erstellen Sie Ihr erstes Basketball-Team und fügen Sie Spieler hinzu.',
            step_2_title: 'Spiele planen',
            step_2_description: 'Planen Sie Spiele und nutzen Sie die Live-Scoring-Funktion.',
            step_3_title: 'System konfigurieren',
            step_3_description: 'Passen Sie Einstellungen, Berechtigungen und Subscription-Pläne an.'
        },
        en: {
            complete_title: 'Installation Complete!',
            complete_subtitle: 'BasketManager Pro is ready to use',
            complete_description: 'Your installation was successful. You can now log in with your administrator credentials.',
            your_credentials: 'Your Credentials',
            email: 'Email',
            password: 'Password',
            password_as_entered: '(as entered)',
            important_note: 'Important Note',
            change_password_note: 'Please change your password after first login.',
            save_credentials_note: 'Save your credentials in a secure location.',
            backup_recommendation: 'Create regular backups of your database.',
            go_to_login: 'Go to Login',
            whats_next: 'What\'s Next',
            step_1_title: 'Create Team',
            step_1_description: 'Create your first basketball team and add players.',
            step_2_title: 'Schedule Games',
            step_2_description: 'Schedule games and use the live scoring feature.',
            step_3_title: 'Configure System',
            step_3_description: 'Customize settings, permissions, and subscription plans.'
        }
    };
    return translations[props.language]?.[key] || key;
};
</script>
