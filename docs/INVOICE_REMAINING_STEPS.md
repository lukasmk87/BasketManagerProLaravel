# Rechnungszahlung - Implementierungsstatus

## Status: Vollständig implementiert

Alle Komponenten für das Rechnungszahlungssystem sind implementiert und einsatzbereit.

---

## Backend-Komponenten

| Status | Komponente | Datei |
|--------|------------|-------|
| ✅ | Migration | `database/migrations/*_create_club_invoices_table.php` |
| ✅ | Migration | `database/migrations/*_create_club_invoice_requests_table.php` |
| ✅ | Migration | `database/migrations/*_add_payment_method_type_to_clubs_table.php` |
| ✅ | Model | `app/Models/ClubInvoice.php` |
| ✅ | Model | `app/Models/ClubInvoiceRequest.php` |
| ✅ | Model erweitert | `app/Models/Club.php` |
| ✅ | Config | `config/invoices.php` |
| ✅ | Service | `app/Services/Invoice/ClubInvoiceService.php` |
| ✅ | Service | `app/Services/Invoice/ClubInvoicePdfService.php` |
| ✅ | Service | `app/Services/Invoice/ClubInvoiceNotificationService.php` |
| ✅ | Controller | `app/Http/Controllers/Admin/ClubInvoiceController.php` |
| ✅ | Controller | `app/Http/Controllers/Admin/ClubInvoiceRequestController.php` |
| ✅ | Controller | `app/Http/Controllers/Stripe/ClubCheckoutController.php` |
| ✅ | Form Requests | `app/Http/Requests/Admin/CreateClubInvoiceRequest.php` |
| ✅ | Form Requests | `app/Http/Requests/Admin/UpdateClubInvoiceRequest.php` |
| ✅ | Form Requests | `app/Http/Requests/Admin/MarkInvoicePaidRequest.php` |
| ✅ | Routes | `routes/admin.php` |
| ✅ | Routes | `routes/club_checkout.php` |

---

## Mail-System

| Status | Komponente | Datei |
|--------|------------|-------|
| ✅ | Mail | `app/Mail/ClubInvoice/InvoiceMail.php` |
| ✅ | Mail | `app/Mail/ClubInvoice/ReminderMail.php` |
| ✅ | Mail | `app/Mail/ClubInvoice/PaymentConfirmationMail.php` |
| ✅ | Mail | `app/Mail/ClubInvoice/CancellationMail.php` |
| ✅ | Mail | `app/Mail/ClubInvoice/SuspensionWarningMail.php` |
| ✅ | Template | `resources/views/emails/club-invoice/invoice.blade.php` |
| ✅ | Template | `resources/views/emails/club-invoice/reminder.blade.php` |
| ✅ | Template | `resources/views/emails/club-invoice/payment-confirmation.blade.php` |
| ✅ | Template | `resources/views/emails/club-invoice/cancellation.blade.php` |
| ✅ | Template | `resources/views/emails/club-invoice/suspension-warning.blade.php` |

---

## Scheduled Commands

| Status | Komponente | Datei |
|--------|------------|-------|
| ✅ | Command | `app/Console/Commands/GenerateRecurringInvoicesCommand.php` |
| ✅ | Command | `app/Console/Commands/ProcessOverdueInvoicesCommand.php` |
| ✅ | Scheduling | `routes/console.php` |

**Scheduler-Konfiguration:**
- `invoices:generate-recurring` - Monatlich am 1. um 08:00
- `invoices:process-overdue` - Täglich um 09:00

---

## Frontend-Komponenten

| Status | Komponente | Datei |
|--------|------------|-------|
| ✅ | Admin Invoices | `resources/js/Pages/Admin/Invoices/Index.vue` |
| ✅ | Admin Invoices | `resources/js/Pages/Admin/Invoices/Show.vue` |
| ✅ | Admin Invoices | `resources/js/Pages/Admin/Invoices/Create.vue` |
| ✅ | Admin Requests | `resources/js/Pages/Admin/InvoiceRequests/Index.vue` |
| ✅ | Navigation | `resources/js/Layouts/AdminLayout.vue` |
| ✅ | Payment Selector | `resources/js/Components/Checkout/PaymentMethodSelector.vue` |
| ✅ | Checkout Integration | `resources/js/Pages/Club/Subscription/Index.vue` |
| ✅ | PDF Template | `resources/views/exports/invoice.blade.php` |

---

## Konfiguration (.env)

Die folgenden Umgebungsvariablen müssen in `.env` konfiguriert werden:

```env
# Invoice Configuration
INVOICE_COMPANY_NAME="Deine Firma GmbH"
INVOICE_COMPANY_ADDRESS_LINE1="Musterstraße 123"
INVOICE_COMPANY_ZIP="12345"
INVOICE_COMPANY_CITY="Musterstadt"
INVOICE_COMPANY_EMAIL="rechnungen@example.com"
INVOICE_COMPANY_PHONE="+49 123 456789"
INVOICE_COMPANY_VAT_NUMBER="DE123456789"
INVOICE_COMPANY_TAX_NUMBER="12/345/67890"
INVOICE_COMPANY_REGISTER_COURT="Amtsgericht Musterstadt"
INVOICE_COMPANY_REGISTER_NUMBER="HRB 12345"
INVOICE_COMPANY_MANAGING_DIRECTOR="Max Mustermann"

# Bank Details
INVOICE_BANK_NAME="Musterbank"
INVOICE_BANK_IBAN="DE89 3704 0044 0532 0130 00"
INVOICE_BANK_BIC="COBADEFFXXX"
INVOICE_BANK_ACCOUNT_HOLDER="Deine Firma GmbH"

# Invoice Settings
INVOICE_DEFAULT_TAX_RATE=19
INVOICE_PAYMENT_TERMS_DAYS=14
INVOICE_REMINDERS_ENABLED=true
INVOICE_SUSPENSION_ENABLED=true
INVOICE_SUSPENSION_DAYS=30
```

---

## Workflow

### Kundenansicht (Self-Service)
1. Kunde wählt auf `/club/{id}/subscription` einen Plan
2. Kunde wählt Zahlungsmethode: "Kreditkarte/SEPA" oder "Auf Rechnung"
3. Bei "Auf Rechnung": Rechnungsadresse eingeben und Anfrage absenden
4. Anfrage erscheint im Admin-Bereich unter "Rechnungsanfragen"

### Admin-Workflow
1. Admin prüft Rechnungsanfragen unter `/admin/invoice-requests`
2. Admin genehmigt Anfrage und erstellt erste Rechnung
3. Rechnung wird automatisch per E-Mail versendet
4. Bei Zahlungseingang: Admin markiert Rechnung als bezahlt
5. Automatische Erinnerungen bei überfälligen Rechnungen (falls aktiviert)

### Automatisierung
- Wiederkehrende Rechnungen werden am 1. jedes Monats generiert
- Überfällige Rechnungen werden täglich geprüft
- Erinnerungen werden nach konfigurierbaren Intervallen versendet
- Sperrungswarnung nach konfigurierter Frist
