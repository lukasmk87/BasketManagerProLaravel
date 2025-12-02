# Rechnungszahlung - Verbleibende Schritte

## Bereits erledigt

- [x] Migration: `create_club_invoices_table`
- [x] Migration: `create_club_invoice_requests_table`
- [x] Migration: `add_payment_method_type_to_clubs_table`
- [x] Model: `ClubInvoice`
- [x] Model: `ClubInvoiceRequest`
- [x] Club Model erweitert (Relationships + payment_method_type)
- [x] Config: `config/invoices.php`
- [x] Service: `ClubInvoiceService`
- [x] Service: `ClubInvoicePdfService`
- [x] Service: `ClubInvoiceNotificationService`
- [x] Controller: `ClubInvoiceController`
- [x] Controller: `ClubInvoiceRequestController`
- [x] Form Requests (Create, Update, MarkPaid)
- [x] Routes in `routes/admin.php`
- [x] Vue: `Admin/Invoices/Index.vue`
- [x] Vue: `Admin/Invoices/Show.vue`
- [x] Vue: `Admin/Invoices/Create.vue`
- [x] Vue: `Admin/InvoiceRequests/Index.vue`
- [x] PDF Template: `resources/views/exports/invoice.blade.php`

---

## Noch zu erledigen

### 1. Mail-Klassen erstellen

**Pfad:** `app/Mail/ClubInvoice/`

```bash
php artisan make:mail ClubInvoice/InvoiceMail
php artisan make:mail ClubInvoice/ReminderMail
php artisan make:mail ClubInvoice/PaymentConfirmationMail
php artisan make:mail ClubInvoice/CancellationMail
php artisan make:mail ClubInvoice/SuspensionWarningMail
```

**Inhalt InvoiceMail.php:**
```php
<?php

namespace App\Mail\ClubInvoice;

use App\Models\ClubInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClubInvoice $invoice,
        public string $pdfContent
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rechnung {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.club-invoice.invoice',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "Rechnung_{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
```

### 2. Mail-Templates erstellen

**Pfad:** `resources/views/emails/club-invoice/`

Erstelle folgende Blade-Templates:
- `invoice.blade.php` - Rechnung versendet
- `reminder.blade.php` - Zahlungserinnerung
- `payment-confirmation.blade.php` - Zahlungsbestätigung
- `cancellation.blade.php` - Stornierung
- `suspension-warning.blade.php` - Sperrungswarnung

### 3. Self-Service Checkout erweitern

**Dateien:**
- `app/Http/Controllers/Stripe/ClubCheckoutController.php` - Neue Methode `requestInvoicePayment()`
- `routes/club_checkout.php` - Route hinzufügen
- `resources/js/Components/Checkout/PaymentMethodSelector.vue` - Neue Komponente

**Route hinzufügen in `routes/club_checkout.php`:**
```php
Route::post('/club/{club}/checkout/request-invoice', [ClubCheckoutController::class, 'requestInvoicePayment'])
    ->name('club.checkout.request-invoice');
```

**Controller-Methode:**
```php
public function requestInvoicePayment(Request $request, Club $club)
{
    $validated = $request->validate([
        'plan_id' => 'required|exists:club_subscription_plans,id',
        'billing_name' => 'required|string|max:255',
        'billing_email' => 'required|email',
        'billing_address' => 'nullable|array',
        'vat_number' => 'nullable|string|max:50',
        'billing_interval' => 'required|in:monthly,yearly',
    ]);

    ClubInvoiceRequest::create([
        'tenant_id' => $club->tenant_id,
        'club_id' => $club->id,
        'club_subscription_plan_id' => $validated['plan_id'],
        'billing_name' => $validated['billing_name'],
        'billing_email' => $validated['billing_email'],
        'billing_address' => $validated['billing_address'],
        'vat_number' => $validated['vat_number'],
        'billing_interval' => $validated['billing_interval'],
        'status' => 'pending',
    ]);

    // Notify admins...

    return redirect()->back()->with('success', 'Ihre Anfrage wurde eingereicht.');
}
```

### 4. Scheduled Commands erstellen

**Command: `GenerateRecurringInvoicesCommand`**

```bash
php artisan make:command GenerateRecurringInvoicesCommand
```

**Pfad:** `app/Console/Commands/GenerateRecurringInvoicesCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Services\Invoice\ClubInvoiceService;
use Illuminate\Console\Command;

class GenerateRecurringInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-recurring';
    protected $description = 'Generate recurring invoices for clubs paying via invoice';

    public function handle(ClubInvoiceService $invoiceService): int
    {
        $clubs = Club::where('payment_method_type', 'invoice')
            ->where('subscription_status', 'active')
            ->whereNotNull('club_subscription_plan_id')
            ->where('subscription_current_period_end', '<=', now()->addDays(7))
            ->with('subscriptionPlan')
            ->get();

        $created = 0;
        foreach ($clubs as $club) {
            // Check if invoice already exists for this period
            $existingInvoice = $club->invoices()
                ->whereDate('issue_date', '>=', now()->startOfMonth())
                ->exists();

            if (!$existingInvoice) {
                $invoice = $invoiceService->createForSubscription(
                    $club,
                    $club->subscriptionPlan,
                    now()->format('d.m.Y') . ' - ' . now()->addMonth()->format('d.m.Y'),
                    'monthly'
                );
                $invoiceService->markAsSent($invoice);
                $created++;
            }
        }

        $this->info("Created {$created} recurring invoices.");
        return 0;
    }
}
```

**Command: `ProcessOverdueInvoicesCommand`**

```bash
php artisan make:command ProcessOverdueInvoicesCommand
```

**Pfad:** `app/Console/Commands/ProcessOverdueInvoicesCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\Invoice\ClubInvoiceService;
use Illuminate\Console\Command;

class ProcessOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:process-overdue';
    protected $description = 'Process overdue invoices, send reminders, and suspend subscriptions';

    public function handle(ClubInvoiceService $invoiceService): int
    {
        $results = $invoiceService->processOverdueInvoices();

        $this->info("Marked overdue: {$results['marked_overdue']}");
        $this->info("Reminders sent: {$results['reminders_sent']}");
        $this->info("Subscriptions suspended: {$results['subscriptions_suspended']}");

        return 0;
    }
}
```

**Kernel registrieren in `app/Console/Kernel.php`:**
```php
protected function schedule(Schedule $schedule): void
{
    // Generate recurring invoices on 1st of each month at 8:00
    $schedule->command('invoices:generate-recurring')->monthlyOn(1, '08:00');

    // Process overdue invoices daily at 9:00
    $schedule->command('invoices:process-overdue')->dailyAt('09:00');
}
```

### 5. AdminLayout Navigation erweitern

**Datei:** `resources/js/Layouts/AdminLayout.vue`

Füge folgende Navigation-Items hinzu:
```javascript
{ name: 'Rechnungen', route: 'admin.invoices.index', icon: '...' },
{ name: 'Rechnungsanfragen', route: 'admin.invoice-requests.index' },
```

### 6. Migrations ausführen

```bash
php artisan migrate
```

### 7. .env Variablen konfigurieren

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

### 8. Tests schreiben (Optional)

```bash
php artisan make:test Feature/Admin/ClubInvoiceControllerTest
php artisan make:test Unit/Services/Invoice/ClubInvoiceServiceTest
```

---

## Zusammenfassung Dateien

| Status | Datei |
|--------|-------|
| ✅ | `database/migrations/*_create_club_invoices_table.php` |
| ✅ | `database/migrations/*_create_club_invoice_requests_table.php` |
| ✅ | `database/migrations/*_add_payment_method_type_to_clubs_table.php` |
| ✅ | `app/Models/ClubInvoice.php` |
| ✅ | `app/Models/ClubInvoiceRequest.php` |
| ✅ | `app/Models/Club.php` (erweitert) |
| ✅ | `config/invoices.php` |
| ✅ | `app/Services/Invoice/ClubInvoiceService.php` |
| ✅ | `app/Services/Invoice/ClubInvoicePdfService.php` |
| ✅ | `app/Services/Invoice/ClubInvoiceNotificationService.php` |
| ✅ | `app/Http/Controllers/Admin/ClubInvoiceController.php` |
| ✅ | `app/Http/Controllers/Admin/ClubInvoiceRequestController.php` |
| ✅ | `app/Http/Requests/Admin/CreateClubInvoiceRequest.php` |
| ✅ | `app/Http/Requests/Admin/UpdateClubInvoiceRequest.php` |
| ✅ | `app/Http/Requests/Admin/MarkInvoicePaidRequest.php` |
| ✅ | `routes/admin.php` (erweitert) |
| ✅ | `resources/js/Pages/Admin/Invoices/Index.vue` |
| ✅ | `resources/js/Pages/Admin/Invoices/Show.vue` |
| ✅ | `resources/js/Pages/Admin/Invoices/Create.vue` |
| ✅ | `resources/js/Pages/Admin/InvoiceRequests/Index.vue` |
| ✅ | `resources/views/exports/invoice.blade.php` |
| ⏳ | `app/Mail/ClubInvoice/InvoiceMail.php` |
| ⏳ | `app/Mail/ClubInvoice/ReminderMail.php` |
| ⏳ | `app/Mail/ClubInvoice/PaymentConfirmationMail.php` |
| ⏳ | `app/Mail/ClubInvoice/CancellationMail.php` |
| ⏳ | `app/Mail/ClubInvoice/SuspensionWarningMail.php` |
| ⏳ | `resources/views/emails/club-invoice/*.blade.php` |
| ⏳ | `app/Console/Commands/GenerateRecurringInvoicesCommand.php` |
| ⏳ | `app/Console/Commands/ProcessOverdueInvoicesCommand.php` |
| ⏳ | `app/Http/Controllers/Stripe/ClubCheckoutController.php` (erweitern) |
| ⏳ | `resources/js/Layouts/AdminLayout.vue` (Navigation) |
