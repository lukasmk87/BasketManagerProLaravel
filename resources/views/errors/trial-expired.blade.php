<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testphase beendet - BasketManager Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            margin: 2rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #e17055;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .trial-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 2rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .trial-summary {
            background: #fff8e1;
            border: 2px solid #fdcb6e;
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .trial-summary h3 {
            color: #e17055;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .trial-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .trial-stat {
            background: rgba(253, 203, 110, 0.2);
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }
        
        .trial-stat .label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .trial-stat .value {
            font-size: 1.3rem;
            font-weight: bold;
            color: #e17055;
        }
        
        .benefits-achieved {
            margin-top: 1rem;
            text-align: left;
        }
        
        .benefits-achieved h4 {
            color: #e17055;
            margin-bottom: 0.5rem;
        }
        
        .benefits-achieved ul {
            list-style: none;
            padding: 0;
        }
        
        .benefits-achieved li {
            padding: 0.25rem 0;
            position: relative;
            padding-left: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .benefits-achieved li:before {
            content: "✅";
            position: absolute;
            left: 0;
        }
        
        .pricing-options {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .pricing-options h3 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.4rem;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .plan-card {
            background: white;
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .plan-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .plan-card.recommended {
            border-color: #e17055;
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
        }
        
        .plan-card .plan-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .plan-card .plan-price {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .plan-card .plan-price .currency {
            font-size: 1rem;
        }
        
        .plan-card .plan-period {
            font-size: 0.9rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .plan-card .plan-features {
            list-style: none;
            padding: 0;
            font-size: 0.9rem;
            text-align: left;
        }
        
        .plan-card .plan-features li {
            padding: 0.25rem 0;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .plan-card .plan-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        .plan-card.recommended .plan-features li:before {
            color: rgba(255,255,255,0.9);
        }
        
        .urgency-note {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }
        
        .urgency-note h4 {
            color: #dc2626;
            margin-bottom: 0.5rem;
        }
        
        .urgency-note p {
            font-size: 0.9rem;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-subscribe {
            background: linear-gradient(135deg, #e17055, #d63031);
            color: white;
            font-size: 1.2rem;
            padding: 1rem 2.5rem;
        }
        
        .btn-subscribe:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(225, 112, 85, 0.3);
        }
        
        .btn-compare {
            background: #3498db;
            color: white;
        }
        
        .btn-compare:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .guarantee {
            margin-top: 2rem;
            padding: 1rem;
            background: #e8f5e8;
            border-left: 4px solid #27ae60;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .guarantee strong {
            color: #27ae60;
        }
        
        .support-info {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #666;
        }
        
        .support-info a {
            color: #3498db;
            text-decoration: none;
        }
        
        .support-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⏰</div>
        <h1>Testphase beendet</h1>
        <div class="trial-badge">TRIAL EXPIRED</div>
        
        <div class="error-message">
            Ihre 14-tägige kostenlose Testphase von BasketManager Pro ist abgelaufen. Um weiterhin alle Funktionen nutzen zu können, wählen Sie einen passenden Tarif.
        </div>
        
        <div class="trial-summary">
            <h3>📊 Ihre Testphase im Überblick</h3>
            
            <div class="trial-stats">
                <div class="trial-stat">
                    <div class="label">Testdauer</div>
                    <div class="value">14 Tage</div>
                </div>
                
                <div class="trial-stat">
                    <div class="label">Funktionen genutzt</div>
                    <div class="value">Vollzugriff</div>
                </div>
            </div>
            
            <div class="benefits-achieved">
                <h4>Was Sie bereits erlebt haben:</h4>
                <ul>
                    <li>Live-Spielverfolgung mit Echtzeit-Statistiken</li>
                    <li>Erweiterte Team- und Spielerverwaltung</li>
                    <li>KI-gestützte Leistungsanalysen</li>
                    <li>Professionelle Report-Erstellung</li>
                    <li>Training-Management & Drill-Bibliothek</li>
                </ul>
            </div>
        </div>
        
        <div class="pricing-options">
            <h3>🚀 Setzen Sie Ihren Erfolg fort</h3>
            
            <div class="plans-grid">
                <div class="plan-card">
                    <div class="plan-name">Basic</div>
                    <div class="plan-price">49<span class="currency">€</span></div>
                    <div class="plan-period">pro Monat</div>
                    <ul class="plan-features">
                        <li>Bis zu 5 Teams</li>
                        <li>50 Spieler</li>
                        <li>Live-Scoring</li>
                        <li>Basis-Analytics</li>
                        <li>E-Mail Support</li>
                    </ul>
                </div>
                
                <div class="plan-card recommended">
                    <div class="plan-name">Professional</div>
                    <div class="plan-price">149<span class="currency">€</span></div>
                    <div class="plan-period">pro Monat</div>
                    <ul class="plan-features">
                        <li>Bis zu 20 Teams</li>
                        <li>500 Spieler</li>
                        <li>KI-Insights</li>
                        <li>Video-Analyse</li>
                        <li>API-Zugriff</li>
                        <li>Priority Support</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="urgency-note">
            <h4>⚡ Jetzt handeln - Daten sichern!</h4>
            <p>
                Ihre Testdaten bleiben 30 Tage erhalten. Aktivieren Sie ein Abonnement, 
                um nahtlos mit Ihren bestehenden Teams und Statistiken fortzufahren.
            </p>
        </div>
        
        <div class="action-buttons">
            <a href="{{ route('subscription.index') }}" class="btn btn-subscribe">
                🏀 Jetzt abonnieren
            </a>
            <a href="/pricing" class="btn btn-compare">Tarife vergleichen</a>
            <button onclick="history.back()" class="btn btn-secondary">Zurück</button>
        </div>
        
        <div class="guarantee">
            💯 <strong>30 Tage Geld-zurück-Garantie:</strong> 
            Nicht zufrieden? Erhalten Sie Ihr Geld vollständig zurück - ohne Fragen!
        </div>
        
        <div class="support-info">
            <p><strong>Haben Sie Fragen oder benötigen eine Demo?</strong></p>
            <p>
                Kontaktieren Sie unser Sales-Team unter 
                <a href="mailto:sales@basketmanager-pro.com">sales@basketmanager-pro.com</a><br>
                oder buchen Sie eine <a href="/demo">kostenlose 15-min Demo</a>
            </p>
        </div>
    </div>
</body>
</html>