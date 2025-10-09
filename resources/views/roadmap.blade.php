<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <title>Roadmap 2025/2026 - BasketManager Pro</title>
    <meta name="description" content="Entdecken Sie unsere Roadmap für 2025 und 2026. Video-Analyse ab Q2 2026, ML Analytics ab Q3 2026 und viele weitere Features für die Basketball-Vereinsverwaltung.">

    <!-- Open Graph -->
    <meta property="og:title" content="Roadmap 2025/2026 - BasketManager Pro">
    <meta property="og:description" content="Video-Analyse ab Q2 2026, ML Analytics ab Q3 2026 - Unsere Roadmap für die Zukunft der Basketball-Vereinsverwaltung.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/roadmap') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .basketball-pattern {
            background-image:
                radial-gradient(circle at 25px 25px, rgba(249, 115, 22, 0.1) 2px, transparent 2px),
                radial-gradient(circle at 75px 75px, rgba(249, 115, 22, 0.1) 2px, transparent 2px);
            background-size: 100px 100px;
        }
        .timeline-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, #f97316, #ea580c);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 4rem;
        }
        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 2rem;
            width: 20px;
            height: 20px;
            background: #f97316;
            border: 4px solid white;
            border-radius: 50%;
            transform: translateX(-50%);
            z-index: 10;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2);
        }
        .feature-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #f97316;
        }
        .quarter-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-available {
            background-color: #10b981;
            color: white;
        }
        .status-in-progress {
            background-color: #3b82f6;
            color: white;
        }
        .status-planned {
            background-color: #f59e0b;
            color: white;
        }
        .status-future {
            background-color: #8b5cf6;
            color: white;
        }
        @media (max-width: 768px) {
            .timeline-line {
                left: 20px;
            }
            .timeline-dot {
                left: 20px;
            }
            .timeline-content {
                margin-left: 50px;
            }
        }
    </style>
</head>

<body class="font-sans antialiased bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ url('/') }}" class="text-2xl font-bold gradient-text">🏀 BasketManager Pro</a>
                    </div>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ url('/') }}#features" class="text-gray-700 hover:text-orange-600 font-medium">Features</a>
                    <a href="{{ url('/') }}#pricing" class="text-gray-700 hover:text-orange-600 font-medium">Preise</a>
                    <a href="{{ route('roadmap') }}" class="text-orange-600 font-medium border-b-2 border-orange-600">Roadmap</a>
                    <a href="{{ url('/') }}#faq" class="text-gray-700 hover:text-orange-600 font-medium">FAQ</a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-orange-600 font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-orange-600 font-medium">Login</a>
                        <a href="{{ route('register') }}" class="bg-orange-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-orange-700 transition-colors">Kostenlos testen</a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" class="text-gray-700 hover:text-orange-600" onclick="toggleMobileMenu()">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="{{ url('/') }}#features" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Features</a>
                <a href="{{ url('/') }}#pricing" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Preise</a>
                <a href="{{ route('roadmap') }}" class="block px-3 py-2 text-orange-600 font-medium">Roadmap</a>
                <a href="{{ url('/') }}#faq" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">FAQ</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Login</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2 bg-orange-600 text-white rounded-lg font-medium mx-3 text-center">Kostenlos testen</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg pt-24 pb-16 basketball-pattern">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    Unsere Roadmap für 2025 & 2026
                </h1>
                <p class="text-xl md:text-2xl text-orange-100 mb-8 max-w-3xl mx-auto">
                    Entdecken Sie, welche innovativen Features wir für die Zukunft von BasketManager Pro planen.
                    Von KI-gestützter Video-Analyse bis zu ML-basierten Performance-Vorhersagen.
                </p>
                <div class="flex flex-wrap gap-4 justify-center">
                    <span class="quarter-badge status-available">✅ Verfügbar</span>
                    <span class="quarter-badge status-in-progress">🚧 In Entwicklung</span>
                    <span class="quarter-badge status-planned">📅 Geplant Q1-Q2 2026</span>
                    <span class="quarter-badge status-future">🔮 Geplant Q3-Q4 2026</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- 2025 Timeline -->
            <div class="mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 text-center">
                    2025 - Foundation & Core Features
                </h2>
                <p class="text-xl text-gray-600 text-center mb-12">
                    Bereits verfügbar und in aktiver Entwicklung
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Q1-Q2 2025 -->
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <span class="quarter-badge status-available">✅ Verfügbar</span>
                            <span class="text-sm text-gray-500">Q1-Q2 2025</span>
                        </div>
                        <div class="text-4xl mb-4">🏀</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Core Features</h3>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li>✓ Live-Scoring System mit WebSockets</li>
                            <li>✓ Team & Spielerverwaltung</li>
                            <li>✓ Basketball-Statistiken (20+ Metriken)</li>
                            <li>✓ Trainingsplanung & Drills</li>
                            <li>✓ Multi-Tenant Architecture</li>
                        </ul>
                    </div>

                    <!-- Q3 2025 -->
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <span class="quarter-badge status-available">✅ Verfügbar</span>
                            <span class="text-sm text-gray-500">Q3 2025</span>
                        </div>
                        <div class="text-4xl mb-4">🚨</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Safety & Compliance</h3>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li>✓ QR-Code Notfallsystem</li>
                            <li>✓ GDPR-Compliance (Art. 15, 17, 20)</li>
                            <li>✓ Progressive Web App (PWA)</li>
                            <li>✓ Offline-Modus für Hallen</li>
                            <li>✓ Push-Benachrichtigungen</li>
                        </ul>
                    </div>

                    <!-- Q4 2025 -->
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <span class="quarter-badge status-in-progress">🚧 In Entwicklung</span>
                            <span class="text-sm text-gray-500">Q4 2025</span>
                        </div>
                        <div class="text-4xl mb-4">📊</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Advanced Analytics</h3>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li>✓ Shot Charts & Heat Maps</li>
                            <li>✓ Performance Monitoring</li>
                            <li>✓ Tournament Management</li>
                            <li>✓ REST API v2 (183 Endpoints)</li>
                            <li>🚧 Advanced Statistics Dashboard</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 2026 Timeline -->
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 text-center">
                    2026 - Next Generation Features
                </h2>
                <p class="text-xl text-gray-600 text-center mb-12">
                    Innovative Features für die Zukunft der Basketball-Vereinsverwaltung
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Q1 2026 -->
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <span class="quarter-badge status-planned">📅 Geplant</span>
                            <span class="text-sm text-gray-500 font-bold">Q1 2026</span>
                        </div>
                        <div class="text-4xl mb-4">🏆</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Federation Integration</h3>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li>• DBB (Deutscher Basketball Bund) API</li>
                            <li>• FIBA International Integration</li>
                            <li>• Automatische Spieler-Lizenzierung</li>
                            <li>• Liga-Daten Synchronisation</li>
                            <li>• Enhanced Mobile Experience</li>
                        </ul>
                    </div>

                    <!-- Q2 2026 - VIDEO ANALYSE -->
                    <div class="feature-card bg-gradient-to-br from-orange-50 to-white p-6 rounded-xl shadow-xl border-2 border-orange-500">
                        <div class="flex items-center justify-between mb-4">
                            <span class="quarter-badge status-future">🔮 Coming Soon</span>
                            <span class="text-sm text-orange-600 font-bold">Q2 2026</span>
                        </div>
                        <div class="text-4xl mb-4">🎬</div>
                        <h3 class="text-xl font-bold text-orange-600 mb-3">Video-Analyse (KI-gestützt)</h3>
                        <p class="text-gray-600 text-sm mb-3 font-medium">
                            Professionelle Video-Analyse mit künstlicher Intelligenz
                        </p>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li>🎥 Automatische Video-Verarbeitung</li>
                            <li>🤖 KI-gestützte Frame-Level-Annotations</li>
                            <li>🏀 Automatische Spielererkennung & Tracking</li>
                            <li>📈 Shot Chart Generation aus Videos</li>
                            <li>✂️ Automatische Highlight-Erstellung</li>
                            <li>📊 Taktische Spielzug-Analyse</li>
                            <li>👁️ Defensive Positioning Analysis</li>
                        </ul>
                        <div class="mt-4 p-3 bg-orange-100 rounded-lg">
                            <p class="text-xs text-orange-800">
                                <strong>Verfügbar in:</strong> Club & Professional Plan
                            </p>
                        </div>
                    </div>

                    <!-- Q3 2026 - ML ANALYTICS -->
                    <div class="feature-card bg-gradient-to-br from-purple-50 to-white p-6 rounded-xl shadow-xl border-2 border-purple-500">
                        <div class="flex items-center justify-between mb-4">
                            <span class="quarter-badge status-future">🔮 Coming Soon</span>
                            <span class="text-sm text-purple-600 font-bold">Q3 2026</span>
                        </div>
                        <div class="text-4xl mb-4">🤖</div>
                        <h3 class="text-xl font-bold text-purple-600 mb-3">ML Analytics & Predictions</h3>
                        <p class="text-gray-600 text-sm mb-3 font-medium">
                            Machine Learning für Performance-Vorhersagen und Spieleranalyse
                        </p>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li>🧠 Performance-Vorhersage Algorithmen</li>
                            <li>⚕️ Verletzungsrisiko-Prognosen</li>
                            <li>📊 Player Efficiency Rating (PER) Optimization</li>
                            <li>🎯 Shot Success Prediction</li>
                            <li>👥 Team Chemistry Analysis</li>
                            <li>🔍 Opponent Pattern Recognition</li>
                            <li>📈 Career Development Tracking</li>
                        </ul>
                        <div class="mt-4 p-3 bg-purple-100 rounded-lg">
                            <p class="text-xs text-purple-800">
                                <strong>Verfügbar in:</strong> Professional & Enterprise Plan
                            </p>
                        </div>
                    </div>

                    <!-- Q4 2026 -->
                    <div class="feature-card bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <span class="quarter-badge status-future">🔮 Coming Soon</span>
                            <span class="text-sm text-gray-500 font-bold">Q4 2026</span>
                        </div>
                        <div class="text-4xl mb-4">🚀</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Advanced ML Features</h3>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li>• Custom Analytics Dashboards</li>
                            <li>• Real-time ML Model Training</li>
                            <li>• Automated Scouting Reports</li>
                            <li>• Draft Recommendation Engine</li>
                            <li>• Advanced Tactical AI Assistant</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Comparison -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Welcher Plan passt zu Ihnen?
                </h2>
                <p class="text-xl text-gray-600">
                    Video-Analyse und ML Analytics sind in höheren Plänen verfügbar
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Starter Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-xl p-6 text-center">
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Starter</h3>
                    <div class="text-3xl font-bold text-gray-900 mb-4">7,99€<span class="text-base font-normal text-gray-600">/Monat</span></div>
                    <ul class="space-y-2 text-sm text-gray-600 mb-6">
                        <li>✅ Live-Scoring</li>
                        <li>✅ Basis Statistiken</li>
                        <li>❌ Video-Analyse</li>
                        <li>❌ ML Analytics</li>
                    </ul>
                </div>

                <!-- Club Plan -->
                <div class="bg-orange-50 border-2 border-orange-500 rounded-xl p-6 text-center relative">
                    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <span class="bg-orange-500 text-white px-4 py-1 rounded-full text-xs font-bold">Beliebt</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Club</h3>
                    <div class="text-3xl font-bold text-gray-900 mb-4">29,99€<span class="text-base font-normal text-gray-600">/Monat</span></div>
                    <ul class="space-y-2 text-sm text-gray-700 mb-6">
                        <li>✅ Alle Starter Features</li>
                        <li>✅ Training Management</li>
                        <li>
                            <span class="font-bold text-orange-600">🎬 Video-Analyse</span>
                            <span class="block text-xs text-orange-600 mt-1">Ab Q2 2026</span>
                        </li>
                        <li>❌ ML Analytics</li>
                    </ul>
                </div>

                <!-- Professional Plan -->
                <div class="bg-purple-50 border-2 border-purple-500 rounded-xl p-6 text-center">
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Professional</h3>
                    <div class="text-3xl font-bold text-gray-900 mb-4">59,99€<span class="text-base font-normal text-gray-600">/Monat</span></div>
                    <ul class="space-y-2 text-sm text-gray-700 mb-6">
                        <li>✅ Alle Club Features</li>
                        <li>
                            <span class="font-bold text-orange-600">🎬 Video-Analyse</span>
                            <span class="block text-xs text-orange-600 mt-1">Ab Q2 2026</span>
                        </li>
                        <li>
                            <span class="font-bold text-purple-600">🤖 ML Analytics</span>
                            <span class="block text-xs text-purple-600 mt-1">Ab Q3 2026</span>
                        </li>
                        <li>✅ API Zugang</li>
                    </ul>
                </div>
            </div>

            <div class="text-center mt-8">
                <a href="{{ url('/') }}#pricing" class="inline-block bg-orange-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-orange-700 transition-colors">
                    Alle Preise ansehen
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                Jetzt starten und die Zukunft mitgestalten
            </h2>
            <p class="text-xl text-orange-100 mb-8">
                Testen Sie BasketManager Pro 30 Tage kostenlos und erhalten Sie als Early Adopter
                exklusiven Zugang zu neuen Features.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="bg-white text-orange-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-orange-50 transition-colors shadow-lg">
                    🚀 Jetzt kostenlos testen
                </a>
                <a href="mailto:support@basketmanager-pro.de" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-orange-600 transition-colors">
                    💬 Fragen zur Roadmap?
                </a>
            </div>

            <div class="mt-8 text-orange-100 text-sm">
                ✅ Keine Kreditkarte erforderlich &nbsp;•&nbsp; ✅ Jederzeit kündbar &nbsp;•&nbsp; ✅ Deutscher Support
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <span class="text-2xl font-bold gradient-text">🏀 BasketManager Pro</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Die moderne All-in-One Basketball Vereinsverwaltung. Made in Germany.
                    </p>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-4">Produkt</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ url('/') }}#features" class="hover:text-orange-500">Features</a></li>
                        <li><a href="{{ url('/') }}#pricing" class="hover:text-orange-500">Preise</a></li>
                        <li><a href="{{ route('roadmap') }}" class="hover:text-orange-500">Roadmap</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="mailto:support@basketmanager-pro.de" class="hover:text-orange-500">Kontakt</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-4">Rechtliches</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-orange-500">Datenschutz</a></li>
                        <li><a href="#" class="hover:text-orange-500">AGB</a></li>
                        <li><a href="#" class="hover:text-orange-500">Impressum</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    © 2025 BasketManager Pro. Alle Rechte vorbehalten.
                </p>
                <div class="flex items-center mt-4 md:mt-0">
                    <span class="text-gray-400 text-sm mr-2">🇩🇪 Hosted in Germany</span>
                    <span class="text-gray-400 text-sm">• GDPR Compliant</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
