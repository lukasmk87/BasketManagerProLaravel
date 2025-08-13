<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Abonnement wählen - BasketManager Pro</title>
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
            padding: 2rem 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .billing-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
        }
        
        .toggle-container {
            background: rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 4px;
            display: flex;
            backdrop-filter: blur(10px);
        }
        
        .toggle-option {
            padding: 0.75rem 2rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            position: relative;
        }
        
        .toggle-option.active {
            background: white;
            color: #667eea;
            font-weight: bold;
        }
        
        .toggle-option .savings {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .plan-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }
        
        .plan-card.recommended {
            border: 3px solid #e74c3c;
            position: relative;
        }
        
        .plan-card.recommended::before {
            content: "EMPFOHLEN";
            position: absolute;
            top: 0;
            right: 0;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 0.5rem 1.5rem;
            font-size: 0.8rem;
            font-weight: bold;
            border-bottom-left-radius: 16px;
        }
        
        .plan-card.current {
            border: 3px solid #27ae60;
            background: linear-gradient(135deg, #d5f4e6, #ffffff);
        }
        
        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .plan-price {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .plan-price .currency {
            font-size: 1.5rem;
        }
        
        .plan-period {
            color: #666;
            font-size: 1rem;
        }
        
        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .plan-features li {
            padding: 0.5rem 0;
            position: relative;
            padding-left: 1.5rem;
            color: #555;
        }
        
        .plan-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
        }
        
        .plan-button {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .plan-button.primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .plan-button.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .plan-button.secondary {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .plan-button.secondary:hover {
            background: #667eea;
            color: white;
        }
        
        .plan-button.current-plan {
            background: #27ae60;
            color: white;
            cursor: default;
        }
        
        .plan-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .spinner {
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 2px solid white;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .features-comparison {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .features-comparison h3 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        .guarantee {
            text-align: center;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .guarantee h4 {
            margin-bottom: 0.5rem;
            color: #fdcb6e;
        }
        
        .support-info {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255,255,255,0.8);
        }
        
        .support-info a {
            color: #fdcb6e;
            text-decoration: none;
        }
        
        .support-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏀 Wählen Sie Ihr Abonnement</h1>
            <p>Starten Sie noch heute mit BasketManager Pro</p>
        </div>
        
        <div class="billing-toggle">
            <div class="toggle-container">
                <div class="toggle-option active" data-cycle="monthly">
                    Monatlich
                </div>
                <div class="toggle-option" data-cycle="yearly">
                    Jährlich
                    <span class="savings">-20%</span>
                </div>
            </div>
        </div>
        
        <div class="plans-grid">
            @foreach($tiers as $tierId => $tier)
            <div class="plan-card @if(isset($tier['recommended'])) recommended @endif @if($currentTier === $tierId) current @endif">
                <div class="plan-header">
                    <div class="plan-name">{{ $tier['name'] }}</div>
                    <div class="plan-price" data-monthly="{{ $tier['price'] }}" data-yearly="{{ $tier['price'] * 10 }}">
                        <span class="amount">{{ number_format($tier['price'] / 100, 0) }}</span><span class="currency">€</span>
                    </div>
                    <div class="plan-period">pro Monat</div>
                </div>
                
                <ul class="plan-features">
                    @foreach($tier['features'] as $feature)
                    <li>{{ $feature }}</li>
                    @endforeach
                </ul>
                
                @if($currentTier === $tierId)
                    <button class="plan-button current-plan" disabled>
                        Aktueller Tarif
                    </button>
                @else
                    <button class="plan-button @if(isset($tier['recommended'])) primary @else secondary @endif" 
                            data-price-id="{{ $tier['price_id'] }}"
                            onclick="selectPlan(this, '{{ $tier['price_id'] }}', '{{ $tier['name'] }}')">
                        <span class="button-text">
                            @if($currentTier === 'trial')
                                Testversion beenden & upgraden
                            @else
                                Zu {{ $tier['name'] }} wechseln
                            @endif
                        </span>
                        <div class="loading">
                            <div class="spinner"></div>
                        </div>
                    </button>
                @endif
            </div>
            @endforeach
        </div>
        
        <div class="guarantee">
            <h4>💯 30 Tage Geld-zurück-Garantie</h4>
            <p>Nicht zufrieden? Erhalten Sie Ihr Geld vollständig zurück - ohne Fragen!</p>
        </div>
        
        <div class="support-info">
            <p>
                <strong>Haben Sie Fragen?</strong><br>
                Kontaktieren Sie uns unter <a href="mailto:sales@basketmanager-pro.com">sales@basketmanager-pro.com</a><br>
                oder buchen Sie eine <a href="{{ route('demo') }}">kostenlose Demo</a>
            </p>
        </div>
    </div>
    
    <script>
        let currentBillingCycle = 'monthly';
        
        // Toggle billing cycle
        document.querySelectorAll('.toggle-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.toggle-option').forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                currentBillingCycle = this.dataset.cycle;
                updatePricing();
            });
        });
        
        function updatePricing() {
            document.querySelectorAll('.plan-price').forEach(priceEl => {
                const monthlyPrice = parseInt(priceEl.dataset.monthly);
                const yearlyPrice = parseInt(priceEl.dataset.yearly);
                const price = currentBillingCycle === 'yearly' ? yearlyPrice : monthlyPrice;
                
                priceEl.querySelector('.amount').textContent = (price / 100).toLocaleString('de-DE');
            });
            
            document.querySelectorAll('.plan-period').forEach(periodEl => {
                periodEl.textContent = currentBillingCycle === 'yearly' ? 'pro Jahr' : 'pro Monat';
            });
        }
        
        async function selectPlan(button, priceId, planName) {
            // Show loading state
            button.disabled = true;
            button.querySelector('.button-text').style.opacity = '0';
            button.querySelector('.loading').style.display = 'block';
            
            try {
                const response = await fetch('{{ route("checkout.subscription.session") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        price_id: priceId,
                        billing_cycle: currentBillingCycle,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success && data.checkout_url) {
                    // Redirect to Stripe Checkout
                    window.location.href = data.checkout_url;
                } else {
                    throw new Error(data.error || 'Checkout konnte nicht gestartet werden');
                }
                
            } catch (error) {
                console.error('Checkout error:', error);
                alert('Es gab einen Fehler beim Starten des Checkouts. Bitte versuchen Sie es erneut.');
                
                // Reset button state
                button.disabled = false;
                button.querySelector('.button-text').style.opacity = '1';
                button.querySelector('.loading').style.display = 'none';
            }
        }
    </script>
</body>
</html>