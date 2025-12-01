<template>
    <DialogModal :show="show" @close="$emit('close')" max-width="xl">
        <template #title>
            <div class="flex items-center">
                <svg class="h-6 w-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
                </svg>
                {{ gymHall ? 'Sporthalle bearbeiten' : 'Neue Sporthalle' }}
            </div>
        </template>

        <template #content>
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'details'"
                        type="button"
                        :class="[
                            activeTab === 'details'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Grunddaten
                    </button>
                    <button
                        v-if="gymHall"
                        @click="activeTab = 'schedule'"
                        type="button"
                        :class="[
                            activeTab === 'schedule'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Öffnungszeiten
                    </button>
                    <button
                        v-if="gymHall && gymHall.hall_type !== 'single'"
                        @click="activeTab = 'courts'"
                        type="button"
                        :class="[
                            activeTab === 'courts'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Plätze verwalten
                    </button>
                </nav>
            </div>

            <!-- Details Tab -->
            <div v-if="activeTab === 'details'">
                <form @submit.prevent="submitForm">
                    <div class="space-y-6">
                    <!-- Club Selection (Admin/Superadmin only) -->
                    <div v-if="showClubSelector" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-3">
                            <svg class="inline-block h-5 w-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
                            </svg>
                            Verein auswählen
                        </h4>
                        <div>
                            <label class="block text-sm font-medium text-blue-800 mb-1">Verein *</label>
                            <select
                                v-model="selectedClubId"
                                class="w-full border border-blue-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 bg-white"
                                required
                            >
                                <option :value="null" disabled>-- Bitte Verein auswählen --</option>
                                <option
                                    v-for="club in availableClubs"
                                    :key="club.id"
                                    :value="club.id"
                                >
                                    {{ club.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-blue-600">
                                Als Administrator können Sie die Sporthalle einem beliebigen Verein zuordnen.
                            </p>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Grundinformationen</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="z.B. Sporthalle Nord"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kapazität</label>
                                <input
                                    v-model.number="form.capacity"
                                    type="number"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="z.B. 200"
                                    min="1"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Adresse</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Straße & Hausnummer</label>
                                <input
                                    v-model="form.address_street"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="z.B. Sportstraße 123"
                                />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PLZ</label>
                                    <input
                                        v-model="form.address_zip"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="12345"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Stadt</label>
                                    <input
                                        v-model="form.address_city"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="z.B. München"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Land</label>
                                    <input
                                        v-model="form.address_country"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Deutschland"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Facilities & Equipment -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-4">Ausstattung</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        v-model="facilitiesCheckboxes.changing_rooms"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Umkleidekabinen</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="facilitiesCheckboxes.showers"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Duschen</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="facilitiesCheckboxes.parking"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Parkplatz</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="facilitiesCheckboxes.wheelchair_accessible"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Rollstuhlgerecht</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-4">Sportgeräte</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        v-model="equipmentCheckboxes.basketballs"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Basketbälle</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="equipmentCheckboxes.scoreboard"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Anzeigetafel</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="equipmentCheckboxes.shot_clock"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Wurfuhr</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="equipmentCheckboxes.sound_system"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Soundanlage</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Kontaktinformationen</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kontaktperson</label>
                                <input
                                    v-model="form.contact_name"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Max Mustermann"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                                <input
                                    v-model="form.contact_phone"
                                    type="tel"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="+49 123 456789"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                                <input
                                    v-model="form.contact_email"
                                    type="email"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="kontakt@sporthalle.de"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Court Configuration -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Platz-Konfiguration</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hallentyp</label>
                                <select
                                    v-model="form.hall_type"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="single">Einfachhalle (1 Platz)</option>
                                    <option value="double">Doppelhalle (2 Plätze)</option>
                                    <option value="triple">Dreifachhalle (3 Plätze)</option>
                                    <option value="multi">Mehrfachhalle (4+ Plätze)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Anzahl Plätze</label>
                                <input
                                    v-model.number="form.court_count"
                                    type="number"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="1"
                                    min="1"
                                    max="10"
                                />
                            </div>
                            <div class="flex items-center pt-6">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.supports_parallel_bookings"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Parallel-Buchungen</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Settings -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Buchungseinstellungen</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mindestbuchungsdauer (Min.)</label>
                                <select
                                    v-model.number="form.min_booking_duration_minutes"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option :value="15">15 Minuten</option>
                                    <option :value="30">30 Minuten</option>
                                    <option :value="60">60 Minuten</option>
                                    <option :value="90">90 Minuten</option>
                                    <option :value="120">120 Minuten</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Buchungsintervall (Min.)</label>
                                <select
                                    v-model.number="form.booking_increment_minutes"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option :value="15">15 Minuten</option>
                                    <option :value="30">30 Minuten</option>
                                    <option :value="60">60 Minuten</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stundensatz (€)</label>
                                <input
                                    v-model.number="form.hourly_rate"
                                    type="number"
                                    step="0.01"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="25.00"
                                    min="0"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Additional Settings -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-4">Weitere Einstellungen</h4>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600"
                                />
                                <span class="ml-2 text-sm text-gray-700">Aktiv</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="form.requires_key"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600"
                                />
                                <span class="ml-2 text-sm text-gray-700">Schlüssel erforderlich</span>
                            </label>
                        </div>
                    </div>

                    <!-- Description & Special Rules -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Zusätzliche Informationen zur Sporthalle..."
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Besondere Regeln</label>
                            <textarea
                                v-model="form.special_rules"
                                rows="4"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="z.B. Keine Straßenschuhe, Anmeldung erforderlich..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Active Time Slots Warning (for existing halls) -->
                    <div v-if="gymHall && gymHall.time_slots_count > 0" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-yellow-800">Aktive Zeitfenster vorhanden</h3>
                                <p class="mt-1 text-sm text-yellow-700">
                                    Diese Halle hat {{ gymHall.time_slots_count }} aktive Zeitfenster. 
                                    Zum Löschen der Halle müssen alle Zeitfenster zuerst entfernt werden.
                                    <button 
                                        @click="activeTab = 'schedule'" 
                                        class="ml-2 font-medium underline hover:no-underline"
                                    >
                                        Zu den Öffnungszeiten →
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Messages -->
                    <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Fehler beim Speichern:</h3>
                                <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                    <li v-for="error in errors" :key="error">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    </div>
                </form>
            </div>

            <!-- Schedule Tab -->
            <div v-if="activeTab === 'schedule' && gymHall">
                <GymTimeSlotManager
                    :gym-hall-id="gymHall.id"
                    :initial-time-slots="hallTimeSlots"
                    :default-open-time="gymHall.opening_time || '08:00'"
                    :default-close-time="gymHall.closing_time || '22:00'"
                    @updated="onTimeSlotsUpdated"
                    @error="onTimeSlotsError"
                />
            </div>

            <!-- Courts Tab -->
            <div v-if="activeTab === 'courts' && gymHall">
                <div class="space-y-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">Platz-Konfiguration</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-blue-700 font-medium">Hallentyp:</span>
                                <span class="ml-2">{{ getHallTypeDisplay(gymHall.hall_type) }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Anzahl Plätze:</span>
                                <span class="ml-2">{{ gymHall.court_count || 1 }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Parallel-Buchungen:</span>
                                <span class="ml-2">{{ gymHall.supports_parallel_bookings ? 'Aktiviert' : 'Deaktiviert' }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Buchungsintervall:</span>
                                <span class="ml-2">{{ gymHall.booking_increment_minutes || 30 }} Min.</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="gymHall.courts && gymHall.courts.length > 0">
                        <h4 class="font-medium text-gray-900 mb-4">Plätze</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="court in gymHall.courts" 
                                :key="court.id"
                                class="border border-gray-200 rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <div 
                                            class="w-4 h-4 rounded-full"
                                            :style="{ backgroundColor: court.color_code }"
                                        ></div>
                                        <span class="font-medium">{{ court.court_name }}</span>
                                    </div>
                                    <span 
                                        :class="[
                                            'px-2 py-1 text-xs font-semibold rounded-full',
                                            court.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        {{ court.is_active ? 'Aktiv' : 'Inaktiv' }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <p>Bezeichner: {{ court.court_identifier }}</p>
                                    <p v-if="court.max_capacity">Kapazität: {{ court.max_capacity }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <div class="text-gray-400 mb-4">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Plätze konfiguriert</h3>
                        <p class="text-gray-600 mb-4">Initialisieren Sie Standard-Plätze für diese Halle.</p>
                        <button
                            @click="initializeCourts"
                            :disabled="initializingCourts"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50"
                        >
                            {{ initializingCourts ? 'Initialisiere...' : 'Standard-Plätze erstellen' }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between">
                <div>
                    <button
                        v-if="gymHall && activeTab === 'details'"
                        @click="deleteGymHall"
                        type="button"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
                        :disabled="submitting"
                        :title="gymHall.time_slots_count > 0 ? `${gymHall.time_slots_count} aktive Zeitfenster müssen zuerst entfernt werden` : 'Sporthalle löschen'"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Löschen
                        <span v-if="gymHall.time_slots_count > 0" class="ml-1 px-1.5 py-0.5 text-xs bg-red-500 rounded-full">
                            {{ gymHall.time_slots_count }}
                        </span>
                    </button>
                </div>
                <div class="flex space-x-3">
                    <button
                        @click="$emit('close')"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md"
                        :disabled="submitting"
                    >
                        {{ activeTab === 'schedule' ? 'Schließen' : 'Abbrechen' }}
                    </button>
                    <button
                        v-if="activeTab === 'details'"
                        @click="submitForm"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                        :disabled="submitting"
                    >
                        {{ submitting ? 'Speichere...' : (gymHall ? 'Aktualisieren' : 'Erstellen') }}
                    </button>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import GymTimeSlotManager from '@/Components/Gym/GymTimeSlotManager.vue'

const props = defineProps({
    show: Boolean,
    gymHall: Object,
    currentClub: Object,
    availableClubs: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'updated'])

const page = usePage()

// Form data
const form = ref({
    club_id: null,
    name: '',
    description: '',
    address_street: '',
    address_city: '',
    address_zip: '',
    address_country: 'Deutschland',
    capacity: null,
    hall_type: 'single',
    court_count: 1,
    supports_parallel_bookings: false,
    min_booking_duration_minutes: 30,
    booking_increment_minutes: 30,
    opening_time: '',
    closing_time: '',
    hourly_rate: null,
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    is_active: true,
    requires_key: false,
    special_rules: ''
})

// Checkbox states for facilities and equipment
const facilitiesCheckboxes = ref({
    changing_rooms: false,
    showers: false,
    parking: false,
    wheelchair_accessible: false
})

const equipmentCheckboxes = ref({
    basketballs: false,
    scoreboard: false,
    shot_clock: false,
    sound_system: false
})

const submitting = ref(false)
const errors = ref([])
const activeTab = ref('details')
const hallTimeSlots = ref([])
const initializingCourts = ref(false)
const selectedClubId = ref(null)

// Check if user is Admin/Superadmin and should see club selector
const showClubSelector = computed(() => {
    const user = page.props.auth?.user
    // Check if user has admin or super_admin role
    const hasAdminRole = user?.roles?.some(r => ['admin', 'super_admin'].includes(r.name))
    // Show selector if admin AND (creating new hall OR editing and has available clubs)
    return hasAdminRole && props.availableClubs.length > 0
})

// Computed
const facilitiesArray = computed(() => {
    return Object.keys(facilitiesCheckboxes.value).filter(key => facilitiesCheckboxes.value[key])
})

const equipmentArray = computed(() => {
    return Object.keys(equipmentCheckboxes.value).filter(key => equipmentCheckboxes.value[key])
})

// Methods
const submitForm = async () => {
    if (!validateForm()) return
    
    submitting.value = true
    errors.value = []
    
    try {
        // Validate gym hall ID for updates
        if (props.gymHall && (!props.gymHall.id || props.gymHall.id === undefined)) {
            errors.value = ['Sporthalle ID fehlt oder ist ungültig. Bitte versuchen Sie es erneut.']
            return
        }
        
        const url = props.gymHall 
            ? `/api/v2/gym-halls/${props.gymHall.id}`
            : '/api/v2/gym-halls'
        
        const method = props.gymHall ? 'PUT' : 'POST'
        
        // Get club_id from multiple possible sources
        let clubId = null

        // 0. If admin/superadmin with club selector, use selected club
        if (showClubSelector.value && selectedClubId.value) {
            clubId = selectedClubId.value
        }
        // 1. If editing existing hall, use its club_id
        else if (props.gymHall?.club_id) {
            clubId = props.gymHall.club_id
        }
        // 2. Try current club from props (if passed)
        else if (props.currentClub?.id) {
            clubId = props.currentClub.id
        }
        // 3. Try auth user's current team club_id
        else if (page.props.auth?.user?.current_team?.club_id) {
            clubId = page.props.auth.user.current_team.club_id
        }
        // 4. Try alternative user structure
        else if (page.props.user?.current_team?.club_id) {
            clubId = page.props.user.current_team.club_id
        }

        // Special error message for admins who haven't selected a club
        if (!clubId && showClubSelector.value) {
            errors.value = ['Bitte wählen Sie einen Verein aus der Liste aus.']
            return
        }

        if (!clubId) {
            errors.value = ['Vereins-ID konnte nicht ermittelt werden. Bitte wenden Sie sich an den Administrator.']
            return
        }

        const data = {
            ...form.value,
            club_id: clubId,
            facilities: facilitiesArray.value,
            equipment: equipmentArray.value
        }
        
        // Use axios instead of fetch for better CSRF token handling
        const response = await window.axios({
            method: method,
            url: url,
            data: data,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            withCredentials: true
        })
        
        if (response.status >= 200 && response.status < 300) {
            emit('updated')
            emit('close')
            resetForm()
        } else {
            errors.value = Object.values(response.data.errors || {}).flat()
        }
    } catch (error) {
        console.error('Error saving gym hall:', error)
        if (error.response?.data?.errors) {
            errors.value = Object.values(error.response.data.errors).flat()
        } else if (error.response?.data?.message) {
            errors.value = [error.response.data.message]
        } else {
            errors.value = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']
        }
    } finally {
        submitting.value = false
    }
}

const deleteGymHall = async () => {
    if (!props.gymHall || !props.gymHall.id) return
    
    const confirmed = confirm('Möchten Sie diese Sporthalle wirklich löschen? Alle zugehörigen Zeitfenster und Buchungen werden ebenfalls gelöscht.')
    if (!confirmed) return
    
    try {
        const response = await window.axios.delete(`/api/v2/gym-halls/${props.gymHall.id}`, {
            headers: {
                'Accept': 'application/json'
            },
            withCredentials: true
        })
        
        if (response.status >= 200 && response.status < 300) {
            emit('updated')
            emit('close')
        } else {
            errors.value = [response.data.message || 'Fehler beim Löschen']
        }
    } catch (error) {
        console.error('Error deleting gym hall:', error)
        
        if (error.response?.status === 422) {
            // Handle constraint violations with specific guidance
            const message = error.response.data.message || 'Sporthalle kann nicht gelöscht werden'
            
            // Show detailed error dialog with options
            const deleteAnyway = confirm(
                `${message}\n\n` +
                `Aktuelle Zeitfenster müssen zuerst entfernt werden.\n\n` +
                `Möchten Sie zur Zeitfenster-Verwaltung wechseln?\n\n` +
                `(Klicken Sie "OK" um zu den Öffnungszeiten zu wechseln, oder "Abbrechen" um zu schließen)`
            )
            
            if (deleteAnyway) {
                // Switch to schedule tab to manage time slots
                activeTab.value = 'schedule'
                errors.value = [
                    'Bitte entfernen Sie zuerst alle Zeitfenster, bevor Sie die Halle löschen.',
                    'Sie befinden sich jetzt im Öffnungszeiten-Tab.'
                ]
            } else {
                errors.value = [message]
            }
        } else if (error.response?.data?.message) {
            errors.value = [error.response.data.message]
        } else {
            errors.value = ['Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']
        }
        
        // Always switch back to details tab to show errors
        if (activeTab.value !== 'schedule') {
            activeTab.value = 'details'
        }
    }
}

const validateForm = () => {
    errors.value = []
    
    if (!form.value.name?.trim()) {
        errors.value.push('Name ist erforderlich')
    }
    
    if (form.value.closing_time && form.value.opening_time && form.value.closing_time <= form.value.opening_time) {
        errors.value.push('Schließzeit muss nach der Öffnungszeit liegen')
    }
    
    if (form.value.contact_email && !isValidEmail(form.value.contact_email)) {
        errors.value.push('Ungültige E-Mail-Adresse')
    }
    
    if (form.value.court_count < 1 || form.value.court_count > 10) {
        errors.value.push('Anzahl Plätze muss zwischen 1 und 10 liegen')
    }
    
    if (form.value.min_booking_duration_minutes < 15 || form.value.min_booking_duration_minutes > 480) {
        errors.value.push('Mindestbuchungsdauer muss zwischen 15 und 480 Minuten liegen')
    }
    
    if (![15, 30, 60].includes(form.value.booking_increment_minutes)) {
        errors.value.push('Buchungsintervall muss 15, 30 oder 60 Minuten sein')
    }
    
    return errors.value.length === 0
}

const isValidEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
}

const loadHallTimeSlots = async (hallId) => {
    if (!hallId) return
    
    try {
        const response = await window.axios.get(`/api/v2/gym-halls/${hallId}/time-slots`, {
            withCredentials: true
        })
        if (response.data.success) {
            hallTimeSlots.value = response.data.data
        }
    } catch (error) {
        console.error('Error loading hall time slots:', error)
    }
}

const onTimeSlotsUpdated = (updatedSlots) => {
    hallTimeSlots.value = updatedSlots
    emit('updated')
}

const onTimeSlotsError = (error) => {
    console.error('Time slots error:', error)
}

const initializeCourts = async () => {
    if (!props.gymHall?.id) return
    
    initializingCourts.value = true
    
    try {
        const response = await window.axios.post(`/api/v2/gym-halls/${props.gymHall.id}/initialize-courts`, {}, {
            headers: {
                'Accept': 'application/json'
            },
            withCredentials: true
        })
        
        if (response.data.success) {
            // Reload the gym hall data to show the courts
            emit('updated')
        }
    } catch (error) {
        console.error('Error initializing courts:', error)
        errors.value = [error.response?.data?.message || 'Fehler beim Initialisieren der Plätze']
    } finally {
        initializingCourts.value = false
    }
}

const getHallTypeDisplay = (hallType) => {
    const types = {
        'single': 'Einfachhalle',
        'double': 'Doppelhalle', 
        'triple': 'Dreifachhalle',
        'multi': 'Mehrfachhalle'
    }
    return types[hallType] || hallType
}

const resetForm = () => {
    form.value = {
        club_id: null,
        name: '',
        description: '',
        address_street: '',
        address_city: '',
        address_zip: '',
        address_country: 'Deutschland',
        capacity: null,
        hall_type: 'single',
        court_count: 1,
        supports_parallel_bookings: false,
        min_booking_duration_minutes: 30,
        booking_increment_minutes: 30,
        opening_time: '',
        closing_time: '',
        hourly_rate: null,
        contact_name: '',
        contact_phone: '',
        contact_email: '',
        is_active: true,
        requires_key: false,
        special_rules: ''
    }
    
    facilitiesCheckboxes.value = {
        changing_rooms: false,
        showers: false,
        parking: false,
        wheelchair_accessible: false
    }
    
    equipmentCheckboxes.value = {
        basketballs: false,
        scoreboard: false,
        shot_clock: false,
        sound_system: false
    }
    
    errors.value = []
    activeTab.value = 'details'
    hallTimeSlots.value = []
    selectedClubId.value = null
}

// Watch for gymHall changes to populate form
watch(() => props.gymHall, (newGymHall) => {
    if (newGymHall) {
        form.value = {
            club_id: newGymHall.club_id,
            name: newGymHall.name || '',
            description: newGymHall.description || '',
            address_street: newGymHall.address_street || '',
            address_city: newGymHall.address_city || '',
            address_zip: newGymHall.address_zip || '',
            address_country: newGymHall.address_country || 'Deutschland',
            capacity: newGymHall.capacity,
            hall_type: newGymHall.hall_type || 'single',
            court_count: newGymHall.court_count || 1,
            supports_parallel_bookings: newGymHall.supports_parallel_bookings ?? false,
            min_booking_duration_minutes: newGymHall.min_booking_duration_minutes || 30,
            booking_increment_minutes: newGymHall.booking_increment_minutes || 30,
            opening_time: newGymHall.opening_time || '',
            closing_time: newGymHall.closing_time || '',
            hourly_rate: newGymHall.hourly_rate,
            contact_name: newGymHall.contact_name || '',
            contact_phone: newGymHall.contact_phone || '',
            contact_email: newGymHall.contact_email || '',
            is_active: newGymHall.is_active ?? true,
            requires_key: newGymHall.requires_key ?? false,
            special_rules: newGymHall.special_rules || ''
        }
        
        // Set facilities checkboxes
        const facilities = newGymHall.facilities || []
        facilitiesCheckboxes.value = {
            changing_rooms: facilities.includes('changing_rooms'),
            showers: facilities.includes('showers'),
            parking: facilities.includes('parking'),
            wheelchair_accessible: facilities.includes('wheelchair_accessible')
        }
        
        // Set equipment checkboxes
        const equipment = newGymHall.equipment || []
        equipmentCheckboxes.value = {
            basketballs: equipment.includes('basketballs'),
            scoreboard: equipment.includes('scoreboard'),
            shot_clock: equipment.includes('shot_clock'),
            sound_system: equipment.includes('sound_system')
        }

        // Set selected club for admin editing
        if (newGymHall.club_id) {
            selectedClubId.value = newGymHall.club_id
        }
        
        // Load time slots for existing hall
        if (newGymHall.id) {
            loadHallTimeSlots(newGymHall.id)
        }
    } else {
        resetForm()
    }
}, { immediate: true })

// Watch for show prop to reset form when modal opens
watch(() => props.show, (show) => {
    if (show && !props.gymHall) {
        resetForm()
    }
})
</script>