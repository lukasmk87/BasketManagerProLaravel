# Berechtigungs-Matrix - BasketManager Pro

## 📊 Vollständige Permissions-Matrix

Diese Tabelle zeigt alle **136 Berechtigungen** und ihre Zuweisung zu den **11 Systemrollen**.

**Legende:**
- ✅ = Berechtigung zugewiesen
- ❌ = Berechtigung nicht zugewiesen
- 🔴 = Super Admin / Admin
- 🔵 = Club Admin
- 🟢 = Trainer Rollen
- 🟡 = Spezialrollen
- 🟠 = Player
- 🟣 = Parent
- ⚪ = Guest

---

## 🔐 User Management (6 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view users** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **create users** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit users** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete users** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **impersonate users** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage user roles** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🏢 Club Management (7 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view clubs** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **create clubs** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit clubs** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete clubs** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage club settings** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage club members** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view club statistics** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 👥 Team Management (8 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view teams** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| **create teams** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit teams** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete teams** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage team rosters** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **assign team coaches** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view team statistics** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **manage team settings** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🏀 Player Management (9 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view players** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **create players** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit players** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete players** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view player statistics** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ | ✅ | ❌ |
| **edit player statistics** | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage player contracts** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view player medical info** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit player medical info** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🎮 Game Management (8 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view games** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **create games** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit games** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete games** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **score games** | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **view live games** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **manage game officials** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **publish game results** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 📊 Statistics & Analytics (5 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view statistics** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **export statistics** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **generate reports** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view analytics dashboard** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage statistics settings** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🏋️ Training Management (6 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view training sessions** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **create training sessions** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit training sessions** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete training sessions** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage training drills** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view training statistics** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |

---

## 🚨 Emergency Contacts (4 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view emergency contacts** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **edit emergency contacts** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **generate emergency qr codes** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **access emergency information** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 💬 Communication (3 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **send notifications** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage announcements** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **access messaging system** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ |

---

## ⚙️ System Administration (6 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **access admin panel** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage system settings** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view activity logs** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage backups** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage integrations** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **view system statistics** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 📸 Media Management (3 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **upload media** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage media library** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete media** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🏆 Tournament Management (5 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view tournaments** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **create tournaments** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **edit tournaments** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **delete tournaments** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage tournament brackets** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 💰 Financial Management (3 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **view financial data** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage budgets** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **generate financial reports** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🔒 GDPR & Compliance (3 Permissions)

| Permission | 🔴 Super Admin | 🔴 Admin | 🔵 Club Admin | 🟢 Trainer | 🟢 Ass. Coach | 🟡 Team Mgr | 🟡 Scorer | 🟡 Referee | 🟠 Player | 🟣 Parent | ⚪ Guest |
|------------|----------------|----------|---------------|------------|---------------|------------|-----------|-----------|----------|----------|----------|
| **export user data** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **manage consent records** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **handle data deletion requests** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 📈 Berechtigungs-Statistiken

### Anzahl Permissions pro Rolle

| Rolle | Anzahl Permissions | Percentage |
|-------|-------------------|------------|
| 🔴 **Super Admin** | 136 | 100% |
| 🔴 **Admin** | 135 | 99.3% |
| 🔵 **Club Admin** | 65 | 47.8% |
| 🟢 **Trainer** | 45 | 33.1% |
| 🟢 **Assistant Coach** | 25 | 18.4% |
| 🟡 **Team Manager** | 20 | 14.7% |
| 🟡 **Scorer** | 8 | 5.9% |
| 🟡 **Referee** | 6 | 4.4% |
| 🟠 **Player** | 12 | 8.8% |
| 🟣 **Parent** | 8 | 5.9% |
| ⚪ **Guest** | 3 | 2.2% |

### Kritische Permissions (nur Admin/Super Admin)

- **impersonate users** - Nur Super Admin
- **delete users** - Admin + Super Admin
- **manage system settings** - Admin + Super Admin
- **access admin panel** - Admin + Super Admin
- **manage backups** - Admin + Super Admin
- **export user data** - Admin + Super Admin
- **handle data deletion requests** - Admin + Super Admin

### Häufig vergebene Permissions

- **view games** - 11/11 Rollen (100%)
- **view teams** - 8/11 Rollen (73%)
- **view players** - 9/11 Rollen (82%)
- **view statistics** - 8/11 Rollen (73%)

---

## 🔄 Permission-Vererbung und Hierarchie

### Vererbungs-Prinzipien

1. **Super Admin** erbt alle Permissions automatisch
2. **Admin** hat fast alle Permissions (außer Super Admin-spezifische)
3. **Club Admin** fokussiert auf Club-bezogene Permissions
4. **Trainer-Rollen** haben Team- und Spieler-spezifische Rechte
5. **Spezialrollen** haben funktions-spezifische Permissions
6. **Player/Parent/Guest** haben minimale, sichtbare Permissions

### Scope-Beschränkungen

- **Club Admin**: Nur eigene Club-Daten
- **Trainer**: Nur zugewiesene Teams
- **Assistant Coach**: Nur zugewiesene Teams (read-heavy)
- **Team Manager**: Nur zugewiesene Teams (organisatorisch)
- **Player**: Nur eigene Daten und Team-Kontext
- **Parent**: Nur Daten der eigenen Kinder

---

*Letzte Aktualisierung: August 2025*
*BasketManager Pro - Vollständige Berechtigungs-Matrix*