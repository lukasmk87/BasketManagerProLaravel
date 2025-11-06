# Landing Page Visuelle Editoren - Implementierungszusammenfassung

## ✅ Bereits erstellt

### Shared Components
1. **IconPicker.vue** - ✅ Vollständig
   - 30+ vordefinierte Icons
   - Suchfunktion
   - Live-Vorschau
   - Pfad: `resources/js/Components/Landing/IconPicker.vue`

2. **ImageUploader.vue** - ✅ Vollständig
   - Dual-Mode: URL + File Upload
   - File-Validierung (Typ, Größe)
   - Preview mit Remove-Funktion
   - Pfad: `resources/js/Components/Landing/ImageUploader.vue`

### Dokumentation
- **NPM_INSTALL_INSTRUCTIONS.md** - Installationsanleitung für Dependencies

---

## 🚧 Noch zu implementieren

### Shared Components (3 verbleibend)
3. **StarRating.vue** - Für Testimonials (1-5 Sterne Picker)
4. **DraggableList.vue** - Wrapper für Sortable.js
5. *(Optional)* **PreviewCard Components** - Live-Vorschau-Komponenten

### Section Editors (4 große Komponenten)
1. **FaqEditor.vue** - Einfachste Section (nur Text)
2. **FeaturesEditor.vue** - Mit IconPicker Integration
3. **TestimonialsEditor.vue** - Mit ImageUploader + StarRating
4. **PricingEditor.vue** - Komplexeste Section (nested arrays)

### Backend
- **FileUploadController.php** - Image Upload Handling
- **Routes** - 2 Upload-Routes hinzufügen
- **Validation** - Erweiterte Validierung für alle Sections

### Integration
- **EditSection.vue** - Integration der visuellen Editoren

---

## 📦 NPM Dependencies (WICHTIG!)

Führen Sie zuerst aus:
```bash
npm install @heroicons/vue sortablejs vue-draggable-plus @vueuse/core
npm run build
```

---

## 🎯 Nächste Schritte (Priorität)

### PHASE 1: Shared Components fertigstellen (1 Stunde)
```bash
# StarRating.vue erstellen
# DraggableList.vue erstellen
```

### PHASE 2: Einfachster Editor als Proof of Concept (2 Stunden)
```bash
# FaqEditor.vue erstellen (nur Text, keine Icons/Bilder)
# In EditSection.vue integrieren
# Testen
```

### PHASE 3: Backend Upload System (1 Stunde)
```bash
# FileUploadController.php
# Routes hinzufügen
# Storage konfigurieren
```

### PHASE 4: Komplexere Editoren (4-6 Stunden)
```bash
# FeaturesEditor.vue (mit IconPicker)
# TestimonialsEditor.vue (mit ImageUploader + StarRating)
# PricingEditor.vue (nested arrays)
```

---

## 💡 Quick Implementation Guide

### StarRating.vue (einfach)
```vue
<template>
  <div class="flex space-x-1">
    <button v-for="star in 5" @click="rating = star">
      <svg :class="star <= rating ? 'text-yellow-400' : 'text-gray-300'">
        <!-- Star icon path -->
      </svg>
    </button>
  </div>
</template>
```

### DraggableList.vue (mit vue-draggable-plus)
```vue
<script setup>
import { VueDraggable } from 'vue-draggable-plus'
</script>

<template>
  <VueDraggable v-model="items" handle=".handle">
    <div v-for="(item, index) in items">
      <span class="handle">⋮⋮</span>
      <slot :item="item" :index="index"></slot>
      <button @click="remove(index)">Remove</button>
    </div>
  </VueDraggable>
</template>
```

### FaqEditor.vue (Beispiel)
```vue
<template>
  <div>
    <TextInput v-model="form.content.headline" label="Headline" />

    <DraggableList v-model="form.content.items">
      <template #default="{ item, index }">
        <div class="border p-4 rounded">
          <TextInput v-model="item.question" label="Frage" />
          <textarea v-model="item.answer" label="Antwort" />
        </div>
      </template>
    </DraggableList>

    <button @click="addFaq">+ FAQ hinzufügen</button>
  </div>
</template>
```

---

## 🔧 Backend - FileUploadController.php

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FileUploadController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $tenantId = auth()->user()->tenant_id ?? 'global';
        $file = $request->file('image');

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->extension();
        $path = "landing/{$tenantId}/{$filename}";

        // Resize & optimize
        $image = Image::make($file)->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Save to storage
        Storage::disk('public')->put($path, $image->encode());

        return response()->json([
            'url' => Storage::url($path),
            'filename' => $filename,
        ]);
    }

    public function deleteImage(Request $request, $filename)
    {
        $tenantId = auth()->user()->tenant_id ?? 'global';
        $path = "landing/{$tenantId}/{$filename}";

        Storage::disk('public')->delete($path);

        return response()->json(['success' => true]);
    }
}
```

**Routes hinzufügen in web.php:**
```php
Route::post('/admin/landing-page/upload-image', [FileUploadController::class, 'uploadImage'])->name('admin.landing-page.upload-image');
Route::delete('/admin/landing-page/delete-image/{filename}', [FileUploadController::class, 'deleteImage'])->name('admin.landing-page.delete-image');
```

---

## ⚠️ Wichtige Hinweise

1. **NPM Packages installieren FIRST!** Sonst kompiliert nichts.
2. **Storage Link:** `php artisan storage:link` ausführen
3. **Intervention Image:** `composer require intervention/image` für Bild-Processing
4. **Start mit FAQ:** Einfachster Editor zum Testen der Infrastruktur
5. **Inkrementell:** Einen Editor nach dem anderen testen

---

## 📊 Geschätzter Zeitaufwand

- Shared Components (2 verbleibend): **1h**
- FAQ Editor + Integration: **2h**
- Backend Upload: **1h**
- Features Editor: **1.5h**
- Testimonials Editor: **1.5h**
- Pricing Editor: **2h**
- Testing & Polish: **2h**

**Total: ~11 Stunden** (1-2 Arbeitstage)

---

## ✨ Quick Win Strategy

Wenn Zeit knapp:
1. ✅ Start mit **FaqEditor** (kein Icon/Bild, einfachst)
2. ✅ Features **ohne Icons** (erst Text, später IconPicker)
3. ✅ Testimonials **ohne Bilder** (erst Text, später ImageUploader)
4. ✅ Pricing als letztes (komplexest)

Dann iterativ verbessern!
