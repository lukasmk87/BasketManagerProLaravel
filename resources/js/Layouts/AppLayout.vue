<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                
                                <!-- Clubs -->
                                <NavLink :href="route('web.clubs.index')" :active="route().current('web.clubs.*')">
                                    Clubs
                                </NavLink>
                                
                                <!-- Teams -->
                                <NavLink :href="route('web.teams.index')" :active="route().current('web.teams.*')">
                                    Teams
                                </NavLink>
                                
                                <!-- Spieler -->
                                <NavLink :href="route('web.players.index')" :active="route().current('web.players.*')">
                                    Spieler
                                </NavLink>
                                
                                <!-- Spiele -->
                                <NavLink :href="route('web.games.index')" :active="route().current('web.games.*')">
                                    Spiele
                                </NavLink>
                                
                                <!-- Spielplan Import (nur für Club Admins, Trainer, Admins) -->
                                <NavLink 
                                    v-if="$page.props.auth.user && ($page.props.auth.user.roles?.includes('club_admin') || $page.props.auth.user.roles?.includes('trainer') || $page.props.auth.user.roles?.includes('admin') || $page.props.auth.user.roles?.includes('super_admin'))"
                                    :href="route('games.import.index')" 
                                    :active="route().current('games.import.*')">
                                    Import
                                </NavLink>
                                
                                <!-- Training -->
                                <NavLink :href="route('training.index')" :active="route().current('training.*')">
                                    Training
                                </NavLink>
                                
                                <!-- Statistiken -->
                                <NavLink :href="route('statistics.index')" :active="route().current('statistics.*')">
                                    Statistiken
                                </NavLink>
                                
                                <!-- Hallenverwaltung (für berechtigte Benutzer) -->
                                <NavLink v-if="$page.props.auth.user?.roles && ($page.props.auth.user.roles.includes('admin') || $page.props.auth.user.roles.includes('super_admin') || $page.props.auth.user.roles.includes('club_admin') || $page.props.auth.user.roles.includes('trainer'))" 
                                         :href="route('gym.index')" 
                                         :active="route().current('gym.*')">
                                    Hallenverwaltung
                                </NavLink>
                                
                                <!-- Admin Dropdown (nur für Admins) -->
                                <div v-if="$page.props.auth.user?.roles && ($page.props.auth.user.roles.includes('admin') || $page.props.auth.user.roles.includes('super_admin'))" class="relative">
                                    <Dropdown align="bottom" width="48">
                                        <template #trigger>
                                            <button type="button" 
                                                    :class="[
                                                        'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none',
                                                        route().current('admin.*') 
                                                            ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' 
                                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300'
                                                    ]">
                                                Admin
                                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('admin.settings')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    Admin Panel
                                                </div>
                                            </DropdownLink>
                                            
                                            <DropdownLink :href="route('admin.users')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                    </svg>
                                                    Benutzer verwalten
                                                </div>
                                            </DropdownLink>
                                            
                                            <DropdownLink :href="route('admin.system')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                                    </svg>
                                                    System-Info
                                                </div>
                                            </DropdownLink>
                                            
                                            <div class="border-t border-gray-100"></div>
                                            
                                            <DropdownLink :href="route('web.clubs.create')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                    Neuer Club
                                                </div>
                                            </DropdownLink>
                                            
                                            <DropdownLink :href="route('web.teams.create')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                    Neues Team
                                                </div>
                                            </DropdownLink>
                                            
                                            <DropdownLink :href="route('web.players.create')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    Neuer Spieler
                                                </div>
                                            </DropdownLink>
                                            
                                            <div class="border-t border-gray-100"></div>
                                            
                                            <DropdownLink 
                                                v-if="$page.props.auth.user && ($page.props.auth.user.roles?.includes('club_admin') || $page.props.auth.user.roles?.includes('trainer') || $page.props.auth.user.roles?.includes('admin') || $page.props.auth.user.roles?.includes('super_admin'))"
                                                :href="route('games.import.index')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                                    </svg>
                                                    Spielplan Import
                                                </div>
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <div class="ms-3 relative">
                                <!-- Teams Dropdown -->
                                <Dropdown v-if="$page.props.jetstream.hasTeamFeatures" align="right" width="60">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.current_team?.name || 'Kein Team ausgewählt' }}

                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="w-60">
                                            <!-- Team Management -->
                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Manage Team
                                            </div>

                                            <!-- Team Settings -->
                                            <DropdownLink v-if="$page.props.auth.user.current_team" :href="route('teams.show', $page.props.auth.user.current_team)">
                                                Team Settings
                                            </DropdownLink>

                                            <DropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('web.teams.create')">
                                                Create New Team
                                            </DropdownLink>

                                            <!-- Team Switcher -->
                                            <template v-if="$page.props.auth.user?.all_teams?.length > 1">
                                                <div class="border-t border-gray-200" />

                                                <div class="block px-4 py-2 text-xs text-gray-400">
                                                    Switch Teams
                                                </div>

                                                <template v-for="team in $page.props.auth.user?.all_teams || []" :key="team.id">
                                                    <form @submit.prevent="switchToTeam(team)">
                                                        <DropdownLink as="button">
                                                            <div class="flex items-center">
                                                                <svg v-if="team.id == $page.props.auth.user.current_team_id && $page.props.auth.user.current_team_id" class="me-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>

                                                                <div>{{ team.name }}</div>
                                                            </div>
                                                        </DropdownLink>
                                                    </form>
                                                </template>
                                            </template>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                            <img class="size-8 rounded-full object-cover" :src="$page.props.auth.user?.profile_photo_url" :alt="$page.props.auth.user?.name">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user?.name }}

                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Manage Account
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Profile
                                        </DropdownLink>

                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                            API Tokens
                                        </DropdownLink>

                                        <div class="border-t border-gray-200" />

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">
                                                Log Out
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="size-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        
                        <!-- Clubs -->
                        <ResponsiveNavLink :href="route('web.clubs.index')" :active="route().current('web.clubs.*')">
                            Clubs
                        </ResponsiveNavLink>
                        
                        <!-- Teams -->
                        <ResponsiveNavLink :href="route('web.teams.index')" :active="route().current('web.teams.*')">
                            Teams
                        </ResponsiveNavLink>
                        
                        <!-- Spieler -->
                        <ResponsiveNavLink :href="route('web.players.index')" :active="route().current('web.players.*')">
                            Spieler
                        </ResponsiveNavLink>
                        
                        <!-- Spiele -->
                        <ResponsiveNavLink :href="route('web.games.index')" :active="route().current('web.games.*')">
                            Spiele
                        </ResponsiveNavLink>
                        
                        <!-- Spielplan Import (nur für Club Admins, Trainer, Admins) -->
                        <ResponsiveNavLink 
                            v-if="$page.props.auth.user && ($page.props.auth.user.roles?.includes('club_admin') || $page.props.auth.user.roles?.includes('trainer') || $page.props.auth.user.roles?.includes('admin') || $page.props.auth.user.roles?.includes('super_admin'))"
                            :href="route('games.import.index')" 
                            :active="route().current('games.import.*')">
                            Import
                        </ResponsiveNavLink>
                        
                        <!-- Training -->
                        <ResponsiveNavLink :href="route('training.index')" :active="route().current('training.*')">
                            Training
                        </ResponsiveNavLink>
                        
                        <!-- Statistiken -->
                        <ResponsiveNavLink :href="route('statistics.index')" :active="route().current('statistics.*')">
                            Statistiken
                        </ResponsiveNavLink>
                        
                        <!-- Hallenverwaltung (für berechtigte Benutzer) -->
                        <ResponsiveNavLink v-if="$page.props.auth.user?.roles && ($page.props.auth.user.roles.includes('admin') || $page.props.auth.user.roles.includes('super_admin') || $page.props.auth.user.roles.includes('club_admin') || $page.props.auth.user.roles.includes('trainer'))" 
                                           :href="route('gym.index')" 
                                           :active="route().current('gym.*')">
                            Hallenverwaltung
                        </ResponsiveNavLink>
                        
                        <!-- Admin Links (nur für Admins) -->
                        <template v-if="$page.props.auth.user?.roles && ($page.props.auth.user.roles.includes('admin') || $page.props.auth.user.roles.includes('super_admin'))">
                            <div class="border-t border-gray-200 my-2"></div>
                            <div class="px-4 py-2 text-xs text-gray-400 uppercase tracking-wide">
                                Administration
                            </div>
                            
                            <ResponsiveNavLink :href="route('admin.settings')" :active="route().current('admin.settings')">
                                Admin Panel
                            </ResponsiveNavLink>
                            
                            <ResponsiveNavLink :href="route('admin.users')" :active="route().current('admin.users')">
                                Benutzer verwalten
                            </ResponsiveNavLink>
                            
                            <ResponsiveNavLink :href="route('admin.system')" :active="route().current('admin.system')">
                                System-Info
                            </ResponsiveNavLink>
                            
                            <div class="border-t border-gray-200 my-2"></div>
                            <div class="px-4 py-2 text-xs text-gray-400 uppercase tracking-wide">
                                Schnellaktionen
                            </div>
                            
                            <ResponsiveNavLink :href="route('web.clubs.create')" :active="route().current('web.clubs.create')">
                                Neuer Club
                            </ResponsiveNavLink>
                            
                            <ResponsiveNavLink :href="route('web.teams.create')" :active="route().current('web.teams.create')">
                                Neues Team
                            </ResponsiveNavLink>
                            
                            <ResponsiveNavLink :href="route('web.players.create')" :active="route().current('players.create')">
                                Neuer Spieler
                            </ResponsiveNavLink>
                        </template>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                <img class="size-10 rounded-full object-cover" :src="$page.props.auth.user?.profile_photo_url" :alt="$page.props.auth.user?.name">
                            </div>

                            <div>
                                <div class="font-medium text-base text-gray-800">
                                    {{ $page.props.auth.user?.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500">
                                    {{ $page.props.auth.user?.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                Profile
                            </ResponsiveNavLink>

                            <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                                API Tokens
                            </ResponsiveNavLink>

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink as="button">
                                    Log Out
                                </ResponsiveNavLink>
                            </form>

                            <!-- Team Management -->
                            <template v-if="$page.props.jetstream.hasTeamFeatures">
                                <div class="border-t border-gray-200" />

                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Manage Team
                                </div>

                                <!-- Team Settings -->
                                <ResponsiveNavLink v-if="$page.props.auth.user.current_team" :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current('teams.show')">
                                    Team Settings
                                </ResponsiveNavLink>

                                <ResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams" :href="route('web.teams.create')" :active="route().current('web.teams.create')">
                                    Create New Team
                                </ResponsiveNavLink>

                                <!-- Team Switcher -->
                                <template v-if="$page.props.auth.user?.all_teams?.length > 1">
                                    <div class="border-t border-gray-200" />

                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Switch Teams
                                    </div>

                                    <template v-for="team in $page.props.auth.user?.all_teams || []" :key="team.id">
                                        <form @submit.prevent="switchToTeam(team)">
                                            <ResponsiveNavLink as="button">
                                                <div class="flex items-center">
                                                    <svg v-if="team.id == $page.props.auth.user.current_team_id && $page.props.auth.user.current_team_id" class="me-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <div>{{ team.name }}</div>
                                                </div>
                                            </ResponsiveNavLink>
                                        </form>
                                    </template>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
