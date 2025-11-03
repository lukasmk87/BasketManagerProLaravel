# 📚 Club Subscription API Reference

**Version:** 1.0
**Erstellt:** 2025-11-03
**Sprache:** Deutsch
**Status:** Produktions-Ready

---

## 📋 Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Authentication & Authorization](#authentication--authorization)
3. [Checkout & Subscription Management](#checkout--subscription-management)
4. [Invoice Management](#invoice-management)
5. [Payment Method Management](#payment-method-management)
6. [Subscription Analytics](#subscription-analytics)
7. [Webhook Events](#webhook-events)
8. [Error Handling](#error-handling)
9. [Stripe Test Data](#stripe-test-data)
10. [Rate Limiting](#rate-limiting)

---

## 🔍 Überblick

Die **Club Subscription API** ermöglicht die vollständige Verwaltung von Club-Abonnements mit Stripe-Integration. Jeder Club kann seine eigene Stripe-Subscription haben, unabhängig vom Tenant-Level-Abonnement.

### Architektur

```
TENANT (z.B. Bayerischer Basketball Verband)
├── Zahlt Enterprise-Subscription (€499/Monat) → Tenant-Level
│
├── CLUB 1: "FC Bayern Basketball"
│   └── Zahlt ZUSÄTZLICH Premium Plan (€149/Monat) → Club-Level ✅ Diese API
│
├── CLUB 2: "Nachwuchsclub München"
│   └── Zahlt ZUSÄTZLICH Standard Plan (€49/Monat) → Club-Level ✅ Diese API
│
└── CLUB 3: "Jugendabteilung"
    └── Zahlt NICHTS (Free Plan) → Kein Stripe
```

### Base URL

```
https://basketmanager.pro
```

### Content-Type

Alle Requests und Responses verwenden `application/json`:

```http
Content-Type: application/json
Accept: application/json
```

---

## 🔐 Authentication & Authorization

### Authentication

Alle API-Endpunkte (außer Webhooks) erfordern **Laravel Sanctum** Authentication:

```http
Authorization: Bearer {your-access-token}
Cookie: {laravel_session}
```

**Middleware-Stack:**
- `auth` - Benutzer muss eingeloggt sein
- `verified` - E-Mail muss verifiziert sein
- `tenant` - Tenant-Kontext muss gesetzt sein

### Authorization Policy

Billing-Operationen prüfen die **`manageBilling`** Policy auf dem `Club` Model:

**Berechtigte Rollen:**
- ✅ `super_admin` - Vollzugriff auf alle Clubs
- ✅ `admin` - Vollzugriff auf alle Clubs
- ✅ `club_admin` - Nur eigene administrierte Clubs
- ❌ Andere Rollen - Kein Zugriff

**Policy-Implementierung** (`app/Policies/ClubPolicy.php`):

```php
public function manageBilling(User $user, Club $club): bool
{
    if ($user->hasRole(['super_admin', 'admin'])) {
        return true;
    }

    if ($user->hasRole('club_admin') && $user->can('view financial data')) {
        $administeredClubIds = $user->getAdministeredClubIds();
        return in_array($club->id, $administeredClubIds);
    }

    return false;
}
```

---

## 🛒 Checkout & Subscription Management

### 1. Subscription Overview anzeigen

Zeigt die Abonnement-Übersicht für einen Club mit verfügbaren Plänen.

**Endpoint:**
```http
GET /club/{club}/subscription
```

**Authorization:** `view` Policy auf Club (beliebiger authentifizierter Benutzer)

**Response:**
```json
{
  "club": {
    "id": 1,
    "name": "FC Bayern Basketball",
    "subscription_status": "active",
    "subscription_plan": {
      "id": 2,
      "name": "Premium Club",
      "price": 149.00,
      "currency": "EUR",
      "features": {
        "live_scoring": true,
        "advanced_stats": true,
        "video_analysis": true
      }
    }
  },
  "available_plans": [...],
  "subscription_limits": {
    "max_teams": 50,
    "max_players": 500,
    "current_teams": 12,
    "current_players": 245
  },
  "has_active_subscription": true,
  "is_on_trial": false,
  "trial_days_remaining": 0,
  "billing_days_remaining": 23
}
```

---

### 2. Checkout Session erstellen

Initiiert einen Stripe Checkout für eine Club-Subscription.

**Endpoint:**
```http
POST /club/{club}/checkout
```

**Authorization:** `manageBilling` Policy

**Request Body:**
```json
{
  "plan_id": 2,
  "billing_interval": "monthly",
  "success_url": "https://basketmanager.pro/club/1/checkout/success",
  "cancel_url": "https://basketmanager.pro/club/1/checkout/cancel"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan_id` | integer | ✅ | ID des `ClubSubscriptionPlan` |
| `billing_interval` | string | ❌ | `monthly` oder `yearly` (default: `monthly`) |
| `success_url` | string (URL) | ❌ | Redirect nach erfolgreichem Checkout |
| `cancel_url` | string (URL) | ❌ | Redirect bei Checkout-Abbruch |

**Response (Success - 200):**
```json
{
  "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_...",
  "session_id": "cs_test_a1B2c3D4e5F6g7H8i9J0k1L2m3N4o5P6"
}
```

**Response (Error - 403):**
```json
{
  "error": "Plan does not belong to club's tenant"
}
```

**Response (Error - 500):**
```json
{
  "error": "Failed to create checkout session: {error message}"
}
```

**Workflow:**
1. Request validieren (plan_id existiert, billing_interval valide)
2. Authorization prüfen (`manageBilling` Policy)
3. Plan-Tenant-Matching validieren
4. Stripe Customer für Club erstellen/abrufen (`ClubStripeCustomerService`)
5. Stripe Checkout Session erstellen (`ClubSubscriptionCheckoutService`)
6. Checkout-URL zurückgeben
7. User wird zu Stripe Checkout redirected

**Code-Beispiel (JavaScript):**
```javascript
async function initiateCheckout(clubId, planId, billingInterval = 'monthly') {
  const response = await fetch(`/club/${clubId}/checkout`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      plan_id: planId,
      billing_interval: billingInterval,
      success_url: `/club/${clubId}/checkout/success`,
      cancel_url: `/club/${clubId}/checkout/cancel`
    })
  });

  const data = await response.json();

  if (response.ok) {
    // Redirect to Stripe Checkout
    window.location.href = data.checkout_url;
  } else {
    console.error('Checkout failed:', data.error);
  }
}
```

---

### 3. Checkout Success Page

Zeigt Erfolgsseite nach abgeschlossenem Checkout.

**Endpoint:**
```http
GET /club/{club}/checkout/success?session_id={session_id}
```

**Authorization:** `view` Policy auf Club

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `session_id` | string | ❌ | Stripe Checkout Session ID |

**Response:** Inertia.js Response (Vue Component: `Club/Checkout/Success.vue`)

**Props:**
```json
{
  "club": {
    "id": 1,
    "name": "FC Bayern Basketball",
    "subscription_plan": {
      "name": "Premium Club",
      "price": 149.00
    }
  },
  "session_id": "cs_test_...",
  "message": "Subscription activated successfully! Welcome to Premium Club plan."
}
```

---

### 4. Checkout Cancel Page

Zeigt Seite an, wenn Checkout abgebrochen wurde.

**Endpoint:**
```http
GET /club/{club}/checkout/cancel
```

**Authorization:** `view` Policy auf Club

**Response:** Inertia.js Response (Vue Component: `Club/Checkout/Cancel.vue`)

**Props:**
```json
{
  "club": {
    "id": 1,
    "name": "FC Bayern Basketball"
  },
  "message": "Checkout was canceled. You can try again anytime."
}
```

---

### 5. Billing Portal Session erstellen

Erstellt eine Stripe Billing Portal Session für Self-Service-Verwaltung.

**Endpoint:**
```http
POST /club/{club}/billing-portal
```

**Authorization:** `manageBilling` Policy

**Request Body:**
```json
{
  "return_url": "https://basketmanager.pro/club/1/subscription"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `return_url` | string (URL) | ❌ | Redirect nach Portal-Session (default: Club Subscription Page) |

**Response (Success - 200):**
```json
{
  "portal_url": "https://billing.stripe.com/session/test_..."
}
```

**Response (Error - 400):**
```json
{
  "error": "Club has no active billing account"
}
```

**Hinweis:** Das Billing Portal erlaubt Clubs:
- Zahlungsmethoden zu verwalten
- Rechnungen herunterzuladen
- Abonnements zu kündigen
- Plan-Upgrades/Downgrades durchzuführen

**Code-Beispiel (Vue.js):**
```javascript
async function openBillingPortal(clubId) {
  const response = await fetch(`/club/${clubId}/billing-portal`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
      return_url: `/club/${clubId}/subscription`
    })
  });

  const data = await response.json();

  if (response.ok) {
    window.location.href = data.portal_url;
  }
}
```

---

## 🧾 Invoice Management

### 6. Liste aller Rechnungen

Ruft alle Rechnungen für einen Club ab.

**Endpoint:**
```http
GET /club/{club}/billing/invoices
```

**Authorization:** `manageBilling` Policy

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `limit` | integer | ❌ | Anzahl der Rechnungen (1-100, default: 10) |
| `starting_after` | string | ❌ | Cursor für Pagination (Invoice ID) |
| `ending_before` | string | ❌ | Cursor für reverse Pagination |
| `status` | string | ❌ | Filter: `draft`, `open`, `paid`, `uncollectible`, `void` |

**Response (Success - 200):**
```json
{
  "invoices": {
    "data": [
      {
        "id": "in_1234567890",
        "number": "INV-2025-001",
        "status": "paid",
        "amount_due": 149.00,
        "amount_paid": 149.00,
        "currency": "EUR",
        "created": 1699564800,
        "due_date": 1699651200,
        "period_start": 1699564800,
        "period_end": 1702243200,
        "pdf": "https://pay.stripe.com/invoice/.../pdf",
        "hosted_invoice_url": "https://invoice.stripe.com/i/...",
        "lines": {
          "data": [
            {
              "description": "Premium Club (Nov 1 - Dec 1, 2025)",
              "amount": 149.00,
              "currency": "EUR",
              "period": {
                "start": 1699564800,
                "end": 1702243200
              }
            }
          ]
        }
      }
    ],
    "has_more": true,
    "url": "/v1/invoices",
    "object": "list"
  },
  "club_id": 1,
  "club_name": "FC Bayern Basketball"
}
```

**Pagination:**

```javascript
// Nächste Seite
const nextPage = await fetch(
  `/club/1/billing/invoices?starting_after=${lastInvoiceId}`
);

// Vorherige Seite
const prevPage = await fetch(
  `/club/1/billing/invoices?ending_before=${firstInvoiceId}`
);
```

---

### 7. Einzelne Rechnung abrufen

Ruft Details einer einzelnen Rechnung ab.

**Endpoint:**
```http
GET /club/{club}/billing/invoices/{invoice}
```

**Authorization:** `manageBilling` Policy

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `invoice` | string | ✅ | Stripe Invoice ID (z.B. `in_1234567890`) |

**Response (Success - 200):**
```json
{
  "invoice": {
    "id": "in_1234567890",
    "number": "INV-2025-001",
    "status": "paid",
    "amount_due": 149.00,
    "amount_paid": 149.00,
    "amount_remaining": 0.00,
    "currency": "EUR",
    "created": 1699564800,
    "due_date": 1699651200,
    "paid": true,
    "period_start": 1699564800,
    "period_end": 1702243200,
    "subtotal": 149.00,
    "tax": 0.00,
    "total": 149.00,
    "pdf": "https://pay.stripe.com/invoice/.../pdf",
    "hosted_invoice_url": "https://invoice.stripe.com/i/...",
    "customer_name": "FC Bayern Basketball",
    "customer_email": "billing@fcbayern.de",
    "lines": {
      "data": [...]
    },
    "payment_intent": {
      "id": "pi_1234567890",
      "status": "succeeded"
    }
  },
  "club_id": 1
}
```

**Response (Error - 500):**
```json
{
  "error": "Failed to retrieve invoice: Invoice not found"
}
```

---

### 8. Vorschau der nächsten Rechnung

Zeigt Vorschau der kommenden Rechnung (Upcoming Invoice).

**Endpoint:**
```http
GET /club/{club}/billing/invoices/upcoming
```

**Authorization:** `manageBilling` Policy

**Response (Success - 200):**
```json
{
  "invoice": {
    "id": "upcoming",
    "amount_due": 149.00,
    "amount_remaining": 149.00,
    "currency": "EUR",
    "period_start": 1702243200,
    "period_end": 1704921600,
    "subtotal": 149.00,
    "tax": 0.00,
    "total": 149.00,
    "next_payment_attempt": 1702243200,
    "lines": {
      "data": [
        {
          "description": "Premium Club (Dec 1, 2025 - Jan 1, 2026)",
          "amount": 149.00,
          "currency": "EUR",
          "period": {
            "start": 1702243200,
            "end": 1704921600
          }
        }
      ]
    }
  },
  "club_id": 1
}
```

**Response (No Upcoming Invoice - 404):**
```json
{
  "message": "No upcoming invoice available",
  "club_id": 1
}
```

**Hinweis:** Upcoming Invoice ist nur verfügbar, wenn der Club ein aktives Abonnement hat.

---

### 9. Rechnung als PDF herunterladen

Lädt Rechnung als PDF herunter (Redirect zu Stripe PDF-URL).

**Endpoint:**
```http
GET /club/{club}/billing/invoices/{invoice}/pdf
```

**Authorization:** `manageBilling` Policy

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `invoice` | string | ✅ | Stripe Invoice ID |

**Response (Success - 302):**
```http
HTTP/1.1 302 Found
Location: https://pay.stripe.com/invoice/acct_.../test_.../pdf
```

**Response (Error - 500):**
```json
{
  "error": "Failed to download invoice PDF: Invoice not found"
}
```

**Code-Beispiel:**
```javascript
// Direct download link
const pdfUrl = `/club/${clubId}/billing/invoices/${invoiceId}/pdf`;

// Open in new tab
window.open(pdfUrl, '_blank');

// Download via <a> tag
<a href={pdfUrl} download target="_blank">
  Download PDF
</a>
```

---

## 💳 Payment Method Management

### 10. Liste aller Zahlungsmethoden

Ruft alle Zahlungsmethoden für einen Club ab.

**Endpoint:**
```http
GET /club/{club}/billing/payment-methods
```

**Authorization:** `manageBilling` Policy

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `type` | string | ❌ | Filter: `card`, `sepa_debit`, `sofort`, `giropay`, `eps`, `bancontact`, `ideal` |

**Response (Success - 200):**
```json
{
  "payment_methods": [
    {
      "id": "pm_1234567890",
      "type": "card",
      "card": {
        "brand": "visa",
        "last4": "4242",
        "exp_month": 12,
        "exp_year": 2025,
        "country": "DE",
        "funding": "credit"
      },
      "billing_details": {
        "name": "FC Bayern Basketball",
        "email": "billing@fcbayern.de",
        "phone": "+49 89 12345678",
        "address": {
          "city": "München",
          "country": "DE",
          "line1": "Säbener Str. 51-57",
          "postal_code": "81547"
        }
      },
      "created": 1699564800,
      "customer": "cus_...",
      "is_default": true
    },
    {
      "id": "pm_0987654321",
      "type": "sepa_debit",
      "sepa_debit": {
        "bank_code": "37040044",
        "branch_code": null,
        "country": "DE",
        "fingerprint": "...",
        "last4": "3000"
      },
      "billing_details": {
        "name": "FC Bayern Basketball",
        "email": "billing@fcbayern.de"
      },
      "created": 1699478400,
      "customer": "cus_...",
      "is_default": false
    }
  ],
  "club_id": 1,
  "type": "card",
  "available_types": [
    "card",
    "sepa_debit",
    "sofort",
    "giropay",
    "eps",
    "bancontact",
    "ideal"
  ],
  "localized_names": {
    "card": "Kreditkarte / EC-Karte",
    "sepa_debit": "SEPA Lastschrift",
    "sofort": "SOFORT Überweisung",
    "giropay": "Giropay",
    "eps": "EPS (Österreich)",
    "bancontact": "Bancontact (Belgien)",
    "ideal": "iDEAL (Niederlande)"
  }
}
```

---

### 11. SetupIntent erstellen (Zahlungsmethode hinzufügen)

Erstellt einen Stripe SetupIntent für sichere Zahlungsmethoden-Erfassung.

**Endpoint:**
```http
POST /club/{club}/billing/payment-methods/setup
```

**Authorization:** `manageBilling` Policy

**Request Body:**
```json
{
  "usage": "off_session",
  "return_url": "https://basketmanager.pro/club/1/billing/payment-methods"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `usage` | string | ❌ | `on_session` oder `off_session` (default: `off_session`) |
| `return_url` | string (URL) | ❌ | Redirect nach 3D Secure Authentifizierung |

**Response (Success - 200):**
```json
{
  "client_secret": "seti_1234567890_secret_...",
  "setup_intent_id": "seti_1234567890"
}
```

**Workflow:**
1. SetupIntent erstellen (Backend)
2. `client_secret` an Frontend senden
3. Frontend verwendet Stripe.js Elements zur Erfassung der Zahlungsmethode
4. Frontend bestätigt SetupIntent mit `stripe.confirmCardSetup()`
5. Payment Method wird automatisch zu Customer attached
6. Frontend ruft `POST /club/{club}/billing/payment-methods/attach` auf

**Code-Beispiel (Vue.js + Stripe Elements):**
```javascript
// 1. SetupIntent erstellen
const { client_secret } = await createSetupIntent(clubId);

// 2. Stripe Elements initialisieren
const stripe = await loadStripe(publishableKey);
const elements = stripe.elements({ clientSecret: client_secret });
const cardElement = elements.create('card');
cardElement.mount('#card-element');

// 3. Payment Method erfassen & bestätigen
const { setupIntent, error } = await stripe.confirmCardSetup(client_secret, {
  payment_method: {
    card: cardElement,
    billing_details: {
      name: 'FC Bayern Basketball',
      email: 'billing@fcbayern.de'
    }
  }
});

if (error) {
  console.error('Setup failed:', error);
} else {
  // 4. Payment Method zu Club attachen
  await attachPaymentMethod(clubId, setupIntent.payment_method, true);
}
```

---

### 12. Zahlungsmethode zu Club attachen

Attacht eine Zahlungsmethode zu einem Club.

**Endpoint:**
```http
POST /club/{club}/billing/payment-methods/attach
```

**Authorization:** `manageBilling` Policy

**Request Body:**
```json
{
  "payment_method_id": "pm_1234567890",
  "set_as_default": true
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `payment_method_id` | string | ✅ | Stripe Payment Method ID |
| `set_as_default` | boolean | ❌ | Als Standard-Zahlungsmethode setzen (default: `false`) |

**Response (Success - 200):**
```json
{
  "message": "Payment method attached successfully",
  "payment_method_id": "pm_1234567890",
  "is_default": true
}
```

**Response (Error - 500):**
```json
{
  "error": "Failed to attach payment method: Payment method already attached to a different customer"
}
```

---

### 13. Zahlungsmethode von Club entfernen

Detacht eine Zahlungsmethode von einem Club.

**Endpoint:**
```http
DELETE /club/{club}/billing/payment-methods/{paymentMethod}
```

**Authorization:** `manageBilling` Policy

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `paymentMethod` | string | ✅ | Stripe Payment Method ID |

**Response (Success - 200):**
```json
{
  "message": "Payment method detached successfully",
  "payment_method_id": "pm_1234567890"
}
```

**Response (Error - 500):**
```json
{
  "error": "Failed to detach payment method: Cannot detach payment method belonging to another customer"
}
```

**Hinweis:** Ownership-Validation verhindert das Entfernen von Payment Methods, die anderen Clubs gehören.

---

### 14. Zahlungsmethoden-Billing-Details aktualisieren

Aktualisiert Billing-Details einer Zahlungsmethode.

**Endpoint:**
```http
PUT /club/{club}/billing/payment-methods/{paymentMethod}
```

**Authorization:** `manageBilling` Policy

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `paymentMethod` | string | ✅ | Stripe Payment Method ID |

**Request Body:**
```json
{
  "billing_details": {
    "name": "FC Bayern Basketball GmbH",
    "email": "billing-updated@fcbayern.de",
    "phone": "+49 89 98765432",
    "address": {
      "city": "München",
      "country": "DE",
      "line1": "Neue Straße 1",
      "line2": "Gebäude A",
      "postal_code": "81547",
      "state": "Bayern"
    }
  }
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `billing_details` | object | ✅ | Billing-Informationen |
| `billing_details.name` | string | ❌ | Name |
| `billing_details.email` | string (email) | ❌ | E-Mail |
| `billing_details.phone` | string | ❌ | Telefonnummer |
| `billing_details.address` | object | ❌ | Adresse |

**Response (Success - 200):**
```json
{
  "message": "Payment method updated successfully",
  "payment_method_id": "pm_1234567890"
}
```

---

### 15. Standard-Zahlungsmethode setzen

Setzt eine Zahlungsmethode als Standard für Customer und Subscription.

**Endpoint:**
```http
POST /club/{club}/billing/payment-methods/{paymentMethod}/default
```

**Authorization:** `manageBilling` Policy

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `paymentMethod` | string | ✅ | Stripe Payment Method ID |

**Response (Success - 200):**
```json
{
  "message": "Default payment method set successfully",
  "payment_method_id": "pm_1234567890"
}
```

**Hinweis:** Die Standard-Zahlungsmethode wird für zukünftige Zahlungen verwendet.

---

## 📊 Subscription Analytics

### 16. Plan-Wechsel-Vorschau mit Proration

Zeigt Vorschau eines Plan-Wechsels mit detaillierter Proration-Berechnung.

**Endpoint:**
```http
POST /club/{club}/billing/preview-plan-swap
```

**Authorization:** `manageBilling` Policy

**Request Body:**
```json
{
  "new_plan_id": 3,
  "billing_interval": "monthly",
  "proration_behavior": "create_prorations"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `new_plan_id` | integer | ✅ | ID des neuen `ClubSubscriptionPlan` |
| `billing_interval` | string | ❌ | `monthly` oder `yearly` (default: `monthly`) |
| `proration_behavior` | string | ❌ | `create_prorations`, `none`, `always_invoice` (default: `create_prorations`) |

**Response (Success - 200):**
```json
{
  "preview": {
    "current_plan": {
      "id": 2,
      "name": "Premium Club",
      "price": 149.00,
      "currency": "EUR"
    },
    "new_plan": {
      "id": 3,
      "name": "Enterprise Club",
      "price": 299.00,
      "currency": "EUR"
    },
    "billing_interval": "monthly",
    "proration": {
      "amount": 150.00,
      "credit": -99.33,
      "debit": 299.00,
      "currency": "EUR"
    },
    "upcoming_invoice": {
      "amount_due": 150.00,
      "amount_remaining": 150.00,
      "subtotal": 150.00,
      "total": 150.00,
      "currency": "EUR",
      "period_start": 1699564800,
      "period_end": 1702243200
    },
    "line_items": [
      {
        "description": "Remaining time on Premium Club after 10 Nov 2025",
        "amount": -99.33,
        "currency": "EUR",
        "proration": true,
        "period": {
          "start": 1699651200,
          "end": 1702243200
        }
      },
      {
        "description": "Enterprise Club (10 Nov 2025 - 1 Dec 2025)",
        "amount": 249.33,
        "currency": "EUR",
        "proration": true,
        "period": {
          "start": 1699651200,
          "end": 1702243200
        }
      }
    ],
    "effective_date": 1699651200,
    "next_billing_date": 1702243200,
    "is_upgrade": true,
    "is_downgrade": false
  },
  "club_id": 1
}
```

**Hinweis:** Diese Vorschau zeigt die **genauen Kosten** vor der Durchführung des Plan-Wechsels.

---

### 17. Plan-Wechsel durchführen (Upgrade/Downgrade)

Führt einen Plan-Wechsel durch.

**Endpoint:**
```http
POST /club/{club}/billing/swap-plan
```

**Authorization:** `manageBilling` Policy

**Request Body:**
```json
{
  "new_plan_id": 3,
  "billing_interval": "monthly",
  "proration_behavior": "create_prorations"
}
```

**Request Parameters:** (identisch mit Preview)

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `new_plan_id` | integer | ✅ | ID des neuen Plans |
| `billing_interval` | string | ❌ | `monthly` oder `yearly` |
| `proration_behavior` | string | ❌ | Proration-Verhalten |

**Response (Success - 200):**
```json
{
  "message": "Plan swapped successfully",
  "club_id": 1,
  "new_plan_id": 3,
  "new_plan_name": "Enterprise Club"
}
```

**Response (Error - 403):**
```json
{
  "error": "Plan does not belong to club's tenant"
}
```

**Response (Error - 500):**
```json
{
  "error": "Failed to swap plan: Subscription must be active to change plans"
}
```

**Workflow:**
1. Request validieren
2. Authorization prüfen
3. Plan-Tenant-Matching validieren
4. Stripe Subscription updaten mit neuem Price
5. Proration automatisch anwenden
6. Club-Model updaten (`club_subscription_plan_id`)

---

## 🔔 Webhook Events

Stripe sendet Webhook-Events an folgende URL:

```
POST /webhooks/stripe/club-subscriptions
```

**⚠️ Wichtig:** Diese Route ist **NICHT authentifiziert**. Stattdessen wird die **Stripe-Signatur** validiert.

### Signatur-Validierung

```php
$payload = $request->getContent();
$sigHeader = $request->header('Stripe-Signature');
$webhookSecret = config('stripe.webhooks.signing_secret_club');

$event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
```

**Konfiguration** (`.env`):
```env
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...
```

---

### Unterstützte Webhook-Events

Das System behandelt **11 Webhook-Events**:

| Event Type | Handler | Beschreibung |
|------------|---------|--------------|
| `checkout.session.completed` | `handleCheckoutCompleted()` | Checkout abgeschlossen, Subscription aktiviert |
| `customer.subscription.created` | `handleSubscriptionCreated()` | Subscription erstellt |
| `customer.subscription.updated` | `handleSubscriptionUpdated()` | Subscription aktualisiert (Renewal, Plan-Wechsel) |
| `customer.subscription.deleted` | `handleSubscriptionDeleted()` | Subscription gelöscht (Churn) |
| `invoice.payment_succeeded` | `handlePaymentSucceeded()` | Zahlung erfolgreich |
| `invoice.payment_failed` | `handlePaymentFailed()` | Zahlung fehlgeschlagen |
| `invoice.created` | `handleInvoiceCreated()` | Rechnung erstellt |
| `invoice.finalized` | `handleInvoiceFinalized()` | Rechnung finalisiert |
| `invoice.payment_action_required` | `handlePaymentActionRequired()` | 3D Secure Authentifizierung erforderlich |
| `payment_method.attached` | `handlePaymentMethodAttached()` | Zahlungsmethode hinzugefügt |
| `payment_method.detached` | `handlePaymentMethodDetached()` | Zahlungsmethode entfernt |

---

### Event: `checkout.session.completed`

**Triggert wenn:** User schließt Stripe Checkout ab

**Actions:**
1. Club-Model updaten:
   - `stripe_customer_id`
   - `stripe_subscription_id`
   - `subscription_status` = `active`
   - `subscription_started_at` = now()
   - `club_subscription_plan_id`
2. `ClubSubscriptionEvent` erstellen (Analytics)
3. Welcome-Email senden (`ClubSubscriptionNotificationService`)

**Payload (Auszug):**
```json
{
  "id": "evt_...",
  "type": "checkout.session.completed",
  "data": {
    "object": {
      "id": "cs_test_...",
      "customer": "cus_...",
      "subscription": "sub_...",
      "metadata": {
        "club_id": "1",
        "club_subscription_plan_id": "2",
        "tenant_id": "1"
      }
    }
  }
}
```

---

### Event: `customer.subscription.created`

**Triggert wenn:** Stripe erstellt Subscription (nach Checkout oder API-Call)

**Actions:**
1. Club-Model updaten:
   - `stripe_subscription_id`
   - `subscription_status`
   - `subscription_current_period_start`
   - `subscription_current_period_end`
   - `subscription_trial_ends_at` (wenn Trial)
2. `ClubSubscriptionEvent` erstellen
   - Event-Type: `trial_started` (wenn Trial) oder `subscription_created`

**Payload (Auszug):**
```json
{
  "type": "customer.subscription.created",
  "data": {
    "object": {
      "id": "sub_...",
      "status": "active",
      "current_period_start": 1699564800,
      "current_period_end": 1702243200,
      "trial_end": null,
      "metadata": {
        "club_id": "1"
      }
    }
  }
}
```

---

### Event: `customer.subscription.updated`

**Triggert wenn:** Subscription-Status ändert sich (Renewal, Plan-Wechsel, Kündigung geplant)

**Actions:**
1. Club-Model updaten:
   - `subscription_status`
   - `subscription_current_period_start`
   - `subscription_current_period_end`
   - `subscription_ends_at` (wenn `cancel_at_period_end` = `true`)

**Payload (Auszug):**
```json
{
  "type": "customer.subscription.updated",
  "data": {
    "object": {
      "id": "sub_...",
      "status": "active",
      "cancel_at_period_end": true,
      "current_period_end": 1702243200
    }
  }
}
```

---

### Event: `customer.subscription.deleted`

**Triggert wenn:** Subscription wird gelöscht (Kündigung wirksam)

**Actions:**
1. Club-Model updaten:
   - `subscription_status` = `canceled`
   - `subscription_ends_at` = now()
   - `club_subscription_plan_id` = `null`
2. `ClubSubscriptionEvent` erstellen (Churn-Tracking)
3. Cancellation-Email senden

**Payload (Auszug):**
```json
{
  "type": "customer.subscription.deleted",
  "data": {
    "object": {
      "id": "sub_...",
      "status": "canceled"
    }
  }
}
```

---

### Event: `invoice.payment_succeeded`

**Triggert wenn:** Zahlung erfolgreich abgeschlossen

**Actions:**
1. Club-Model updaten:
   - `subscription_status` = `active` (falls vorher `past_due`)
2. `ClubSubscriptionEvent` erstellen
   - Event-Type: `payment_recovered` (wenn vorher `past_due`) oder `payment_succeeded`
3. Payment-Success-Email senden mit Invoice-Details

**Payload (Auszug):**
```json
{
  "type": "invoice.payment_succeeded",
  "data": {
    "object": {
      "id": "in_...",
      "customer": "cus_...",
      "amount_paid": 14900,
      "currency": "eur",
      "number": "INV-2025-001",
      "invoice_pdf": "https://pay.stripe.com/..."
    }
  }
}
```

---

### Event: `invoice.payment_failed`

**Triggert wenn:** Zahlung fehlgeschlagen

**Actions:**
1. Club-Model updaten:
   - `subscription_status` = `past_due`
2. `ClubSubscriptionEvent` erstellen (Churn-Risk-Tracking)
3. Payment-Failed-Email senden mit Retry-Informationen

**Payload (Auszug):**
```json
{
  "type": "invoice.payment_failed",
  "data": {
    "object": {
      "id": "in_...",
      "customer": "cus_...",
      "amount_due": 14900,
      "attempt_count": 1,
      "next_payment_attempt": 1699651200,
      "charge": {
        "failure_code": "card_declined",
        "failure_message": "Your card was declined"
      }
    }
  }
}
```

---

### Event: `invoice.payment_action_required`

**Triggert wenn:** 3D Secure Authentifizierung erforderlich

**Actions:**
1. 3D-Secure-Email senden mit Payment-Intent-Link

**Payload (Auszug):**
```json
{
  "type": "invoice.payment_action_required",
  "data": {
    "object": {
      "id": "in_...",
      "payment_intent": "pi_...",
      "amount_due": 14900
    }
  }
}
```

---

### Event: `payment_method.attached`

**Triggert wenn:** Zahlungsmethode zu Customer attached

**Actions:**
1. Logging (keine Model-Änderungen)

**Payload (Auszug):**
```json
{
  "type": "payment_method.attached",
  "data": {
    "object": {
      "id": "pm_...",
      "type": "card",
      "customer": "cus_...",
      "card": {
        "brand": "visa",
        "last4": "4242"
      }
    }
  }
}
```

---

### Event: `payment_method.detached`

**Triggert wenn:** Zahlungsmethode von Customer entfernt

**Actions:**
1. Club-Model updaten:
   - `payment_method_id` = `null` (falls es die Default-Methode war)

**Payload (Auszug):**
```json
{
  "type": "payment_method.detached",
  "data": {
    "object": {
      "id": "pm_...",
      "type": "card"
    }
  }
}
```

---

### Webhook-Testing

**Mit Stripe CLI:**
```bash
# Webhook-Events forwarden
stripe listen --forward-to localhost:8000/webhooks/stripe/club-subscriptions

# Test-Event manuell triggern
stripe trigger customer.subscription.created
stripe trigger invoice.payment_succeeded
stripe trigger invoice.payment_failed
```

**Mit curl:**
```bash
curl -X POST http://localhost:8000/webhooks/stripe/club-subscriptions \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: t=..." \
  -d @webhook_payload.json
```

---

## ❌ Error Handling

### Standard Error Response Format

```json
{
  "error": "Human-readable error message",
  "code": "ERROR_CODE",
  "details": {
    "field": "field_name",
    "reason": "specific reason"
  }
}
```

### HTTP Status Codes

| Status Code | Bedeutung | Beispiel |
|-------------|-----------|----------|
| `200 OK` | Erfolgreiche Request | Invoice abgerufen |
| `302 Found` | Redirect | PDF-Download |
| `400 Bad Request` | Validierungsfehler | Ungültiger `billing_interval` |
| `401 Unauthorized` | Nicht authentifiziert | Fehlender Auth-Token |
| `403 Forbidden` | Nicht autorisiert | User hat keine `manageBilling` Berechtigung |
| `404 Not Found` | Ressource nicht gefunden | Invoice existiert nicht |
| `500 Internal Server Error` | Server-Fehler | Stripe API-Fehler |

### Häufige Fehler

#### 1. Plan gehört nicht zum Tenant

**Status:** `403 Forbidden`

**Response:**
```json
{
  "error": "Plan does not belong to club's tenant"
}
```

**Ursache:** `plan.tenant_id !== club.tenant_id`

**Lösung:** Nur Pläne des gleichen Tenants auswählen

---

#### 2. Club hat keinen Stripe Customer

**Status:** `400 Bad Request`

**Response:**
```json
{
  "error": "Club has no active billing account"
}
```

**Ursache:** `club.stripe_customer_id` ist `null`

**Lösung:** Checkout durchführen, um Stripe Customer zu erstellen

---

#### 3. Subscription nicht aktiv

**Status:** `500 Internal Server Error`

**Response:**
```json
{
  "error": "Failed to swap plan: Subscription must be active to change plans"
}
```

**Ursache:** `club.subscription_status !== 'active'`

**Lösung:** Nur bei aktiven Subscriptions Plan-Wechsel erlauben

---

#### 4. Stripe API Fehler

**Status:** `500 Internal Server Error`

**Response:**
```json
{
  "error": "Failed to create checkout session: The price you provided is invalid"
}
```

**Ursache:** Ungültige Stripe Price ID oder API-Problem

**Lösung:** Logs prüfen, Stripe Dashboard prüfen, Price ID validieren

---

#### 5. Ungültige Zahlungsmethode

**Status:** `500 Internal Server Error`

**Response:**
```json
{
  "error": "Failed to attach payment method: Payment method already attached to a different customer"
}
```

**Ursache:** Payment Method gehört bereits einem anderen Customer

**Lösung:** Neue Payment Method erstellen statt bestehende zu attachen

---

### Error Logging

Alle Errors werden geloggt mit folgendem Format:

```php
Log::error('Club checkout initiation failed', [
    'club_id' => $club->id,
    'plan_id' => $planId,
    'error' => $e->getMessage(),
    'user_id' => auth()->id(),
    'trace' => $e->getTraceAsString(),
]);
```

**Log-Dateien:**
- `storage/logs/laravel.log` (Development)
- CloudWatch / Sentry (Production)

---

## 🧪 Stripe Test Data

### Test API Keys

```env
# Test Mode (Entwicklung)
STRIPE_KEY=pk_test_51...
STRIPE_SECRET=sk_test_51...
STRIPE_WEBHOOK_SECRET_CLUB=whsec_...
```

### Test Cards

#### Erfolgreiche Zahlungen

| Card Number | Brand | 3D Secure | Beschreibung |
|-------------|-------|-----------|--------------|
| `4242 4242 4242 4242` | Visa | Nein | Erfolgreiche Zahlung |
| `5555 5555 5555 4444` | Mastercard | Nein | Erfolgreiche Zahlung |
| `3782 822463 10005` | American Express | Nein | Erfolgreiche Zahlung |

#### 3D Secure Cards

| Card Number | Brand | Verhalten |
|-------------|-------|-----------|
| `4000 0027 6000 3184` | Visa | Benötigt 3D Secure Authentifizierung |
| `4000 0082 6000 3178` | Visa | 3DS: Authentifizierung erforderlich |

#### Fehlschlagende Zahlungen

| Card Number | Brand | Fehler |
|-------------|-------|--------|
| `4000 0000 0000 0002` | Visa | Card declined (generic_decline) |
| `4000 0000 0000 9995` | Visa | Insufficient funds |
| `4000 0000 0000 9987` | Visa | Lost card |
| `4000 0000 0000 9979` | Visa | Stolen card |

#### SEPA Direct Debit (Test)

| IBAN | Verhalten |
|------|-----------|
| `DE89370400440532013000` | Erfolgreiche Lastschrift |
| `DE62370400440532013001` | Fehlgeschlagene Lastschrift |

### Test-Daten für Billing Details

```json
{
  "name": "Test Club GmbH",
  "email": "test@basketmanager.pro",
  "phone": "+49 89 12345678",
  "address": {
    "line1": "Teststraße 123",
    "city": "München",
    "postal_code": "80331",
    "country": "DE"
  }
}
```

### CVV / Expiry (Beliebig für Test-Karten)

- **CVV:** Beliebige 3-stellige Zahl (z.B. `123`)
- **Expiry:** Beliebiges zukünftiges Datum (z.B. `12/25`)

---

## ⏱️ Rate Limiting

### Tenant-Based Rate Limiting

Alle API-Endpunkte unterliegen **Tenant-basiertem Rate Limiting**:

**Middleware:** `EnterpriseRateLimitMiddleware`

**Limits (pro Tenant):**
- **Free Tier:** 60 Requests / Minute
- **Basic Tier:** 120 Requests / Minute
- **Professional Tier:** 300 Requests / Minute
- **Enterprise Tier:** 1000 Requests / Minute

**Header-Response:**
```http
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 115
X-RateLimit-Reset: 1699564860
```

**Response (Rate Limit Exceeded - 429):**
```json
{
  "error": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

### Webhook Rate Limiting

Webhooks haben **KEIN Rate Limit**, da sie von Stripe kommen.

---

## 📝 Zusammenfassung

### Alle 17 API-Endpunkte

| # | Methode | Endpoint | Beschreibung |
|---|---------|----------|--------------|
| 1 | `GET` | `/club/{club}/subscription` | Subscription Overview |
| 2 | `POST` | `/club/{club}/checkout` | Checkout Session erstellen |
| 3 | `GET` | `/club/{club}/checkout/success` | Success Page |
| 4 | `GET` | `/club/{club}/checkout/cancel` | Cancel Page |
| 5 | `POST` | `/club/{club}/billing-portal` | Billing Portal Session |
| 6 | `GET` | `/club/{club}/billing/invoices` | Liste aller Rechnungen |
| 7 | `GET` | `/club/{club}/billing/invoices/{invoice}` | Einzelne Rechnung |
| 8 | `GET` | `/club/{club}/billing/invoices/upcoming` | Upcoming Invoice |
| 9 | `GET` | `/club/{club}/billing/invoices/{invoice}/pdf` | Invoice PDF Download |
| 10 | `GET` | `/club/{club}/billing/payment-methods` | Liste aller Payment Methods |
| 11 | `POST` | `/club/{club}/billing/payment-methods/setup` | SetupIntent erstellen |
| 12 | `POST` | `/club/{club}/billing/payment-methods/attach` | Payment Method attachen |
| 13 | `DELETE` | `/club/{club}/billing/payment-methods/{pm}` | Payment Method detachen |
| 14 | `PUT` | `/club/{club}/billing/payment-methods/{pm}` | Billing Details updaten |
| 15 | `POST` | `/club/{club}/billing/payment-methods/{pm}/default` | Default Payment Method |
| 16 | `POST` | `/club/{club}/billing/preview-plan-swap` | Plan-Wechsel-Vorschau |
| 17 | `POST` | `/club/{club}/billing/swap-plan` | Plan-Wechsel durchführen |

### Alle 11 Webhook-Events

1. `checkout.session.completed`
2. `customer.subscription.created`
3. `customer.subscription.updated`
4. `customer.subscription.deleted`
5. `invoice.payment_succeeded`
6. `invoice.payment_failed`
7. `invoice.created`
8. `invoice.finalized`
9. `invoice.payment_action_required`
10. `payment_method.attached`
11. `payment_method.detached`

---

## 🔗 Verwandte Dokumentation

- [Integration Guide](/docs/SUBSCRIPTION_INTEGRATION_GUIDE.md) - Setup & Konfiguration
- [Deployment Guide](/docs/SUBSCRIPTION_DEPLOYMENT_GUIDE.md) - Produktions-Deployment
- [Architecture Guide](/docs/SUBSCRIPTION_ARCHITECTURE.md) - System-Architektur
- [Admin Guide](/docs/SUBSCRIPTION_ADMIN_GUIDE.md) - Admin-Handbuch
- [Testing Guide](/docs/SUBSCRIPTION_TESTING.md) - Test-Strategie

---

## 📞 Support

Bei Fragen oder Problemen:
- **GitHub Issues:** https://github.com/yourorg/basketmanager-pro/issues
- **Email:** support@basketmanager.pro
- **Dokumentation:** https://docs.basketmanager.pro

---

**© 2025 BasketManager Pro** | Version 1.0 | Erstellt: 2025-11-03
