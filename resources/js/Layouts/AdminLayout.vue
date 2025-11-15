<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import TenantSelector from '@/Components/Admin/TenantSelector.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(true);

const logout = () => {
    router.post(route('logout'));
};

// Navigation items for Admin Panel
const navigationItems = [
    {
        name: 'Dashboard',
        route: 'admin.dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    },
    {
        name: 'Subscription Plans',
        route: 'admin.plans.index',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    },
    {
        name: 'Tenants',
        route: 'admin.tenants.index',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        name: 'Club Transfers',
        route: 'admin.club-transfers.index',
        icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    },
    {
        name: 'Usage Statistics',
        route: 'admin.usage.stats',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        name: 'User Management',
        route: 'admin.users',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    },
    {
        name: 'System Settings',
        route: 'admin.settings',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        name: 'Landing Page',
        route: 'admin.landing-page.index',
        icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
];

const isCurrentRoute = (routeName) => {
    return route().current(routeName);
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-100">
            <!-- Top Navigation -->
            <nav class="bg-gradient-to-r from-indigo-600 to-indigo-800 border-b border-indigo-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('admin.dashboard')">
                                    <ApplicationMark class="block h-9 w-auto text-white" />
                                </Link>
                            </div>

                            <!-- Admin Badge -->
                            <div class="ml-4 flex items-center px-3 py-1 bg-indigo-500 rounded-full">
                                <svg class="w-4 h-4 text-white mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-xs font-bold text-white uppercase tracking-wider">Admin Panel</span>
                            </div>

                            <!-- Tenant Selector (Super Admin Only) -->
                            <div class="ml-4">
                                <TenantSelector
                                    v-if="$page.props.superAdmin"
                                    :selected-tenant-id="$page.props.superAdmin.selectedTenantId"
                                    :selected-tenant="$page.props.superAdmin.selectedTenant"
                                    :available-tenants="$page.props.superAdmin.availableTenants"
                                />
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <!-- Back to Main Dashboard -->
                            <Link
                                :href="route('dashboard')"
                                class="mr-4 inline-flex items-center px-3 py-2 border border-indigo-400 text-xs font-medium rounded-md text-white hover:bg-indigo-500 transition-colors duration-150"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Zurück zum Hauptdashboard
                            </Link>

                            <!-- User Dropdown -->
                            <div v-if="$page.props.auth.user" class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-indigo-400 rounded-full focus:outline-none focus:border-white transition">
                                            <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user?.name || 'User'">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white hover:text-indigo-100 focus:outline-none transition ease-in-out duration-150">
                                                {{ $page.props.auth.user?.name || 'User' }}

                                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Account verwalten
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Profil
                                        </DropdownLink>

                                        <div class="border-t border-gray-200" />

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">
                                                Abmelden
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-indigo-100 hover:bg-indigo-700 focus:outline-none focus:bg-indigo-700 transition duration-150 ease-in-out" @click="showingNavigationDropdown = !showingNavigationDropdown">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink
                            v-for="item in navigationItems"
                            :key="item.route"
                            :href="route(item.route)"
                            :active="isCurrentRoute(item.route)"
                            class="text-white"
                        >
                            {{ item.name }}
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div v-if="$page.props.auth.user" class="pt-4 pb-1 border-t border-indigo-700">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
                                <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user?.name || 'User'">
                            </div>

                            <div>
                                <div class="font-medium text-base text-white">
                                    {{ $page.props.auth.user?.name || 'Unbekannt' }}
                                </div>
                                <div class="font-medium text-sm text-indigo-200">
                                    {{ $page.props.auth.user?.email || '' }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')" class="text-white">
                                Profil
                            </ResponsiveNavLink>

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink as="button" class="text-white">
                                    Abmelden
                                </ResponsiveNavLink>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content with Sidebar -->
            <div class="flex">
                <!-- Sidebar -->
                <aside
                    :class="sidebarOpen ? 'w-64' : 'w-20'"
                    class="bg-white shadow-lg min-h-screen sticky top-0 transition-all duration-300 hidden md:block border-r border-gray-200"
                >
                    <!-- Toggle Button -->
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="absolute -right-3 top-6 bg-white border border-gray-200 rounded-full p-1.5 shadow-md hover:shadow-lg transition-all z-10"
                    >
                        <svg
                            class="w-4 h-4 text-gray-600 transition-transform"
                            :class="{ 'rotate-180': !sidebarOpen }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Navigation Items -->
                    <nav class="mt-8 px-3 space-y-1">
                        <Link
                            v-for="item in navigationItems"
                            :key="item.route"
                            :href="route(item.route)"
                            :class="[
                                'flex items-center px-3 py-2.5 rounded-lg transition-all duration-200',
                                isCurrentRoute(item.route)
                                    ? 'bg-indigo-50 text-indigo-700 font-semibold shadow-sm'
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                            ]"
                        >
                            <svg
                                class="flex-shrink-0 w-6 h-6"
                                :class="isCurrentRoute(item.route) ? 'text-indigo-600' : 'text-gray-400'"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span
                                v-if="sidebarOpen"
                                class="ml-3 text-sm"
                            >
                                {{ item.name }}
                            </span>
                        </Link>
                    </nav>

                    <!-- Sidebar Footer -->
                    <div v-if="sidebarOpen" class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-xs font-semibold text-indigo-700">
                                Admin Panel
                            </p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            System Administration
                        </p>
                    </div>
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1">
                    <!-- Page Heading -->
                    <header v-if="$slots.header" class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            <slot name="header" />
                        </div>
                    </header>

                    <!-- Page Content -->
                    <div>
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
