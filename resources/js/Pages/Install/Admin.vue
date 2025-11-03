<template>
    <Layout :app-name="appName" :subtitle="$t('admin_title')" :current-step="6" :language="language">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                {{ $t('admin_title') }}
            </h2>
            <p class="text-gray-600 mb-8">
                {{ $t('admin_description') }}
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tenant Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('tenant_name_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.tenant_name"
                        type="text"
                        required
                        :placeholder="$t('tenant_name_placeholder')"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    />
                    <p v-if="form.errors.tenant_name" class="mt-1 text-sm text-red-600">{{ form.errors.tenant_name }}</p>
                </div>

                <!-- Admin Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('admin_name_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.admin_name"
                        type="text"
                        required
                        :placeholder="$t('admin_name_placeholder')"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    />
                    <p v-if="form.errors.admin_name" class="mt-1 text-sm text-red-600">{{ form.errors.admin_name }}</p>
                </div>

                <!-- Admin Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('admin_email_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.admin_email"
                        type="email"
                        required
                        :placeholder="$t('admin_email_placeholder')"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    />
                    <p v-if="form.errors.admin_email" class="mt-1 text-sm text-red-600">{{ form.errors.admin_email }}</p>
                </div>

                <!-- Admin Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('admin_password_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.admin_password"
                        type="password"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        @input="checkPasswordStrength"
                    />
                    <p v-if="form.errors.admin_password" class="mt-1 text-sm text-red-600">{{ form.errors.admin_password }}</p>

                    <!-- Password Strength Meter -->
                    <div v-if="form.admin_password" class="mt-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-600">{{ $t('password_strength') }}</span>
                            <span class="text-xs font-semibold" :class="passwordStrength.color">
                                {{ $t(passwordStrength.label) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="passwordStrength.bgColor"
                                :style="{ width: passwordStrength.width + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('admin_password_confirm_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.admin_password_confirmation"
                        type="password"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    />
                </div>

                <!-- Subscription Tier -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $t('subscription_tier_label') }} <span class="text-red-500">*</span>
                    </label>
                    <p class="text-sm text-gray-600 mb-4">{{ $t('subscription_tier_description') }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button
                            v-for="(tier, key) in subscriptionTiers"
                            :key="key"
                            type="button"
                            @click="form.subscription_tier = key"
                            class="p-4 border-2 rounded-lg transition-all hover:shadow-md text-left"
                            :class="{
                                'border-orange-500 bg-orange-50': form.subscription_tier === key,
                                'border-gray-200': form.subscription_tier !== key
                            }"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ tier.name }}</h4>
                                    <p class="text-sm text-orange-600 font-semibold">{{ tier.price }}</p>
                                </div>
                                <div v-if="form.subscription_tier === key" class="text-orange-500">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-xs text-gray-600 space-y-1">
                                <div>👥 {{ tier.limits.users }} Users</div>
                                <div>🏀 {{ tier.limits.teams }} Teams</div>
                                <div>💾 {{ tier.limits.storage }} Storage</div>
                            </div>
                        </button>
                    </div>
                    <p v-if="form.errors.subscription_tier" class="mt-2 text-sm text-red-600">{{ form.errors.subscription_tier }}</p>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between pt-6 border-t border-gray-200">
                    <Link
                        :href="route('install.database')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        ← {{ $t('back') }}
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-8 py-4 bg-orange-600 text-white text-lg font-semibold rounded-lg hover:bg-orange-700 disabled:bg-gray-400 transition-colors shadow-lg"
                    >
                        <span v-if="!form.processing">🚀 {{ $t('finish') }}</span>
                        <span v-else>
                            <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Layout from './Layout.vue';

const props = defineProps({
    appName: {
        type: String,
        default: 'BasketManager Pro'
    },
    language: {
        type: String,
        default: 'de'
    },
    subscriptionTiers: {
        type: Object,
        required: true
    }
});

const form = useForm({
    tenant_name: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
    subscription_tier: 'professional'
});

// Auto-fill tenant name with app name when component mounts
onMounted(() => {
    // Only auto-fill if tenant_name is empty and appName is not the default
    if (!form.tenant_name && props.appName && props.appName !== 'BasketManager Pro') {
        form.tenant_name = props.appName;
    }
});

const passwordStrengthScore = ref(0);

const checkPasswordStrength = () => {
    const password = form.admin_password;
    let score = 0;

    if (password.length >= 8) score += 25;
    if (password.length >= 12) score += 25;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 20;
    if (/\d/.test(password)) score += 15;
    if (/[^a-zA-Z0-9]/.test(password)) score += 15;

    passwordStrengthScore.value = Math.min(score, 100);
};

const passwordStrength = computed(() => {
    const score = passwordStrengthScore.value;

    if (score < 40) {
        return {
            label: 'password_weak',
            color: 'text-red-600',
            bgColor: 'bg-red-500',
            width: score
        };
    } else if (score < 70) {
        return {
            label: 'password_medium',
            color: 'text-yellow-600',
            bgColor: 'bg-yellow-500',
            width: score
        };
    } else {
        return {
            label: 'password_strong',
            color: 'text-green-600',
            bgColor: 'bg-green-500',
            width: score
        };
    }
});

const submit = () => {
    form.post(route('install.admin.create'));
};

const $t = (key) => {
    const translations = {
        de: {
            admin_title: 'Super Admin erstellen',
            admin_description: 'Dieser Account wird die vollständige Kontrolle über Ihre BasketManager Pro Installation haben.',
            tenant_name_label: 'Organisationsname',
            tenant_name_placeholder: 'Mein Basketball Club',
            admin_name_label: 'Admin-Name',
            admin_name_placeholder: 'Max Mustermann',
            admin_email_label: 'Admin-E-Mail',
            admin_email_placeholder: 'admin@example.com',
            admin_password_label: 'Passwort',
            admin_password_confirm_label: 'Passwort bestätigen',
            password_strength: 'Passwortstärke',
            password_weak: 'Schwach',
            password_medium: 'Mittel',
            password_strong: 'Stark',
            subscription_tier_label: 'Subscription-Tier',
            subscription_tier_description: 'Wählen Sie Ihren Subscription-Plan (kann später geändert werden)',
            back: 'Zurück',
            finish: 'Installation abschließen'
        },
        en: {
            admin_title: 'Create Super Admin',
            admin_description: 'This account will have full control over your BasketManager Pro installation.',
            tenant_name_label: 'Organization Name',
            tenant_name_placeholder: 'My Basketball Club',
            admin_name_label: 'Admin Name',
            admin_name_placeholder: 'John Doe',
            admin_email_label: 'Admin Email',
            admin_email_placeholder: 'admin@example.com',
            admin_password_label: 'Password',
            admin_password_confirm_label: 'Confirm Password',
            password_strength: 'Password Strength',
            password_weak: 'Weak',
            password_medium: 'Medium',
            password_strong: 'Strong',
            subscription_tier_label: 'Subscription Tier',
            subscription_tier_description: 'Choose your subscription plan (can be changed later)',
            back: 'Back',
            finish: 'Finish Installation'
        }
    };
    return translations[props.language]?.[key] || key;
};
</script>
