<template>
    <AppLayout title="Team erstellen">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Team erstellen
                </h2>
                <SecondaryButton 
                    :href="route('teams.index')"
                    as="Link"
                >
                    Zurück
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Team Name -->
                            <div class="md:col-span-2">
                                <InputLabel for="name" value="Team-Name*" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <!-- Club -->
                            <div>
                                <InputLabel for="club_id" value="Verein*" />
                                <select
                                    id="club_id"
                                    v-model="form.club_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="">Verein auswählen</option>
                                    <option v-for="club in clubs" :key="club.id" :value="club.id">
                                        {{ club.name }}
                                    </option>
                                </select>
                                <!-- Debug info and fallback message -->
                                <div v-if="!clubs || clubs.length === 0" class="mt-2 p-3 bg-yellow-100 border border-yellow-400 rounded-md">
                                    <p class="text-yellow-800 text-sm">
                                        <strong>Keine Vereine verfügbar:</strong> 
                                        Es wurden keine aktiven Vereine gefunden. Bitte kontaktieren Sie den Administrator, um einen Verein zu erstellen.
                                    </p>
                                </div>
                                <InputError :message="form.errors.club_id" class="mt-2" />
                            </div>

                            <!-- Season -->
                            <div>
                                <InputLabel for="season" value="Saison*" />
                                <TextInput
                                    id="season"
                                    v-model="form.season"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="2023/2024"
                                    maxlength="9"
                                    required
                                />
                                <InputError :message="form.errors.season" class="mt-2" />
                            </div>

                            <!-- League -->
                            <div>
                                <InputLabel for="league" value="Liga" />
                                <TextInput
                                    id="league"
                                    v-model="form.league"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="z.B. Bezirksliga"
                                />
                                <InputError :message="form.errors.league" class="mt-2" />
                            </div>

                            <!-- Division -->
                            <div>
                                <InputLabel for="division" value="Staffel" />
                                <TextInput
                                    id="division"
                                    v-model="form.division"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="z.B. Staffel A"
                                />
                                <InputError :message="form.errors.division" class="mt-2" />
                            </div>

                            <!-- Age Group -->
                            <div>
                                <InputLabel for="age_group" value="Altersklasse" />
                                <TextInput
                                    id="age_group"
                                    v-model="form.age_group"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="z.B. U16, Herren, Damen"
                                />
                                <InputError :message="form.errors.age_group" class="mt-2" />
                            </div>

                            <!-- Gender -->
                            <div>
                                <InputLabel for="gender" value="Geschlecht*" />
                                <select
                                    id="gender"
                                    v-model="form.gender"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required
                                >
                                    <option value="male">Männlich</option>
                                    <option value="female">Weiblich</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                                <InputError :message="form.errors.gender" class="mt-2" />
                            </div>

                            <!-- Active Status -->
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2">Team ist aktiv</span>
                                </label>
                            </div>

                            <!-- Training Schedule -->
                            <div class="md:col-span-2">
                                <InputLabel value="Trainingszeiten" />
                                <div class="mt-2 space-y-2">
                                    <div 
                                        v-for="(schedule, index) in trainingSchedules" 
                                        :key="index"
                                        class="flex items-center gap-2"
                                    >
                                        <select
                                            v-model="schedule.day"
                                            class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                            <option value="">Tag wählen</option>
                                            <option value="monday">Montag</option>
                                            <option value="tuesday">Dienstag</option>
                                            <option value="wednesday">Mittwoch</option>
                                            <option value="thursday">Donnerstag</option>
                                            <option value="friday">Freitag</option>
                                            <option value="saturday">Samstag</option>
                                            <option value="sunday">Sonntag</option>
                                        </select>
                                        <TextInput
                                            v-model="schedule.time"
                                            type="time"
                                            class="flex-1"
                                        />
                                        <TextInput
                                            v-model="schedule.location"
                                            type="text"
                                            placeholder="Ort"
                                            class="flex-1"
                                        />
                                        <SecondaryButton
                                            type="button"
                                            @click="removeTrainingSchedule(index)"
                                        >
                                            Entfernen
                                        </SecondaryButton>
                                    </div>
                                    <PrimaryButton
                                        type="button"
                                        @click="addTrainingSchedule"
                                    >
                                        Trainingszeit hinzufügen
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <InputLabel for="description" value="Beschreibung" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    maxlength="1000"
                                ></textarea>
                                <InputError :message="form.errors.description" class="mt-2" />
                                <div class="text-xs text-gray-500 mt-1">Max. 1000 Zeichen</div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end mt-6">
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Team erstellen
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    clubs: Array,
})

// Debug logging
console.log('Teams Create - Clubs data:', props.clubs)
console.log('Teams Create - Clubs count:', props.clubs?.length || 0)

const form = useForm({
    name: '',
    club_id: '',
    season: '',
    league: '',
    division: '',
    age_group: '',
    gender: 'male',
    is_active: true,
    training_schedule: '[]',
    description: '',
})

const trainingSchedules = ref([])

watch(trainingSchedules, (value) => {
    form.training_schedule = JSON.stringify(value)
}, { deep: true })

const addTrainingSchedule = () => {
    trainingSchedules.value.push({
        day: '',
        time: '',
        location: ''
    })
}

const removeTrainingSchedule = (index) => {
    trainingSchedules.value.splice(index, 1)
}

const submit = () => {
    form.post(route('teams.store'))
}
</script>