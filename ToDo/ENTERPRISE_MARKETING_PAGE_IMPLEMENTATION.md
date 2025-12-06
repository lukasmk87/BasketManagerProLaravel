# Enterprise/White-Label Marketing-Seite für BasketManager Pro

## Ziel
Marketing-Seite für Verbände und große Vereine, die BasketManager Pro unter eigener Marke hosten möchten.

---

## URL & Routing

**Haupt-URL:** `/enterprise`
**Redirects:** `/white-label`, `/fuer-verbaende` → `/enterprise`

```php
// routes/web.php (nach Zeile 48, roadmap route)
Route::get('/enterprise', [EnterpriseController::class, 'index'])->name('enterprise');
Route::post('/enterprise/contact', [EnterpriseLeadController::class, 'store'])
    ->name('enterprise.contact')->middleware('throttle:5,1');
Route::redirect('/white-label', '/enterprise', 301);
Route::redirect('/fuer-verbaende', '/enterprise', 301);
```

---

## Neue Dateien

### 1. Controller
- `app/Http/Controllers/EnterpriseController.php` - Zeigt Seite mit DB-Content
- `app/Http/Controllers/EnterpriseLeadController.php` - Verarbeitet Lead-Formular

### 2. Model & Migration
- `app/Models/EnterpriseLead.php` - Lead-Daten
- `database/migrations/..._create_enterprise_leads_table.php`

### 3. View
- `resources/views/enterprise.blade.php` - Haupt-Template (Pattern von `landing.blade.php`)

### 4. E-Mails
- `app/Mail/EnterpriseLeadNotification.php` - Admin-Benachrichtigung
- `app/Mail/EnterpriseLeadConfirmation.php` - Bestätigung an Lead
- `resources/views/emails/enterprise-lead-notification.blade.php`
- `resources/views/emails/enterprise-lead-confirmation.blade.php`

### 5. Admin-Seiten (Vue/Inertia) - NUR Super Admin
- `resources/js/Pages/Admin/Enterprise/Index.vue` - Lead-Übersicht
- `resources/js/Pages/Admin/Enterprise/Show.vue` - Lead-Details
- `resources/js/Pages/Admin/EnterprisePage/Index.vue` - Enterprise Page Sections
- `resources/js/Pages/Admin/EnterprisePage/EditSection.vue` - Section Editor

### 6. Admin Controller (Super Admin)
- `app/Http/Controllers/Admin/EnterprisePageController.php` - Content-Verwaltung
- `app/Http/Controllers/Admin/EnterpriseLeadController.php` - Lead-Verwaltung

### 7. LandingPageService erweitern
- Neue Section-Constants in `app/Services/LandingPageService.php`

---

## Datenbank

### enterprise_leads Tabelle
```php
Schema::create('enterprise_leads', function (Blueprint $table) {
    $table->id();
    $table->string('organization_name');
    $table->enum('organization_type', ['verband', 'grossverein', 'akademie', 'sonstige']);
    $table->string('contact_name');
    $table->string('email');
    $table->string('phone')->nullable();
    $table->string('club_count')->nullable();     // 1-10, 11-50, 51-100, 100+
    $table->string('team_count')->nullable();
    $table->text('message')->nullable();
    $table->boolean('gdpr_accepted')->default(false);
    $table->boolean('newsletter_optin')->default(false);
    $table->enum('status', ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'])->default('new');
    $table->text('notes')->nullable();
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('contacted_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### LandingPageContent erweitern
Neue Sections für Enterprise-Seite (nutzt bestehendes `landing_page_contents` mit `page_type` = 'enterprise'):
- `enterprise_hero`
- `enterprise_audience`
- `enterprise_whitelabel`
- `enterprise_multiclub`
- `enterprise_federation`
- `enterprise_usecases`
- `enterprise_pricing`
- `enterprise_faq`

---

## Seiten-Sektionen (Deutsch)

### 1. Hero
- **Titel:** "Ihre Basketball-Plattform. Ihre Marke."
- **Untertitel:** "Die vollständig anpassbare White-Label-Lösung für Verbände und große Organisationen"
- **Stats:** 99.9% SLA | Unbegrenzte Teams | Custom Domain | Dedizierter Support
- **CTAs:** "Beratungsgespräch vereinbaren" / "Preise anfragen"

### 2. Zielgruppen (2-spaltig)
**Für Verbände:**
- DBB Landesverbände, Regionalverbände
- Liga-Management, Spieler-Lizenzierung, Verbands-Integration

**Für große Organisationen:**
- Multi-Team Vereine, Akademien
- Marken-Integration, Multi-Club-Verwaltung, API-Anbindung

### 3. White-Label Features
- Eigene Domain/Subdomain
- Logo-Branding überall
- Anpassbare Farbschemata
- Gebrandete E-Mails
- Personalisierte Landing Pages
- Gebrandete PDF-Exports

### 4. Multi-Club Management
- Zentrale Benutzerverwaltung
- Vereinsübergreifende Statistiken
- Delegierte Administration
- Verbands-weite Turniere

### 5. Federation Integration
- DBB API Integration
- FIBA Europe Integration
- Automatische Lizenzprüfung
- Spielergebnis-Übermittlung

### 6. Case Study: Landesverband NRW (Platzhalter)
- 52 Vereine, 200+ Teams
- Challenge → Lösung → Ergebnis
- Zitat vom Verband

### 7. Enterprise Pricing
- "Ab €499/Monat" (individuell)
- Feature-Vergleich: Professional vs Enterprise
- "Preis nach Vereinsanzahl"

### 8. Lead-Formular
Felder: Organisation, Typ (Dropdown), Kontakt, E-Mail, Telefon, Anzahl Vereine, Anzahl Teams, Nachricht, DSGVO-Checkbox, Newsletter-Opt-in

### 9. Enterprise FAQ
- Einrichtungsdauer
- Datenmigration
- Vertragslaufzeit
- SLA-Garantien
- DSGVO-Konformität

---

## Implementierungs-Reihenfolge

### Phase 1: Basis-Infrastruktur
- [ ] Migration `enterprise_leads` erstellen
- [ ] Model `EnterpriseLead` erstellen
- [ ] `EnterpriseController` erstellen
- [ ] Route `/enterprise` hinzufügen

### Phase 2: Blade-Template
- [ ] `enterprise.blade.php` erstellen (Struktur von `landing.blade.php`)
- [ ] Alle Sektionen mit statischem Content implementieren
- [ ] Navigation in `landing.blade.php` erweitern (Link zu Enterprise)

### Phase 3: Lead-Formular
- [ ] `EnterpriseLeadController` mit Validierung
- [ ] E-Mail-Templates erstellen
- [ ] Formular in Template einbauen

### Phase 4: DB-gesteuerte Inhalte
- [ ] `LandingPageService` um Enterprise-Sections erweitern
- [ ] Content-Loading im Controller implementieren
- [ ] Fallback-Defaults definieren

### Phase 5: Admin-Interface
- [ ] Admin-Route für Leads
- [ ] Vue-Komponente für Lead-Übersicht
- [ ] Lead-Detail-Ansicht mit Status-Management

---

## Zugriffskontrolle (NUR Super Admin)

**Wichtig:** Die Verwaltung der Enterprise-Seite und Leads ist **ausschließlich für Super Admins** zugänglich.

### Admin-Routes (nur Super Admin)
```php
// routes/web.php - Im Super Admin Bereich
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->group(function () {
    // Enterprise Landing Page Content Management
    Route::get('/enterprise-page', [Admin\EnterprisePageController::class, 'index'])
        ->name('admin.enterprise-page.index');
    Route::get('/enterprise-page/{section}/edit', [Admin\EnterprisePageController::class, 'edit'])
        ->name('admin.enterprise-page.edit');
    Route::put('/enterprise-page/{section}', [Admin\EnterprisePageController::class, 'update'])
        ->name('admin.enterprise-page.update');
    Route::post('/enterprise-page/{section}/publish', [Admin\EnterprisePageController::class, 'publish'])
        ->name('admin.enterprise-page.publish');

    // Enterprise Leads Management
    Route::resource('enterprise-leads', Admin\EnterpriseLeadController::class)
        ->names('admin.enterprise-leads');
});
```

### Middleware-Absicherung
- `role:super_admin` Middleware für alle Admin-Routes
- Enterprise Content immer mit `tenant_id = null` (global, nicht tenant-spezifisch)
- Lead-Zuweisung nur an Super Admins möglich

### LandingPageService Anpassung
```php
// Nur Super Admin kann Enterprise-Content bearbeiten
public function canEditEnterpriseContent(): bool
{
    return auth()->user()?->hasRole('super_admin') ?? false;
}

// Enterprise-Content ist immer global (tenant_id = null)
public function getEnterpriseContent(string $section, string $locale = 'de'): array
{
    return $this->getContent(null, $section, $locale, 'enterprise');
}
```

---

## Kritische Dateien (zu modifizieren)

| Datei | Änderung |
|-------|----------|
| `routes/web.php` | Neue Public Routes + Super Admin Routes hinzufügen |
| `app/Services/LandingPageService.php` | Enterprise-Sections + Super Admin Check |
| `resources/views/landing.blade.php` | Nav-Link zu Enterprise |
| `config/tenants.php` | Referenz für Enterprise-Features |

---

## Design-Vorgaben

- **Farbschema:** Orange-Gradient (#f97316 → #ea580c) - wie Haupt-Landing
- **Font:** Figtree (via bunny.net)
- **CSS:** Tailwind CDN + Custom Styles von `landing.blade.php`
- **Responsive:** Mobile-first mit Hamburger-Menü
- **Animationen:** `.animate-fade-in-up`, Hover-Effekte auf Cards
