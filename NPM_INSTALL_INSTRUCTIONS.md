# NPM Dependencies für Visuelle Landing Page Editoren

Führen Sie diese Befehle aus, um die erforderlichen NPM-Packages zu installieren:

```bash
# Heroicons - Icon Library von Tailwind Labs
npm install @heroicons/vue

# Sortable.js & Vue Wrapper - Drag & Drop Funktionalität
npm install sortablejs
npm install vue-draggable-plus

# VueUse - Collection of Vue Composition Utilities
npm install @vueuse/core

# Nach Installation: Frontend neu kompilieren
npm run dev
# ODER für Production:
npm run build
```

## Package Details

### @heroicons/vue (^2.0.0)
- **Verwendung**: IconPicker Component
- **Features**: 200+ SVG Icons (Outline & Solid)
- **Docs**: https://heroicons.com/

### sortablejs (^1.15.0) + vue-draggable-plus (^0.5.0)
- **Verwendung**: DraggableList Component für Features, Pricing, Testimonials, FAQ
- **Features**: Drag & Drop, Touch Support, Animations
- **Docs**: https://github.com/Alfred-Skyblue/vue-draggable-plus

### @vueuse/core (^10.0.0)
- **Verwendung**: Utility Composables (useDropZone für ImageUploader)
- **Features**: 200+ Vue Composition API utilities
- **Docs**: https://vueuse.org/

## Verifikation

Nach Installation prüfen:

```bash
npm list @heroicons/vue
npm list sortablejs vue-draggable-plus
npm list @vueuse/core
```
