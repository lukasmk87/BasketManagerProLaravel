<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <title>{{ $page->title }} - {{ app_name() }}</title>
    <meta name="description" content="{{ $page->meta_description ?? $page->excerpt }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $page->title }} - {{ app_name() }}">
    <meta property="og:description" content="{{ $page->meta_description ?? $page->excerpt }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

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

    <style>
        .legal-content h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        .legal-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #374151;
        }
        .legal-content h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: #4b5563;
        }
        .legal-content p {
            margin-bottom: 1rem;
            line-height: 1.75;
            color: #4b5563;
        }
        .legal-content ul, .legal-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
            color: #4b5563;
        }
        .legal-content li {
            margin-bottom: 0.5rem;
            line-height: 1.75;
        }
        .legal-content a {
            color: #ea580c;
            text-decoration: underline;
        }
        .legal-content a:hover {
            color: #c2410c;
        }
        .legal-content strong {
            font-weight: 600;
            color: #1f2937;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50">
    <!-- Simple Navigation -->
    <nav class="bg-white shadow-md">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="{{ route('landing') }}" class="flex items-center">
                        <span class="text-2xl font-bold gradient-text">🏀 {{ app_name() }}</span>
                    </a>
                </div>

                <div class="flex items-center space-x-6">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-orange-600 font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-orange-600 font-medium">Login</a>
                        <a href="{{ route('register') }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-orange-700 transition-colors">Registrieren</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    {{ $page->title }}
                </h1>
                @if($page->meta_description)
                    <p class="text-lg text-gray-600">
                        {{ $page->meta_description }}
                    </p>
                @endif
            </div>

            <!-- Page Content -->
            <article class="bg-white rounded-xl shadow-lg p-8 lg:p-12">
                <div class="legal-content prose prose-lg max-w-none">
                    {!! $page->content !!}
                </div>
            </article>

            <!-- Last Updated -->
            <div class="mt-6 text-sm text-gray-500 text-center">
                Zuletzt aktualisiert: {{ $page->updated_at->format('d.m.Y') }}
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <span class="text-2xl font-bold gradient-text">🏀 {{ app_name() }}</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Die moderne All-in-One Basketball Vereinsverwaltung. Made in Germany.
                    </p>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-4">Produkt</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('landing') }}#features" class="hover:text-orange-500">Features</a></li>
                        <li><a href="{{ route('landing') }}#pricing" class="hover:text-orange-500">Preise</a></li>
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
                        <li><a href="{{ route('legal.show', 'datenschutz') }}" class="hover:text-orange-500">Datenschutz</a></li>
                        <li><a href="{{ route('legal.show', 'agb') }}" class="hover:text-orange-500">AGB</a></li>
                        <li><a href="{{ route('legal.show', 'impressum') }}" class="hover:text-orange-500">Impressum</a></li>
                        <li><a href="{{ route('legal.show', 'gdpr') }}" class="hover:text-orange-500">GDPR</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    © {{ date('Y') }} {{ app_name() }}. Alle Rechte vorbehalten.
                </p>
                <div class="flex items-center mt-4 md:mt-0">
                    <span class="text-gray-400 text-sm mr-2">🇩🇪 Hosted in Germany</span>
                    <span class="text-gray-400 text-sm">• GDPR Compliant</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
