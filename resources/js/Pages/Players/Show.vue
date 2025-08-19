<template>
    <AppLayout :title="player.user?.name || `Spieler #${player.jersey_number}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('web.players.index')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ player.user?.name || `Spieler #${player.jersey_number}` }}
                    </h2>
                    <!-- Status Badges in Header -->
                    <div class="flex space-x-2">
                        <span
                            v-if="player.medical_clearance_expired"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full"
                        >
                            Medical Clearance abgelaufen
                        </span>
                        <span
                            v-if="player.insurance_expired"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full"
                        >
                            Versicherung abgelaufen
                        </span>
                        <span
                            v-if="!player.academic_eligibility"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full"
                        >
                            Schulische Berechtigung fehlt
                        </span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <Link
                        v-if="can.update"
                        :href="route('players.edit', player.id)"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                    >
                        Bearbeiten
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Tab Navigation -->
                <div class="bg-white shadow-sm rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                                    activeTab === tab.id
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                                class="flex items-center space-x-2"
                            >
                                <component :is="tab.icon" class="h-5 w-5" />
                                <span>{{ tab.name }}</span>
                                <span
                                    v-if="tab.badge"
                                    class="ml-2 bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full"
                                >
                                    {{ tab.badge }}
                                </span>
                            </button>
                        </nav>
                    </div>
                </div>
                <!-- Tab Content -->
                <div v-show="activeTab === 'overview'">
                    <!-- Player Basic Info Card -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center space-x-6">
                                <!-- Jersey Number -->
                                <div class="flex-shrink-0">
                                    <div class="w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-800 font-bold text-3xl">
                                            {{ player.jersey_number || '?' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Basic Info -->
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                        {{ player.user?.name || 'Unbekannter Spieler' }}
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span class="font-medium">Position:</span> 
                                            {{ player.primary_position || 'Nicht angegeben' }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Team:</span> 
                                            {{ player.team?.name || 'Kein Team' }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Verein:</span> 
                                            {{ player.team?.club?.name || 'Kein Verein' }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Status:</span>
                                            <span
                                                :class="{
                                                    'text-green-600': player.status === 'active',
                                                    'text-red-600': player.status === 'injured',
                                                    'text-gray-600': player.status === 'inactive',
                                                    'text-yellow-600': player.status === 'suspended'
                                                }"
                                                class="font-medium"
                                            >
                                                {{ getStatusText(player.status) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Badges -->
                                    <div class="flex space-x-2 mt-4">
                                        <span
                                            v-if="player.is_captain"
                                            class="inline-flex items-center px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full"
                                        >
                                            Kapitän
                                        </span>
                                        <span
                                            v-if="player.is_starter"
                                            class="inline-flex items-center px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded-full"
                                        >
                                            Starter
                                        </span>
                                        <span
                                            v-if="player.is_rookie"
                                            class="inline-flex items-center px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded-full"
                                        >
                                            Rookie
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Info & Stats Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <!-- Physical Information -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                            <div class="p-6">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                    Physische Daten
                                </h4>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Größe:</span>
                                        <span class="font-medium">
                                            {{ player.height_cm ? `${player.height_cm} cm` : 'Nicht angegeben' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Gewicht:</span>
                                        <span class="font-medium">
                                            {{ player.weight_kg ? `${player.weight_kg} kg` : 'Nicht angegeben' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Dominante Hand:</span>
                                        <span class="font-medium">
                                            {{ getDominantHandText(player.dominant_hand) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Erfahrung:</span>
                                        <span class="font-medium">
                                            {{ player.years_experience ? `${player.years_experience} Jahre` : 'Nicht angegeben' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Spielbeginn:</span>
                                        <span class="font-medium">
                                            {{ player.started_playing ? formatDate(player.started_playing) : 'Nicht angegeben' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Schuhgröße:</span>
                                        <span class="font-medium">
                                            {{ player.shoe_size || 'Nicht angegeben' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Statistics -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                            <div class="p-6">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                    Schnell-Statistiken
                                </h4>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Spiele:</span>
                                        <span class="font-medium">{{ player.games_played || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Punkte:</span>
                                        <span class="font-medium">{{ player.points_scored || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Assists:</span>
                                        <span class="font-medium">{{ player.assists || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Rebounds:</span>
                                        <span class="font-medium">{{ player.rebounds_total || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">PPG:</span>
                                        <span class="font-medium">{{ player.points_per_game || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Overall Rating:</span>
                                        <span class="font-medium text-indigo-600">{{ player.overall_rating || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Positions and Secondary Info -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Positionen & Rollen
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <h5 class="font-medium text-gray-900 mb-2">Primäre Position</h5>
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium bg-indigo-100 text-indigo-800 rounded-full">
                                        {{ player.primary_position || 'Nicht angegeben' }}
                                    </span>
                                </div>
                                <div v-if="player.secondary_positions && player.secondary_positions.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-2">Sekundäre Positionen</h5>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="position in player.secondary_positions"
                                            :key="position"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full"
                                        >
                                            {{ position }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="font-medium text-gray-900 mb-2">Vertragsinformationen</h5>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <div v-if="player.contract_start">
                                            Vertragsbeginn: {{ formatDate(player.contract_start) }}
                                        </div>
                                        <div v-if="player.contract_end">
                                            Vertragsende: {{ formatDate(player.contract_end) }}
                                        </div>
                                        <div v-if="player.registration_number">
                                            Reg.-Nr.: {{ player.registration_number }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Tab -->
                <div v-show="activeTab === 'statistics'">
                    <!-- Season Stats Overview -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Saison-Statistiken {{ player.team?.season || '2024-25' }}
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-900">{{ player.games_played || 0 }}</div>
                                    <div class="text-sm text-gray-600">Spiele</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-900">{{ player.minutes_played || 0 }}</div>
                                    <div class="text-sm text-gray-600">Minuten</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-900">{{ player.points_per_game || 0 }}</div>
                                    <div class="text-sm text-gray-600">PPG</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-900">{{ player.field_goal_percentage || 0 }}%</div>
                                    <div class="text-sm text-gray-600">FG%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Stats -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <!-- Scoring Stats -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                            <div class="p-6">
                                <h5 class="text-lg font-semibold text-gray-900 mb-4">Scoring</h5>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Punkte gesamt:</span>
                                        <span class="font-medium">{{ player.points_scored || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Feldwürfe:</span>
                                        <span class="font-medium">{{ player.field_goals_made || 0 }}/{{ player.field_goals_attempted || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">3-Punkte:</span>
                                        <span class="font-medium">{{ player.three_pointers_made || 0 }}/{{ player.three_pointers_attempted || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Freiwürfe:</span>
                                        <span class="font-medium">{{ player.free_throws_made || 0 }}/{{ player.free_throws_attempted || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">3P%:</span>
                                        <span class="font-medium">{{ player.three_point_percentage || 0 }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">FT%:</span>
                                        <span class="font-medium">{{ player.free_throw_percentage || 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Stats -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                            <div class="p-6">
                                <h5 class="text-lg font-semibold text-gray-900 mb-4">Weitere Statistiken</h5>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Rebounds gesamt:</span>
                                        <span class="font-medium">{{ player.rebounds_total || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Offensive Rebounds:</span>
                                        <span class="font-medium">{{ player.rebounds_offensive || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Defensive Rebounds:</span>
                                        <span class="font-medium">{{ player.rebounds_defensive || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Assists:</span>
                                        <span class="font-medium">{{ player.assists || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Steals:</span>
                                        <span class="font-medium">{{ player.steals || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Blocks:</span>
                                        <span class="font-medium">{{ player.blocks || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Turnovers:</span>
                                        <span class="font-medium">{{ player.turnovers || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Fouls:</span>
                                        <span class="font-medium">{{ player.fouls_personal || 0 }} ({{ player.fouls_technical || 0 }} Tech)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Player Ratings -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6" v-if="hasRatings">
                        <div class="p-6">
                            <h5 class="text-lg font-semibold text-gray-900 mb-4">
                                Spieler-Bewertungen
                            </h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-indigo-600">
                                        {{ player.overall_rating || '-' }}
                                    </div>
                                    <div class="text-sm text-gray-600">Overall</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600">
                                        {{ player.shooting_rating || '-' }}
                                    </div>
                                    <div class="text-sm text-gray-600">Wurf</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600">
                                        {{ player.defense_rating || '-' }}
                                    </div>
                                    <div class="text-sm text-gray-600">Verteidigung</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600">
                                        {{ player.passing_rating || '-' }}
                                    </div>
                                    <div class="text-sm text-gray-600">Pass</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600">
                                        {{ player.rebounding_rating || '-' }}
                                    </div>
                                    <div class="text-sm text-gray-600">Rebound</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600">
                                        {{ player.speed_rating || '-' }}
                                    </div>
                                    <div class="text-sm text-gray-600">Schnelligkeit</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medical Tab -->
                <div v-show="activeTab === 'medical'">
                    <!-- Medical Clearance Status -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900">
                                    Medizinischer Status
                                </h4>
                                <div class="flex items-center space-x-2">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800': player.medical_clearance && !player.medical_clearance_expired,
                                            'bg-red-100 text-red-800': !player.medical_clearance || player.medical_clearance_expired
                                        }"
                                        class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full"
                                    >
                                        {{ player.medical_clearance && !player.medical_clearance_expired ? 'Freigegeben' : 'Nicht freigegeben' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="font-medium text-gray-900 mb-3">Medical Clearance</h5>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Status:</span>
                                            <span class="font-medium">
                                                {{ player.medical_clearance ? 'Erteilt' : 'Ausstehend' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between" v-if="player.medical_clearance_expires">
                                            <span class="text-gray-600">Gültig bis:</span>
                                            <span class="font-medium">{{ formatDate(player.medical_clearance_expires) }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="player.last_medical_check">
                                            <span class="text-gray-600">Letzte Untersuchung:</span>
                                            <span class="font-medium">{{ formatDate(player.last_medical_check) }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="player.blood_type">
                                            <span class="text-gray-600">Blutgruppe:</span>
                                            <span class="font-medium">{{ player.blood_type }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="font-medium text-gray-900 mb-3">Versicherung</h5>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between" v-if="player.insurance_provider">
                                            <span class="text-gray-600">Anbieter:</span>
                                            <span class="font-medium">{{ player.insurance_provider }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="player.insurance_policy_number">
                                            <span class="text-gray-600">Policennummer:</span>
                                            <span class="font-medium">{{ player.insurance_policy_number }}</span>
                                        </div>
                                        <div class="flex justify-between" v-if="player.insurance_expires">
                                            <span class="text-gray-600">Gültig bis:</span>
                                            <span :class="{ 'text-red-600': player.insurance_expired }" class="font-medium">
                                                {{ formatDate(player.insurance_expires) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between" v-if="player.preferred_hospital">
                                            <span class="text-gray-600">Bevorzugtes Krankenhaus:</span>
                                            <span class="font-medium">{{ player.preferred_hospital }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Conditions -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6" v-if="player.medical_conditions || player.allergies || player.medications">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Medizinische Informationen
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div v-if="player.medical_conditions && player.medical_conditions.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-2">Medizinische Bedingungen</h5>
                                    <ul class="text-sm text-gray-600 space-y-1">
                                        <li v-for="condition in player.medical_conditions" :key="condition">
                                            • {{ condition }}
                                        </li>
                                    </ul>
                                </div>
                                <div v-if="player.allergies && player.allergies.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-2">Allergien</h5>
                                    <ul class="text-sm text-gray-600 space-y-1">
                                        <li v-for="allergy in player.allergies" :key="allergy">
                                            • {{ allergy }}
                                        </li>
                                    </ul>
                                </div>
                                <div v-if="player.medications && player.medications.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-2">Medikamente</h5>
                                    <ul class="text-sm text-gray-600 space-y-1">
                                        <li v-for="medication in player.medications" :key="medication">
                                            • {{ medication }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div v-if="player.medical_notes" class="mt-4 pt-4 border-t border-gray-200">
                                <h5 class="font-medium text-gray-900 mb-2">Medizinische Notizen</h5>
                                <p class="text-sm text-gray-600">{{ player.medical_notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contacts -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Notfallkontakte
                            </h4>
                            <div class="space-y-4">
                                <div v-if="player.emergency_medical_contact" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <h5 class="font-medium text-red-900 mb-2">Primärer Notfallkontakt</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-red-700">Name:</span>
                                            <span class="font-medium ml-2">{{ player.emergency_medical_contact }}</span>
                                        </div>
                                        <div v-if="player.emergency_medical_phone">
                                            <span class="text-red-700">Telefon:</span>
                                            <span class="font-medium ml-2">{{ player.emergency_medical_phone }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="player.parent" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <h5 class="font-medium text-blue-900 mb-2">Erziehungsberechtigter</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-blue-700">Name:</span>
                                            <span class="font-medium ml-2">{{ player.parent.name }}</span>
                                        </div>
                                        <div v-if="player.parent.phone">
                                            <span class="text-blue-700">Telefon:</span>
                                            <span class="font-medium ml-2">{{ player.parent.phone }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="player.guardian_contacts && player.guardian_contacts.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-2">Weitere Kontakte</h5>
                                    <div class="space-y-2">
                                        <div 
                                            v-for="(contact, index) in player.guardian_contacts" 
                                            :key="index"
                                            class="p-3 bg-gray-50 border border-gray-200 rounded-md"
                                        >
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                                                <div>
                                                    <span class="text-gray-600">Name:</span>
                                                    <span class="font-medium ml-2">{{ contact.name }}</span>
                                                </div>
                                                <div v-if="contact.phone">
                                                    <span class="text-gray-600">Telefon:</span>
                                                    <span class="font-medium ml-2">{{ contact.phone }}</span>
                                                </div>
                                                <div v-if="contact.relationship">
                                                    <span class="text-gray-600">Beziehung:</span>
                                                    <span class="font-medium ml-2">{{ contact.relationship }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Development Tab -->
                <div v-show="activeTab === 'development'">
                    <!-- Development Goals -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Entwicklungsziele & Training
                            </h4>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="font-medium text-gray-900 mb-3">Trainingsfokusbereiche</h5>
                                    <div v-if="player.training_focus_areas && player.training_focus_areas.length > 0" class="space-y-2">
                                        <div 
                                            v-for="area in player.training_focus_areas"
                                            :key="area"
                                            class="inline-flex items-center px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full mr-2 mb-2"
                                        >
                                            {{ area }}
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 italic">
                                        Keine Trainingsfokusbereiche definiert
                                    </div>
                                </div>

                                <div>
                                    <h5 class="font-medium text-gray-900 mb-3">Entwicklungsziele</h5>
                                    <div v-if="player.development_goals && player.development_goals.length > 0" class="space-y-2">
                                        <ul class="text-sm text-gray-600 space-y-1">
                                            <li v-for="goal in player.development_goals" :key="goal" class="flex items-start">
                                                <span class="text-green-500 mr-2">•</span>
                                                {{ goal }}
                                            </li>
                                        </ul>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 italic">
                                        Keine Entwicklungsziele definiert
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coach Notes -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Trainer-Notizen
                            </h4>
                            <div v-if="player.coach_notes" class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ player.coach_notes }}</p>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                Keine Trainer-Notizen vorhanden
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information (for young players) -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6" v-if="player.school_name || player.grade_level || player.gpa">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Schulische Informationen
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                                <div v-if="player.school_name">
                                    <span class="text-gray-600">Schule:</span>
                                    <div class="font-medium mt-1">{{ player.school_name }}</div>
                                </div>
                                <div v-if="player.grade_level">
                                    <span class="text-gray-600">Klassenstufe:</span>
                                    <div class="font-medium mt-1">{{ player.grade_level }}</div>
                                </div>
                                <div v-if="player.gpa">
                                    <span class="text-gray-600">Notendurchschnitt:</span>
                                    <div class="font-medium mt-1">{{ player.gpa }}</div>
                                </div>
                            </div>
                            <div class="mt-4 p-3 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-600">Schulische Berechtigung:</span>
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800': player.academic_eligibility,
                                            'bg-red-100 text-red-800': !player.academic_eligibility
                                        }"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ player.academic_eligibility ? 'Berechtigt' : 'Nicht berechtigt' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Teams & Achievements -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6" v-if="player.previous_teams || player.achievements">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Karriere-Informationen
                            </h4>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div v-if="player.previous_teams && player.previous_teams.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-3">Vorherige Teams</h5>
                                    <ul class="text-sm text-gray-600 space-y-2">
                                        <li v-for="team in player.previous_teams" :key="team" class="flex items-start">
                                            <span class="text-indigo-500 mr-2">•</span>
                                            {{ team }}
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="player.achievements && player.achievements.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-3">Erfolge & Auszeichnungen</h5>
                                    <ul class="text-sm text-gray-600 space-y-2">
                                        <li v-for="achievement in player.achievements" :key="achievement" class="flex items-start">
                                            <span class="text-yellow-500 mr-2">🏆</span>
                                            {{ achievement }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Tab -->
                <div v-show="activeTab === 'media'">
                    <!-- Media Settings -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Medien-Einstellungen
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="p-4 border border-gray-200 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="font-medium text-gray-900">Foto-Erlaubnis</h5>
                                            <p class="text-sm text-gray-600">Erlaubnis für Fotos bei Spielen und Training</p>
                                        </div>
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': player.allow_photos,
                                                'bg-red-100 text-red-800': !player.allow_photos
                                            }"
                                            class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full"
                                        >
                                            {{ player.allow_photos ? 'Erlaubt' : 'Nicht erlaubt' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="p-4 border border-gray-200 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="font-medium text-gray-900">Medien-Interviews</h5>
                                            <p class="text-sm text-gray-600">Erlaubnis für Interviews und Medienauftritte</p>
                                        </div>
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': player.allow_media_interviews,
                                                'bg-red-100 text-red-800': !player.allow_media_interviews
                                            }"
                                            class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full"
                                        >
                                            {{ player.allow_media_interviews ? 'Erlaubt' : 'Nicht erlaubt' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6" v-if="player.social_media">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Social Media Profile
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div 
                                    v-for="(handle, platform) in player.social_media" 
                                    :key="platform"
                                    class="p-3 border border-gray-200 rounded-lg"
                                >
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium text-gray-900 capitalize">{{ platform }}:</span>
                                        <span class="text-sm text-gray-600">{{ handle }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Gallery Placeholder -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mt-6">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Fotos & Dokumente
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <!-- Placeholder for future media integration -->
                                <div class="aspect-square bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center">
                                    <div class="text-center">
                                        <svg class="h-8 w-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-xs text-gray-500">Profilbild</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-sm text-gray-500 italic">
                                Medien-Integration wird in einer zukünftigen Version hinzugefügt
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div v-show="activeTab === 'preferences'">
                    <!-- Player Preferences -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Spieler-Einstellungen
                            </h4>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Dietary Restrictions -->
                                <div v-if="player.dietary_restrictions && player.dietary_restrictions.length > 0">
                                    <h5 class="font-medium text-gray-900 mb-3">Ernährungseinschränkungen</h5>
                                    <div class="space-y-2">
                                        <span 
                                            v-for="restriction in player.dietary_restrictions"
                                            :key="restriction"
                                            class="inline-flex items-center px-3 py-1 text-sm bg-orange-100 text-orange-800 rounded-full mr-2 mb-2"
                                        >
                                            {{ restriction }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Preferences -->
                                <div v-if="player.preferences">
                                    <h5 class="font-medium text-gray-900 mb-3">Allgemeine Präferenzen</h5>
                                    <div class="space-y-2 text-sm">
                                        <div v-for="(value, key) in player.preferences" :key="key" class="flex justify-between">
                                            <span class="text-gray-600 capitalize">{{ key.replace('_', ' ') }}:</span>
                                            <span class="font-medium">{{ value }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration & Administrative -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h5 class="font-medium text-gray-900 mb-3">Registrierung & Verwaltung</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Registriert:</span>
                                        <span
                                            :class="{
                                                'text-green-600': player.is_registered,
                                                'text-red-600': !player.is_registered
                                            }"
                                            class="font-medium"
                                        >
                                            {{ player.is_registered ? 'Ja' : 'Nein' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between" v-if="player.registered_at">
                                        <span class="text-gray-600">Registriert am:</span>
                                        <span class="font-medium">{{ formatDate(player.registered_at) }}</span>
                                    </div>
                                    <div class="flex justify-between" v-if="player.registration_number">
                                        <span class="text-gray-600">Registrierungsnummer:</span>
                                        <span class="font-medium">{{ player.registration_number }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    player: Object,
    statistics: Object,
    can: Object,
})

// Tab Management
const activeTab = ref('overview')

// Tab Configuration with Heroicons
const tabs = computed(() => {
    const medicalAlerts = (props.player.medical_clearance_expired ? 1 : 0) + 
                         (props.player.insurance_expired ? 1 : 0)
    
    return [
        {
            id: 'overview',
            name: 'Übersicht',
            icon: 'UserIcon'
        },
        {
            id: 'statistics',
            name: 'Statistiken',
            icon: 'ChartBarIcon'
        },
        {
            id: 'medical',
            name: 'Medizinisch',
            icon: 'HeartIcon',
            badge: medicalAlerts > 0 ? medicalAlerts : null
        },
        {
            id: 'development',
            name: 'Entwicklung',
            icon: 'AcademicCapIcon'
        },
        {
            id: 'media',
            name: 'Medien',
            icon: 'CameraIcon'
        },
        {
            id: 'preferences',
            name: 'Einstellungen',
            icon: 'CogIcon'
        }
    ]
})

const hasRatings = computed(() => {
    return props.player.shooting_rating || 
           props.player.defense_rating || 
           props.player.passing_rating || 
           props.player.rebounding_rating || 
           props.player.speed_rating
})

// Helper Functions
const getStatusText = (status) => {
    const statusTexts = {
        'active': 'Aktiv',
        'inactive': 'Inaktiv',
        'injured': 'Verletzt',
        'suspended': 'Gesperrt',
        'retired': 'Zurückgetreten'
    }
    return statusTexts[status] || status
}

const getDominantHandText = (hand) => {
    const handTexts = {
        'left': 'Links',
        'right': 'Rechts',
        'ambidextrous': 'Beidhändig'
    }
    return handTexts[hand] || 'Nicht angegeben'
}

const formatDate = (date) => {
    if (!date) return 'Nicht angegeben'
    return new Date(date).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

// Icon Components (we'll use simple SVG icons for now)
const UserIcon = {
    template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
    `
}

const ChartBarIcon = {
    template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
    `
}

const HeartIcon = {
    template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
    `
}

const AcademicCapIcon = {
    template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
        </svg>
    `
}

const CameraIcon = {
    template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    `
}

const CogIcon = {
    template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    `
}
</script>