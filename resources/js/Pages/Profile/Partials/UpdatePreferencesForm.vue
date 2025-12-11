<script setup>
import { ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SegmentControl from '@/Components/SegmentControl.vue';
import { useDarkMode } from '@/Composables/core/useDarkMode';

const props = defineProps({
    user: Object,
});

const { theme: currentTheme, setTheme } = useDarkMode();

// Parse JSON fields with fallback
const parseJsonField = (field, defaultValue = {}) => {
    try {
        if (typeof field === 'object' && field !== null) {
            return field;
        }
        return field ? JSON.parse(field) : defaultValue;
    } catch (e) {
        return defaultValue;
    }
};

const notificationSettings = ref(parseJsonField(props.user?.notification_settings, {
    email_notifications: true,
    push_notifications: true,
    game_reminders: true,
    training_reminders: true,
    team_announcements: true,
}));

const privacySettings = ref(parseJsonField(props.user?.privacy_settings, {
    profile_visible: true,
    show_email: false,
    show_phone: false,
    show_statistics: true,
}));

// Get theme from user preferences
const userPreferences = parseJsonField(props.user?.preferences, {});

const form = useForm({
    language: props.user?.language ?? 'de',
    locale: props.user?.locale ?? 'de',
    timezone: props.user?.timezone ?? 'Europe/Berlin',
    date_format: props.user?.date_format ?? 'd.m.Y',
    time_format: props.user?.time_format ?? 'H:i',
    theme: userPreferences.theme ?? currentTheme.value ?? 'system',
    notification_settings: notificationSettings.value,
    privacy_settings: privacySettings.value,
});

// Theme options for SegmentControl
const themeOptions = [
    {
        value: 'light',
        label: 'Hell',
        iconPath: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z',
    },
    {
        value: 'dark',
        label: 'Dunkel',
        iconPath: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
    },
    {
        value: 'system',
        label: 'System',
        iconPath: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    },
];

// Apply theme immediately when selection changes (for live preview)
watch(() => form.theme, (newTheme) => {
    setTheme(newTheme);
});

const availableTimezones = [
    { value: 'Europe/Berlin', label: 'Berlin (MEZ/MESZ)' },
    { value: 'Europe/Vienna', label: 'Wien (MEZ/MESZ)' },
    { value: 'Europe/Zurich', label: 'Zürich (MEZ/MESZ)' },
    { value: 'Europe/London', label: 'London (GMT/BST)' },
    { value: 'Europe/Paris', label: 'Paris (MEZ/MESZ)' },
    { value: 'America/New_York', label: 'New York (EST/EDT)' },
    { value: 'America/Los_Angeles', label: 'Los Angeles (PST/PDT)' },
    { value: 'Asia/Tokyo', label: 'Tokyo (JST)' },
];

const dateFormats = [
    { value: 'd.m.Y', label: '31.12.2024 (DE)' },
    { value: 'Y-m-d', label: '2024-12-31 (ISO)' },
    { value: 'm/d/Y', label: '12/31/2024 (US)' },
    { value: 'd/m/Y', label: '31/12/2024 (UK)' },
];

const timeFormats = [
    { value: 'H:i', label: '23:59 (24h)' },
    { value: 'h:i A', label: '11:59 PM (12h)' },
];

const updatePreferences = () => {
    form.post(route('user.preferences.update'), {
        errorBag: 'updatePreferences',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updatePreferences">
        <template #title>
            Einstellungen & Präferenzen
        </template>

        <template #description>
            Passen Sie Ihre App-Einstellungen, Benachrichtigungen und Datenschutz-Optionen an.
        </template>

        <template #form>
            <!-- Theme Selection -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="theme" value="Erscheinungsbild" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Wählen Sie das Farbschema für die Anwendung.
                </p>
                <SegmentControl
                    v-model="form.theme"
                    :options="themeOptions"
                    size="md"
                />
                <InputError :message="form.errors.theme" class="mt-2" />
            </div>

            <!-- Language -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="language" value="Sprache" />
                <select
                    id="language"
                    v-model="form.language"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="de">Deutsch</option>
                    <option value="en">English</option>
                </select>
                <InputError :message="form.errors.language" class="mt-2" />
            </div>

            <!-- Timezone -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="timezone" value="Zeitzone" />
                <select
                    id="timezone"
                    v-model="form.timezone"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option
                        v-for="tz in availableTimezones"
                        :key="tz.value"
                        :value="tz.value"
                    >
                        {{ tz.label }}
                    </option>
                </select>
                <InputError :message="form.errors.timezone" class="mt-2" />
            </div>

            <!-- Date Format -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="date_format" value="Datumsformat" />
                <select
                    id="date_format"
                    v-model="form.date_format"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option
                        v-for="format in dateFormats"
                        :key="format.value"
                        :value="format.value"
                    >
                        {{ format.label }}
                    </option>
                </select>
                <InputError :message="form.errors.date_format" class="mt-2" />
            </div>

            <!-- Time Format -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="time_format" value="Zeitformat" />
                <select
                    id="time_format"
                    v-model="form.time_format"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option
                        v-for="format in timeFormats"
                        :key="format.value"
                        :value="format.value"
                    >
                        {{ format.label }}
                    </option>
                </select>
                <InputError :message="form.errors.time_format" class="mt-2" />
            </div>

            <!-- Notification Settings -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Benachrichtigungen" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Wählen Sie aus, welche Benachrichtigungen Sie erhalten möchten.</p>

                <div class="mt-3 space-y-3">
                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.email_notifications"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">E-Mail Benachrichtigungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.push_notifications"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Push-Benachrichtigungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.game_reminders"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Spiel-Erinnerungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.training_reminders"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Training-Erinnerungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.team_announcements"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Team-Ankündigungen</span>
                    </label>
                </div>
                <InputError :message="form.errors.notification_settings" class="mt-2" />
            </div>

            <!-- Privacy Settings -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Datenschutz-Einstellungen" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Kontrollieren Sie, welche Informationen für andere sichtbar sind.</p>

                <div class="mt-3 space-y-3">
                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.profile_visible"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Profil öffentlich sichtbar</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.show_email"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">E-Mail-Adresse anzeigen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.show_phone"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Telefonnummer anzeigen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.show_statistics"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-900 dark:focus:ring-offset-gray-800"
                        >
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Basketball-Statistiken anzeigen</span>
                    </label>
                </div>
                <InputError :message="form.errors.privacy_settings" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Gespeichert.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Speichern
            </PrimaryButton>
        </template>
    </FormSection>
</template>
