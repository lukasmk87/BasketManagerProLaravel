<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Limit erreicht - BasketManager Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff9a56 0%, #e67e22 100%);
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
            color: #e67e22;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .error-code {
            font-size: 1.2rem;
            color: #e67e22;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .rate-limit-info {
            background: #fff8e1;
            border: 2px solid #e67e22;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .rate-limit-info h3 {
            color: #e67e22;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .limit-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .limit-item {
            background: rgba(230, 126, 34, 0.1);
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }
        
        .limit-item .label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .limit-item .value {
            font-size: 1.3rem;
            font-weight: bold;
            color: #e67e22;
        }
        
        .retry-info {
            background: #e8f6f3;
            border-left: 4px solid #1abc9c;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }
        
        .retry-info h4 {
            color: #1abc9c;
            margin-bottom: 0.5rem;
        }
        
        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1abc9c;
            margin: 0.5rem 0;
        }
        
        .retry-info p {
            font-size: 0.9rem;
            color: #666;
        }
        
        .tips {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .tips h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .tips ul {
            list-style: none;
            padding: 0;
        }
        
        .tips li {
            padding: 0.5rem 0;
            position: relative;
            padding-left: 1.5rem;
            color: #555;
            font-size: 0.9rem;
        }
        
        .tips li:before {
            content: "💡";
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
        
        .btn-retry {
            background: linear-gradient(135deg, #1abc9c, #16a085);
            color: white;
            font-size: 1.1rem;
        }
        
        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 188, 156, 0.3);
        }
        
        .btn-upgrade {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
        }
        
        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(155, 89, 182, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .upgrade-hint {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f4ecf7;
            border-left: 4px solid #9b59b6;
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
    <script>
        let retryAfter = {{ $retryAfter ?? 60 }};
        
        function updateCountdown() {
            const countdownEl = document.getElementById('countdown');
            if (countdownEl && retryAfter > 0) {
                const minutes = Math.floor(retryAfter / 60);
                const seconds = retryAfter % 60;
                countdownEl.textContent = minutes > 0 
                    ? `${minutes}:${seconds.toString().padStart(2, '0')} min`
                    : `${seconds} sek`;
                retryAfter--;
                
                if (retryAfter <= 0) {
                    countdownEl.textContent = 'Bereit zum Wiederholen!';
                    document.getElementById('retry-btn').disabled = false;
                    document.getElementById('retry-btn').style.opacity = '1';
                }
            }
        }
        
        setInterval(updateCountdown, 1000);
    </script>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚦</div>
        <h1>Rate Limit erreicht</h1>
        <div class="error-code">HTTP 429 - Too Many Requests</div>
        
        <div class="error-message">
            Sie haben zu viele Anfragen in kurzer Zeit gesendet. Bitte warten Sie einen Moment, bevor Sie es erneut versuchen.
        </div>
        
        <div class="rate-limit-info">
            <h3>Rate Limit Details</h3>
            
            <div class="limit-details">
                @if(isset($limit))
                <div class="limit-item">
                    <div class="label">Limit</div>
                    <div class="value">{{ $limit }}</div>
                </div>
                @endif
                
                @if(isset($period))
                <div class="limit-item">
                    <div class="label">Zeitraum</div>
                    <div class="value">{{ $period }}s</div>
                </div>
                @endif
                
                <div class="limit-item">
                    <div class="label">Status</div>
                    <div class="value">🔴</div>
                </div>
            </div>
        </div>
        
        <div class="retry-info">
            <h4>⏱️ Wiederholen in:</h4>
            <div class="countdown" id="countdown">{{ $retryAfter ?? 60 }} sek</div>
            <p>Die Rate-Limits werden automatisch zurückgesetzt.</p>
        </div>
        
        <div class="tips">
            <h4>💡 Tipps zur Vermeidung von Rate Limits</h4>
            <ul>
                <li>Implementieren Sie Retry-Logic mit exponential backoff</li>
                <li>Verwenden Sie Caching für häufige Anfragen</li>
                <li>Batch-Operationen statt einzelne API-Calls</li>
                <li>Überwachen Sie Ihre API-Nutzung im Dashboard</li>
                <li>Upgraden Sie für höhere Rate-Limits</li>
            </ul>
        </div>
        
        <div class="action-buttons">
            <button id="retry-btn" onclick="location.reload()" class="btn btn-retry" disabled style="opacity: 0.5;">
                🔄 Erneut versuchen
            </button>
            @if(isset($upgrade_url))
            <a href="{{ $upgrade_url }}" class="btn btn-upgrade">📈 Tarif upgraden</a>
            @else
            <a href="{{ route('subscription.index') }}" class="btn btn-upgrade">📈 Tarif upgraden</a>
            @endif
            <button onclick="history.back()" class="btn btn-secondary">Zurück</button>
        </div>
        
        <div class="upgrade-hint">
            🚀 <strong>Professional & Enterprise Tarife</strong> haben deutlich höhere Rate-Limits und Burst-Kapazitäten!
        </div>
        
        <div class="support-info">
            <p><strong>Benötigen Sie höhere Rate-Limits?</strong></p>
            <p>
                Kontaktieren Sie unser API-Team unter 
                <a href="mailto:api-support@basketmanager-pro.com">api-support@basketmanager-pro.com</a><br>
                für individuelle <strong>Enterprise-Limits</strong>
            </p>
        </div>
    </div>
    
    <script>
        // Initialize countdown on page load
        updateCountdown();
    </script>
</body>
</html>