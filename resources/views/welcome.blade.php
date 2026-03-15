<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock Purchase System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
                <span class="text-2xl font-bold text-indigo-600">SPS</span>
                <div class="space-x-4">
                    @auth
                        <a href="/dashboard" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Entrar</a>
                        <a href="{{ route('register') }}" class="text-sm font-medium bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Criar Conta</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="grow flex items-center justify-center">
            <div class="max-w-4xl mx-auto text-center px-4 py-20">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">
                    Compra Programada de <span class="text-indigo-600">Ações</span>
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    Sistema automatizado de investimento recorrente na cesta Top Five.
                    Consolidação, distribuição proporcional, rebalanceamento automático e cálculo de IR.
                </p>

                <div class="flex justify-center space-x-4 mb-16">
                    @auth
                        <a href="/dashboard" class="bg-indigo-600 text-white px-8 py-3 rounded-lg text-lg font-medium hover:bg-indigo-700">Ir para o Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-8 py-3 rounded-lg text-lg font-medium hover:bg-indigo-700">Começar Agora</a>
                        <a href="{{ route('login') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg text-lg font-medium border border-indigo-200 hover:bg-indigo-50">Já tenho conta</a>
                    @endauth
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h3 class="font-semibold text-gray-900 mb-2">Compra Consolidada</h3>
                        <p class="text-sm text-gray-600">Aportes consolidados para compra única nos dias 5, 15 e 25 de cada mês.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h3 class="font-semibold text-gray-900 mb-2">Rebalanceamento</h3>
                        <p class="text-sm text-gray-600">Ajuste automático quando a cesta muda ou proporção desvia mais de 5%.</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h3 class="font-semibold text-gray-900 mb-2">IR Automatizado</h3>
                        <p class="text-sm text-gray-600">IR dedo-duro e IR sobre vendas com publicação automática no Kafka.</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-100 py-6">
            <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">
                Stock Purchase System &mdash; Adaptado do desafio técnico Itaú Corretora
            </div>
        </footer>
    </div>
</body>
</html>
