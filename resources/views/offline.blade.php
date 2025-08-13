<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline - BasketManager Pro</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .offline-container {
            text-align: center;
            max-width: 500px;
            padding: 2rem;
        }
        
        .basketball-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            opacity: 0.9;
        }
        
        .basketball {
            width: 120px;
            height: 120px;
            background: #e67e22;
            border-radius: 50%;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: bounce 2s infinite;
        }
        
        .basketball::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #333;
            transform: translateY(-1px);
        }
        
        .basketball::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 10%;
            bottom: 10%;
            width: 2px;
            background: #333;
            transform: translateX(-1px);
        }
        
        .basketball .line1,
        .basketball .line2 {
            position: absolute;
            background: #333;
            height: 2px;
            width: 60px;
        }
        
        .basketball .line1 {
            top: 30%;
            left: 30px;
            transform: rotate(45deg);
        }
        
        .basketball .line2 {
            bottom: 30%;
            left: 30px;
            transform: rotate(-45deg);
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            opacity: 0;
            animation: fadeInUp 1s 0.5s forwards;
        }
        
        .subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0;
            font-weight: 500;
            animation: fadeInUp 1s 0.7s forwards;
        }
        
        .offline-message {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0;
            color: rgba(255,255,255,0.9);
            animation: fadeInUp 1s 0.9s forwards;
        }
        
        .features-available {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            opacity: 0;
            animation: fadeInUp 1s 1.1s forwards;
        }
        
        .features-available h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #fff;
        }
        
        .feature-list {
            list-style: none;
            text-align: left;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.9);
        }
        
        .feature-list li::before {
            content: '🏀';
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .retry-button {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            animation: fadeInUp 1s 1.3s forwards;
            text-decoration: none;
            display: inline-block;
        }
        
        .retry-button:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
        }
        
        .connection-status {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 8px;
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.3);
            opacity: 0;
            animation: fadeInUp 1s 1.5s forwards;
        }
        
        .connection-status.online {
            background: rgba(46, 204, 113, 0.2);
            border-color: rgba(46, 204, 113, 0.3);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 640px) {
            .offline-container {
                padding: 1rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
            
            .basketball-icon {
                width: 100px;
                height: 100px;
            }
            
            .basketball {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>

<body>
    <div class="offline-container">
        <!-- Basketball Animation -->
        <div class="basketball-icon">
            <div class="basketball">
                <div class="line1"></div>
                <div class="line2"></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <h1>Offline Modus</h1>
        <div class="subtitle">BasketManager Pro</div>
        
        <div class="offline-message">
            Du bist momentan offline. Keine Sorge - viele Funktionen sind auch ohne Internetverbindung verfügbar!
        </div>
        
        <!-- Available Features -->
        <div class="features-available">
            <h3>Verfügbare Offline-Funktionen:</h3>
            <ul class="feature-list">
                <li>Spielstatistiken einsehen</li>
                <li>Spielerprofil verwalten</li>
                <li>Trainingseinheiten planen</li>
                <li>Taktische Übungen durchführen</li>
                <li>Vergangene Spiele analysieren</li>
                <li>Mannschaftsaufstellungen erstellen</li>
            </ul>
        </div>
        
        <!-- Connection Status -->
        <div class="connection-status" id="connectionStatus">
            <span id="statusText">Keine Internetverbindung</span>
        </div>
        
        <!-- Retry Button -->
        <a href="/" class="retry-button" id="retryButton">
            Erneut versuchen
        </a>
    </div>

    <script>
        // Check connection status
        function updateConnectionStatus() {
            const statusElement = document.getElementById('connectionStatus');
            const statusText = document.getElementById('statusText');
            
            if (navigator.onLine) {
                statusElement.classList.add('online');
                statusText.textContent = 'Verbindung wiederhergestellt!';
                
                // Auto-redirect after 2 seconds when online
                setTimeout(() => {
                    window.location.href = '/';
                }, 2000);
            } else {
                statusElement.classList.remove('online');
                statusText.textContent = 'Keine Internetverbindung';
            }
        }
        
        // Listen for connection changes
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        
        // Initial check
        updateConnectionStatus();
        
        // Retry button functionality
        document.getElementById('retryButton').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if we're online
            if (navigator.onLine) {
                window.location.href = '/';
            } else {
                // Show feedback that we're still offline
                const button = e.target;
                const originalText = button.textContent;
                button.textContent = 'Immer noch offline...';
                button.style.background = 'rgba(231, 76, 60, 0.3)';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = 'rgba(255,255,255,0.2)';
                }, 2000);
            }
        });
        
        // Service Worker registration check
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(function(registration) {
                console.log('Service Worker ready:', registration);
            });
        }
        
        // Cache information display
        if ('caches' in window) {
            caches.keys().then(function(cacheNames) {
                console.log('Available caches:', cacheNames);
            });
        }
        
        // Auto-refresh every 30 seconds to check connection
        setInterval(() => {
            if (navigator.onLine) {
                // Try to fetch a small resource to verify real connectivity
                fetch('/manifest.json', { method: 'HEAD' })
                    .then(() => {
                        window.location.href = '/';
                    })
                    .catch(() => {
                        // Still offline, continue showing offline page
                    });
            }
        }, 30000);
    </script>
</body>
</html>