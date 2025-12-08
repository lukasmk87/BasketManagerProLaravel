/**
 * Composable für Marketing-Style Feature-Texte in Club-Subscriptions
 *
 * Transformiert technische Feature-Namen zu benutzerfreundlichen deutschen Texten
 * wie sie auf der Landingpage verwendet werden.
 */
export function useSubscriptionFeatures() {
    /**
     * Feature-Mapping: technischer Name -> Marketing-Text (deutsch)
     */
    const featureLabels = {
        'basic_team_management': 'Team-Verwaltung',
        'basic_player_profiles': 'Spieler-Profile',
        'game_scheduling': 'Spielplanung',
        'basic_statistics': 'Basis-Statistiken',
        'live_scoring': 'Live-Scoring',
        'training_management': 'Training-Management',
        'advanced_statistics': 'Erweiterte Stats',
        'tournament_management': 'Turniere & Ligen',
        'video_analysis': 'Video-Analyse',
        'custom_reports': 'Custom Reports',
        'api_access': 'API-Zugang',
        'priority_support': 'Priority Support',
        'custom_integrations': 'Custom Integrations',
        'emergency_contacts': 'Notfall-Kontakte',
        'email_notifications': 'E-Mail-Benachrichtigungen',
        'push_notifications': 'Push-Benachrichtigungen',
        'basic_analytics': 'Basis-Analytics',
        'advanced_analytics': 'Erweiterte Analytics',
        'data_export': 'Daten-Export',
        'custom_branding': 'Custom Branding',
        'federation_integration': 'Verbands-Integration',
        'white_label': 'White-Label',
        'dedicated_support': 'Dedizierter Support',
        'sla_guarantee': 'SLA-Garantie',
        'unlimited_api': 'Unbegrenzte API',
        'multi_club_management': 'Multi-Club-Verwaltung',
        'advanced_security': 'Erweiterte Sicherheit',
        'audit_logs': 'Audit-Logs',
        'compliance_tools': 'DSGVO-Tools',
        'mobile_web_access': 'Mobile App',
    };

    /**
     * Limit-Namen: technischer Name -> benutzerfreundlicher Name
     */
    const limitNames = {
        'max_teams': 'Teams',
        'max_players': 'Spieler',
        'max_storage_gb': 'Speicher',
        'max_games_per_month': 'Spiele/Monat',
        'max_training_sessions_per_month': 'Trainings/Monat',
        'max_api_calls_per_hour': 'API-Calls/Stunde',
    };

    /**
     * Limit-Mapping: technischer Name -> Label-Funktion
     */
    const limitLabels = {
        'max_teams': (value) => value === -1 ? 'Unbegrenzte Teams' : `${value} Teams`,
        'max_players': (value) => value === -1 ? 'Unbegrenzte Spieler' : `${value} Spieler`,
        'max_storage_gb': (value) => value === -1 ? 'Unbegrenzter Speicher' : `${value} GB Speicher`,
        'max_games_per_month': (value) => value === -1 ? 'Unbegrenzte Spiele' : `${value} Spiele/Monat`,
        'max_training_sessions_per_month': (value) => value === -1 ? 'Unbegrenzte Trainings' : `${value} Trainings/Monat`,
        'max_api_calls_per_hour': (value) => value === -1 ? 'Unbegrenzte API-Calls' : `${value} API-Calls/Stunde`,
    };

    /**
     * Reihenfolge der Limits für die Anzeige (wichtigste zuerst)
     */
    const limitDisplayOrder = [
        'max_teams',
        'max_players',
        'max_storage_gb',
        'max_games_per_month',
        'max_training_sessions_per_month',
        'max_api_calls_per_hour',
    ];

    /**
     * Transformiert einen Plan zu einer Marketing-Style Feature-Liste
     *
     * @param {Object} plan - Der Plan mit features und limits Arrays
     * @returns {Array} - Liste von benutzerfreundlichen Feature-Strings
     */
    function getDisplayFeatures(plan) {
        const displayFeatures = [];

        // Zuerst Limits (wichtigste Info für den Benutzer)
        if (plan.limits) {
            for (const key of limitDisplayOrder) {
                if (plan.limits[key] !== undefined && limitLabels[key]) {
                    displayFeatures.push(limitLabels[key](plan.limits[key]));
                }
            }
        }

        // Dann Features
        if (plan.features && Array.isArray(plan.features)) {
            for (const feature of plan.features) {
                if (featureLabels[feature]) {
                    displayFeatures.push(featureLabels[feature]);
                }
            }
        }

        return displayFeatures;
    }

    /**
     * Gibt das Label für ein einzelnes Feature zurück
     *
     * @param {string} key - Technischer Feature-Name
     * @returns {string} - Benutzerfreundlicher Name oder Original wenn nicht gefunden
     */
    function getFeatureLabel(key) {
        return featureLabels[key] || key;
    }

    /**
     * Gibt das Label für ein einzelnes Limit zurück
     *
     * @param {string} key - Technischer Limit-Name
     * @param {number} value - Limit-Wert
     * @returns {string} - Benutzerfreundlicher Text
     */
    function getLimitLabel(key, value) {
        if (limitLabels[key]) {
            return limitLabels[key](value);
        }
        return value === -1 ? `${key}: Unbegrenzt` : `${key}: ${value}`;
    }

    /**
     * Gibt nur den Namen für ein Limit zurück (ohne Wert)
     *
     * @param {string} key - Technischer Limit-Name
     * @returns {string} - Benutzerfreundlicher Name
     */
    function getLimitName(key) {
        return limitNames[key] || key;
    }

    return {
        featureLabels,
        limitLabels,
        limitNames,
        limitDisplayOrder,
        getDisplayFeatures,
        getFeatureLabel,
        getLimitLabel,
        getLimitName,
    };
}
