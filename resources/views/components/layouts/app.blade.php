<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Stock Purchase System' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-xl font-bold text-indigo-600">SPS</a>
                    <a href="/dashboard" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">Dashboard</a>
                    <a href="/admin/cesta" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">Cesta</a>
                    <a href="/admin/compras" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">Compras</a>
                    <a href="/admin/master" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">Conta Master</a>
                    <a href="/api/documentation" class="text-gray-500 hover:text-indigo-600 text-sm">API Docs</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
