<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Zahlungsmethoden - BasketManager Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .header p {
            font-size: 1.1rem;
            color: #666;
        }
        
        .payment-methods-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f2f6;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }
        
        .add-payment-method-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-payment-method-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .payment-methods-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .payment-method-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .payment-method-item:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .payment-method-item.default {
            border-color: #27ae60;
            background: linear-gradient(135deg, #d5f4e6, #ffffff);
        }
        
        .payment-method-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .payment-method-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .icon-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .icon-sepa {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
        }
        
        .icon-sofort {
            background: linear-gradient(135deg, #ff7675, #d63031);
        }
        
        .icon-giropay {
            background: linear-gradient(135deg, #00b894, #00a085);
        }
        
        .payment-method-details {
            display: flex;
            flex-direction: column;
        }
        
        .payment-method-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .payment-method-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .default-badge {
            background: #27ae60;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .payment-method-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-btn.default {
            background: #3498db;
            color: white;
        }
        
        .action-btn.default:hover {
            background: #2980b9;
        }
        
        .action-btn.remove {
            background: #e74c3c;
            color: white;
        }
        
        .action-btn.remove:hover {
            background: #c0392b;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }
        
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .supported-methods {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .supported-methods h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .method-card {
            text-align: center;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .method-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .method-card .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .method-card .name {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            z-index: 1000;
        }
        
        .spinner {
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 2px solid white;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💳 Zahlungsmethoden</h1>
            <p>Verwalten Sie Ihre Zahlungsmethoden für BasketManager Pro</p>
        </div>
        
        <div class="payment-methods-section">
            <div class="section-header">
                <h2 class="section-title">Ihre Zahlungsmethoden</h2>
                <button class="add-payment-method-btn" onclick="addPaymentMethod()">
                    ➕ Hinzufügen
                </button>
            </div>
            
            @if(count($paymentMethods) > 0)
                <div class="payment-methods-list">
                    @foreach($paymentMethods as $method)
                    <div class="payment-method-item @if($loop->first) default @endif">
                        <div class="payment-method-info">
                            <div class="payment-method-icon icon-{{ $method['type'] }}">
                                @switch($method['type'])
                                    @case('card')
                                        💳
                                        @break
                                    @case('sepa_debit')
                                        🏦
                                        @break
                                    @case('sofort')
                                        ⚡
                                        @break
                                    @case('giropay')
                                        🔄
                                        @break
                                    @default
                                        💰
                                @endswitch
                            </div>
                            <div class="payment-method-details">
                                <div class="payment-method-name">{{ $method['display_name'] }}</div>
                                <div class="payment-method-description">
                                    {{ $supportedMethods[$method['type']] ?? ucfirst($method['type']) }}
                                </div>
                            </div>
                            @if($loop->first)
                                <span class="default-badge">Standard</span>
                            @endif
                        </div>
                        
                        <div class="payment-method-actions">
                            @if(!$loop->first)
                                <button class="action-btn default" 
                                        onclick="setDefaultPaymentMethod('{{ $method['id'] }}')">
                                    Als Standard
                                </button>
                            @endif
                            <button class="action-btn remove" 
                                    onclick="removePaymentMethod('{{ $method['id'] }}')">
                                Entfernen
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="icon">💳</div>
                    <h3>Keine Zahlungsmethoden</h3>
                    <p>Fügen Sie eine Zahlungsmethode hinzu, um Ihr Abonnement zu verwalten.</p>
                </div>
            @endif
        </div>
        
        <div class="supported-methods">
            <h3>🔒 Unterstützte Zahlungsmethoden</h3>
            <div class="methods-grid">
                @foreach($supportedMethods as $type => $name)
                <div class="method-card">
                    <div class="icon">
                        @switch($type)
                            @case('card')
                                💳
                                @break
                            @case('sepa_debit')
                                🏦
                                @break
                            @case('sofort')
                                ⚡
                                @break
                            @case('giropay')
                                🔄
                                @break
                            @case('eps')
                                🇦🇹
                                @break
                            @case('bancontact')
                                🇧🇪
                                @break
                            @case('ideal')
                                🇳🇱
                                @break
                            @default
                                💰
                        @endswitch
                    </div>
                    <div class="name">{{ $name }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div>Wird verarbeitet...</div>
    </div>
    
    <script>
        async function addPaymentMethod() {
            showLoading();
            
            try {
                const response = await fetch('{{ route("checkout.payment-methods.session") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                
                const data = await response.json();
                
                if (data.success && data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    throw new Error(data.error || 'Setup konnte nicht gestartet werden');
                }
                
            } catch (error) {
                hideLoading();
                console.error('Setup error:', error);
                alert('Es gab einen Fehler beim Hinzufügen der Zahlungsmethode. Bitte versuchen Sie es erneut.');
            }
        }
        
        async function removePaymentMethod(paymentMethodId) {
            if (!confirm('Möchten Sie diese Zahlungsmethode wirklich entfernen?')) {
                return;
            }
            
            showLoading();
            
            try {
                const response = await fetch(`/checkout/payment-methods/${paymentMethodId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.error || 'Zahlungsmethode konnte nicht entfernt werden');
                }
                
            } catch (error) {
                hideLoading();
                console.error('Remove error:', error);
                alert('Es gab einen Fehler beim Entfernen der Zahlungsmethode.');
            }
        }
        
        async function setDefaultPaymentMethod(paymentMethodId) {
            showLoading();
            
            try {
                const response = await fetch(`/checkout/payment-methods/${paymentMethodId}/default`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.error || 'Standard-Zahlungsmethode konnte nicht gesetzt werden');
                }
                
            } catch (error) {
                hideLoading();
                console.error('Default error:', error);
                alert('Es gab einen Fehler beim Setzen der Standard-Zahlungsmethode.');
            }
        }
        
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
    </script>
</body>
</html>