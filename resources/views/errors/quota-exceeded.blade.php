<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutzungslimit erreicht - {{ app_name() }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff7b7b 0%, #d63031 100%);
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
            color: #e74c3c;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .error-code {
            font-size: 1.2rem;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .quota-info {
            background: #fff3f3;
            border: 2px solid #e74c3c;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .quota-info h3 {
            color: #e74c3c;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .usage-bar {
            background: #f8f9fa;
            border-radius: 10px;
            height: 20px;
            margin: 1rem 0;
            overflow: hidden;
            position: relative;
        }
        
        .usage-fill {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .usage-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .quota-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .quota-item {
            background: rgba(231, 76, 60, 0.1);
            padding: 0.75rem;
            border-radius: 6px;
        }
        
        .quota-item .label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
        }
        
        .quota-item .value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .reset-info {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }
        
        .reset-info h4 {
            color: #3498db;
            margin-bottom: 0.5rem;
        }
        
        .reset-info p {
            font-size: 0.9rem;
            color: #666;
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
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            font-size: 1.1rem;
            padding: 1rem 2rem;
        }
        
        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .pricing-comparison {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .pricing-comparison h4 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .tier-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            font-size: 0.9rem;
        }
        
        .current-tier, .upgrade-tier {
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }
        
        .current-tier {
            background: #ecf0f1;
            border: 2px solid #bdc3c7;
        }
        
        .upgrade-tier {
            background: #d5f4e6;
            border: 2px solid #27ae60;
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
        <div class="error-icon">📊</div>
        <h1>Nutzungslimit erreicht</h1>
        <div class="error-code">HTTP 429 - Quota Exceeded</div>
        
        <div class="error-message">
            Sie haben das Nutzungslimit für Ihren aktuellen Tarif erreicht.
        </div>
        
        <div class="quota-info">
            <h3>Aktuelle Nutzung</h3>
            
            @if(isset($metric) && isset($current) && isset($limit))
            <div class="usage-bar">
                @php 
                    $percentage = $limit > 0 ? min(100, ($current / $limit) * 100) : 100;
                @endphp
                <div class="usage-fill" style="width: {{ $percentage }}%"></div>
                <div class="usage-text">{{ number_format($percentage, 1) }}%</div>
            </div>
            
            <div class="quota-details">
                <div class="quota-item">
                    <div class="label">Genutzt</div>
                    <div class="value">{{ number_format($current) }}</div>
                </div>
                <div class="quota-item">
                    <div class="label">Limit</div>
                    <div class="value">{{ number_format($limit) }}</div>
                </div>
            </div>
            @endif
            
            @if(isset($tenant) && $tenant)
            <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                <strong>Tarif:</strong> {{ ucfirst($tenant->subscription_tier) }}
            </p>
            @endif
        </div>
        
        <div class="reset-info">
            <h4>⏰ Nächste Rücksetzung</h4>
            <p>Ihre Nutzungslimits werden am 1. jeden Monats zurückgesetzt.</p>
        </div>
        
        <div class="action-buttons">
            @if(isset($upgrade_url))
            <a href="{{ $upgrade_url }}" class="btn btn-upgrade">📈 Tarif upgraden</a>
            @else
            <a href="{{ route('subscription.index') }}" class="btn btn-upgrade">📈 Tarif upgraden</a>
            @endif
            <button onclick="history.back()" class="btn btn-secondary">Zurück</button>
        </div>
        
        <div class="pricing-comparison">
            <h4>💡 Upgraden für höhere Limits</h4>
            <div class="tier-comparison">
                <div class="current-tier">
                    <strong>Aktuell</strong><br>
                    @if(isset($tenant) && $tenant)
                        {{ ucfirst($tenant->subscription_tier) }}
                    @else
                        Basic
                    @endif
                </div>
                <div class="upgrade-tier">
                    <strong>Empfohlen</strong><br>
                    Professional<br>
                    <small>5x höhere Limits</small>
                </div>
            </div>
        </div>
        
        <div class="support-info">
            <p><strong>Benötigen Sie eine individuelle Lösung?</strong></p>
            <p>
                Kontaktieren Sie unser Sales-Team unter 
                <a href="mailto:sales@basketmanager-pro.com">sales@basketmanager-pro.com</a><br>
                für <strong>Enterprise-Tarife</strong> mit unbegrenzten Limits
            </p>
        </div>
    </div>
</body>
</html>