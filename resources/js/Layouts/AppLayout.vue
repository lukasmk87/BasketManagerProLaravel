<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const page = usePage();
const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'), {}, {
        onSuccess: () => {
            window.location.href = '/';
        }
    });
};

const switchLanguage = (locale) => {
    const currentLocale = page.props.locale || 'de';
    if (locale === currentLocale) {
        return;
    }

    router.post(route('user.locale.update'), {
        locale: locale,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            window.location.href = `/${locale}${window.location.pathname.substring(3)}`;
        },
    });
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
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

                                <!-- Spieler-Einladungen (für Trainer & Club Admins) -->
                                <NavLink
                                    v-if="$page.props.auth.user && ($page.props.auth.user.roles?.includes('club_admin') || $page.props.auth.user.roles?.includes('trainer'))"
                                    :href="route('trainer.invitations.index')"
                                    :active="route().current('trainer.invitations.*')">
                                    Spieler-Einladungen
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

                                <!-- Club Admin (nur für club_admin Rolle) -->
                                <NavLink v-if="$page.props.auth.user?.roles && $page.props.auth.user.roles.includes('club_admin')"
                                         :href="route('club-admin.dashboard')"
                                         :active="route().current('club-admin.*')">
                                    Club Admin
                                </NavLink>

                                <!-- Admin Dropdown (nur für Admins) -->
                                <div v-if="$page.props.auth.user?.roles && ($page.props.auth.user.roles.includes('admin') || $page.props.auth.user.roles.includes('super_admin'))" class="relative">
                                    <Dropdown align="bottom" width="48">
                                        <template #trigger>
                                            <button type="button" 
                                                    :class="[
                                                        'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none',
                                                        route().current('admin.*')
                                                            ? 'border-indigo-400 dark:border-indigo-500 text-gray-900 dark:text-gray-100 focus:border-indigo-700'
                                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 focus:text-gray-700 dark:focus:text-gray-200 focus:border-gray-300 dark:focus:border-gray-600'
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

                                            <DropdownLink :href="route('admin.legal-pages.index')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    Rechtliche Seiten
                                                </div>
                                            </DropdownLink>

                                            <div class="border-t border-gray-100 dark:border-gray-700"></div>

                                            <!-- Subscription Management -->
                                            <div class="px-4 py-2 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                                Subscription Management
                                            </div>

                                            <DropdownLink :href="route('admin.dashboard')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                    Admin Dashboard
                                                </div>
                                            </DropdownLink>

                                            <DropdownLink :href="route('admin.plans.index')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                                    </svg>
                                                    Subscription Plans
                                                </div>
                                            </DropdownLink>

                                            <DropdownLink :href="route('admin.tenants.index')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                    Tenant Management
                                                </div>
                                            </DropdownLink>

                                            <DropdownLink :href="route('admin.usage.stats')">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                    </svg>
                                                    Usage Statistics
                                                </div>
                                            </DropdownLink>

                                            <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                            
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
                                            
                                            <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                            
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
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
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
                                            <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
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
                                                <div class="border-t border-gray-200 dark:border-gray-700" />

                                                <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
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
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user?.name }}

                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
                                            Manage Account
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Profile
                                        </DropdownLink>

                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                            API Tokens
                                        </DropdownLink>

                                        <div class="border-t border-gray-200 dark:border-gray-700" />

                                        <!-- Language Selection -->
                                        <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
                                            Sprache / Language
                                        </div>

                                        <button
                                            @click="switchLanguage('de')"
                                            type="button"
                                            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 transition duration-150 ease-in-out"
                                            :class="{ 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 font-semibold': $page.props.locale === 'de' }"
                                        >
                                            <span class="flex items-center justify-between">
                                                <span>Deutsch</span>
                                                <svg v-if="$page.props.locale === 'de'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </button>

                                        <button
                                            @click="switchLanguage('en')"
                                            type="button"
                                            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 transition duration-150 ease-in-out"
                                            :class="{ 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 font-semibold': $page.props.locale === 'en' }"
                                        >
                                            <span class="flex items-center justify-between">
                                                <span>English</span>
                                                <svg v-if="$page.props.locale === 'en'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </button>

                                        <div class="border-t border-gray-200 dark:border-gray-700" />

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
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
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

                        <!-- Spieler-Einladungen (für Trainer & Club Admins) -->
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user && ($page.props.auth.user.roles?.includes('club_admin') || $page.props.auth.user.roles?.includes('trainer'))"
                            :href="route('trainer.invitations.index')"
                            :active="route().current('trainer.invitations.*')">
                            Spieler-Einladungen
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

                        <!-- Club Admin (nur für club_admin Rolle) -->
                        <ResponsiveNavLink v-if="$page.props.auth.user?.roles && $page.props.auth.user.roles.includes('club_admin')"
                                           :href="route('club-admin.dashboard')"
                                           :active="route().current('club-admin.*')">
                            Club Admin
                        </ResponsiveNavLink>

                        <!-- Admin Links (nur für Admins) -->
                        <template v-if="$page.props.auth.user?.roles && ($page.props.auth.user.roles.includes('admin') || $page.props.auth.user.roles.includes('super_admin'))">
                            <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                            <div class="px-4 py-2 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
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

                            <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                            <div class="px-4 py-2 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                Subscription Management
                            </div>

                            <ResponsiveNavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                                Admin Dashboard
                            </ResponsiveNavLink>

                            <ResponsiveNavLink :href="route('admin.plans.index')" :active="route().current('admin.plans.*')">
                                Subscription Plans
                            </ResponsiveNavLink>

                            <ResponsiveNavLink :href="route('admin.tenants.index')" :active="route().current('admin.tenants.*')">
                                Tenant Management
                            </ResponsiveNavLink>

                            <ResponsiveNavLink :href="route('admin.usage.stats')" :active="route().current('admin.usage.*')">
                                Usage Statistics
                            </ResponsiveNavLink>

                            <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                            <div class="px-4 py-2 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
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
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                <img class="size-10 rounded-full object-cover" :src="$page.props.auth.user?.profile_photo_url" :alt="$page.props.auth.user?.name">
                            </div>

                            <div>
                                <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                    {{ $page.props.auth.user?.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">
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
                                <div class="border-t border-gray-200 dark:border-gray-700" />

                                <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
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
                                    <div class="border-t border-gray-200 dark:border-gray-700" />

                                    <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
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
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow dark:shadow-gray-900/50">
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
