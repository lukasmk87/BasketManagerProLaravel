<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});

const isNumeric = (link) => {
    return !isNaN(link.label);
};

const isPrevious = (link) => {
    return link.label.includes('Previous') || link.label.includes('&laquo;');
};

const isNext = (link) => {
    return link.label.includes('Next') || link.label.includes('&raquo;');
};

const getDisplayLabel = (link) => {
    if (isPrevious(link)) {
        return 'Zurück';
    }
    if (isNext(link)) {
        return 'Weiter';
    }
    return link.label;
};
</script>

<template>
    <nav v-if="links.length > 3" class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0">
        <div class="-mt-px flex w-0 flex-1">
            <!-- Previous Link -->
            <Link
                v-for="link in links.slice(0, 1)"
                :key="link.label"
                :href="link.url"
                :class="[
                    'inline-flex items-center border-t-2 pr-1 pt-4 text-sm font-medium',
                    link.url
                        ? 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                        : 'border-transparent text-gray-300 cursor-not-allowed'
                ]"
                :disabled="!link.url"
            >
                <svg class="mr-3 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a.75.75 0 01-.75.75H4.66l2.1 1.95a.75.75 0 11-1.02 1.1l-3.5-3.25a.75.75 0 010-1.1l3.5-3.25a.75.75 0 111.02 1.1L4.66 9.25h12.59A.75.75 0 0118 10z" clip-rule="evenodd" />
                </svg>
                {{ getDisplayLabel(link) }}
            </Link>
        </div>

        <!-- Page Numbers -->
        <div class="hidden md:-mt-px md:flex">
            <template v-for="link in links.slice(1, -1)" :key="link.label">
                <!-- Current Page -->
                <span
                    v-if="link.active"
                    class="inline-flex items-center border-t-2 border-indigo-500 px-4 pt-4 text-sm font-medium text-indigo-600"
                    aria-current="page"
                >
                    {{ link.label }}
                </span>
                
                <!-- Clickable Page -->
                <Link
                    v-else-if="link.url && isNumeric(link)"
                    :href="link.url"
                    class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                >
                    {{ link.label }}
                </Link>
                
                <!-- Ellipsis -->
                <span
                    v-else-if="!link.url && link.label === '...'"
                    class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500"
                >
                    {{ link.label }}
                </span>
            </template>
        </div>

        <div class="-mt-px flex w-0 flex-1 justify-end">
            <!-- Next Link -->
            <Link
                v-for="link in links.slice(-1)"
                :key="link.label"
                :href="link.url"
                :class="[
                    'inline-flex items-center border-t-2 pl-1 pt-4 text-sm font-medium',
                    link.url
                        ? 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                        : 'border-transparent text-gray-300 cursor-not-allowed'
                ]"
                :disabled="!link.url"
            >
                {{ getDisplayLabel(link) }}
                <svg class="ml-3 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M2 10a.75.75 0 01.75-.75h12.59l-2.1-1.95a.75.75 0 111.02-1.1l3.5 3.25a.75.75 0 010 1.1l-3.5 3.25a.75.75 0 11-1.02-1.1l2.1-1.95H2.75A.75.75 0 012 10z" clip-rule="evenodd" />
                </svg>
            </Link>
        </div>
    </nav>

    <!-- Mobile Pagination -->
    <nav v-if="links.length > 3" class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6 md:hidden">
        <div class="flex flex-1 justify-between">
            <!-- Previous Link Mobile -->
            <Link
                v-for="link in links.slice(0, 1)"
                :key="'mobile-' + link.label"
                :href="link.url"
                :class="[
                    'relative inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300',
                    link.url
                        ? 'text-gray-900 hover:bg-gray-50'
                        : 'text-gray-400 cursor-not-allowed'
                ]"
                :disabled="!link.url"
            >
                <svg class="mr-1 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a.75.75 0 01-.75.75H4.66l2.1 1.95a.75.75 0 11-1.02 1.1l-3.5-3.25a.75.75 0 010-1.1l3.5-3.25a.75.75 0 111.02 1.1L4.66 9.25h12.59A.75.75 0 0118 10z" clip-rule="evenodd" />
                </svg>
                {{ getDisplayLabel(link) }}
            </Link>

            <!-- Next Link Mobile -->
            <Link
                v-for="link in links.slice(-1)"
                :key="'mobile-' + link.label"
                :href="link.url"
                :class="[
                    'relative ml-3 inline-flex items-center rounded-md px-3 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300',
                    link.url
                        ? 'text-gray-900 hover:bg-gray-50'
                        : 'text-gray-400 cursor-not-allowed'
                ]"
                :disabled="!link.url"
            >
                {{ getDisplayLabel(link) }}
                <svg class="ml-1 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M2 10a.75.75 0 01.75-.75h12.59l-2.1-1.95a.75.75 0 111.02-1.1l3.5 3.25a.75.75 0 010 1.1l-3.5 3.25a.75.75 0 11-1.02-1.1l2.1-1.95H2.75A.75.75 0 012 10z" clip-rule="evenodd" />
                </svg>
            </Link>
        </div>
    </nav>

    <!-- Page Info -->
    <div v-if="links.length > 3" class="mt-4 text-center">
        <template v-for="link in links" :key="'info-' + link.label">
            <p v-if="link.active" class="text-sm text-gray-700">
                Seite <span class="font-medium">{{ link.label }}</span>
            </p>
        </template>
    </div>
</template>