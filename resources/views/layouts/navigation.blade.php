<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center space-x-8">
                <a href="/dashboard" class="text-xl font-bold text-indigo-600">SPS</a>
                <a href="/dashboard" class="text-sm font-medium {{ request()->is('dashboard') ? 'text-indigo-600 border-b-2 border-indigo-600 pb-5 pt-5' : 'text-gray-500 hover:text-gray-700' }}">Dashboard</a>
                <a href="/admin/cesta" class="text-sm font-medium {{ request()->is('admin/cesta') ? 'text-indigo-600 border-b-2 border-indigo-600 pb-5 pt-5' : 'text-gray-500 hover:text-gray-700' }}">Cesta</a>
                <a href="/admin/compras" class="text-sm font-medium {{ request()->is('admin/compras') ? 'text-indigo-600 border-b-2 border-indigo-600 pb-5 pt-5' : 'text-gray-500 hover:text-gray-700' }}">Compras</a>
                <a href="/admin/master" class="text-sm font-medium {{ request()->is('admin/master') ? 'text-indigo-600 border-b-2 border-indigo-600 pb-5 pt-5' : 'text-gray-500 hover:text-gray-700' }}">Conta Master</a>
                <a href="/admin/security" class="text-sm font-medium {{ request()->is('admin/security') ? 'text-indigo-600 border-b-2 border-indigo-600 pb-5 pt-5' : 'text-gray-500 hover:text-gray-700' }}">Security</a>
                <a href="/api/documentation" class="text-sm text-gray-400 hover:text-gray-600">API Docs</a>
            </div>

            <div class="flex items-center space-x-4">
                @auth
                    <span class="text-sm text-gray-500">{{ Auth::user()->name }} <span class="text-xs text-gray-400">({{ Auth::user()->role }})</span></span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Sair</button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</nav>
