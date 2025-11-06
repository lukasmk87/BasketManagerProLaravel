<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>{{ $content['seo']['title'] ?? app_name() . ' - Die All-in-One Basketball Vereinsverwaltung' }}</title>
    <meta name="description" content="{{ $content['seo']['description'] ?? 'Professionelle Basketball-Vereinsverwaltung mit Live-Scoring, Spieler-Management, Training-Tools und mehr.' }}">
    <meta name="keywords" content="{{ $content['seo']['keywords'] ?? 'Basketball, Vereinsverwaltung, Live Scoring, Basketball Software, Basketball App, Team Management' }}">
    <meta name="author" content="{{ app_name() }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $content['seo']['title'] ?? app_name() . ' - Die All-in-One Basketball Vereinsverwaltung' }}">
    <meta property="og:description" content="{{ $content['seo']['description'] ?? 'Professionelle Basketball-Vereinsverwaltung mit Live-Scoring, Spieler-Management, Training-Tools und mehr.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    @if(!empty($content['seo']['og_image']))
    <meta property="og:image" content="{{ asset($content['seo']['og_image']) }}">
    @endif
    
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
        .animate-bounce-slow {
            animation: bounce 3s infinite;
        }
        .animate-fade-in-up {
            animation: fadeInUp 1s ease-out forwards;
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
        .pricing-card {
            transition: all 0.3s ease;
        }
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .feature-icon {
            transition: all 0.3s ease;
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        .future-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.65rem;
            font-weight: 700;
            margin-left: 0.25rem;
            vertical-align: middle;
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
                        <span class="text-2xl font-bold gradient-text">🏀 {{ app_name() }}</span>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-orange-600 font-medium">Features</a>
                    <a href="#pricing" class="text-gray-700 hover:text-orange-600 font-medium">Preise</a>
                    <a href="#testimonials" class="text-gray-700 hover:text-orange-600 font-medium">Referenzen</a>
                    <a href="#faq" class="text-gray-700 hover:text-orange-600 font-medium">FAQ</a>
                    
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
                <a href="#features" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Features</a>
                <a href="#pricing" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Preise</a>
                <a href="#testimonials" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Referenzen</a>
                <a href="#faq" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">FAQ</a>
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
    <section class="gradient-bg pt-20 pb-16 basketball-pattern">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center animate-fade-in-up">
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
                    {!! nl2br(e($content['hero']['headline'] ?? 'Die All-in-One Basketball Vereinsverwaltung')) !!}
                </h1>
                <p class="text-xl md:text-2xl text-gray-700 mb-8 max-w-3xl mx-auto">
                    {{ $content['hero']['subheadline'] ?? 'Professionelles Team-Management, Live-Scoring, Training-Tools und mehr. 20% günstiger als die Konkurrenz. GDPR-konform und Made in Germany.' }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ $content['hero']['cta_primary_link'] ?? route('register') }}" class="bg-white text-orange-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-orange-50 transition-colors shadow-lg">
                        {{ $content['hero']['cta_primary_text'] ?? '🚀 Kostenlos testen' }}
                    </a>
                    @if(!empty($content['hero']['cta_secondary_text']))
                    <a href="{{ $content['hero']['cta_secondary_link'] ?? '#demo' }}" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-orange-600 transition-colors">
                        {{ $content['hero']['cta_secondary_text'] }}
                    </a>
                    @endif
                </div>
            </div>

            <!-- Hero Stats -->
            <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="text-gray-900">
                    <div class="text-3xl md:text-4xl font-bold">{{ $content['hero']['stats']['clubs'] ?? '500+' }}</div>
                    <div class="text-gray-600">{{ $content['hero']['stats_labels']['clubs'] ?? 'Vereine' }}</div>
                </div>
                <div class="text-gray-900">
                    <div class="text-3xl md:text-4xl font-bold">{{ $content['hero']['stats']['teams'] ?? '2.000+' }}</div>
                    <div class="text-gray-600">{{ $content['hero']['stats_labels']['teams'] ?? 'Teams' }}</div>
                </div>
                <div class="text-gray-900">
                    <div class="text-3xl md:text-4xl font-bold">{{ $content['hero']['stats']['players'] ?? '15.000+' }}</div>
                    <div class="text-gray-600">{{ $content['hero']['stats_labels']['players'] ?? 'Spieler' }}</div>
                </div>
                <div class="text-gray-900">
                    <div class="text-3xl md:text-4xl font-bold">{{ $content['hero']['stats']['uptime'] ?? '99,9%' }}</div>
                    <div class="text-gray-600">{{ $content['hero']['stats_labels']['uptime'] ?? 'Verfügbarkeit' }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Alles was Ihr Basketball-Verein braucht
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Von der Spielerverwaltung bis zum Live-Scoring - {{ app_name() }} deckt alle Bereiche der modernen Vereinsarbeit ab.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Live Scoring -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <div class="feature-icon text-4xl mb-4">📊</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Live-Scoring & Statistiken</h3>
                    <p class="text-gray-600 mb-4">Echtzeiterfassung aller Spielereignisse mit automatischer Statistikberechnung. WebSocket-basierte Live-Updates für Zuschauer.</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li>✅ 20+ Basketball-Statistiken</li>
                        <li>✅ Tablet-optimierte Scorer-App</li>
                        <li>✅ Offline-Modus für Hallen</li>
                    </ul>
                </div>
                
                <!-- Team Management -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <div class="feature-icon text-4xl mb-4">👥</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Team & Spielerverwaltung</h3>
                    <p class="text-gray-600 mb-4">Komplette Roster-Verwaltung mit Basketball-spezifischen Daten, Positionen und medizinischen Informationen.</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li>✅ Jersey-Nummer Management</li>
                        <li>✅ Position & Statistik-Tracking</li>
                        <li>✅ Medizinische Daten (GDPR)</li>
                    </ul>
                </div>
                
                <!-- Training -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <div class="feature-icon text-4xl mb-4">🏋️</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Training & Drill Management</h3>
                    <p class="text-gray-600 mb-4">Trainingsplanung mit Video-Analyse, Drill-Bibliothek und Leistungsverfolgung für jeden Spieler.</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li>✅ 100+ vorgefertigte Drills</li>
                        <li>✅ Video-Annotation Tools</li>
                        <li>✅ Anwesenheits-Tracking</li>
                    </ul>
                </div>
                
                <!-- Tournaments -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <div class="feature-icon text-4xl mb-4">🏆</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Turniere & Ligen</h3>
                    <p class="text-gray-600 mb-4">Automatisierte Bracket-Generierung, Turnierverwaltung und Integration mit Basketball-Verbänden.</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li>✅ DBB & FIBA Integration</li>
                        <li>✅ Automatische Brackets</li>
                        <li>✅ Schiedsrichter-Management</li>
                    </ul>
                </div>
                
                <!-- Emergency -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <div class="feature-icon text-4xl mb-4">🚨</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Notfall-Management</h3>
                    <p class="text-gray-600 mb-4">Integriertes Notfallkontakte-System mit Quick-Access und automatischen Benachrichtigungen.</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li>✅ Prioritäts-basierte Kontakte</li>
                        <li>✅ Medizinische Allergien</li>
                        <li>✅ Emergency Quick-Dial</li>
                    </ul>
                </div>
                
                <!-- Mobile PWA -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <div class="feature-icon text-4xl mb-4">📱</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Mobile App (PWA)</h3>
                    <p class="text-gray-600 mb-4">Progressive Web App mit Offline-Funktionalität, Push-Notifications und nativer App-Performance.</p>
                    <ul class="text-sm text-gray-500 space-y-1">
                        <li>✅ Offline-fähig</li>
                        <li>✅ Push-Benachrichtigungen</li>
                        <li>✅ App Store Installation</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Transparente Preise - 20% günstiger
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Faire Preise ohne versteckte Kosten. Alle Features in jedem Plan. 30 Tage kostenlos testen.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Starter -->
                <div class="pricing-card bg-white border-2 border-gray-200 rounded-xl p-8 text-center">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Starter</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">7,99€</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="space-y-3 text-gray-600 mb-8">
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> 1 Team</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> 25 Spieler</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Live-Scoring</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Basis Statistiken</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Mobile App</li>
                    </ul>
                    <a href="{{ route('register') }}" class="w-full bg-gray-900 text-white py-3 rounded-lg font-bold hover:bg-gray-800 transition-colors block">
                        Kostenlos starten
                    </a>
                </div>
                
                <!-- Club (Most Popular) -->
                <div class="pricing-card bg-orange-50 border-2 border-orange-500 rounded-xl p-8 text-center relative">
                    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <span class="bg-orange-500 text-white px-4 py-1 rounded-full text-sm font-bold">Beliebt</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Club</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">29,99€</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="space-y-3 text-gray-600 mb-8">
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> 5 Teams</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> 125 Spieler</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Training Management</li>
                        <li class="flex items-center justify-center">
                            <span class="text-orange-500 mr-2">🎬</span>
                            <span>Video-Analyse</span>
                            <span class="future-badge">AB Q2 2026</span>
                        </li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Turniere</li>
                    </ul>
                    <a href="{{ route('register') }}" class="w-full bg-orange-600 text-white py-3 rounded-lg font-bold hover:bg-orange-700 transition-colors block">
                        Kostenlos starten
                    </a>
                </div>
                
                <!-- Professional -->
                <div class="pricing-card bg-white border-2 border-gray-200 rounded-xl p-8 text-center">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Professional</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">59,99€</span>
                        <span class="text-gray-600">/Monat</span>
                    </div>
                    <ul class="space-y-3 text-gray-600 mb-8">
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> 15 Teams</li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> 375 Spieler</li>
                        <li class="flex items-center justify-center">
                            <span class="text-orange-500 mr-2">🎬</span>
                            <span>Video-Analyse</span>
                            <span class="future-badge">AB Q2 2026</span>
                        </li>
                        <li class="flex items-center justify-center">
                            <span class="text-purple-500 mr-2">🤖</span>
                            <span>ML Analytics</span>
                            <span class="future-badge">AB Q3 2026</span>
                        </li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> API Zugang</li>
                    </ul>
                    <a href="{{ route('register') }}" class="w-full bg-gray-900 text-white py-3 rounded-lg font-bold hover:bg-gray-800 transition-colors block">
                        Kostenlos starten
                    </a>
                </div>
                
                <!-- Enterprise -->
                <div class="pricing-card bg-gray-900 text-white rounded-xl p-8 text-center">
                    <h3 class="text-xl font-bold mb-4">Enterprise</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold">Custom</span>
                        <div class="text-gray-300 text-sm">individuell</div>
                    </div>
                    <ul class="space-y-3 text-gray-300 mb-8">
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> Unbegrenzte Teams</li>
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> Unbegrenzte Spieler</li>
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> White-Label</li>
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> On-Premise</li>
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> Dedizierter Support</li>
                    </ul>
                    <a href="mailto:enterprise@basketmanager-pro.de" class="w-full bg-white text-gray-900 py-3 rounded-lg font-bold hover:bg-gray-100 transition-colors block">
                        Kontakt aufnehmen
                    </a>
                </div>
            </div>
            
            <!-- Money Back Guarantee -->
            <div class="text-center mt-12">
                <p class="text-gray-600">
                    💰 <strong>30 Tage Geld-zurück-Garantie</strong> • Keine Einrichtungsgebühr • Jederzeit kündbar
                </p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Was Trainer und Vereine sagen
                </h2>
                <p class="text-xl text-gray-600">
                    Über 500 Vereine vertrauen bereits auf {{ app_name() }}
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="flex items-center mb-4">
                        <div class="text-orange-500">⭐⭐⭐⭐⭐</div>
                    </div>
                    <p class="text-gray-600 mb-6">
                        "{{ app_name() }} hat unsere Vereinsarbeit revolutioniert. Das Live-Scoring ist ein Game-Changer für unsere Zuschauer und die Statistiken helfen uns enorm bei der Spielerentwicklung."
                    </p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                            MS
                        </div>
                        <div class="ml-3">
                            <p class="font-bold text-gray-900">Michael Schmidt</p>
                            <p class="text-sm text-gray-600">Head Coach, BBC München</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="flex items-center mb-4">
                        <div class="text-orange-500">⭐⭐⭐⭐⭐</div>
                    </div>
                    <p class="text-gray-600 mb-6">
                        "Die Notfall-Features haben uns schon mehrmals geholfen. Als Jugendverein ist es beruhigend zu wissen, dass wir im Ernstfall alle wichtigen Informationen sofort griffbereit haben."
                    </p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                            AK
                        </div>
                        <div class="ml-3">
                            <p class="font-bold text-gray-900">Andrea Klein</p>
                            <p class="text-sm text-gray-600">Vereinsmanagerin, JSG Rheinland</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="flex items-center mb-4">
                        <div class="text-orange-500">⭐⭐⭐⭐⭐</div>
                    </div>
                    <p class="text-gray-600 mb-6">
                        "Endlich eine Software, die wirklich für Basketball entwickelt wurde. Die Video-Analyse Features sind professionell und der Support ist erstklassig. Klare Empfehlung!"
                    </p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                            TH
                        </div>
                        <div class="ml-3">
                            <p class="font-bold text-gray-900">Thomas Hoffmann</p>
                            <p class="text-sm text-gray-600">Trainer, Basketball Löwen Berlin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Häufig gestellte Fragen
                </h2>
                <p class="text-xl text-gray-600">
                    Alles was Sie über {{ app_name() }} wissen müssen
                </p>
            </div>
            
            <div class="space-y-8">
                <div class="border border-gray-200 rounded-lg p-6">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(1)">
                        <h3 class="text-lg font-bold text-gray-900">Ist {{ app_name() }} GDPR-konform?</h3>
                        <span class="text-gray-500" id="faq-icon-1">+</span>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-1">
                        Ja, {{ app_name() }} ist vollständig GDPR-konform entwickelt. Alle Daten werden auf deutschen Servern gehostet, wir haben umfassende Datenschutz-Features implementiert und unterstützen alle GDPR-Anforderungen wie Datenexport und Löschung auf Anfrage.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-6">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(2)">
                        <h3 class="text-lg font-bold text-gray-900">Funktioniert die App auch ohne Internet?</h3>
                        <span class="text-gray-500" id="faq-icon-2">+</span>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-2">
                        Ja! Unsere Progressive Web App (PWA) funktioniert vollständig offline. Sie können Spiele erfassen, Statistiken eingeben und auf alle wichtigen Daten zugreifen, auch ohne Internetverbindung. Die Daten werden automatisch synchronisiert, sobald Sie wieder online sind.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-6">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(3)">
                        <h3 class="text-lg font-bold text-gray-900">Kann ich meine bestehenden Daten importieren?</h3>
                        <span class="text-gray-500" id="faq-icon-3">+</span>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-3">
                        Absolut! Wir unterstützen den Import aus Excel-Dateien, CSV-Dateien und anderen Vereinsverwaltungs-Tools. Unser Support-Team hilft Ihnen gerne beim Datenimport - kostenfrei während der Testphase.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-6">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(4)">
                        <h3 class="text-lg font-bold text-gray-900">Gibt es eine Mindestvertragslaufzeit?</h3>
                        <span class="text-gray-500" id="faq-icon-4">+</span>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-4">
                        Nein, alle unsere Pläne sind monatlich kündbar. Es gibt keine Mindestvertragslaufzeit oder versteckten Kosten. Sie können jederzeit upgraden, downgraden oder kündigen.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-6">
                    <button class="w-full text-left flex justify-between items-center" onclick="toggleFAQ(5)">
                        <h3 class="text-lg font-bold text-gray-900">Wie unterscheidet sich {{ app_name() }} von der Konkurrenz?</h3>
                        <span class="text-gray-500" id="faq-icon-5">+</span>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-5">
                        {{ app_name() }} ist speziell für Basketball entwickelt - nicht nur eine angepasste Fußball-Software. Wir bieten einzigartige Features wie Basketball-spezifische Statistiken, Notfall-Management, Video-Analyse und ML-basierte Performance-Vorhersagen. Dabei sind wir 20% günstiger als vergleichbare Lösungen.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                Bereit für die Zukunft der Basketball-Vereinsverwaltung?
            </h2>
            <p class="text-xl text-orange-100 mb-8">
                Schließen Sie sich über 500 Vereinen an und digitalisieren Sie Ihre Vereinsarbeit noch heute.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="bg-white text-orange-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-orange-50 transition-colors shadow-lg">
                    🚀 Jetzt 30 Tage kostenlos testen
                </a>
                <a href="mailto:support@basketmanager-pro.de" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-orange-600 transition-colors">
                    💬 Persönliche Demo buchen
                </a>
            </div>
            
            <div class="mt-8 text-orange-100 text-sm">
                ✅ Keine Kreditkarte erforderlich &nbsp;•&nbsp; ✅ Setup in unter 5 Minuten &nbsp;•&nbsp; ✅ Deutscher Support
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <span class="text-2xl font-bold gradient-text">🏀 {{ app_name() }}</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Die moderne All-in-One Basketball Vereinsverwaltung. Made in Germany.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-orange-500">Twitter</a>
                        <a href="#" class="text-gray-400 hover:text-orange-500">LinkedIn</a>
                        <a href="#" class="text-gray-400 hover:text-orange-500">YouTube</a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Produkt</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#features" class="hover:text-orange-500">Features</a></li>
                        <li><a href="#pricing" class="hover:text-orange-500">Preise</a></li>
                        <li><a href="{{ route('roadmap') }}" class="hover:text-orange-500">Roadmap</a></li>
                        <li><a href="#" class="hover:text-orange-500">Changelog</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-orange-500">Hilfe-Center</a></li>
                        <li><a href="#" class="hover:text-orange-500">API Dokumentation</a></li>
                        <li><a href="#" class="hover:text-orange-500">System Status</a></li>
                        <li><a href="mailto:support@basketmanager-pro.de" class="hover:text-orange-500">Kontakt</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Rechtliches</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('legal.show', 'datenschutz') }}" class="hover:text-orange-500">Datenschutz</a></li>
                        <li><a href="{{ route('legal.show', 'agb') }}" class="hover:text-orange-500">AGB</a></li>
                        <li><a href="{{ route('legal.show', 'impressum') }}" class="hover:text-orange-500">Impressum</a></li>
                        <li><a href="{{ route('legal.show', 'gdpr') }}" class="hover:text-orange-500">GDPR</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    © 2025 {{ app_name() }}. Alle Rechte vorbehalten.
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
        
        // FAQ toggle
        function toggleFAQ(num) {
            const content = document.getElementById(`faq-content-${num}`);
            const icon = document.getElementById(`faq-icon-${num}`);
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.textContent = '-';
            } else {
                content.classList.add('hidden');
                icon.textContent = '+';
            }
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