<template>
    <AppLayout :title="`${player.user?.name || player.first_name + ' ' + player.last_name} bearbeiten`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ player.user?.name || player.first_name + ' ' + player.last_name }} bearbeiten
                </h2>
                <SecondaryButton 
                    :href="route('web.players.show', player.id)"
                    as="Link"
                >
                    Zurück
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Form Navigation -->
                        <div class="mb-8 border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8">
                                <button
                                    v-for="section in formSections"
                                    :key="section.id"
                                    @click.prevent="activeSection = section.id"
                                    :class="[
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                        activeSection === section.id
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                    type="button"
                                >
                                    {{ section.name }}
                                    <span v-if="hasErrors(section.fields)" class="ml-2 text-red-500">•</span>
                                </button>
                            </nav>
                        </div>

                        <!-- Basis Informationen -->
                        <div v-show="activeSection === 'basic'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basis Informationen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Personal Information -->
                                <div>
                                    <InputLabel for="first_name" value="Vorname*" />
                                    <TextInput
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                        autofocus
                                    />
                                    <InputError :message="form.errors.first_name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="last_name" value="Nachname*" />
                                    <TextInput
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.last_name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="email" value="E-Mail*" />
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="phone" value="Telefon" />
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.phone" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="birth_date" value="Geburtsdatum" />
                                    <TextInput
                                        id="birth_date"
                                        v-model="form.birth_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        :max="maxBirthDate"
                                    />
                                    <InputError :message="form.errors.birth_date" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="gender" value="Geschlecht" />
                                    <select
                                        id="gender"
                                        v-model="form.gender"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                        <option value="">Auswählen</option>
                                        <option value="male">Männlich</option>
                                        <option value="female">Weiblich</option>
                                        <option value="other">Divers</option>
                                    </select>
                                    <InputError :message="form.errors.gender" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Team & Position -->
                        <div v-show="activeSection === 'team'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Team & Position</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <InputLabel for="team_id" value="Team*" />
                                    <select
                                        id="team_id"
                                        v-model="form.team_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        required
                                    >
                                        <option value="">Team auswählen</option>
                                        <option v-for="team in teams" :key="team.id" :value="team.id">
                                            {{ team.name }} ({{ team.club.name }})
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.team_id" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="jersey_number" value="Rückennummer*" />
                                    <TextInput
                                        id="jersey_number"
                                        v-model="form.jersey_number"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="0"
                                        max="99"
                                        required
                                    />
                                    <InputError :message="form.errors.jersey_number" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="primary_position" value="Hauptposition*" />
                                    <select
                                        id="primary_position"
                                        v-model="form.primary_position"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        required
                                    >
                                        <option value="">Position wählen</option>
                                        <option value="PG">Point Guard (PG)</option>
                                        <option value="SG">Shooting Guard (SG)</option>
                                        <option value="SF">Small Forward (SF)</option>
                                        <option value="PF">Power Forward (PF)</option>
                                        <option value="C">Center (C)</option>
                                    </select>
                                    <InputError :message="form.errors.primary_position" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="secondary_positions" value="Sekundäre Positionen" />
                                    <div class="mt-1 space-y-2">
                                        <label v-for="position in positions" :key="position.value" class="flex items-center">
                                            <input
                                                type="checkbox"
                                                :value="position.value"
                                                v-model="form.secondary_positions"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <span class="ml-2 text-sm">{{ position.label }}</span>
                                        </label>
                                    </div>
                                    <InputError :message="form.errors.secondary_positions" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="status" value="Status*" />
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        required
                                    >
                                        <option value="active">Aktiv</option>
                                        <option value="inactive">Inaktiv</option>
                                        <option value="injured">Verletzt</option>
                                        <option value="suspended">Gesperrt</option>
                                    </select>
                                    <InputError :message="form.errors.status" class="mt-2" />
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_captain"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Kapitän</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_starter"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Stammspieler</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_rookie"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Rookie</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Physische Daten -->
                        <div v-show="activeSection === 'physical'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Physische Daten</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <InputLabel for="height_cm" value="Größe (cm)" />
                                    <TextInput
                                        id="height_cm"
                                        v-model="form.height_cm"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="100"
                                        max="250"
                                    />
                                    <InputError :message="form.errors.height_cm" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="weight_kg" value="Gewicht (kg)" />
                                    <TextInput
                                        id="weight_kg"
                                        v-model="form.weight_kg"
                                        type="number"
                                        step="0.1"
                                        class="mt-1 block w-full"
                                        min="30"
                                        max="200"
                                    />
                                    <InputError :message="form.errors.weight_kg" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="dominant_hand" value="Dominante Hand" />
                                    <select
                                        id="dominant_hand"
                                        v-model="form.dominant_hand"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                        <option value="">Auswählen</option>
                                        <option value="left">Links</option>
                                        <option value="right">Rechts</option>
                                        <option value="ambidextrous">Beidhändig</option>
                                    </select>
                                    <InputError :message="form.errors.dominant_hand" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="shoe_size" value="Schuhgröße" />
                                    <TextInput
                                        id="shoe_size"
                                        v-model="form.shoe_size"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="42"
                                    />
                                    <InputError :message="form.errors.shoe_size" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Basketball Experience -->
                        <div v-show="activeSection === 'experience'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basketball Erfahrung</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="started_playing" value="Spiel begonnen am" />
                                    <TextInput
                                        id="started_playing"
                                        v-model="form.started_playing"
                                        type="date"
                                        class="mt-1 block w-full"
                                        :max="maxBirthDate"
                                    />
                                    <InputError :message="form.errors.started_playing" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="years_experience" value="Jahre Erfahrung" />
                                    <TextInput
                                        id="years_experience"
                                        v-model="form.years_experience"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="0"
                                        max="50"
                                    />
                                    <InputError :message="form.errors.years_experience" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="previous_teams" value="Vorherige Teams" />
                                    <TagInput
                                        v-model="form.previous_teams"
                                        placeholder="Team hinzufügen..."
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.previous_teams" class="mt-2" />
                                    <div class="text-xs text-gray-500 mt-1">Drücken Sie Enter, um Teams hinzuzufügen</div>
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="achievements" value="Erfolge & Auszeichnungen" />
                                    <TagInput
                                        v-model="form.achievements"
                                        placeholder="Erfolg hinzufügen..."
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.achievements" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Medical Information -->
                        <div v-show="activeSection === 'medical'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Medizinische Informationen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="blood_type" value="Blutgruppe" />
                                    <select
                                        id="blood_type"
                                        v-model="form.blood_type"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                        <option value="">Auswählen</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                    <InputError :message="form.errors.blood_type" class="mt-2" />
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.medical_clearance"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Medical Clearance erhalten</span>
                                    </label>
                                </div>

                                <div v-if="form.medical_clearance">
                                    <InputLabel for="medical_clearance_expires" value="Medical Clearance gültig bis" />
                                    <TextInput
                                        id="medical_clearance_expires"
                                        v-model="form.medical_clearance_expires"
                                        type="date"
                                        class="mt-1 block w-full"
                                        :min="tomorrow"
                                    />
                                    <InputError :message="form.errors.medical_clearance_expires" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="preferred_hospital" value="Bevorzugtes Krankenhaus" />
                                    <TextInput
                                        id="preferred_hospital"
                                        v-model="form.preferred_hospital"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.preferred_hospital" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="medical_conditions" value="Medizinische Bedingungen" />
                                    <TagInput
                                        v-model="form.medical_conditions"
                                        placeholder="Bedingung hinzufügen..."
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.medical_conditions" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="allergies" value="Allergien" />
                                    <TagInput
                                        v-model="form.allergies"
                                        placeholder="Allergie hinzufügen..."
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.allergies" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="medications" value="Medikamente" />
                                    <TagInput
                                        v-model="form.medications"
                                        placeholder="Medikament hinzufügen..."
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.medications" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="medical_notes" value="Medizinische Notizen" />
                                    <textarea
                                        id="medical_notes"
                                        v-model="form.medical_notes"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        maxlength="2000"
                                    ></textarea>
                                    <InputError :message="form.errors.medical_notes" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contacts -->
                        <div v-show="activeSection === 'emergency'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Notfallkontakte</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="emergency_medical_contact" value="Notfallkontakt Name" />
                                    <TextInput
                                        id="emergency_medical_contact"
                                        v-model="form.emergency_medical_contact"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.emergency_medical_contact" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="emergency_medical_phone" value="Notfallkontakt Telefon" />
                                    <TextInput
                                        id="emergency_medical_phone"
                                        v-model="form.emergency_medical_phone"
                                        type="tel"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.emergency_medical_phone" class="mt-2" />
                                </div>

                                <div v-if="showMinorFields" class="md:col-span-2">
                                    <h4 class="text-base font-medium text-gray-900 mb-3">Zusätzliche Kontakte (für Minderjährige)</h4>
                                    <div class="space-y-4">
                                        <div 
                                            v-for="(contact, index) in form.guardian_contacts" 
                                            :key="index"
                                            class="p-4 border border-gray-200 rounded-lg"
                                        >
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <InputLabel :for="`guardian_name_${index}`" value="Name" />
                                                    <TextInput
                                                        :id="`guardian_name_${index}`"
                                                        v-model="contact.name"
                                                        type="text"
                                                        class="mt-1 block w-full"
                                                    />
                                                </div>
                                                <div>
                                                    <InputLabel :for="`guardian_phone_${index}`" value="Telefon" />
                                                    <TextInput
                                                        :id="`guardian_phone_${index}`"
                                                        v-model="contact.phone"
                                                        type="tel"
                                                        class="mt-1 block w-full"
                                                    />
                                                </div>
                                                <div>
                                                    <InputLabel :for="`guardian_relationship_${index}`" value="Beziehung" />
                                                    <TextInput
                                                        :id="`guardian_relationship_${index}`"
                                                        v-model="contact.relationship"
                                                        type="text"
                                                        class="mt-1 block w-full"
                                                        placeholder="z.B. Mutter, Vater"
                                                    />
                                                </div>
                                            </div>
                                            <button 
                                                @click="removeGuardianContact(index)"
                                                type="button"
                                                class="mt-2 text-red-600 text-sm hover:text-red-800"
                                            >
                                                Kontakt entfernen
                                            </button>
                                        </div>
                                        <button 
                                            @click="addGuardianContact"
                                            type="button"
                                            class="text-indigo-600 text-sm hover:text-indigo-800"
                                        >
                                            + Weiteren Kontakt hinzufügen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preferences -->
                        <div v-show="activeSection === 'preferences'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Einstellungen & Präferenzen</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <InputLabel for="dietary_restrictions" value="Ernährungseinschränkungen" />
                                    <TagInput
                                        v-model="form.dietary_restrictions"
                                        placeholder="Einschränkung hinzufügen..."
                                        class="mt-1"
                                    />
                                    <InputError :message="form.errors.dietary_restrictions" class="mt-2" />
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_photos"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Fotos erlauben</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_media_interviews"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Medien-Interviews erlauben</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.academic_eligibility"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2">Schulische Berechtigung vorhanden</span>
                                    </label>
                                </div>

                                <div v-if="showMinorFields">
                                    <InputLabel for="school_name" value="Schule" />
                                    <TextInput
                                        id="school_name"
                                        v-model="form.school_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.school_name" class="mt-2" />
                                </div>

                                <div v-if="showMinorFields">
                                    <InputLabel for="grade_level" value="Klassenstufe" />
                                    <TextInput
                                        id="grade_level"
                                        v-model="form.grade_level"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="z.B. 10. Klasse"
                                    />
                                    <InputError :message="form.errors.grade_level" class="mt-2" />
                                </div>

                                <div v-if="showMinorFields">
                                    <InputLabel for="gpa" value="Notendurchschnitt" />
                                    <TextInput
                                        id="gpa"
                                        v-model="form.gpa"
                                        type="number"
                                        step="0.1"
                                        min="1.0"
                                        max="4.0"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.gpa" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="coach_notes" value="Trainer-Notizen" />
                                    <textarea
                                        id="coach_notes"
                                        v-model="form.coach_notes"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        maxlength="2000"
                                    ></textarea>
                                    <InputError :message="form.errors.coach_notes" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between mt-6">
                            <DangerButton
                                v-if="can?.delete"
                                type="button"
                                @click="confirmingPlayerDeletion = true"
                            >
                                Spieler löschen
                            </DangerButton>

                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Änderungen speichern
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Player Confirmation Modal -->
        <ConfirmationModal :show="confirmingPlayerDeletion" @close="confirmingPlayerDeletion = false">
            <template #title>
                Spieler löschen
            </template>

            <template #content>
                Sind Sie sicher, dass Sie diesen Spieler löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingPlayerDeletion = false">
                    Abbrechen
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deletePlayerConfirmed"
                >
                    Spieler löschen
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import TagInput from '@/Components/TagInput.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'

const props = defineProps({
    player: Object,
    teams: Array,
    can: Object,
})

// Form Section Management
const activeSection = ref('basic')

const formSections = ref([
    { id: 'basic', name: 'Basis', fields: ['first_name', 'last_name', 'email', 'phone', 'birth_date', 'gender'] },
    { id: 'team', name: 'Team', fields: ['team_id', 'jersey_number', 'primary_position', 'secondary_positions', 'status'] },
    { id: 'physical', name: 'Physisch', fields: ['height_cm', 'weight_kg', 'dominant_hand', 'shoe_size'] },
    { id: 'experience', name: 'Erfahrung', fields: ['started_playing', 'years_experience', 'previous_teams', 'achievements'] },
    { id: 'medical', name: 'Medizinisch', fields: ['blood_type', 'medical_clearance', 'medical_conditions', 'allergies', 'medications'] },
    { id: 'emergency', name: 'Notfall', fields: ['emergency_medical_contact', 'emergency_medical_phone', 'guardian_contacts'] },
    { id: 'preferences', name: 'Präferenzen', fields: ['dietary_restrictions', 'allow_photos', 'allow_media_interviews', 'school_name', 'grade_level'] }
])

// Extended Form with all fields from Create.vue - with safe property access
const form = useForm({
    // Basic Information
    first_name: props.player?.first_name || '',
    last_name: props.player?.last_name || '',
    email: props.player?.email || '',
    phone: props.player?.phone || '',
    birth_date: props.player?.birth_date || '',
    gender: props.player?.gender || '',
    
    // Team & Position
    team_id: props.player?.team_id || '',
    jersey_number: props.player?.jersey_number || '',
    primary_position: props.player?.primary_position || '',
    secondary_positions: props.player?.secondary_positions || [],
    status: props.player?.status || 'active',
    is_captain: props.player?.is_captain || false,
    is_starter: props.player?.is_starter || false,
    is_rookie: props.player?.is_rookie || false,
    
    // Physical Information
    height_cm: props.player?.height_cm || props.player?.height || null,
    weight_kg: props.player?.weight_kg || props.player?.weight || null,
    dominant_hand: props.player?.dominant_hand || '',
    shoe_size: props.player?.shoe_size || '',
    
    // Basketball Experience
    started_playing: props.player?.started_playing || '',
    years_experience: props.player?.years_experience || null,
    previous_teams: props.player?.previous_teams || [],
    achievements: props.player?.achievements || [],
    
    // Medical Information
    medical_conditions: props.player?.medical_conditions || [],
    allergies: props.player?.allergies || [],
    medications: props.player?.medications || [],
    blood_type: props.player?.blood_type || '',
    medical_clearance: props.player?.medical_clearance || false,
    medical_clearance_expires: props.player?.medical_clearance_expires || '',
    preferred_hospital: props.player?.preferred_hospital || '',
    medical_notes: props.player?.medical_notes || '',
    
    // Emergency Contacts
    emergency_medical_contact: props.player?.emergency_medical_contact || '',
    emergency_medical_phone: props.player?.emergency_medical_phone || '',
    guardian_contacts: props.player?.guardian_contacts || [],
    
    // Development & Training
    training_focus_areas: props.player?.training_focus_areas || [],
    development_goals: props.player?.development_goals || [],
    coach_notes: props.player?.coach_notes || '',
    
    // Academic Information
    school_name: props.player?.school_name || '',
    grade_level: props.player?.grade_level || '',
    gpa: props.player?.gpa || null,
    academic_eligibility: props.player?.academic_eligibility !== undefined ? props.player.academic_eligibility : true,
    
    // Preferences
    preferences: props.player?.preferences || {},
    dietary_restrictions: props.player?.dietary_restrictions || [],
    social_media: props.player?.social_media || {},
    allow_photos: props.player?.allow_photos !== undefined ? props.player.allow_photos : true,
    allow_media_interviews: props.player?.allow_media_interviews || false,
})

// Position Options
const positions = ref([
    { value: 'PG', label: 'Point Guard (PG)' },
    { value: 'SG', label: 'Shooting Guard (SG)' },
    { value: 'SF', label: 'Small Forward (SF)' },
    { value: 'PF', label: 'Power Forward (PF)' },
    { value: 'C', label: 'Center (C)' }
])

const deleteForm = useForm({})

const confirmingPlayerDeletion = ref(false)

// Computed Properties
const maxBirthDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

const tomorrow = computed(() => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow.toISOString().split('T')[0]
})

const showMinorFields = computed(() => {
    if (!form.birth_date) return false
    const today = new Date()
    const birthDate = new Date(form.birth_date)
    let age = today.getFullYear() - birthDate.getFullYear()
    const monthDiff = today.getMonth() - birthDate.getMonth()
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--
    }
    
    return age < 18
})

// Helper Functions
const hasErrors = computed(() => {
    return (fields) => fields.some(field => form.errors[field])
})

const addGuardianContact = () => {
    form.guardian_contacts.push({
        name: '',
        phone: '',
        relationship: ''
    })
}

const removeGuardianContact = (index) => {
    form.guardian_contacts.splice(index, 1)
}

const submit = () => {
    form.put(route('web.players.update', props.player.id), {
        onError: (errors) => {
            if (errors.status === 419) {
                console.warn('CSRF token mismatch in player update form');
                // The axios interceptor will handle the page refresh
                return;
            }
            console.error('Player update failed:', errors);
        },
        onSuccess: () => {
            console.log('Player updated successfully');
        }
    });
}

const deletePlayerConfirmed = () => {
    deleteForm.delete(route('web.players.destroy', props.player.id))
}
</script>