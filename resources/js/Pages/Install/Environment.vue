<template>
    <Layout :app-name="'BasketManager Pro'" :subtitle="$t('environment_title')" :current-step="4" :language="language">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                {{ $t('environment_title') }}
            </h2>
            <p class="text-gray-600 mb-8">
                {{ $t('environment_description') }}
            </p>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'border-orange-500 text-orange-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        {{ tab.icon }} {{ $t(tab.label) }}
                    </button>
                </nav>
            </div>

            <form @submit.prevent="submit">
                <!-- Application Tab -->
                <div v-show="activeTab === 'application'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('app_name_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.app_name"
                            type="text"
                            required
                            :placeholder="$t('app_name_placeholder')"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.app_name" class="mt-1 text-sm text-red-600">{{ form.errors.app_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('app_url_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.app_url"
                            type="url"
                            required
                            :placeholder="$t('app_url_placeholder')"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.app_url" class="mt-1 text-sm text-red-600">{{ form.errors.app_url }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('app_env_label') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.app_env"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                            <option value="local">{{ $t('app_env_local') }}</option>
                            <option value="staging">{{ $t('app_env_staging') }}</option>
                            <option value="production">{{ $t('app_env_production') }}</option>
                        </select>
                        <p v-if="form.errors.app_env" class="mt-1 text-sm text-red-600">{{ form.errors.app_env }}</p>
                    </div>

                    <div class="flex items-center">
                        <input
                            v-model="form.app_debug"
                            type="checkbox"
                            id="app_debug"
                            class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded"
                        />
                        <label for="app_debug" class="ml-2 block text-sm text-gray-700">
                            {{ $t('app_debug_label') }}
                        </label>
                    </div>
                    <p class="text-xs text-gray-500">{{ $t('app_debug_help') }}</p>
                </div>

                <!-- Database Tab -->
                <div v-show="activeTab === 'database'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('db_connection_label') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.db_connection"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                            <option value="mysql">MySQL</option>
                            <option value="pgsql">PostgreSQL</option>
                            <option value="sqlite">SQLite</option>
                        </select>
                        <p v-if="form.errors.db_connection" class="mt-1 text-sm text-red-600">{{ form.errors.db_connection }}</p>
                    </div>

                    <div v-if="form.db_connection !== 'sqlite'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('db_host_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.db_host"
                            type="text"
                            required
                            placeholder="127.0.0.1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.db_host" class="mt-1 text-sm text-red-600">{{ form.errors.db_host }}</p>
                    </div>

                    <div v-if="form.db_connection !== 'sqlite'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $t('db_port_label') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.db_port"
                                type="number"
                                required
                                :placeholder="form.db_connection === 'mysql' ? '3306' : '5432'"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.db_port" class="mt-1 text-sm text-red-600">{{ form.errors.db_port }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $t('db_database_label') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.db_database"
                                type="text"
                                required
                                placeholder="basketmanager"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.db_database" class="mt-1 text-sm text-red-600">{{ form.errors.db_database }}</p>
                        </div>
                    </div>

                    <div v-if="form.db_connection !== 'sqlite'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('db_username_label') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.db_username"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.db_username" class="mt-1 text-sm text-red-600">{{ form.errors.db_username }}</p>
                    </div>

                    <div v-if="form.db_connection !== 'sqlite'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('db_password_label') }}
                        </label>
                        <input
                            v-model="form.db_password"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.db_password" class="mt-1 text-sm text-red-600">{{ form.errors.db_password }}</p>
                    </div>

                    <div v-if="form.db_connection !== 'sqlite'">
                        <button
                            type="button"
                            @click="testDatabase"
                            :disabled="testingDatabase"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition-colors"
                        >
                            <span v-if="!testingDatabase">🔍 {{ $t('test_database_connection') }}</span>
                            <span v-else>Testing...</span>
                        </button>
                        <p v-if="databaseTestResult" :class="[
                            'mt-2 text-sm',
                            databaseTestResult.success ? 'text-green-600' : 'text-red-600'
                        ]">
                            {{ databaseTestResult.message }}
                        </p>
                    </div>
                </div>

                <!-- Mail Tab -->
                <div v-show="activeTab === 'mail'" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800">
                            ℹ️ {{ $t('mail_configuration_optional') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('mail_mailer_label') }}
                        </label>
                        <select
                            v-model="form.mail_mailer"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                            <option value="smtp">SMTP</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="mailgun">Mailgun</option>
                            <option value="ses">Amazon SES</option>
                            <option value="postmark">Postmark</option>
                        </select>
                    </div>

                    <div v-if="form.mail_mailer === 'smtp'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $t('mail_host_label') }}
                            </label>
                            <input
                                v-model="form.mail_host"
                                type="text"
                                placeholder="smtp.mailtrap.io"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $t('mail_port_label') }}
                            </label>
                            <input
                                v-model="form.mail_port"
                                type="number"
                                placeholder="587"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div v-if="form.mail_mailer === 'smtp'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('mail_username_label') }}
                        </label>
                        <input
                            v-model="form.mail_username"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                    </div>

                    <div v-if="form.mail_mailer === 'smtp'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('mail_password_label') }}
                        </label>
                        <input
                            v-model="form.mail_password"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                    </div>

                    <div v-if="form.mail_mailer === 'smtp'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('mail_encryption_label') }}
                        </label>
                        <select
                            v-model="form.mail_encryption"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('mail_from_address_label') }}
                        </label>
                        <input
                            v-model="form.mail_from_address"
                            type="email"
                            placeholder="noreply@example.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('mail_from_name_label') }}
                        </label>
                        <input
                            v-model="form.mail_from_name"
                            type="text"
                            :placeholder="form.app_name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        />
                    </div>
                </div>

                <!-- Stripe Tab -->
                <div v-show="activeTab === 'stripe'" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800">
                            ℹ️ {{ $t('stripe_configuration_optional') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('stripe_key_label') }}
                        </label>
                        <input
                            v-model="form.stripe_key"
                            type="text"
                            :placeholder="$t('stripe_key_placeholder')"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono text-sm"
                        />
                        <p v-if="form.errors.stripe_key" class="mt-1 text-sm text-red-600">{{ form.errors.stripe_key }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('stripe_secret_label') }}
                        </label>
                        <input
                            v-model="form.stripe_secret"
                            type="password"
                            :placeholder="$t('stripe_secret_placeholder')"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono text-sm"
                        />
                        <p v-if="form.errors.stripe_secret" class="mt-1 text-sm text-red-600">{{ form.errors.stripe_secret }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('stripe_webhook_label') }}
                        </label>
                        <input
                            v-model="form.stripe_webhook_secret"
                            type="password"
                            :placeholder="$t('stripe_webhook_placeholder')"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono text-sm"
                        />
                    </div>

                    <div v-if="form.stripe_key && form.stripe_secret">
                        <button
                            type="button"
                            @click="testStripe"
                            :disabled="testingStripe"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition-colors"
                        >
                            <span v-if="!testingStripe">🔍 {{ $t('test_stripe_connection') }}</span>
                            <span v-else>Testing...</span>
                        </button>
                        <p v-if="stripeTestResult" :class="[
                            'mt-2 text-sm',
                            stripeTestResult.success ? 'text-green-600' : 'text-red-600'
                        ]">
                            {{ stripeTestResult.message }}
                            <span v-if="stripeTestResult.account_name" class="font-semibold">
                                ({{ stripeTestResult.account_name }})
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                    <Link
                        :href="route('install.permissions')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        ← {{ $t('back') }}
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 disabled:bg-gray-400 transition-colors"
                    >
                        <span v-if="!form.processing">{{ $t('save_configuration') }} →</span>
                        <span v-else>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </Layout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Layout from './Layout.vue';
import axios from 'axios';

const props = defineProps({
    currentEnv: {
        type: Object,
        required: true
    },
    language: {
        type: String,
        default: 'de'
    }
});

const activeTab = ref('application');
const testingDatabase = ref(false);
const testingStripe = ref(false);
const databaseTestResult = ref(null);
const stripeTestResult = ref(null);

const tabs = [
    { id: 'application', label: 'tab_application', icon: '⚙️' },
    { id: 'database', label: 'tab_database', icon: '🗄️' },
    { id: 'mail', label: 'tab_mail', icon: '📧' },
    { id: 'stripe', label: 'tab_stripe', icon: '💳' }
];

const form = useForm({
    app_name: props.currentEnv.app_name,
    app_url: props.currentEnv.app_url,
    app_env: props.currentEnv.app_env,
    app_debug: props.currentEnv.app_debug,
    db_connection: props.currentEnv.db_connection,
    db_host: props.currentEnv.db_host,
    db_port: props.currentEnv.db_port,
    db_database: props.currentEnv.db_database,
    db_username: props.currentEnv.db_username,
    db_password: props.currentEnv.db_password,
    mail_mailer: props.currentEnv.mail_mailer,
    mail_host: props.currentEnv.mail_host,
    mail_port: props.currentEnv.mail_port,
    mail_username: props.currentEnv.mail_username,
    mail_password: props.currentEnv.mail_password,
    mail_encryption: props.currentEnv.mail_encryption,
    mail_from_address: props.currentEnv.mail_from_address,
    mail_from_name: props.currentEnv.mail_from_name,
    stripe_key: props.currentEnv.stripe_key,
    stripe_secret: props.currentEnv.stripe_secret,
    stripe_webhook_secret: props.currentEnv.stripe_webhook_secret
});

const testDatabase = async () => {
    testingDatabase.value = true;
    databaseTestResult.value = null;

    try {
        const response = await axios.post(route('install.environment.test-database'), {
            db_connection: form.db_connection,
            db_host: form.db_host,
            db_port: form.db_port,
            db_database: form.db_database,
            db_username: form.db_username,
            db_password: form.db_password
        });

        databaseTestResult.value = response.data;
    } catch (error) {
        databaseTestResult.value = {
            success: false,
            message: error.response?.data?.message || 'Connection test failed'
        };
    } finally {
        testingDatabase.value = false;
    }
};

const testStripe = async () => {
    testingStripe.value = true;
    stripeTestResult.value = null;

    try {
        const response = await axios.post(route('install.environment.test-stripe'), {
            stripe_key: form.stripe_key,
            stripe_secret: form.stripe_secret
        });

        stripeTestResult.value = response.data;
    } catch (error) {
        stripeTestResult.value = {
            success: false,
            message: error.response?.data?.message || 'Stripe test failed'
        };
    } finally {
        testingStripe.value = false;
    }
};

const submit = () => {
    form.post(route('install.environment.save'));
};

const $t = (key) => {
    const translations = {
        de: {
            environment_title: 'Umgebungskonfiguration',
            environment_description: 'Konfigurieren Sie Ihre Anwendung, Datenbank und externe Services.',
            tab_application: 'Anwendung',
            tab_database: 'Datenbank',
            tab_mail: 'E-Mail',
            tab_stripe: 'Stripe',
            app_name_label: 'Anwendungsname',
            app_name_placeholder: 'BasketManager Pro',
            app_url_label: 'Anwendungs-URL',
            app_url_placeholder: 'https://your-domain.com',
            app_env_label: 'Umgebung',
            app_env_local: 'Lokal (Entwicklung)',
            app_env_staging: 'Staging (Test)',
            app_env_production: 'Produktion (Live)',
            app_debug_label: 'Debug-Modus aktivieren',
            app_debug_help: 'Debug-Modus nur in Entwicklungsumgebungen aktivieren',
            db_connection_label: 'Datenbank-Typ',
            db_host_label: 'Host',
            db_port_label: 'Port',
            db_database_label: 'Datenbankname',
            db_username_label: 'Benutzername',
            db_password_label: 'Passwort',
            test_database_connection: 'Datenbankverbindung testen',
            mail_mailer_label: 'Mail-Treiber',
            mail_host_label: 'SMTP Host',
            mail_port_label: 'SMTP Port',
            mail_username_label: 'SMTP Benutzername',
            mail_password_label: 'SMTP Passwort',
            mail_encryption_label: 'Verschlüsselung',
            mail_from_address_label: 'Absender-E-Mail',
            mail_from_name_label: 'Absender-Name',
            mail_configuration_optional: 'E-Mail-Konfiguration ist optional und kann später eingerichtet werden.',
            stripe_key_label: 'Stripe Publishable Key',
            stripe_key_placeholder: 'pk_test_...',
            stripe_secret_label: 'Stripe Secret Key',
            stripe_secret_placeholder: 'sk_test_...',
            stripe_webhook_label: 'Stripe Webhook Secret',
            stripe_webhook_placeholder: 'whsec_...',
            test_stripe_connection: 'Stripe-Verbindung testen',
            stripe_configuration_optional: 'Stripe-Konfiguration ist optional und kann später eingerichtet werden.',
            save_configuration: 'Konfiguration speichern',
            back: 'Zurück'
        },
        en: {
            environment_title: 'Environment Configuration',
            environment_description: 'Configure your application, database, and external services.',
            tab_application: 'Application',
            tab_database: 'Database',
            tab_mail: 'Email',
            tab_stripe: 'Stripe',
            app_name_label: 'Application Name',
            app_name_placeholder: 'BasketManager Pro',
            app_url_label: 'Application URL',
            app_url_placeholder: 'https://your-domain.com',
            app_env_label: 'Environment',
            app_env_local: 'Local (Development)',
            app_env_staging: 'Staging (Testing)',
            app_env_production: 'Production (Live)',
            app_debug_label: 'Enable Debug Mode',
            app_debug_help: 'Enable debug mode only in development environments',
            db_connection_label: 'Database Type',
            db_host_label: 'Host',
            db_port_label: 'Port',
            db_database_label: 'Database Name',
            db_username_label: 'Username',
            db_password_label: 'Password',
            test_database_connection: 'Test Database Connection',
            mail_mailer_label: 'Mail Driver',
            mail_host_label: 'SMTP Host',
            mail_port_label: 'SMTP Port',
            mail_username_label: 'SMTP Username',
            mail_password_label: 'SMTP Password',
            mail_encryption_label: 'Encryption',
            mail_from_address_label: 'From Email',
            mail_from_name_label: 'From Name',
            mail_configuration_optional: 'Email configuration is optional and can be set up later.',
            stripe_key_label: 'Stripe Publishable Key',
            stripe_key_placeholder: 'pk_test_...',
            stripe_secret_label: 'Stripe Secret Key',
            stripe_secret_placeholder: 'sk_test_...',
            stripe_webhook_label: 'Stripe Webhook Secret',
            stripe_webhook_placeholder: 'whsec_...',
            test_stripe_connection: 'Test Stripe Connection',
            stripe_configuration_optional: 'Stripe configuration is optional and can be set up later.',
            save_configuration: 'Save Configuration',
            back: 'Back'
        }
    };
    return translations[props.language]?.[key] || key;
};
</script>
