# 👤 Club Subscription Admin & User-Guide

**Version:** 1.0
**Erstellt:** 2025-11-03
**Sprache:** Deutsch
**Zielgruppe:** Club-Administratoren, Billing-Manager, Endbenutzer

---

## 📋 Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Subscription-Pläne](#subscription-pläne)
3. [Abonnement abschließen](#abonnement-abschließen)
4. [Billing-Verwaltung](#billing-verwaltung)
5. [Zahlungsmethoden verwalten](#zahlungsmethoden-verwalten)
6. [Rechnungen & Zahlungshistorie](#rechnungen--zahlungshistorie)
7. [Plan upgraden/downgraden](#plan-upgradendowngraden)
8. [Abonnement kündigen](#abonnement-kündigen)
9. [Troubleshooting](#troubleshooting)

---

## 🔍 Überblick

Das **Club Subscription-System** ermöglicht es jedem Club, sein eigenes Abonnement unabhängig vom Tenant zu verwalten. Als Club-Administrator können Sie:

✅ Subscription-Pläne auswählen und abonnieren
✅ Zahlungsmethoden hinzufügen und verwalten
✅ Rechnungen einsehen und herunterladen
✅ Pläne upgraden oder downgraden
✅ Abonnements kündigen oder fortsetzen

---

## 💎 Subscription-Pläne

### Verfügbare Pläne

| Plan | Preis (Monatlich) | Preis (Jährlich) | Features |
|------|-------------------|------------------|----------|
| **Free Club** | €0 | €0 | Basis-Features, max. 2 Teams |
| **Standard Club** | €49 | €441 (10% Rabatt) | Live-Scoring, max. 10 Teams |
| **Premium Club** | €149 | €1,341 (10% Rabatt) | Advanced Stats, Video, max. 50 Teams |
| **Enterprise Club** | €299 | €2,691 (10% Rabatt) | Alle Features, max. 100 Teams |

### Plan-Features im Detail

**Free Club:**
- ✅ Basis Team-Management
- ✅ Spieler-Profile
- ❌ Live-Scoring
- ❌ Advanced Statistics

**Standard Club:**
- ✅ Alle Free-Features
- ✅ **Live-Scoring** während Spielen
- ✅ Basis-Statistiken (FG%, Punkte, Rebounds)
- ✅ Training-Management
- ❌ Advanced Stats (PER, TS%, etc.)

**Premium Club:**
- ✅ Alle Standard-Features
- ✅ **Advanced Statistics** (PER, True Shooting%, etc.)
- ✅ **Video-Analysis**
- ✅ Erweiterte Statistik-Dashboards
- ✅ API-Zugriff

**Enterprise Club:**
- ✅ Alle Premium-Features
- ✅ **Unlimitierte Teams & Spieler**
- ✅ **Priority Support**
- ✅ **Custom Features auf Anfrage**
- ✅ **Dedicated Account Manager**

---

## 🛒 Abonnement abschließen

### Schritt 1: Subscription-Seite öffnen

1. Loggen Sie sich als Club-Administrator ein
2. Navigieren Sie zu **Club → Subscription** im Menü
3. Sie sehen die Subscription-Übersicht und verfügbare Pläne

### Schritt 2: Plan auswählen

1. Wählen Sie zwischen **Monatlicher** oder **Jährlicher** Zahlung
   - 💡 **Tipp:** Jährliche Zahlung spart 10%!
2. Klicken Sie auf **"Jetzt buchen"** beim gewünschten Plan

### Schritt 3: Stripe Checkout

Sie werden zu **Stripe Checkout** weitergeleitet (sicher & GDPR-konform):

1. **Kontakt-Informationen:**
   - Email (für Rechnungen)
   - Name des Clubs

2. **Zahlungsmethode auswählen:**
   - 💳 **Kreditkarte / EC-Karte** (Visa, Mastercard, Amex)
   - 🏦 **SEPA Lastschrift** (deutsche Bankkonten)
   - ⚡ **SOFORT Überweisung**
   - 🇩🇪 **Giropay**
   - 🇦🇹 **EPS** (Österreich)

3. **Zahlungsdaten eingeben**

4. **Bestätigen**

### Schritt 4: Erfolg!

Nach erfolgreichem Checkout:
- ✅ Ihr Abonnement ist **sofort aktiv**
- ✅ Sie erhalten eine **Bestätigungs-Email**
- ✅ Ihre **erste Rechnung** wird per Email zugestellt
- ✅ Alle Plan-Features sind verfügbar

---

## 💼 Billing-Verwaltung

### Billing-Dashboard öffnen

**Navigation:** Club → Subscription → Billing

Das Billing-Dashboard zeigt:
- 📊 Aktueller Plan & Status
- 💳 Aktive Zahlungsmethoden
- 🧾 Rechnungshistorie
- 📅 Nächstes Abrechnungsdatum

### Subscription-Status verstehen

| Status | Bedeutung | Aktion erforderlich |
|--------|-----------|---------------------|
| **✅ Active** | Abonnement aktiv, alles läuft | Keine |
| **🔵 Trial** | Test-Phase läuft noch (X Tage verbleibend) | Optional: Zahlungsmethode hinterlegen |
| **⚠️ Past Due** | Zahlung fehlgeschlagen, Grace Period | Zahlungsmethode prüfen/aktualisieren |
| **❌ Canceled** | Abonnement gekündigt, läuft bis Periodenende | Optional: Kündigung rückgängig machen |
| **⏳ Incomplete** | Checkout nicht abgeschlossen | Checkout abschließen |

---

## 💳 Zahlungsmethoden verwalten

### Neue Zahlungsmethode hinzufügen

1. Gehe zu **Club → Subscription → Zahlungsmethoden**
2. Klicke auf **"Zahlungsmethode hinzufügen"**
3. Wähle Zahlungsmethoden-Typ:
   - **Kreditkarte**
   - **SEPA Lastschrift**
   - **SOFORT / Giropay** (einmalige Zahlung)
4. Gib Zahlungsdaten ein
5. Optional: **"Als Standard festlegen"** aktivieren
6. Klicke **"Hinzufügen"**

### Zahlungsmethode als Standard festlegen

1. Finde Zahlungsmethode in der Liste
2. Klicke auf **"⋮"** (Mehr-Optionen)
3. Wähle **"Als Standard festlegen"**

Die Standard-Zahlungsmethode wird für **zukünftige automatische Zahlungen** verwendet.

### Zahlungsmethode löschen

1. Finde Zahlungsmethode in der Liste
2. Klicke auf **"⋮"** (Mehr-Optionen)
3. Wähle **"Löschen"**
4. Bestätige Löschung

⚠️ **Wichtig:** Mindestens eine Zahlungsmethode muss hinterlegt sein, wenn Sie ein aktives Abonnement haben.

### Billing-Details aktualisieren

1. Klicke auf Zahlungsmethode
2. Wähle **"Bearbeiten"**
3. Aktualisiere:
   - Name
   - Email
   - Rechnungsadresse
4. Speichern

---

## 🧾 Rechnungen & Zahlungshistorie

### Rechnungen anzeigen

1. Gehe zu **Club → Subscription → Rechnungen**
2. Sie sehen:
   - 📋 Alle bisherigen Rechnungen
   - 🔮 **Vorschau der nächsten Rechnung**
   - 📊 Rechnungs-Status (Bezahlt, Offen, Überfällig)

### Rechnung herunterladen (PDF)

1. Finde Rechnung in der Liste
2. Klicke auf **"PDF herunterladen"** Button
3. PDF wird in neuem Tab geöffnet / heruntergeladen

### Rechnung bezahlen (bei fehlgeschlagener Zahlung)

Falls eine Zahlung fehlgeschlagen ist:

1. Gehe zu **Rechnungen**
2. Finde **"Offen"** oder **"Überfällig"** Rechnung
3. Klicke auf **"Jetzt bezahlen"**
4. Wähle Zahlungsmethode
5. Bestätige Zahlung

---

## ⬆️ Plan upgraden/downgraden

### Upgrade (zu höherem Plan)

1. Gehe zu **Club → Subscription**
2. Wähle gewünschten Plan (z.B. Premium → Enterprise)
3. Klicke auf **"Upgraden"**
4. **Proration Preview** wird angezeigt:
   - ✅ Gutschrift für verbleibende Zeit des alten Plans
   - ➕ Kosten für neuen Plan (anteilig)
   - 💰 **Gesamt-Differenz** (sofort fällig)
5. Klicke **"Upgrade bestätigen"**

**Beispiel Proration:**
```
Aktueller Plan: Premium (€149/Monat)
Neuer Plan: Enterprise (€299/Monat)
Verbleibende Zeit: 15 Tage (50% des Monats)

Gutschrift: -€74.50 (50% von €149)
Neue Kosten: +€149.50 (50% von €299)
Gesamt: €75.00 (sofort fällig)
```

**Hinweis:** Upgrade ist **sofort wirksam**!

### Downgrade (zu niedrigerem Plan)

1. Gehe zu **Club → Subscription**
2. Wähle gewünschten Plan (z.B. Enterprise → Standard)
3. Klicke auf **"Downgraden"**
4. **Proration Preview** wird angezeigt
5. Klicke **"Downgrade bestätigen"**

**⚠️ Wichtig bei Downgrade:**
- Downgrade wirkt **am Ende der aktuellen Abrechnungsperiode**
- Sie können den höheren Plan bis zum Periodenende nutzen
- Keine sofortige Rückerstattung (Gutschrift für nächste Rechnung)

**Beispiel:**
```
Aktueller Plan: Premium (€149/Monat)
Neuer Plan: Standard (€49/Monat)
Nächste Abrechnung: 01.12.2025

→ Premium läuft bis 01.12.2025
→ Ab 01.12.2025: Standard (€49/Monat)
```

---

## ❌ Abonnement kündigen

### Kündigung zum Periodenende

1. Gehe zu **Club → Subscription**
2. Klicke auf **"Abonnement verwalten"**
3. Wähle **"Kündigen"**
4. Wähle Kündigungsgrund (optional)
5. Klicke **"Kündigung bestätigen"**

**Was passiert:**
- ✅ Ihr Abonnement läuft **bis zum Ende der bezahlten Periode**
- ✅ Sie haben weiterhin Zugriff auf alle Features
- ✅ Keine weiteren Zahlungen werden eingezogen
- 📅 Am Periodenende: Abonnement wird inaktiv, Free-Plan wird aktiviert

**Beispiel:**
```
Aktuelles Abonnement: Premium (€149/Monat)
Letzte Zahlung: 01.11.2025
Nächste Abrechnung: 01.12.2025

→ Kündigung eingereicht: 15.11.2025
→ Premium läuft bis: 01.12.2025
→ Ab 01.12.2025: Free Plan
```

### Sofortige Kündigung

⚠️ **Nur für Admins verfügbar**

Kontaktieren Sie den Support für sofortige Kündigung:
- support@basketmanager.pro
- **Keine Rückerstattung** für bereits bezahlte Zeit

### Kündigung rückgängig machen

Falls Sie Ihre Meinung ändern (vor Periodenende):

1. Gehe zu **Club → Subscription**
2. Sie sehen Banner: **"Abonnement läuft bis XX.XX.XXXX aus"**
3. Klicke auf **"Kündigung rückgängig machen"**
4. Bestätigen

→ Ihr Abonnement wird automatisch verlängert!

---

## 🐛 Troubleshooting

### Problem: Zahlung fehlgeschlagen

**Symptome:**
- Email: "Ihre Zahlung ist fehlgeschlagen"
- Status: "Past Due"

**Lösungen:**
1. **Zahlungsmethode prüfen:**
   - Ist die Karte abgelaufen?
   - Ist ausreichend Deckung vorhanden?
   - SEPA-Lastschrift: Mandat gültig?

2. **Neue Zahlungsmethode hinzufügen:**
   - Gehe zu Zahlungsmethoden
   - Füge neue Methode hinzu
   - Setze als Standard

3. **Rechnung manuell bezahlen:**
   - Gehe zu Rechnungen
   - Klicke "Jetzt bezahlen" bei offener Rechnung

**Grace Period:** Sie haben **3 Tage** Zeit, bevor das Abonnement deaktiviert wird.

---

### Problem: Checkout funktioniert nicht

**Symptome:**
- Fehler beim Klick auf "Jetzt buchen"
- Checkout-Seite lädt nicht

**Lösungen:**
1. **Browser aktualisieren:** Strg+F5 (Windows) oder Cmd+Shift+R (Mac)
2. **Anderen Browser testen:** Chrome, Firefox, Safari
3. **Cookie & Cache leeren**
4. **Ad-Blocker deaktivieren** (kann Stripe blockieren)
5. **Support kontaktieren:** support@basketmanager.pro

---

### Problem: Keine Rechnungen sichtbar

**Symptome:**
- Rechnungen-Seite ist leer
- "Keine Rechnungen verfügbar"

**Ursachen & Lösungen:**
1. **Noch keine Zahlung erfolgt:** Erste Rechnung kommt nach erstem Abrechnungszyklus
2. **Filter aktiv:** Prüfe Status-Filter (Alle, Bezahlt, Offen)
3. **Subscription erst kürzlich abgeschlossen:** Warte 1-2 Minuten

---

### Problem: Features sind nach Upgrade nicht verfügbar

**Symptome:**
- Plan wurde upgraded
- Features (z.B. Video-Analysis) sind nicht sichtbar

**Lösungen:**
1. **Ausloggen & Neu einloggen:**
   - Feature-Cache wird aktualisiert
2. **Seite neu laden:** Strg+F5
3. **Subscription-Status prüfen:**
   - Gehe zu Club → Subscription
   - Status muss "Active" sein
4. **Warte 5 Minuten:**
   - Webhook-Verarbeitung kann 1-5 Min dauern
5. **Support kontaktieren** falls Problem besteht

---

### Problem: Email-Benachrichtigungen kommen nicht an

**Symptome:**
- Keine Welcome-Email nach Checkout
- Keine Rechnungs-Emails

**Lösungen:**
1. **Spam-Ordner prüfen:**
   - Suche nach "noreply@basketmanager.pro"
   - Markiere als "Kein Spam"
2. **Email-Adresse prüfen:**
   - Gehe zu Club → Einstellungen
   - Prüfe Billing-Email
3. **Email-Preferences prüfen:**
   - Gehe zu Club → Subscription → Benachrichtigungen
   - Stelle sicher, dass Benachrichtigungen aktiviert sind

---

## 📞 Support & Kontakt

### Hilfe benötigt?

**Email:** support@basketmanager.pro
**Telefon:** +49 XXX XXXXXXX (Mo-Fr, 9-17 Uhr)
**Live-Chat:** In-App-Chat (unten rechts)

### Dokumentation

- **API-Referenz:** [SUBSCRIPTION_API_REFERENCE.md](/docs/SUBSCRIPTION_API_REFERENCE.md)
- **Entwickler-Guide:** [SUBSCRIPTION_INTEGRATION_GUIDE.md](/docs/SUBSCRIPTION_INTEGRATION_GUIDE.md)
- **Deployment-Guide:** [SUBSCRIPTION_DEPLOYMENT_GUIDE.md](/docs/SUBSCRIPTION_DEPLOYMENT_GUIDE.md)
- **Architektur-Guide:** [SUBSCRIPTION_ARCHITECTURE.md](/docs/SUBSCRIPTION_ARCHITECTURE.md)

---

## 💡 Tipps & Best Practices

### 1. Jährliche Zahlung nutzen

💰 **Sparen Sie 10%** bei jährlicher Zahlung!

Beispiel:
- Monatlich: €149 × 12 = **€1,788/Jahr**
- Jährlich: €1,341/Jahr → **€447 gespart!**

### 2. Trial-Periode nutzen

Viele Pläne bieten **14 Tage kostenlose Testphase**:
- ✅ Alle Features verfügbar
- ✅ Keine Zahlungsdaten erforderlich (bei manchen Plänen)
- ✅ Jederzeit kündbar

### 3. Zahlungsmethoden-Backup

Fügen Sie **2 Zahlungsmethoden** hinzu:
- Primär: Kreditkarte
- Backup: SEPA Lastschrift

→ Vermeidet fehlgeschlagene Zahlungen bei abgelaufenen Karten

### 4. Rechnungen für Buchhaltung

Laden Sie Rechnungen regelmäßig herunter:
- 📥 PDF-Download verfügbar
- 🗂️ Speichern Sie PDFs in Buchhaltungs-Software
- 📧 Leiten Sie Rechnung an Steuerberater weiter

### 5. Plan-Wechsel strategisch planen

**Upgrade:**
- Jederzeit möglich
- Sofort wirksam
- Anteilige Berechnung

**Downgrade:**
- Besser am **Monatsende** durchführen
- Nutzen Sie den höheren Plan bis zum Ende
- Keine Rückerstattung

---

## ✅ Häufig gestellte Fragen (FAQ)

### Kann ich meinen Plan jederzeit wechseln?

Ja! Plan-Wechsel sind **jederzeit möglich**:
- **Upgrade:** Sofort wirksam, anteilige Berechnung
- **Downgrade:** Am Ende der Abrechnungsperiode

### Was passiert mit meinen Daten bei Kündigung?

- **Während Grace Period (30 Tage):** Alle Daten bleiben erhalten
- **Nach 30 Tagen:** Daten werden archiviert (read-only)
- **Nach 90 Tagen:** Daten werden gelöscht (GDPR-konform)

### Kann ich eine Rückerstattung bekommen?

**Allgemein:** Keine Rückerstattungen für bereits bezahlte Zeiträume.

**Ausnahmen:**
- Technische Probleme (nachweislich)
- Doppelte Zahlungen
- Fehlerhafte Abrechnung

Kontaktieren Sie den Support: support@basketmanager.pro

### Welche Zahlungsmethoden werden akzeptiert?

✅ Kreditkarte (Visa, Mastercard, Amex)
✅ EC-Karte (Debit-Karte)
✅ SEPA Lastschrift (deutsche Bankkonten)
✅ SOFORT Überweisung
✅ Giropay
✅ EPS (Österreich)
✅ Bancontact (Belgien)
✅ iDEAL (Niederlande)

### Sind meine Zahlungsdaten sicher?

**Absolut!** Wir verwenden **Stripe** (PCI DSS Level 1 zertifiziert):
- 🔒 256-Bit SSL/TLS Verschlüsselung
- 🏦 Zahlungsdaten werden NICHT auf unseren Servern gespeichert
- 🇪🇺 GDPR-konform
- 🛡️ 3D Secure für zusätzliche Sicherheit

---

**© 2025 BasketManager Pro** | Version 1.0 | Erstellt: 2025-11-03
