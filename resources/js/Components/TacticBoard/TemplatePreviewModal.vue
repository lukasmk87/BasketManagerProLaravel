<script setup>
import { ref, watch } from 'vue';
import { XMarkIcon, DocumentDuplicateIcon, PlayIcon } from '@heroicons/vue/24/outline';
import TacticBoardViewer from './TacticBoardViewer.vue';
import FavoriteButton from './FavoriteButton.vue';

const props = defineProps({
    template: {
        type: Object,
        default: null,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'use']);

const customName = ref('');
const isAnimating = ref(false);

watch(() => props.template, (newTemplate) => {
    if (newTemplate) {
        customName.value = newTemplate.name;
    }
}, { immediate: true });

function handleClose() {
    emit('close');
}

function handleUse() {
    emit('use', {
        template: props.template,
        name: customName.value || props.template?.name,
    });
}

function toggleAnimation() {
    isAnimating.value = !isAnimating.value;
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && template"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="handleClose"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="handleClose"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center space-x-3">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    {{ template.name }}
                                </h2>
                                <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                    {{ template.category_display }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <FavoriteButton
                                    :playId="template.id"
                                    size="md"
                                />
                                <button
                                    @click="handleClose"
                                    class="p-1 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                                >
                                    <XMarkIcon class="w-6 h-6" />
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Viewer -->
                                <div class="lg:col-span-2">
                                    <div class="aspect-[4/3] bg-gray-100 rounded-lg overflow-hidden">
                                        <TacticBoardViewer
                                            :playData="template.play_data"
                                            :animationData="template.animation_data"
                                            :readonly="true"
                                            :showControls="true"
                                            class="w-full h-full"
                                        />
                                    </div>
                                    <!-- Animation controls -->
                                    <div v-if="template.has_animation" class="mt-3 flex items-center justify-center">
                                        <button
                                            @click="toggleAnimation"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            <PlayIcon class="w-4 h-4 mr-2" />
                                            {{ isAnimating ? 'Stoppen' : 'Animation abspielen' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Details -->
                                <div class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">Beschreibung</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ template.description || 'Keine Beschreibung vorhanden.' }}
                                        </p>
                                    </div>

                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">Feldtyp</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ template.court_type_display }}
                                        </p>
                                    </div>

                                    <div v-if="template.tags && template.tags.length">
                                        <h3 class="text-sm font-medium text-gray-900">Tags</h3>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span
                                                v-for="tag in template.tags"
                                                :key="tag"
                                                class="inline-block px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"
                                            >
                                                {{ tag }}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">Statistik</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ template.usage_count }} mal verwendet
                                        </p>
                                    </div>

                                    <!-- Custom name input -->
                                    <div class="pt-4 border-t border-gray-200">
                                        <label for="customName" class="block text-sm font-medium text-gray-700">
                                            Name für deinen Spielzug
                                        </label>
                                        <input
                                            id="customName"
                                            v-model="customName"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :placeholder="template.name"
                                        />
                                    </div>

                                    <!-- Use button -->
                                    <button
                                        @click="handleUse"
                                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <DocumentDuplicateIcon class="w-5 h-5 mr-2" />
                                        Template verwenden
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
