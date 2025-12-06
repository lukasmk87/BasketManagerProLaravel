# Plan: Enterprise-Admin Features

## Ziel
1. **Enterprise-Leads im Admin-Panel verlinken** (Navigation fehlt)
2. **Enterprise-Seite Texte editierbar machen** (neues CMS mit separatem Model)

---

## Teil 1: Enterprise-Leads Navigation hinzufügen

### Datei zu ändern:
- `resources/js/Layouts/AdminLayout.vue` (Zeile 81)

### Änderung:
Neuen Eintrag im `navigationItems` Array nach "Landing Page" hinzufügen:
```javascript
{
    name: 'Enterprise Leads',
    route: 'admin.enterprise-leads.index',
    icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
},
```

---

## Teil 2: Enterprise-Seite CMS (Separates System)

### Neue Dateien erstellen:

| Typ | Pfad |
|-----|------|
| Migration | `database/migrations/*_create_enterprise_page_contents_table.php` |
| Model | `app/Models/EnterprisePageContent.php` |
| Service | `app/Services/EnterprisePageService.php` |
| Controller | `app/Http/Controllers/Admin/EnterprisePageController.php` |
| Vue Index | `resources/js/Pages/Admin/EnterprisePage/Index.vue` |
| Vue Edit | `resources/js/Pages/Admin/EnterprisePage/EditSection.vue` |
| Vue Preview | `resources/js/Pages/Admin/EnterprisePage/Preview.vue` |
| Editors | `resources/js/Pages/Admin/EnterprisePage/Editors/*.vue` |

### Bestehende Dateien als Vorlage:
- `app/Models/LandingPageContent.php` → Vorlage für EnterprisePageContent
- `app/Services/LandingPageService.php` → Vorlage für EnterprisePageService
- `app/Http/Controllers/LandingPageController.php` → Vorlage für EnterprisePageController
- `resources/js/Pages/Admin/LandingPage/` → Vorlage für Vue-Komponenten

### 10 Sections für Enterprise-Seite:

| Section | Beschreibung | Felder |
|---------|--------------|--------|
| `seo` | SEO Metadaten | title, description, keywords |
| `hero` | Hero-Bereich | headline, subheadline, cta_primary_text, cta_primary_link, cta_secondary_text, cta_secondary_link, stats[] |
| `audience` | Zielgruppen | headline, verbaende{title, subtitle, items[]}, organisationen{title, subtitle, items[]} |
| `whitelabel` | White-Label Features | headline, subheadline, items[]{icon, title, description} |
| `multiclub` | Multi-Club Management | headline, subheadline, items[]{title, description} |
| `federation` | Verbandsintegration | headline, subheadline, items[]{icon, title, description, features[]} |
| `usecases` | Erfolgsgeschichten | headline, items[]{name, type, stats, challenge, solution, result, quote, quote_author, quote_role} |
| `pricing` | Enterprise Preise | headline, subheadline, base_price, base_price_period, base_price_note, comparison{professional, enterprise}, features[], cta_text, cta_link |
| `faq` | FAQ | headline, items[]{question, answer} |
| `contact` | Kontakt Headlines | headline, subheadline, success_message |

### Routes hinzufügen in `routes/admin.php`:
```php
use App\Http\Controllers\Admin\EnterprisePageController;

// Enterprise Page Content Management
Route::prefix('enterprise-page')->name('enterprise-page.')->group(function () {
    Route::get('/', [EnterprisePageController::class, 'index'])->name('index');
    Route::get('/{section}/edit', [EnterprisePageController::class, 'edit'])->name('edit');
    Route::put('/{section}', [EnterprisePageController::class, 'update'])->name('update');
    Route::post('/{section}/publish', [EnterprisePageController::class, 'publish'])->name('publish');
    Route::post('/{section}/unpublish', [EnterprisePageController::class, 'unpublish'])->name('unpublish');
    Route::get('/{section}/preview', [EnterprisePageController::class, 'preview'])->name('preview');
    Route::post('/{section}/copy-locale', [EnterprisePageController::class, 'copyToLocale'])->name('copy-locale');
});
```

### Navigation erweitern in `AdminLayout.vue`:
```javascript
{
    name: 'Enterprise Seite',
    route: 'admin.enterprise-page.index',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
},
```

### EnterpriseController anpassen:
- `app/Http/Controllers/EnterpriseController.php`
- Hardcodierte `getEnterpriseContent()` durch Service-Aufruf ersetzen:
```php
public function __construct(private EnterprisePageService $enterprisePageService) {}

public function index(): View
{
    $content = $this->enterprisePageService->getAllContent(null, app()->getLocale());
    // ...
}
```

---

## Implementierungsreihenfolge

### Phase 1: Quick Win - Enterprise Leads Navigation
1. [ ] `AdminLayout.vue` - Enterprise Leads Link hinzufügen

### Phase 2: Backend
2. [ ] Migration erstellen (`php artisan make:migration create_enterprise_page_contents_table`)
3. [ ] Migration ausführen (`php artisan migrate`)
4. [ ] Model `EnterprisePageContent` erstellen
5. [ ] Service `EnterprisePageService` erstellen (mit Defaults aus EnterpriseController)
6. [ ] Controller `EnterprisePageController` erstellen
7. [ ] Routes in `admin.php` hinzufügen

### Phase 3: Frontend
8. [ ] Vue Index-Seite erstellen (`Pages/Admin/EnterprisePage/Index.vue`)
9. [ ] Vue Edit-Seite erstellen (`Pages/Admin/EnterprisePage/EditSection.vue`)
10. [ ] Section-Editoren erstellen:
    - [ ] `Editors/SeoEditor.vue`
    - [ ] `Editors/HeroEditor.vue`
    - [ ] `Editors/AudienceEditor.vue`
    - [ ] `Editors/WhitelabelEditor.vue`
    - [ ] `Editors/MulticlubEditor.vue`
    - [ ] `Editors/FederationEditor.vue`
    - [ ] `Editors/UsecasesEditor.vue`
    - [ ] `Editors/PricingEditor.vue`
    - [ ] `Editors/FaqEditor.vue`
    - [ ] `Editors/ContactEditor.vue`
11. [ ] Preview-Seite erstellen (`Pages/Admin/EnterprisePage/Preview.vue`)

### Phase 4: Integration
12. [ ] Navigation - Enterprise Seite Link in `AdminLayout.vue` hinzufügen
13. [ ] `EnterpriseController` anpassen (Service nutzen statt hardcodierte Daten)

### Phase 5: Testen
14. [ ] Admin-Panel Navigation testen
15. [ ] CMS-Funktionalität testen (CRUD für alle 10 Sections)
16. [ ] Enterprise-Seite im Frontend testen (dynamische Inhalte)

---

## Kritische Dateien

### Zu ändern:
| Datei | Änderung |
|-------|----------|
| `resources/js/Layouts/AdminLayout.vue` | 2 neue Nav-Items (Enterprise Leads + Enterprise Seite) |
| `routes/admin.php` | Enterprise Page Routes hinzufügen |
| `app/Http/Controllers/EnterpriseController.php` | Service integrieren |

### Als Vorlage verwenden:
| Vorlage | Für |
|---------|-----|
| `app/Models/LandingPageContent.php` | EnterprisePageContent Model |
| `app/Services/LandingPageService.php` | EnterprisePageService |
| `app/Http/Controllers/LandingPageController.php` | EnterprisePageController |
| `resources/js/Pages/Admin/LandingPage/Index.vue` | EnterprisePage Index |
| `resources/js/Pages/Admin/LandingPage/EditSection.vue` | EnterprisePage EditSection |
| `resources/js/Pages/Admin/LandingPage/Editors/*.vue` | Section-Editoren |

---

## Migration Schema

```php
Schema::create('enterprise_page_contents', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
    $table->string('section'); // seo, hero, audience, whitelabel, multiclub, federation, usecases, pricing, faq, contact
    $table->string('locale', 2)->default('de');
    $table->json('content');
    $table->boolean('is_published')->default(false);
    $table->timestamp('published_at')->nullable();
    $table->timestamps();

    $table->index(['tenant_id', 'section', 'locale']);
    $table->index(['section', 'is_published']);
    $table->unique(['tenant_id', 'section', 'locale']);
});
```

---

## Model Konstanten

```php
class EnterprisePageContent extends Model
{
    public const SECTIONS = [
        'seo',        // SEO Metadaten
        'hero',       // Hero-Bereich mit Stats
        'audience',   // Zielgruppen (Verbände & Organisationen)
        'whitelabel', // White-Label Features
        'multiclub',  // Multi-Club Management
        'federation', // Verbandsintegration
        'usecases',   // Erfolgsgeschichten
        'pricing',    // Enterprise Pricing
        'faq',        // FAQ
        'contact',    // Kontakt Headlines
    ];

    public const SECTION_LABELS = [
        'seo' => 'SEO Metadaten',
        'hero' => 'Hero Bereich',
        'audience' => 'Zielgruppen',
        'whitelabel' => 'White-Label Features',
        'multiclub' => 'Multi-Club Management',
        'federation' => 'Verbandsintegration',
        'usecases' => 'Erfolgsgeschichten',
        'pricing' => 'Enterprise Preise',
        'faq' => 'FAQ',
        'contact' => 'Kontakt',
    ];
}
```
