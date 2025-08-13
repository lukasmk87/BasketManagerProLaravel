<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant nicht gefunden - BasketManager Pro</title>
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
            max-width: 500px;
            margin: 2rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #f39c12;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .error-code {
            font-size: 1.2rem;
            color: #f39c12;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .help-text {
            background: #f8f9fa;
            border-left: 4px solid #f39c12;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
            border-radius: 4px;
        }
        
        .help-text h3 {
            color: #f39c12;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .help-text ul {
            margin-left: 1rem;
            color: #666;
        }
        
        .help-text li {
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
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
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
        <div class="error-icon">🏀</div>
        <h1>Tenant nicht gefunden</h1>
        <div class="error-code">HTTP 404</div>
        
        <div class="error-message">
            Der angeforderte Verein oder die Organisation konnte nicht gefunden werden.
        </div>
        
        <div class="help-text">
            <h3>Mögliche Ursachen:</h3>
            <ul>
                <li>Die URL wurde falsch eingegeben</li>
                <li>Der Verein wurde deaktiviert oder gelöscht</li>
                <li>Sie haben keine Berechtigung für diese Organisation</li>
                <li>Es liegt ein temporäres Problem vor</li>
            </ul>
        </div>
        
        <div class="action-buttons">
            <a href="/" class="btn btn-primary">Zur Startseite</a>
            <a href="/register" class="btn btn-success">Verein registrieren</a>
            <button onclick="history.back()" class="btn btn-secondary">Zurück</button>
        </div>
        
        <div class="support-info">
            <p><strong>Benötigen Sie Hilfe?</strong></p>
            <p>
                Kontaktieren Sie unser Support-Team unter 
                <a href="mailto:support@basketmanager-pro.com">support@basketmanager-pro.com</a><br>
                oder besuchen Sie unser <a href="/help">Hilfezentrum</a>
            </p>
        </div>
    </div>
</body>
</html>