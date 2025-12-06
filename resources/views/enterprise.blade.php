<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <title>{{ $content['seo']['title'] ?? 'Enterprise & White-Label Lösung | ' . app_name() }}</title>
    <meta name="description" content="{{ $content['seo']['description'] ?? 'Die White-Label Basketball-Plattform für Verbände und große Vereine.' }}">
    <meta name="keywords" content="{{ $content['seo']['keywords'] ?? 'Basketball Software Enterprise, White Label Basketball, Verband Software' }}">
    <meta name="author" content="{{ app_name() }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $content['seo']['title'] ?? 'Enterprise & White-Label | ' . app_name() }}">
    <meta property="og:description" content="{{ $content['seo']['description'] ?? 'Die White-Label Basketball-Plattform für Verbände und große Vereine.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/enterprise') }}">
    <link rel="canonical" href="{{ url('/enterprise') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

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
        .enterprise-pattern {
            background-image:
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.1) 2px, transparent 2px),
                radial-gradient(circle at 75px 75px, rgba(255, 255, 255, 0.1) 2px, transparent 2px);
            background-size: 100px 100px;
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
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .audience-card {
            transition: all 0.3s ease;
        }
        .audience-card:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body class="font-sans antialiased bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('landing') }}" class="flex-shrink-0 flex items-center">
                        <span class="text-2xl font-bold gradient-text">{{ app_name() }}</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('landing') }}" class="text-gray-700 hover:text-orange-600 font-medium">Startseite</a>
                    <a href="#audience" class="text-gray-700 hover:text-orange-600 font-medium">Zielgruppen</a>
                    <a href="#whitelabel" class="text-gray-700 hover:text-orange-600 font-medium">White-Label</a>
                    <a href="#pricing" class="text-gray-700 hover:text-orange-600 font-medium">Preise</a>
                    <a href="#faq" class="text-gray-700 hover:text-orange-600 font-medium">FAQ</a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-orange-600 font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-orange-600 font-medium">Login</a>
                        <a href="#contact" class="bg-orange-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-orange-700 transition-colors">Kontakt aufnehmen</a>
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
                <a href="{{ route('landing') }}" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Startseite</a>
                <a href="#audience" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Zielgruppen</a>
                <a href="#whitelabel" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">White-Label</a>
                <a href="#pricing" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Preise</a>
                <a href="#faq" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">FAQ</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-gray-700 hover:text-orange-600 font-medium">Login</a>
                    <a href="#contact" class="block px-3 py-2 bg-orange-600 text-white rounded-lg font-medium mx-3 text-center">Kontakt aufnehmen</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg pt-24 pb-20 enterprise-pattern">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center animate-fade-in-up">
                <span class="inline-block bg-white/20 text-white px-4 py-1 rounded-full text-sm font-medium mb-6">
                    Enterprise & White-Label
                </span>
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    {{ $content['hero']['headline'] ?? 'Ihre Basketball-Plattform. Ihre Marke.' }}
                </h1>
                <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-3xl mx-auto">
                    {{ $content['hero']['subheadline'] ?? 'Die vollständig anpassbare White-Label-Lösung für Verbände und große Organisationen' }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ $content['hero']['cta_primary_link'] ?? '#contact' }}" class="bg-white text-orange-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-orange-50 transition-colors shadow-lg">
                        {{ $content['hero']['cta_primary_text'] ?? 'Beratungsgespräch vereinbaren' }}
                    </a>
                    <a href="{{ $content['hero']['cta_secondary_link'] ?? '#pricing' }}" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-orange-600 transition-colors">
                        {{ $content['hero']['cta_secondary_text'] ?? 'Preise anfragen' }}
                    </a>
                </div>
            </div>

            <!-- Hero Stats -->
            <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                @foreach($content['hero']['stats'] ?? [] as $stat)
                <div class="text-white">
                    <div class="text-3xl md:text-4xl font-bold">{{ $stat['value'] }}</div>
                    <div class="text-white/80">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Target Audience Section -->
    <section id="audience" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $content['audience']['headline'] ?? 'Für wen ist die Enterprise-Lösung?' }}
                </h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Verbände -->
                <div class="audience-card bg-white p-8 rounded-2xl shadow-lg border-2 border-orange-100">
                    <div class="flex items-center mb-6">
                        <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $content['audience']['verbaende']['title'] ?? 'Für Verbände' }}</h3>
                            <p class="text-orange-600 font-medium">{{ $content['audience']['verbaende']['subtitle'] ?? 'DBB Landesverbände & Regionalverbände' }}</p>
                        </div>
                    </div>
                    <ul class="space-y-3">
                        @foreach($content['audience']['verbaende']['items'] ?? [] as $item)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Große Organisationen -->
                <div class="audience-card bg-white p-8 rounded-2xl shadow-lg border-2 border-orange-100">
                    <div class="flex items-center mb-6">
                        <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $content['audience']['organisationen']['title'] ?? 'Für große Organisationen' }}</h3>
                            <p class="text-orange-600 font-medium">{{ $content['audience']['organisationen']['subtitle'] ?? 'Multi-Team Vereine & Akademien' }}</p>
                        </div>
                    </div>
                    <ul class="space-y-3">
                        @foreach($content['audience']['organisationen']['items'] ?? [] as $item)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- White-Label Section -->
    <section id="whitelabel" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $content['whitelabel']['headline'] ?? 'Ihr Branding. Überall.' }}
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    {{ $content['whitelabel']['subheadline'] ?? 'Ihre Marke steht im Vordergrund – von der Domain bis zur E-Mail' }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($content['whitelabel']['items'] ?? [] as $item)
                <div class="feature-card bg-gray-50 p-6 rounded-xl">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @switch($item['icon'] ?? 'globe')
                                @case('globe')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    @break
                                @case('photograph')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    @break
                                @case('color-swatch')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                    @break
                                @case('mail')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    @break
                                @case('template')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                    @break
                                @case('document-report')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    @break
                                @default
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            @endswitch
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $item['title'] }}</h3>
                    <p class="text-gray-600">{{ $item['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Multi-Club Management Section -->
    <section class="py-20 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    {{ $content['multiclub']['headline'] ?? 'Eine Plattform. Alle Vereine.' }}
                </h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    {{ $content['multiclub']['subheadline'] ?? 'Zentrale Verwaltung mit maximaler Flexibilität' }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($content['multiclub']['items'] ?? [] as $index => $item)
                <div class="bg-gray-800 p-6 rounded-xl">
                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mb-4 text-white font-bold">
                        {{ $index + 1 }}
                    </div>
                    <h3 class="text-lg font-bold mb-2">{{ $item['title'] }}</h3>
                    <p class="text-gray-400">{{ $item['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Federation Integration Section -->
    <section id="federation" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $content['federation']['headline'] ?? 'Nahtlose Verbandsintegration' }}
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    {{ $content['federation']['subheadline'] ?? 'Direkte Anbindung an offizielle Verbandssysteme' }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                @foreach($content['federation']['items'] ?? [] as $item)
                <div class="bg-gray-50 p-8 rounded-2xl">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @switch($item['icon'] ?? 'badge-check')
                                @case('badge-check')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    @break
                                @case('globe-alt')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    @break
                                @case('refresh')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    @break
                                @default
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            @endswitch
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $item['title'] }}</h3>
                    <p class="text-gray-600 mb-4">{{ $item['description'] }}</p>
                    @if(!empty($item['features']))
                    <ul class="space-y-2">
                        @foreach($item['features'] as $feature)
                        <li class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Case Study Section -->
    <section class="py-20 bg-orange-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $content['usecases']['headline'] ?? 'Erfolgsgeschichten' }}
                </h2>
            </div>

            @foreach($content['usecases']['items'] ?? [] as $usecase)
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="p-8 lg:p-12">
                    <div class="flex flex-wrap items-center gap-4 mb-6">
                        <span class="bg-orange-100 text-orange-700 px-4 py-1 rounded-full text-sm font-medium">
                            {{ $usecase['type'] ?? 'Landesverband' }}
                        </span>
                        @foreach($usecase['stats'] ?? [] as $key => $value)
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                            {{ $value }}
                        </span>
                        @endforeach
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-6">{{ $usecase['name'] ?? 'Landesverband NRW' }}</h3>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                                <span class="w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xs mr-2">!</span>
                                Herausforderung
                            </h4>
                            <p class="text-gray-600">{{ $usecase['challenge'] }}</p>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                                <span class="w-6 h-6 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs mr-2">&#10003;</span>
                                Lösung
                            </h4>
                            <p class="text-gray-600">{{ $usecase['solution'] }}</p>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs mr-2">&#9733;</span>
                                Ergebnis
                            </h4>
                            <p class="text-gray-600">{{ $usecase['result'] }}</p>
                        </div>
                    </div>

                    @if(!empty($usecase['quote']))
                    <blockquote class="border-l-4 border-orange-500 pl-6 py-2">
                        <p class="text-lg text-gray-700 italic mb-4">"{{ $usecase['quote'] }}"</p>
                        <footer class="text-gray-600">
                            <strong>{{ $usecase['quote_author'] ?? '' }}</strong>
                            @if(!empty($usecase['quote_role']))
                            <span class="text-gray-400"> — {{ $usecase['quote_role'] }}</span>
                            @endif
                        </footer>
                    </blockquote>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $content['pricing']['headline'] ?? 'Individuelle Lösungen. Faire Preise.' }}
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    {{ $content['pricing']['subheadline'] ?? 'Maßgeschneidert auf Ihre Anforderungen' }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                <!-- Professional Plan -->
                <div class="bg-gray-50 rounded-2xl p-8 border-2 border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        {{ $content['pricing']['comparison']['professional']['name'] ?? 'Professional' }}
                    </h3>
                    <div class="text-3xl font-bold text-gray-900 mb-6">
                        {{ $content['pricing']['comparison']['professional']['price'] ?? '€149/Monat' }}
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">{{ $content['pricing']['comparison']['professional']['teams'] ?? 'Bis zu 50 Teams' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">{{ $content['pricing']['comparison']['professional']['clubs'] ?? 'Bis zu 10 Clubs' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">{{ $content['pricing']['comparison']['professional']['api'] ?? '5.000 API Calls/Stunde' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">{{ $content['pricing']['comparison']['professional']['support'] ?? 'Prioritäts-Support' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">{{ $content['pricing']['comparison']['professional']['whitelabel'] ?? 'Basis-Branding' }}</span>
                        </li>
                    </ul>
                    <a href="{{ route('landing') }}#pricing" class="block text-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                        Mehr erfahren
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="bg-orange-50 rounded-2xl p-8 border-2 border-orange-500 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-orange-500 text-white px-4 py-1 rounded-full text-sm font-medium">
                            Empfohlen
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        {{ $content['pricing']['comparison']['enterprise']['name'] ?? 'Enterprise' }}
                    </h3>
                    <div class="text-3xl font-bold gradient-text mb-2">
                        {{ $content['pricing']['base_price'] ?? 'Ab €499' }}<span class="text-lg text-gray-600">{{ $content['pricing']['base_price_period'] ?? '/Monat' }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-6">{{ $content['pricing']['base_price_note'] ?? 'Preis abhängig von Anzahl Vereine/Teams' }}</p>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-900 font-medium">{{ $content['pricing']['comparison']['enterprise']['teams'] ?? 'Unbegrenzt' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-900 font-medium">{{ $content['pricing']['comparison']['enterprise']['clubs'] ?? 'Unbegrenzt' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-900 font-medium">{{ $content['pricing']['comparison']['enterprise']['api'] ?? 'Unbegrenzter API-Zugang' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-900 font-medium">{{ $content['pricing']['comparison']['enterprise']['support'] ?? 'Dedizierter Manager' }}</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-900 font-medium">{{ $content['pricing']['comparison']['enterprise']['whitelabel'] ?? 'Vollständiges White-Label' }}</span>
                        </li>
                    </ul>
                    <a href="#contact" class="block text-center bg-orange-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-orange-700 transition-colors">
                        {{ $content['pricing']['cta_text'] ?? 'Individuelles Angebot anfragen' }}
                    </a>
                </div>
            </div>

            <!-- Feature List -->
            <div class="bg-gray-50 rounded-2xl p-8">
                <h4 class="font-bold text-gray-900 mb-4">Enterprise beinhaltet:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($content['pricing']['features'] ?? [] as $feature)
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section id="contact" class="py-20 gradient-bg enterprise-pattern">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    {{ $content['contact']['headline'] ?? 'Lassen Sie uns sprechen' }}
                </h2>
                <p class="text-xl text-white/90">
                    {{ $content['contact']['subheadline'] ?? 'Erfahren Sie, wie wir Ihren Verband oder Ihre Organisation unterstützen können' }}
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-2xl p-8">
                @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('enterprise.contact') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="organization_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Name der Organisation *
                            </label>
                            <input type="text" name="organization_name" id="organization_name" required
                                   value="{{ old('organization_name') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="z.B. Basketball Verband NRW">
                        </div>

                        <div>
                            <label for="organization_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Organisationstyp *
                            </label>
                            <select name="organization_type" id="organization_type" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Bitte wählen...</option>
                                @foreach($organizationTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('organization_type') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Ansprechpartner *
                            </label>
                            <input type="text" name="contact_name" id="contact_name" required
                                   value="{{ old('contact_name') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="Vor- und Nachname">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                E-Mail-Adresse *
                            </label>
                            <input type="email" name="email" id="email" required
                                   value="{{ old('email') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="ihre@email.de">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Telefon
                            </label>
                            <input type="tel" name="phone" id="phone"
                                   value="{{ old('phone') }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="+49 123 456789">
                        </div>

                        <div>
                            <label for="club_count" class="block text-sm font-medium text-gray-700 mb-1">
                                Anzahl Vereine
                            </label>
                            <select name="club_count" id="club_count"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Bitte wählen...</option>
                                @foreach($clubCountOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('club_count') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="team_count" class="block text-sm font-medium text-gray-700 mb-1">
                                Anzahl Teams
                            </label>
                            <select name="team_count" id="team_count"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Bitte wählen...</option>
                                @foreach($teamCountOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('team_count') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">
                            Ihre Nachricht / Anforderungen
                        </label>
                        <textarea name="message" id="message" rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                  placeholder="Beschreiben Sie kurz Ihre Anforderungen...">{{ old('message') }}</textarea>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-start">
                            <input type="checkbox" name="gdpr_accepted" value="1" required
                                   class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                   {{ old('gdpr_accepted') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-600">
                                Ich stimme der Verarbeitung meiner Daten gemäß der
                                <a href="{{ route('legal.show', 'datenschutz') }}" class="text-orange-600 hover:underline" target="_blank">Datenschutzerklärung</a> zu. *
                            </span>
                        </label>

                        <label class="flex items-start">
                            <input type="checkbox" name="newsletter_optin" value="1"
                                   class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                   {{ old('newsletter_optin') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-600">
                                Ich möchte über Neuigkeiten und Updates informiert werden (optional)
                            </span>
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full bg-orange-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-orange-700 transition-colors">
                        Anfrage absenden
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $content['faq']['headline'] ?? 'Häufig gestellte Fragen' }}
                </h2>
            </div>

            <div class="space-y-4">
                @foreach($content['faq']['items'] ?? [] as $index => $faq)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <button onclick="toggleFAQ({{ $index }})"
                            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors">
                        <span class="font-medium text-gray-900">{{ $faq['question'] }}</span>
                        <svg id="faq-icon-{{ $index }}" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="faq-answer-{{ $index }}" class="hidden px-6 pb-4">
                        <p class="text-gray-600">{{ $faq['answer'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <span class="text-2xl font-bold">{{ app_name() }}</span>
                    <p class="mt-4 text-gray-400">
                        Die professionelle Basketball-Vereinsverwaltung für Verbände und große Organisationen.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold mb-4">Produkt</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('landing') }}#features" class="hover:text-white">Features</a></li>
                        <li><a href="{{ route('landing') }}#pricing" class="hover:text-white">Preise</a></li>
                        <li><a href="{{ route('enterprise') }}" class="hover:text-white">Enterprise</a></li>
                        <li><a href="{{ route('roadmap') }}" class="hover:text-white">Roadmap</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold mb-4">Enterprise</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#whitelabel" class="hover:text-white">White-Label</a></li>
                        <li><a href="#federation" class="hover:text-white">Verbandsintegration</a></li>
                        <li><a href="#pricing" class="hover:text-white">Enterprise Pricing</a></li>
                        <li><a href="#contact" class="hover:text-white">Kontakt</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold mb-4">Rechtliches</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('legal.show', 'impressum') }}" class="hover:text-white">Impressum</a></li>
                        <li><a href="{{ route('legal.show', 'datenschutz') }}" class="hover:text-white">Datenschutz</a></li>
                        <li><a href="{{ route('legal.show', 'agb') }}" class="hover:text-white">AGB</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} {{ app_name() }}. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        function toggleFAQ(index) {
            const answer = document.getElementById(`faq-answer-${index}`);
            const icon = document.getElementById(`faq-icon-${index}`);

            answer.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }
    </script>
</body>
</html>
