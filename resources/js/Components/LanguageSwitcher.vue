<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            type="button"
            class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md px-3 py-2 transition-colors"
            :class="{ 'bg-gray-100': isOpen }"
        >
            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
            </svg>
            <span>{{ currentLanguageName }}</span>
            <svg class="ml-2 h-5 w-5 text-gray-400 transition-transform" :class="{ 'rotate-180': isOpen }" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                v-click-outside="closeDropdown"
                class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
            >
                <div class="py-1">
                    <button
                        v-for="language in languages"
                        :key="language.code"
                        @click="switchLanguage(language.code)"
                        type="button"
                        class="flex items-center justify-between w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        :class="{ 'bg-indigo-50 text-indigo-600 font-semibold': currentLocale === language.code }"
                    >
                        <span>{{ language.name }}</span>
                        <svg
                            v-if="currentLocale === language.code"
                            class="w-5 h-5 text-indigo-600"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const isOpen = ref(false);

const languages = [
    { code: 'de', name: 'Deutsch' },
    { code: 'en', name: 'English' },
];

const currentLocale = computed(() => usePage().props.locale || 'de');

const currentLanguageName = computed(() => {
    const language = languages.find(lang => lang.code === currentLocale.value);
    return language ? language.name : 'Deutsch';
});

const switchLanguage = (locale) => {
    if (locale === currentLocale.value) {
        isOpen.value = false;
        return;
    }

    // Send request to update user locale preference
    router.post(route('user.locale.update'), {
        locale: locale,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Reload the page with new locale
            window.location.href = `/${locale}${window.location.pathname.substring(3)}`;
        },
        onError: (errors) => {
            console.error('Failed to update locale:', errors);
            isOpen.value = false;
        }
    });
};

const closeDropdown = () => {
    isOpen.value = false;
};

// Click outside directive
const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent);
    },
};
</script>
