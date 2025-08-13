<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature gesperrt - BasketManager Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 550px;
            margin: 2rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #9b59b6;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .feature-name {
            background: #f8f9fa;
            border: 2px solid #9b59b6;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            margin: 1rem 0;
            font-weight: bold;
            color: #9b59b6;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .tier-info {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: left;
        }
        
        .tier-info h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .current-tier {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .required-tier {
            display: inline-block;
            background: rgba(255,255,255,0.3);
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .features-preview {
            text-align: left;
            margin-top: 1rem;
        }
        
        .features-preview ul {
            list-style: none;
            padding: 0;
        }
        
        .features-preview li {
            padding: 0.25rem 0;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .features-preview li:before {
            content: "✨";
            position: absolute;
            left: 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
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
        
        .btn-upgrade {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            font-size: 1.1rem;
            padding: 1rem 2rem;
        }
        
        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .pricing-hint {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #666;
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
        <div class="error-icon">🔒</div>
        <h1>Feature nicht verfügbar</h1>
        
        @if(isset($feature))
        <div class="feature-name">{{ $feature }}</div>
        @endif
        
        <div class="error-message">
            Diese Funktion ist in Ihrem aktuellen Tarif nicht enthalten. Upgraden Sie Ihr Abonnement, um auf erweiterte Features zuzugreifen.
        </div>
        
        <div class="tier-info">
            <h3>Tarif-Information</h3>
            @if(isset($tenant) && $tenant)
            <div class="current-tier">Aktueller Tarif: {{ ucfirst($tenant->subscription_tier) }}</div><br>
            @endif
            @if(isset($required_tier))
            <div class="required-tier">Benötigt: {{ $required_tier }}</div>
            @endif
            
            <div class="features-preview">
                <p style="margin-top: 1rem; margin-bottom: 0.5rem;"><strong>Mit dem Upgrade erhalten Sie:</strong></p>
                <ul>
                    <li>Erweiterte Analytics & KI-Insights</li>
                    <li>Video-Analyse & Highlight-Erstellung</li>
                    <li>Turnier-Management</li>
                    <li>API-Zugriff & Integrationen</li>
                    <li>Prioritärer Support</li>
                </ul>
            </div>
        </div>
        
        <div class="action-buttons">
            @if(isset($upgrade_url))
            <a href="{{ $upgrade_url }}" class="btn btn-upgrade">🚀 Jetzt upgraden</a>
            @else
            <a href="{{ route('subscription.index') }}" class="btn btn-upgrade">🚀 Jetzt upgraden</a>
            @endif
            <button onclick="history.back()" class="btn btn-secondary">Zurück</button>
        </div>
        
        <div class="pricing-hint">
            💡 <strong>Tipp:</strong> Sparen Sie bis zu 20% mit einem Jahres-Abonnement!
        </div>
        
        <div class="support-info">
            <p><strong>Fragen zu den Tarifen?</strong></p>
            <p>
                Kontaktieren Sie unser Sales-Team unter 
                <a href="mailto:sales@basketmanager-pro.com">sales@basketmanager-pro.com</a><br>
                oder vergleichen Sie alle <a href="/pricing">Tarife & Features</a>
            </p>
        </div>
    </div>
</body>
</html>