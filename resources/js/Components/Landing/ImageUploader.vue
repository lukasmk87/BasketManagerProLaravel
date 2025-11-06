<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    modelValue: String,
    label: {
        type: String,
        default: 'Bild'
    },
    maxSize: {
        type: Number,
        default: 2048 // KB
    },
});

const emit = defineEmits(['update:modelValue']);

const activeTab = ref('url'); // 'url' or 'upload'
const imageUrl = ref(props.modelValue || '');
const selectedFile = ref(null);
const previewUrl = ref(props.modelValue || '');
const uploading = ref(false);
const uploadError = ref(null);

const updateImageUrl = () => {
    emit('update:modelValue', imageUrl.value);
    previewUrl.value = imageUrl.value;
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        uploadError.value = 'Ungültiger Dateityp. Nur JPG, PNG, WebP oder GIF erlaubt.';
        return;
    }

    // Validate file size
    if (file.size > props.maxSize * 1024) {
        uploadError.value = `Datei zu groß. Maximal ${props.maxSize / 1024}MB erlaubt.`;
        return;
    }

    uploadError.value = null;
    selectedFile.value = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const uploadFile = async () => {
    if (!selectedFile.value) return;

    uploading.value = true;
    uploadError.value = null;

    const formData = new FormData();
    formData.append('image', selectedFile.value);

    try {
        const response = await fetch(route('admin.landing-page.upload-image'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Upload fehlgeschlagen');
        }

        const data = await response.json();
        emit('update:modelValue', data.url);
        previewUrl.value = data.url;
        imageUrl.value = data.url;
    } catch (error) {
        uploadError.value = 'Upload fehlgeschlagen. Bitte versuchen Sie es erneut.';
    } finally {
        uploading.value = false;
    }
};

const removeImage = () => {
    emit('update:modelValue', null);
    imageUrl.value = '';
    previewUrl.value = '';
    selectedFile.value = null;
};
</script>

<template>
    <div class="space-y-3">
        <InputLabel :value="label" />

        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200">
            <button
                type="button"
                @click="activeTab = 'url'"
                class="px-4 py-2 text-sm font-medium border-b-2 transition"
                :class="activeTab === 'url' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            >
                URL eingeben
            </button>
            <button
                type="button"
                @click="activeTab = 'upload'"
                class="px-4 py-2 text-sm font-medium border-b-2 transition"
                :class="activeTab === 'upload' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            >
                Datei hochladen
            </button>
        </div>

        <!-- URL Tab -->
        <div v-if="activeTab === 'url'" class="space-y-3">
            <div class="flex space-x-2">
                <TextInput
                    v-model="imageUrl"
                    type="url"
                    placeholder="https://example.com/image.jpg"
                    class="flex-1"
                    @blur="updateImageUrl"
                />
                <button
                    type="button"
                    @click="updateImageUrl"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition text-sm"
                >
                    Übernehmen
                </button>
            </div>
            <p class="text-xs text-gray-500">Geben Sie die vollständige URL zu einem Bild ein</p>
        </div>

        <!-- Upload Tab -->
        <div v-if="activeTab === 'upload'" class="space-y-3">
            <div class="flex flex-col items-center justify-center w-full">
                <label
                    for="file-upload"
                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition"
                >
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500">
                            <span class="font-semibold">Klicken zum Hochladen</span> oder Drag & Drop
                        </p>
                        <p class="text-xs text-gray-500">PNG, JPG, WebP (max. {{ maxSize / 1024 }}MB)</p>
                    </div>
                    <input
                        id="file-upload"
                        type="file"
                        class="hidden"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        @change="handleFileSelect"
                    />
                </label>
            </div>

            <div v-if="selectedFile" class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                <span class="text-sm text-gray-700">{{ selectedFile.name }}</span>
                <button
                    type="button"
                    @click="uploadFile"
                    :disabled="uploading"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition text-sm disabled:opacity-50"
                >
                    {{ uploading ? 'Hochladen...' : 'Hochladen' }}
                </button>
            </div>

            <InputError v-if="uploadError" :message="uploadError" class="mt-2" />
        </div>

        <!-- Image Preview -->
        <div v-if="previewUrl" class="mt-4 space-y-2">
            <div class="relative inline-block">
                <img
                    :src="previewUrl"
                    :alt="label"
                    class="max-w-full h-32 rounded-lg border border-gray-300 object-cover"
                />
                <button
                    type="button"
                    @click="removeImage"
                    class="absolute top-2 right-2 p-1 bg-red-600 text-white rounded-full hover:bg-red-700 transition"
                    title="Bild entfernen"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-500">Aktuelles Bild</p>
        </div>
    </div>
</template>
