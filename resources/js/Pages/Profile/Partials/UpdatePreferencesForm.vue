<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: Object,
});

// Parse JSON fields with fallback
const parseJsonField = (field, defaultValue = {}) => {
    try {
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

const form = useForm({
    language: props.user?.language ?? 'de',
    locale: props.user?.locale ?? 'de',
    timezone: props.user?.timezone ?? 'Europe/Berlin',
    date_format: props.user?.date_format ?? 'd.m.Y',
    time_format: props.user?.time_format ?? 'H:i',
    notification_settings: notificationSettings.value,
    privacy_settings: privacySettings.value,
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
            <!-- Language -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="language" value="Sprache" />
                <select
                    id="language"
                    v-model="form.language"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
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
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
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
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
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
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
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
                <p class="text-xs text-gray-500 mb-2">Wählen Sie aus, welche Benachrichtigungen Sie erhalten möchten.</p>

                <div class="mt-3 space-y-3">
                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.email_notifications"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">E-Mail Benachrichtigungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.push_notifications"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">Push-Benachrichtigungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.game_reminders"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">Spiel-Erinnerungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.training_reminders"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">Training-Erinnerungen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.notification_settings.team_announcements"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">Team-Ankündigungen</span>
                    </label>
                </div>
                <InputError :message="form.errors.notification_settings" class="mt-2" />
            </div>

            <!-- Privacy Settings -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Datenschutz-Einstellungen" />
                <p class="text-xs text-gray-500 mb-2">Kontrollieren Sie, welche Informationen für andere sichtbar sind.</p>

                <div class="mt-3 space-y-3">
                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.profile_visible"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">Profil öffentlich sichtbar</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.show_email"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">E-Mail-Adresse anzeigen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.show_phone"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">Telefonnummer anzeigen</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            v-model="form.privacy_settings.show_statistics"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <span class="ms-2 text-sm text-gray-600">Basketball-Statistiken anzeigen</span>
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
