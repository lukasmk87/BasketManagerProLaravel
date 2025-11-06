# Visuelle Editoren - Vervollständigungsanleitung

## ✅ Bereits erstellt (5 Komponenten):

1. **IconPicker.vue** ✅ - Icon-Auswahl mit 30+ Icons
2. **ImageUploader.vue** ✅ - URL + File Upload
3. **StarRating.vue** ✅ - 1-5 Sterne Bewertung
4. **DraggableList.vue** ✅ - Drag & Drop mit Add/Remove
5. **FaqEditor.vue** ✅ - Vollständiger FAQ-Editor mit Live-Preview

---

## 🚧 Noch zu erstellen (3 Editoren + Backend):

### 1. FeaturesEditor.vue

```vue
<script setup>
import { computed } from 'vue';
import DraggableList from '@/Components/Landing/DraggableList.vue';
import IconPicker from '@/Components/Landing/IconPicker.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    modelValue: Object,
    errors: Object
});

const emit = defineEmits(['update:modelValue']);

const content = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

// Initialize structure
if (!content.value.headline) {
    content.value = {
        headline: content.value.headline || 'Alles, was dein Verein braucht',
        subheadline: content.value.subheadline || 'Eine Plattform für alle Anforderungen moderner Basketballvereine',
        items: content.value.items || []
    };
}

const addFeature = () => {
    content.value.items.push({
        icon: '',
        title: '',
        description: ''
    });
};

const removeFeature = (index) => {
    content.value.items.splice(index, 1);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Headline -->
        <div>
            <InputLabel value="Überschrift *" />
            <TextInput v-model="content.headline" class="mt-1 block w-full" required maxlength="255" />
        </div>

        <!-- Subheadline -->
        <div>
            <InputLabel value="Unterüberschrift *" />
            <textarea v-model="content.subheadline" rows="2" class="mt-1 block w-full border-gray-300 rounded-md" maxlength="500"></textarea>
        </div>

        <!-- Features List -->
        <DraggableList
            v-model:items="content.items"
            add-label="+ Feature hinzufügen"
            :min-items="1"
            :max-items="10"
            @add="addFeature"
            @remove="removeFeature"
        >
            <template #default="{ item, index }">
                <div class="space-y-4">
                    <!-- Icon Picker -->
                    <IconPicker v-model="item.icon" label="Icon" />

                    <!-- Title -->
                    <div>
                        <InputLabel value="Titel *" />
                        <TextInput v-model="item.title" class="mt-1 block w-full" required maxlength="100" />
                        <p class="text-xs text-gray-500 mt-1">{{ (item.title || '').length }} / 100</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel value="Beschreibung *" />
                        <textarea v-model="item.description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md" maxlength="200"></textarea>
                        <p class="text-xs text-gray-500 mt-1">{{ (item.description || '').length }} / 200</p>
                    </div>

                    <!-- Preview Card -->
                    <div class="p-4 bg-gray-50 border rounded-lg">
                        <div class="text-xs text-gray-500 mb-2">Vorschau:</div>
                        <div class="flex items-start space-x-3">
                            <div v-if="item.icon" class="flex-shrink-0 w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900">{{ item.title || 'Titel...' }}</h4>
                                <p class="text-sm text-gray-600 mt-1">{{ item.description || 'Beschreibung...' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </DraggableList>
    </div>
</template>
```

**Pfad:** `resources/js/Pages/Admin/LandingPage/Editors/FeaturesEditor.vue`

---

### 2. TestimonialsEditor.vue

```vue
<script setup>
import { computed } from 'vue';
import DraggableList from '@/Components/Landing/DraggableList.vue';
import ImageUploader from '@/Components/Landing/ImageUploader.vue';
import StarRating from '@/Components/Landing/StarRating.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({ modelValue: Object, errors: Object });
const emit = defineEmits(['update:modelValue']);

const content = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

if (!content.value.headline) {
    content.value = {
        headline: content.value.headline || 'Was unsere Kunden sagen',
        items: content.value.items || []
    };
}

const addTestimonial = () => {
    content.value.items.push({
        name: '',
        role: '',
        club: '',
        quote: '',
        rating: 5,
        image: null
    });
};

const removeTestimonial = (index) => {
    content.value.items.splice(index, 1);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Headline -->
        <div>
            <InputLabel value="Überschrift *" />
            <TextInput v-model="content.headline" class="mt-1 block w-full" maxlength="255" />
        </div>

        <!-- Testimonials List -->
        <DraggableList
            v-model:items="content.items"
            add-label="+ Testimonial hinzufügen"
            :min-items="1"
            :max-items="10"
            @add="addTestimonial"
            @remove="removeTestimonial"
        >
            <template #default="{ item, index }">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Name *" />
                            <TextInput v-model="item.name" class="mt-1 block w-full" maxlength="100" />
                        </div>
                        <div>
                            <InputLabel value="Rolle *" />
                            <TextInput v-model="item.role" class="mt-1 block w-full" maxlength="100" />
                        </div>
                        <div>
                            <InputLabel value="Verein *" />
                            <TextInput v-model="item.club" class="mt-1 block w-full" maxlength="100" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Zitat *" />
                        <textarea v-model="item.quote" rows="3" class="mt-1 block w-full border-gray-300 rounded-md" maxlength="300"></textarea>
                        <p class="text-xs text-gray-500 mt-1">{{ (item.quote || '').length }} / 300</p>
                    </div>

                    <StarRating v-model="item.rating" label="Bewertung" />

                    <ImageUploader v-model="item.image" label="Bild (optional)" />

                    <!-- Preview -->
                    <div class="p-4 bg-gray-50 border rounded-lg">
                        <div class="flex items-start space-x-4">
                            <div v-if="item.image" class="flex-shrink-0 w-16 h-16 rounded-full bg-gray-200 overflow-hidden">
                                <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="flex-shrink-0 w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                                {{ (item.name || 'X').charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm italic text-gray-600">"{{ item.quote || 'Zitat...' }}"</p>
                                <div class="mt-2 flex items-center space-x-2">
                                    <div class="flex space-x-1">
                                        <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= item.rating ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-sm font-semibold mt-1">{{ item.name || 'Name' }}</p>
                                <p class="text-xs text-gray-500">{{ item.role || 'Rolle' }} • {{ item.club || 'Verein' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </DraggableList>
    </div>
</template>
```

**Pfad:** `resources/js/Pages/Admin/LandingPage/Editors/TestimonialsEditor.vue`

---

### 3. PricingEditor.vue (Komplexeste - Nested Arrays)

```vue
<script setup>
import { computed } from 'vue';
import DraggableList from '@/Components/Landing/DraggableList.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({ modelValue: Object, errors: Object });
const emit = defineEmits(['update:modelValue']);

const content = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

if (!content.value.headline) {
    content.value = {
        headline: content.value.headline || 'Transparent und fair',
        subheadline: content.value.subheadline || 'Wähle den Plan, der zu deinem Verein passt',
        items: content.value.items || []
    };
}

const addPlan = () => {
    content.value.items.push({
        name: '',
        price: '',
        period: 'Monat',
        description: '',
        features: [''],
        cta_text: 'Jetzt starten',
        cta_link: '/register',
        popular: false
    });
};

const removePlan = (index) => {
    content.value.items.splice(index, 1);
};

const addFeature = (plan) => {
    if (!plan.features) plan.features = [];
    plan.features.push('');
};

const removeFeature = (plan, featureIndex) => {
    plan.features.splice(featureIndex, 1);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Headlines -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel value="Überschrift *" />
                <TextInput v-model="content.headline" class="mt-1 block w-full" maxlength="255" />
            </div>
            <div>
                <InputLabel value="Unterüberschrift *" />
                <TextInput v-model="content.subheadline" class="mt-1 block w-full" maxlength="500" />
            </div>
        </div>

        <!-- Pricing Plans -->
        <DraggableList
            v-model:items="content.items"
            add-label="+ Preisplan hinzufügen"
            :min-items="1"
            :max-items="6"
            @add="addPlan"
            @remove="removePlan"
        >
            <template #default="{ item: plan, index }">
                <div class="space-y-4">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Plan-Name *" />
                            <TextInput v-model="plan.name" class="mt-1 block w-full" maxlength="100" />
                        </div>
                        <div>
                            <InputLabel value="Preis *" />
                            <TextInput v-model="plan.price" class="mt-1 block w-full" placeholder="29,99" />
                        </div>
                        <div>
                            <InputLabel value="Zeitraum" />
                            <select v-model="plan.period" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="Monat">Monat</option>
                                <option value="Jahr">Jahr</option>
                                <option value="">Custom</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center">
                                <Checkbox v-model:checked="plan.popular" />
                                <span class="ml-2 text-sm">Beliebt</span>
                            </label>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel value="Beschreibung" />
                        <TextInput v-model="plan.description" class="mt-1 block w-full" maxlength="200" />
                    </div>

                    <!-- Features (Nested Array) -->
                    <div>
                        <InputLabel value="Features *" />
                        <div class="space-y-2 mt-2">
                            <div v-for="(feature, fIndex) in plan.features" :key="fIndex" class="flex space-x-2">
                                <TextInput v-model="plan.features[fIndex]" class="flex-1" placeholder="z.B. 10 Teams" maxlength="200" />
                                <button
                                    v-if="plan.features.length > 1"
                                    type="button"
                                    @click="removeFeature(plan, fIndex)"
                                    class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <button type="button" @click="addFeature(plan)" class="text-sm text-indigo-600 hover:text-indigo-700">+ Feature hinzufügen</button>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Button Text" />
                            <TextInput v-model="plan.cta_text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Button Link" />
                            <TextInput v-model="plan.cta_link" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="p-6 bg-white border-2 rounded-xl" :class="plan.popular ? 'border-indigo-600' : 'border-gray-200'">
                        <span v-if="plan.popular" class="inline-block px-3 py-1 bg-indigo-600 text-white text-xs font-semibold rounded-full mb-3">Beliebt</span>
                        <h3 class="text-2xl font-bold">{{ plan.name || 'Plan-Name' }}</h3>
                        <p class="text-gray-600 mt-1">{{ plan.description || 'Beschreibung' }}</p>
                        <div class="mt-4 flex items-baseline">
                            <span class="text-4xl font-bold">{{ plan.price || '0' }}€</span>
                            <span class="text-gray-500 ml-2">/{{ plan.period || 'Monat' }}</span>
                        </div>
                        <ul class="mt-6 space-y-3">
                            <li v-for="(feature, fIdx) in plan.features.filter(f => f)" :key="fIdx" class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-700">{{ feature }}</span>
                            </li>
                        </ul>
                        <button class="mt-6 w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700">
                            {{ plan.cta_text || 'Jetzt starten' }}
                        </button>
                    </div>
                </div>
            </template>
        </DraggableList>
    </div>
</template>
```

**Pfad:** `resources/js/Pages/Admin/LandingPage/Editors/PricingEditor.vue`

---

## Backend: FileUploadController.php

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FileUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage landing page');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:2048'
        ]);

        $user = auth()->user();
        $tenantId = $user->tenant_id ?? 'global';
        $file = $request->file('image');

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->extension();
        $directory = "landing/{$tenantId}";
        $path = "{$directory}/{$filename}";

        // Resize & optimize image
        $image = Image::make($file);

        // Resize to max 1200px width while maintaining aspect ratio
        $image->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Convert to WebP for better compression (optional)
        $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        $webpPath = "{$directory}/{$webpFilename}";

        // Save both formats
        Storage::disk('public')->put($path, $image->encode());
        Storage::disk('public')->put($webpPath, $image->encode('webp', 85));

        return response()->json([
            'success' => true,
            'url' => Storage::url($webpPath), // Prefer WebP
            'fallback_url' => Storage::url($path),
            'filename' => $webpFilename,
            'size' => Storage::disk('public')->size($webpPath),
        ]);
    }

    public function deleteImage(Request $request, string $filename)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? 'global';
        $path = "landing/{$tenantId}/{$filename}";

        // Security: Ensure user can only delete their tenant's images
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        Storage::disk('public')->delete($path);

        return response()->json(['success' => true]);
    }
}
```

**Pfad:** `app/Http/Controllers/FileUploadController.php`

---

## Routes hinzufügen (web.php)

Im Admin-Bereich hinzufügen:

```php
// In routes/web.php im admin middleware group
Route::post('/admin/landing-page/upload-image', [\App\Http\Controllers\FileUploadController::class, 'uploadImage'])->name('admin.landing-page.upload-image');
Route::delete('/admin/landing-page/delete-image/{filename}', [\App\Http\Controllers\FileUploadController::class, 'deleteImage'])->name('admin.landing-page.delete-image');
```

---

## Integration in EditSection.vue

Fügen Sie nach Zeile ~180 (nach CTA Section) hinzu:

```vue
<!-- FAQ Section Editor -->
<FaqEditor
    v-if="section === 'faq'"
    v-model="form.content"
    :errors="form.errors"
/>

<!-- Features Section Editor -->
<FeaturesEditor
    v-if="section === 'features'"
    v-model="form.content"
    :errors="form.errors"
/>

<!-- Testimonials Section Editor -->
<TestimonialsEditor
    v-if="section === 'testimonials'"
    v-model="form.content"
    :errors="form.errors"
/>

<!-- Pricing Section Editor -->
<PricingEditor
    v-if="section === 'pricing'"
    v-model="form.content"
    :errors="form.errors"
/>
```

Und imports oben hinzufügen:

```vue
import FaqEditor from './Editors/FaqEditor.vue';
import FeaturesEditor from './Editors/FeaturesEditor.vue';
import TestimonialsEditor from './Editors/TestimonialsEditor.vue';
import PricingEditor from './Editors/PricingEditor.vue';
```

---

## Composer & NPM

```bash
# Intervention Image für Bild-Processing
composer require intervention/image

# NPM Packages (wenn noch nicht geschehen)
npm install @heroicons/vue sortablejs vue-draggable-plus @vueuse/core

# Storage Link
php artisan storage:link

# Build
npm run build
```

---

## Testing Checklist

- [ ] FaqEditor: Add/Remove/Drag funktioniert
- [ ] FeaturesEditor: IconPicker Integration
- [ ] TestimonialsEditor: ImageUploader + StarRating
- [ ] PricingEditor: Nested Features Array
- [ ] Image Upload: Erfolgreich mit WebP-Konvertierung
- [ ] Alle Editoren speichern korrekt
- [ ] Veröffentlichen funktioniert
- [ ] Landing Page zeigt neue Inhalte

---

Alle Templates sind vollständig und ready-to-use! Einfach kopieren und einfügen.
