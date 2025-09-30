<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#4f46e5">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'WP-POS') }}">
        
        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.json">
        
        <!-- PWA Icons -->
        <link rel="icon" type="image/png" sizes="192x192" href="/images/icons/icon-192x192.png">
        <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <!-- Offline Status Bar -->
            <div id="offline-banner" class="hidden bg-yellow-500 text-white px-4 py-2 text-center text-sm font-medium">
                <span class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span id="offline-message">Working offline. Data will sync when connection is restored.</span>
                    <span id="pending-count" class="ml-2 px-2 py-0.5 bg-yellow-600 rounded-full text-xs"></span>
                </span>
            </div>

            <!-- PWA Install Button -->
            <button id="pwa-install-button" style="display: none;" class="fixed bottom-4 right-4 bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-lg hover:bg-indigo-700 transition-colors z-50">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Install App
                </span>
            </button>

            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Offline Status Script -->
        <script>
            // Update offline banner based on connection status
            if (window.connectionMonitor) {
                window.connectionMonitor.on('statusChanged', ({ isOnline }) => {
                    const banner = document.getElementById('offline-banner');
                    if (isOnline) {
                        banner.classList.add('hidden');
                    } else {
                        banner.classList.remove('hidden');
                    }
                });

                window.connectionMonitor.on('pendingCountChanged', ({ count }) => {
                    const countEl = document.getElementById('pending-count');
                    if (count > 0) {
                        countEl.textContent = `${count} pending`;
                        countEl.classList.remove('hidden');
                    } else {
                        countEl.classList.add('hidden');
                    }
                });

                // Initial check
                const status = window.connectionMonitor.getStatus();
                if (!status.isOnline) {
                    document.getElementById('offline-banner').classList.remove('hidden');
                }
                if (status.pendingCount > 0) {
                    const countEl = document.getElementById('pending-count');
                    countEl.textContent = `${status.pendingCount} pending`;
                    countEl.classList.remove('hidden');
                }
            }
        </script>
    </body>
</html>
