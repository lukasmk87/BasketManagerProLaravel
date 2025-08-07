# Rollen-Hierarchie Diagramme - BasketManager Pro

## 🎯 Visuelle Darstellung der Rollen-Struktur

Diese Datei enthält verschiedene Mermaid-Diagramme zur Visualisierung der Rollen-Hierarchie und Beziehungen im BasketManager Pro System.

---

## 📊 Haupthierarchie-Diagramm

```mermaid
graph TD
    SA[🔴 Super Admin<br/>super_admin<br/>136 Permissions]
    
    SA --> A[🔴 System Administrator<br/>admin<br/>135 Permissions]
    
    A --> CA[🔵 Club Administrator<br/>club_admin<br/>65 Permissions]
    
    CA --> T[🟢 Trainer/Head Coach<br/>trainer<br/>45 Permissions]
    CA --> TM[🟡 Team Manager<br/>team_manager<br/>20 Permissions]
    CA --> S[🟡 Scorer/Statistician<br/>scorer<br/>8 Permissions]
    CA --> R[🟡 Referee<br/>referee<br/>6 Permissions]
    
    T --> AC[🟢 Assistant Coach<br/>assistant_coach<br/>25 Permissions]
    
    A --> P[🟠 Player<br/>player<br/>12 Permissions]
    A --> PAR[🟣 Parent/Guardian<br/>parent<br/>8 Permissions]
    A --> G[⚪ Guest/Fan<br/>guest<br/>3 Permissions]
    
    %% Styling
    classDef superAdmin fill:#fee2e2,stroke:#dc2626,stroke-width:3px,color:#991b1b
    classDef admin fill:#fef2f2,stroke:#dc2626,stroke-width:2px,color:#991b1b
    classDef clubAdmin fill:#dbeafe,stroke:#2563eb,stroke-width:2px,color:#1d4ed8
    classDef trainer fill:#dcfce7,stroke:#16a34a,stroke-width:2px,color:#166534
    classDef specialist fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#92400e
    classDef player fill:#fed7aa,stroke:#ea580c,stroke-width:2px,color:#c2410c
    classDef parent fill:#f3e8ff,stroke:#9333ea,stroke-width:2px,color:#7c3aed
    classDef guest fill:#f9fafb,stroke:#6b7280,stroke-width:2px,color:#4b5563
    
    class SA superAdmin
    class A admin
    class CA clubAdmin
    class T,AC trainer
    class TM,S,R specialist
    class P player
    class PAR parent
    class G guest
```

---

## 🏢 Organisationsstruktur-Diagramm

```mermaid
graph TB
    subgraph "🏀 BasketManager Pro System"
        subgraph "🔴 System Level"
            SA[Super Admin]
            A[Administrator]
        end
        
        subgraph "🔵 Club Level"
            CA[Club Administrator]
        end
        
        subgraph "🟢 Team Level"
            T[Trainer]
            AC[Assistant Coach]
            TM[Team Manager]
        end
        
        subgraph "🟡 Game Level"
            S[Scorer]
            R[Referee]
        end
        
        subgraph "🟠 Player Level"
            P[Player]
        end
        
        subgraph "🟣 Family Level"
            PAR[Parent/Guardian]
        end
        
        subgraph "⚪ Public Level"
            G[Guest/Fan]
        end
    end
    
    SA --> A
    A --> CA
    CA --> T
    CA --> TM
    T --> AC
    T --> S
    T --> R
    CA --> P
    P --> PAR
    A --> G
```

---

## 🎛️ Dashboard-Zuordnungs-Diagramm

```mermaid
graph LR
    subgraph "Roles"
        SA[🔴 Super Admin]
        A[🔴 Admin]
        CA[🔵 Club Admin]
        T[🟢 Trainer]
        AC[🟢 Assistant Coach]
        TM[🟡 Team Manager]
        S[🟡 Scorer]
        R[🟡 Referee]
        P[🟠 Player]
        PAR[🟣 Parent]
        G[⚪ Guest]
    end
    
    subgraph "Dashboards"
        AD[📊 AdminDashboard<br/>System-Administration]
        CAD[🏢 ClubAdminDashboard<br/>Club-Verwaltung]
        TD[👨‍🏫 TrainerDashboard<br/>Trainer-Dashboard]
        PD[🏀 PlayerDashboard<br/>Spieler-Dashboard]
        BD[📋 BasicDashboard<br/>Basic Dashboard]
    end
    
    SA --> AD
    A --> AD
    CA --> CAD
    T --> TD
    AC --> TD
    TM --> BD
    S --> BD
    R --> BD
    P --> PD
    PAR --> BD
    G --> BD
    
    %% Styling
    classDef adminRole fill:#fee2e2,stroke:#dc2626
    classDef clubRole fill:#dbeafe,stroke:#2563eb
    classDef trainerRole fill:#dcfce7,stroke:#16a34a
    classDef specialRole fill:#fef3c7,stroke:#d97706
    classDef playerRole fill:#fed7aa,stroke:#ea580c
    classDef parentRole fill:#f3e8ff,stroke:#9333ea
    classDef guestRole fill:#f9fafb,stroke:#6b7280
    
    classDef adminDash fill:#fca5a5,stroke:#dc2626,stroke-width:3px
    classDef clubDash fill:#93c5fd,stroke:#2563eb,stroke-width:3px
    classDef trainerDash fill:#86efac,stroke:#16a34a,stroke-width:3px
    classDef playerDash fill:#fdba74,stroke:#ea580c,stroke-width:3px
    classDef basicDash fill:#d1d5db,stroke:#6b7280,stroke-width:3px
    
    class SA,A adminRole
    class CA clubRole
    class T,AC trainerRole
    class TM,S,R specialRole
    class P playerRole
    class PAR parentRole
    class G guestRole
    
    class AD adminDash
    class CAD clubDash
    class TD trainerDash
    class PD playerDash
    class BD basicDash
```

---

## 🔐 Berechtigungs-Pyramide

```mermaid
graph TD
    subgraph "Permission Levels"
        L1["🔴 Level 1: Super Admin<br/>All 136 Permissions<br/>System Critical Operations"]
        L2["🔴 Level 2: Admin<br/>135 Permissions<br/>System Management"]
        L3["🔵 Level 3: Club Admin<br/>65 Permissions<br/>Club Management"]
        L4["🟢 Level 4: Trainer<br/>45 Permissions<br/>Team & Player Management"]
        L5["🟢 Level 5: Assistant Coach<br/>25 Permissions<br/>Limited Team Access"]
        L6["🟡 Level 6: Specialists<br/>6-20 Permissions<br/>Function-Specific"]
        L7["🟠 Level 7: Player<br/>12 Permissions<br/>Personal & Team View"]
        L8["🟣 Level 8: Parent<br/>8 Permissions<br/>Child-Related Access"]
        L9["⚪ Level 9: Guest<br/>3 Permissions<br/>Public Information"]
    end
    
    L1 --> L2
    L2 --> L3
    L3 --> L4
    L4 --> L5
    L3 --> L6
    L2 --> L7
    L7 --> L8
    L2 --> L9
    
    style L1 fill:#fee2e2,stroke:#dc2626,stroke-width:4px
    style L2 fill:#fef2f2,stroke:#dc2626,stroke-width:3px
    style L3 fill:#dbeafe,stroke:#2563eb,stroke-width:3px
    style L4 fill:#dcfce7,stroke:#16a34a,stroke-width:3px
    style L5 fill:#dcfce7,stroke:#16a34a,stroke-width:2px
    style L6 fill:#fef3c7,stroke:#d97706,stroke-width:2px
    style L7 fill:#fed7aa,stroke:#ea580c,stroke-width:2px
    style L8 fill:#f3e8ff,stroke:#9333ea,stroke-width:2px
    style L9 fill:#f9fafb,stroke:#6b7280,stroke-width:2px
```

---

## 🌐 Datenzugriff-Scope-Diagramm

```mermaid
graph TB
    subgraph "Data Access Scopes"
        subgraph "🌍 Global Access"
            SA2[Super Admin]
            A2[Admin]
        end
        
        subgraph "🏢 Club Scope"
            CA2[Club Admin]
        end
        
        subgraph "👥 Team Scope"
            T2[Trainer]
            AC2[Assistant Coach]
            TM2[Team Manager]
        end
        
        subgraph "🎮 Game Scope"
            S2[Scorer]
            R2[Referee]
        end
        
        subgraph "👤 Personal Scope"
            P2[Player]
            PAR2[Parent]
        end
        
        subgraph "📖 Public Scope"
            G2[Guest]
        end
    end
    
    subgraph "Data Access Rights"
        ALL[All System Data]
        CLUB[Club-specific Data]
        TEAM[Team-specific Data]
        GAME[Game-specific Data]
        PERSONAL[Personal Data Only]
        PUBLIC[Public Data Only]
    end
    
    SA2 --> ALL
    A2 --> ALL
    CA2 --> CLUB
    T2 --> TEAM
    AC2 --> TEAM
    TM2 --> TEAM
    S2 --> GAME
    R2 --> GAME
    P2 --> PERSONAL
    PAR2 --> PERSONAL
    G2 --> PUBLIC
    
    style ALL fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    style CLUB fill:#dbeafe,stroke:#2563eb,stroke-width:2px
    style TEAM fill:#dcfce7,stroke:#16a34a,stroke-width:2px
    style GAME fill:#fef3c7,stroke:#d97706,stroke-width:2px
    style PERSONAL fill:#fed7aa,stroke:#ea580c,stroke-width:2px
    style PUBLIC fill:#f9fafb,stroke:#6b7280,stroke-width:2px
```

---

## 🔄 Permission-Vererbung-Diagramm

```mermaid
graph TD
    subgraph "Permission Inheritance Flow"
        SUPER["🔴 Super Admin<br/>- ALL Permissions<br/>- System Critical<br/>- Force Operations"]
        
        ADMIN["🔴 Admin<br/>- 135/136 Permissions<br/>- System Management<br/>- User Management<br/>- All CRUD Operations"]
        
        CLUB["🔵 Club Admin<br/>- Club Management<br/>- Team Management<br/>- Player Management<br/>- Financial Reports"]
        
        TRAINER["🟢 Trainer<br/>- Team Operations<br/>- Player Statistics<br/>- Game Scoring<br/>- Training Management"]
        
        ASSIST["🟢 Assistant Coach<br/>- Limited Team View<br/>- Game Scoring<br/>- Training Support"]
        
        MANAGER["🟡 Team Manager<br/>- Team Organization<br/>- Game Planning<br/>- Communication"]
        
        SCORER["🟡 Scorer<br/>- Game Scoring<br/>- Statistics View"]
        
        PLAYER["🟠 Player<br/>- Personal Stats<br/>- Team View<br/>- Game View"]
        
        PARENT["🟣 Parent<br/>- Child's Data<br/>- Emergency Contacts"]
        
        GUEST["⚪ Guest<br/>- Public Information<br/>- Game Results"]
    end
    
    SUPER -.-> ADMIN
    ADMIN -.-> CLUB
    CLUB -.-> TRAINER
    CLUB -.-> MANAGER
    CLUB -.-> SCORER
    TRAINER -.-> ASSIST
    ADMIN -.-> PLAYER
    PLAYER -.-> PARENT
    ADMIN -.-> GUEST
    
    %% Permission inheritance shown with dotted lines
```

---

## 🎨 Legende und Farbschema

### Farb-Codierung

| Farbe | Beschreibung | Hex-Code | Rollen |
|-------|--------------|----------|--------|
| 🔴 **Rot** | System-Administration | `#dc2626` | Super Admin, Admin |
| 🔵 **Blau** | Club-Verwaltung | `#2563eb` | Club Admin |
| 🟢 **Grün** | Trainer-Rollen | `#16a34a` | Trainer, Assistant Coach |
| 🟡 **Gelb** | Spezial-Funktionen | `#d97706` | Team Manager, Scorer, Referee |
| 🟠 **Orange** | Spieler-Rollen | `#ea580c` | Player |
| 🟣 **Lila** | Eltern-Rollen | `#9333ea` | Parent/Guardian |
| ⚪ **Grau** | Gast-Rollen | `#6b7280` | Guest/Fan |

### Icon-Bedeutungen

- 🔴 = Höchste Berechtigungsebene
- 🔵 = Club-spezifische Verwaltung
- 🟢 = Team-fokussierte Rollen
- 🟡 = Funktions-spezifische Rollen
- 🟠 = Spieler-Perspektive
- 🟣 = Familien-Kontext
- ⚪ = Öffentlicher Zugang

---

## 📝 Verwendung der Diagramme

### In Dokumentation
1. **Kopiere den Mermaid-Code** in Markdown-Dokumente
2. **GitHub und GitLab** rendern diese automatisch
3. **VS Code** mit Mermaid-Extension zeigt Live-Preview

### Online-Tools
- [Mermaid Live Editor](https://mermaid.live/)
- [GitHub/GitLab** Markdown-Renderer
- **Notion, Confluence** mit Mermaid-Support

### Export-Optionen
- **SVG** für skalierbare Grafiken
- **PNG** für Präsentationen
- **PDF** für Dokumentation

---

*Letzte Aktualisierung: August 2025*
*BasketManager Pro - Visuelle Rollen-Hierarchie*