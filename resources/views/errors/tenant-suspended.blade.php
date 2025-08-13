<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account gesperrt - BasketManager Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #636e72 0%, #2d3436 100%);
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 550px;
            margin: 2rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #636e72;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .suspension-badge {
            display: inline-block;
            background: linear-gradient(135deg, #636e72, #2d3436);
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
        
        .suspension-details {
            background: #f8f9fa;
            border: 2px solid #636e72;
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .suspension-details h3 {
            color: #636e72;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .reason-box {
            background: rgba(99, 110, 114, 0.1);
            border-left: 4px solid #636e72;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        
        .reason-box h4 {
            color: #636e72;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .reason-text {
            color: #555;
            font-style: italic;
        }
        
        .contact-info {
            background: #e8f4fd;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .contact-info h3 {
            color: #3498db;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .contact-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .contact-method {
            text-align: center;
            padding: 1rem;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 6px;
        }
        
        .contact-method .icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .contact-method .label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
        }
        
        .contact-method .value {
            font-weight: bold;
            color: #3498db;
        }
        
        .contact-method a {
            color: #3498db;
            text-decoration: none;
        }
        
        .contact-method a:hover {
            text-decoration: underline;
        }
        
        .next-steps {
            background: #fff8e1;
            border-left: 4px solid #f39c12;
            padding: 1.5rem;
            margin: 2rem 0;
            border-radius: 4px;
            text-align: left;
        }
        
        .next-steps h4 {
            color: #f39c12;
            margin-bottom: 1rem;
        }
        
        .next-steps ol {
            margin-left: 1rem;
            color: #666;
        }
        
        .next-steps li {
            margin-bottom: 0.5rem;
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
        
        .btn-support {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            font-size: 1.1rem;
            padding: 1rem 2rem;
        }
        
        .btn-support:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .legal-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: #ecf0f1;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #666;
            text-align: left;
        }
        
        .legal-notice h5 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
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
        <div class="error-icon">🚫</div>
        <h1>Account gesperrt</h1>
        <div class="suspension-badge">SUSPENDED</div>
        
        <div class="error-message">
            Ihr Tenant-Account wurde vorübergehend gesperrt. Der Zugriff auf alle Funktionen ist bis zur Klärung nicht möglich.
        </div>
        
        <div class="suspension-details">
            <h3>Sperrung Details</h3>
            
            @if(isset($tenant) && $tenant && $tenant->suspension_reason)
            <div class="reason-box">
                <h4>🔍 Grund der Sperrung:</h4>
                <div class="reason-text">{{ $tenant->suspension_reason }}</div>
            </div>
            @endif
            
            @if(isset($tenant) && $tenant)
            <p style="text-align: center; margin-top: 1rem; color: #666;">
                <strong>Account:</strong> {{ $tenant->name }} ({{ $tenant->id }})
            </p>
            @endif
        </div>
        
        <div class="contact-info">
            <h3>🤝 Support kontaktieren</h3>
            <p style="text-align: center; color: #666; margin-bottom: 1rem;">
                Unser Support-Team steht Ihnen zur Verfügung, um diese Angelegenheit zu klären.
            </p>
            
            <div class="contact-methods">
                <div class="contact-method">
                    <div class="icon">📧</div>
                    <div class="label">E-Mail Support</div>
                    <div class="value">
                        <a href="mailto:support@basketmanager-pro.com?subject=Account Suspension - {{ $tenant->id ?? 'Unknown' }}">
                            support@basketmanager-pro.com
                        </a>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="icon">📞</div>
                    <div class="label">Telefon</div>
                    <div class="value">+49 (0) 800 123 4567</div>
                </div>
            </div>
        </div>
        
        <div class="next-steps">
            <h4>📋 Nächste Schritte</h4>
            <ol>
                <li>Kontaktieren Sie unser Support-Team mit Ihrer Account-ID</li>
                <li>Stellen Sie alle relevanten Informationen zur Klärung bereit</li>
                <li>Warten Sie auf die Bearbeitung Ihres Falls</li>
                <li>Befolgen Sie die Anweisungen des Support-Teams</li>
            </ol>
        </div>
        
        <div class="action-buttons">
            <a href="mailto:support@basketmanager-pro.com?subject=Account Suspension - {{ $tenant->id ?? 'Unknown' }}" 
               class="btn btn-support">
                📧 Support kontaktieren
            </a>
            <a href="/" class="btn btn-secondary">Zur Startseite</a>
        </div>
        
        <div class="legal-notice">
            <h5>⚖️ Rechtliche Hinweise</h5>
            <p>
                Account-Sperrungen erfolgen gemäß unseren Nutzungsbedingungen. 
                Sie haben das Recht auf Einspruch und faire Überprüfung. 
                Weitere Informationen finden Sie in unseren 
                <a href="/terms" style="color: #3498db;">AGB</a> und 
                <a href="/privacy" style="color: #3498db;">Datenschutzbestimmungen</a>.
            </p>
        </div>
        
        <div class="support-info">
            <p><strong>Dringende Angelegenheit?</strong></p>
            <p>
                Für dringende Fälle erreichen Sie uns auch über unser 
                <a href="/contact">Kontaktformular</a> oder per Telefon während der Geschäftszeiten.
            </p>
        </div>
    </div>
</body>
</html>