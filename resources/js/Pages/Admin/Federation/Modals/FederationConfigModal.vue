<template>
  <DialogModal :show="show" @close="close" max-width="5xl">
    <template #title>
      Federation API Konfiguration
    </template>

    <template #content>
      <div class="space-y-6">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              @click="activeTab = 'dbb'"
              :class="[
                activeTab === 'dbb' 
                  ? 'border-indigo-500 text-indigo-600' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              DBB Konfiguration
            </button>
            <button
              @click="activeTab = 'fiba'"
              :class="[
                activeTab === 'fiba' 
                  ? 'border-indigo-500 text-indigo-600' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              FIBA Konfiguration
            </button>
            <button
              @click="activeTab = 'general'"
              :class="[
                activeTab === 'general' 
                  ? 'border-indigo-500 text-indigo-600' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              Allgemeine Einstellungen
            </button>
          </nav>
        </div>

        <!-- DBB Configuration -->
        <div v-if="activeTab === 'dbb'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">DBB API Einstellungen</h3>
            <div class="flex items-center space-x-2">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                dbbConfig.enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ dbbConfig.enabled ? 'Aktiviert' : 'Deaktiviert' }}
              </span>
              <SecondaryButton @click="testDbbConnection" :disabled="testingDbb" size="sm">
                <svg v-if="testingDbb" class="animate-spin -ml-1 mr-2 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Verbindung testen
              </SecondaryButton>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Connection Settings -->
            <div class="space-y-4">
              <h4 class="font-medium text-gray-900">Verbindungseinstellungen</h4>
              
              <div>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.enabled"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">DBB API aktivieren</span>
                </label>
              </div>

              <div>
                <InputLabel for="dbb_api_url" value="API Base URL" />
                <TextInput
                  id="dbb_api_url"
                  v-model="dbbConfig.apiUrl"
                  type="url"
                  class="mt-1 block w-full"
                  placeholder="https://api.basketball-bund.de"
                />
              </div>

              <div>
                <InputLabel for="dbb_api_key" value="API Key" />
                <div class="relative">
                  <TextInput
                    id="dbb_api_key"
                    v-model="dbbConfig.apiKey"
                    :type="showDbbApiKey ? 'text' : 'password'"
                    class="mt-1 block w-full pr-10"
                    placeholder="Ihr DBB API Schlüssel"
                  />
                  <button
                    type="button"
                    @click="showDbbApiKey = !showDbbApiKey"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path v-if="!showDbbApiKey" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path v-if="!showDbbApiKey" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                    </svg>
                  </button>
                </div>
              </div>

              <div>
                <InputLabel for="dbb_timeout" value="Timeout (Sekunden)" />
                <TextInput
                  id="dbb_timeout"
                  v-model="dbbConfig.timeout"
                  type="number"
                  min="5"
                  max="120"
                  class="mt-1 block w-full"
                />
              </div>
            </div>

            <!-- Rate Limiting & Caching -->
            <div class="space-y-4">
              <h4 class="font-medium text-gray-900">Rate Limiting & Caching</h4>

              <div>
                <InputLabel for="dbb_rate_limit" value="Requests pro Minute" />
                <TextInput
                  id="dbb_rate_limit"
                  v-model="dbbConfig.rateLimit"
                  type="number"
                  min="1"
                  max="1000"
                  class="mt-1 block w-full"
                />
              </div>

              <div>
                <InputLabel for="dbb_cache_ttl" value="Cache TTL (Minuten)" />
                <TextInput
                  id="dbb_cache_ttl"
                  v-model="dbbConfig.cacheTtl"
                  type="number"
                  min="1"
                  max="1440"
                  class="mt-1 block w-full"
                />
              </div>

              <div>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.enableRetries"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Automatische Wiederholung bei Fehlern</span>
                </label>
              </div>

              <div v-if="dbbConfig.enableRetries">
                <InputLabel for="dbb_max_retries" value="Maximale Wiederholungen" />
                <TextInput
                  id="dbb_max_retries"
                  v-model="dbbConfig.maxRetries"
                  type="number"
                  min="1"
                  max="5"
                  class="mt-1 block w-full"
                />
              </div>
            </div>
          </div>

          <!-- DBB Endpoints Configuration -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Endpoint Konfiguration</h4>
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.endpoints.players"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Spieler-API</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.endpoints.teams"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Team-API</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.endpoints.leagues"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Liga-API</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.endpoints.games"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Spiel-API</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.endpoints.referees"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Schiedsrichter-API</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="dbbConfig.endpoints.transfers"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Transfer-API</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- FIBA Configuration -->
        <div v-if="activeTab === 'fiba'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">FIBA API Einstellungen</h3>
            <div class="flex items-center space-x-2">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                fibaConfig.enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ fibaConfig.enabled ? 'Aktiviert' : 'Deaktiviert' }}
              </span>
              <SecondaryButton @click="testFibaConnection" :disabled="testingFiba" size="sm">
                <svg v-if="testingFiba" class="animate-spin -ml-1 mr-2 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Verbindung testen
              </SecondaryButton>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- FIBA Connection Settings -->
            <div class="space-y-4">
              <h4 class="font-medium text-gray-900">Verbindungseinstellungen</h4>
              
              <div>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="fibaConfig.enabled"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">FIBA API aktivieren</span>
                </label>
              </div>

              <div>
                <InputLabel for="fiba_api_url" value="API Base URL" />
                <TextInput
                  id="fiba_api_url"
                  v-model="fibaConfig.apiUrl"
                  type="url"
                  class="mt-1 block w-full"
                  placeholder="https://api.fiba.basketball"
                />
              </div>

              <div>
                <InputLabel for="fiba_client_id" value="Client ID" />
                <TextInput
                  id="fiba_client_id"
                  v-model="fibaConfig.clientId"
                  type="text"
                  class="mt-1 block w-full"
                  placeholder="Ihre FIBA Client ID"
                />
              </div>

              <div>
                <InputLabel for="fiba_client_secret" value="Client Secret" />
                <div class="relative">
                  <TextInput
                    id="fiba_client_secret"
                    v-model="fibaConfig.clientSecret"
                    :type="showFibaSecret ? 'text' : 'password'"
                    class="mt-1 block w-full pr-10"
                    placeholder="Ihr FIBA Client Secret"
                  />
                  <button
                    type="button"
                    @click="showFibaSecret = !showFibaSecret"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  >
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path v-if="!showFibaSecret" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path v-if="!showFibaSecret" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- FIBA OAuth & Settings -->
            <div class="space-y-4">
              <h4 class="font-medium text-gray-900">OAuth & Einstellungen</h4>

              <div>
                <InputLabel for="fiba_scope" value="OAuth Scope" />
                <TextInput
                  id="fiba_scope"
                  v-model="fibaConfig.scope"
                  type="text"
                  class="mt-1 block w-full"
                  placeholder="read:competitions read:players"
                />
              </div>

              <div>
                <InputLabel for="fiba_region" value="Region" />
                <select 
                  id="fiba_region" 
                  v-model="fibaConfig.region" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                >
                  <option value="europe">Europa</option>
                  <option value="americas">Amerika</option>
                  <option value="asia">Asien</option>
                  <option value="africa">Afrika</option>
                  <option value="oceania">Ozeanien</option>
                </select>
              </div>

              <div>
                <InputLabel for="fiba_competition_level" value="Wettkampf-Ebene" />
                <select 
                  id="fiba_competition_level" 
                  v-model="fibaConfig.competitionLevel" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                >
                  <option value="professional">Professionell</option>
                  <option value="amateur">Amateur</option>
                  <option value="youth">Jugend</option>
                  <option value="all">Alle</option>
                </select>
              </div>
            </div>
          </div>

          <!-- FIBA Endpoints -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900">FIBA Endpoint Konfiguration</h4>
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="fibaConfig.endpoints.competitions"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Wettkämpfe</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="fibaConfig.endpoints.players"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Spieler</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="fibaConfig.endpoints.clubs"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Vereine</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="fibaConfig.endpoints.standings"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Tabellen</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="fibaConfig.endpoints.officials"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Offizielle</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="fibaConfig.endpoints.statistics"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">Statistiken</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- General Settings -->
        <div v-if="activeTab === 'general'" class="space-y-6">
          <h3 class="text-lg font-medium text-gray-900">Allgemeine Einstellungen</h3>

          <!-- Sync Settings -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Automatische Synchronisation</h4>
            
            <div class="bg-gray-50 rounded-lg p-4 space-y-4">
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="generalConfig.autoSync.enabled"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Automatische Synchronisation aktivieren</span>
              </label>

              <div v-if="generalConfig.autoSync.enabled" class="grid grid-cols-2 gap-4">
                <div>
                  <InputLabel for="sync_interval" value="Synchronisations-Intervall" />
                  <select 
                    id="sync_interval" 
                    v-model="generalConfig.autoSync.interval" 
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                  >
                    <option value="hourly">Stündlich</option>
                    <option value="daily">Täglich</option>
                    <option value="weekly">Wöchentlich</option>
                  </select>
                </div>

                <div>
                  <InputLabel for="sync_time" value="Synchronisations-Zeit" />
                  <TextInput
                    id="sync_time"
                    v-model="generalConfig.autoSync.time"
                    type="time"
                    class="mt-1 block w-full"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Notification Settings -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Benachrichtigungen</h4>
            
            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="generalConfig.notifications.syncSuccess"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Bei erfolgreicher Synchronisation benachrichtigen</span>
              </label>

              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="generalConfig.notifications.syncFailure"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Bei Synchronisations-Fehlern benachrichtigen</span>
              </label>

              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="generalConfig.notifications.apiErrors"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Bei API-Fehlern benachrichtigen</span>
              </label>
            </div>

            <div>
              <InputLabel for="notification_email" value="E-Mail für Benachrichtigungen" />
              <TextInput
                id="notification_email"
                v-model="generalConfig.notifications.email"
                type="email"
                class="mt-1 block w-full"
                placeholder="admin@example.com"
              />
            </div>
          </div>

          <!-- Logging Settings -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Protokollierung</h4>
            
            <div class="bg-gray-50 rounded-lg p-4 space-y-4">
              <div>
                <InputLabel for="log_level" value="Log-Level" />
                <select 
                  id="log_level" 
                  v-model="generalConfig.logging.level" 
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                >
                  <option value="debug">Debug</option>
                  <option value="info">Info</option>
                  <option value="warning">Warning</option>
                  <option value="error">Error</option>
                </select>
              </div>

              <div>
                <InputLabel for="log_retention" value="Log-Aufbewahrung (Tage)" />
                <TextInput
                  id="log_retention"
                  v-model="generalConfig.logging.retention"
                  type="number"
                  min="1"
                  max="365"
                  class="mt-1 block w-full"
                />
              </div>

              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="generalConfig.logging.includeRequestData"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Request-Daten in Logs einschließen</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Connection Test Results -->
        <div v-if="testResult" class="mt-6">
          <div :class="[
            'border rounded-lg p-4',
            testResult.success ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'
          ]">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg v-if="testResult.success" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h5 :class="[
                  'text-sm font-medium',
                  testResult.success ? 'text-green-800' : 'text-red-800'
                ]">
                  {{ testResult.success ? 'Verbindungstest erfolgreich' : 'Verbindungstest fehlgeschlagen' }}
                </h5>
                <div :class="[
                  'mt-2 text-sm',
                  testResult.success ? 'text-green-700' : 'text-red-700'
                ]">
                  <p>{{ testResult.message }}</p>
                  <p v-if="testResult.responseTime">Antwortzeit: {{ testResult.responseTime }}ms</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-between">
        <SecondaryButton @click="close">
          Abbrechen
        </SecondaryButton>
        
        <PrimaryButton @click="saveConfiguration" :disabled="saving">
          <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ saving ? 'Speichern...' : 'Konfiguration speichern' }}
        </PrimaryButton>
      </div>
    </template>
  </DialogModal>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DialogModal from '@/Components/DialogModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

// Props
const props = defineProps({
  show: Boolean
})

// Emits
const emit = defineEmits(['close', 'configUpdated'])

// Reactive data
const activeTab = ref('dbb')
const saving = ref(false)
const testingDbb = ref(false)
const testingFiba = ref(false)
const testResult = ref(null)
const showDbbApiKey = ref(false)
const showFibaSecret = ref(false)

// Configuration objects
const dbbConfig = ref({
  enabled: true,
  apiUrl: 'https://api.basketball-bund.de',
  apiKey: '',
  timeout: 30,
  rateLimit: 100,
  cacheTtl: 60,
  enableRetries: true,
  maxRetries: 3,
  endpoints: {
    players: true,
    teams: true,
    leagues: true,
    games: true,
    referees: true,
    transfers: true
  }
})

const fibaConfig = ref({
  enabled: false,
  apiUrl: 'https://api.fiba.basketball',
  clientId: '',
  clientSecret: '',
  scope: 'read:competitions read:players',
  region: 'europe',
  competitionLevel: 'professional',
  endpoints: {
    competitions: true,
    players: true,
    clubs: true,
    standings: true,
    officials: false,
    statistics: true
  }
})

const generalConfig = ref({
  autoSync: {
    enabled: false,
    interval: 'daily',
    time: '02:00'
  },
  notifications: {
    syncSuccess: true,
    syncFailure: true,
    apiErrors: true,
    email: ''
  },
  logging: {
    level: 'info',
    retention: 30,
    includeRequestData: false
  }
})

// Mock initialization
onMounted(() => {
  loadConfiguration()
})

// Methods
const loadConfiguration = async () => {
  try {
    // API call to load current configuration
    // const response = await axios.get('/federation/config')
    // dbbConfig.value = response.data.dbb
    // fibaConfig.value = response.data.fiba
    // generalConfig.value = response.data.general
    
    // Mock data for development
    generalConfig.value.notifications.email = 'admin@basketmanager.pro'
    
  } catch (error) {
    console.error('Failed to load configuration:', error)
  }
}

const testDbbConnection = async () => {
  if (!dbbConfig.value.enabled || !dbbConfig.value.apiUrl) return
  
  testingDbb.value = true
  testResult.value = null

  try {
    const startTime = Date.now()
    
    // API call to test DBB connection
    // const response = await axios.post('/federation/dbb/test-connection', {
    //   api_url: dbbConfig.value.apiUrl,
    //   api_key: dbbConfig.value.apiKey
    // })
    
    // Mock successful test
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    const responseTime = Date.now() - startTime
    
    testResult.value = {
      success: true,
      message: 'DBB API ist erreichbar und funktionsfähig',
      responseTime
    }

  } catch (error) {
    testResult.value = {
      success: false,
      message: `Verbindung zur DBB API fehlgeschlagen: ${error.message || 'Unbekannter Fehler'}`
    }
  } finally {
    testingDbb.value = false
  }
}

const testFibaConnection = async () => {
  if (!fibaConfig.value.enabled || !fibaConfig.value.apiUrl) return
  
  testingFiba.value = true
  testResult.value = null

  try {
    const startTime = Date.now()
    
    // API call to test FIBA connection
    // const response = await axios.post('/federation/fiba/test-connection', {
    //   api_url: fibaConfig.value.apiUrl,
    //   client_id: fibaConfig.value.clientId,
    //   client_secret: fibaConfig.value.clientSecret
    // })
    
    // Mock test (with failure for demo)
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    const responseTime = Date.now() - startTime
    
    testResult.value = {
      success: false,
      message: 'FIBA API Authentication fehlgeschlagen - Client Credentials ungültig',
      responseTime
    }

  } catch (error) {
    testResult.value = {
      success: false,
      message: `Verbindung zur FIBA API fehlgeschlagen: ${error.message || 'Unbekannter Fehler'}`
    }
  } finally {
    testingFiba.value = false
  }
}

const saveConfiguration = async () => {
  saving.value = true

  try {
    // API call to save configuration
    // await axios.post('/federation/config', {
    //   dbb: dbbConfig.value,
    //   fiba: fibaConfig.value,
    //   general: generalConfig.value
    // })

    // Mock successful save
    await new Promise(resolve => setTimeout(resolve, 1000))

    emit('configUpdated', {
      dbb: dbbConfig.value,
      fiba: fibaConfig.value,
      general: generalConfig.value
    })

    close()

  } catch (error) {
    console.error('Failed to save configuration:', error)
    // Show error notification
  } finally {
    saving.value = false
  }
}

const close = () => {
  activeTab.value = 'dbb'
  testResult.value = null
  showDbbApiKey.value = false
  showFibaSecret.value = false
  emit('close')
}
</script>