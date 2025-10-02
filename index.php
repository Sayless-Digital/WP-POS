<?php
// Load WordPress to access nonce functions
require_once __DIR__ . '/../wp-load.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modern POS</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛒</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- JPOS Routing Module -->
    <script src="assets/js/modules/routing.js"></script>
    <!-- JPOS Original JavaScript (temporarily reverting for debugging) -->
    <script src="assets/js/main.js?v=1.5.3"></script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; } /* bg-slate-800 */
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; } /* bg-slate-600 */
        ::-webkit-scrollbar-thumb:hover { background: #64748b; } /* bg-slate-500 */

        /* Active state for segmented controls */
        .segmented-control button[data-state='active'] {
            background-color: #e2e8f0; /* slate-200 */
            color: #0f172a; /* slate-900 */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
        }
        .segmented-control button:hover:not([data-state='active']) {
            background-color: #334155; /* slate-700 lighter on hover */
            color: #e2e8f0; /* slate-200 */
        }

        /* Placeholder background for images */
        .placeholder-bg {
            background-image: linear-gradient(45deg, #334155 25%, transparent 25%), linear-gradient(-45deg, #334155 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #334155 75%), linear-gradient(-45deg, transparent 75%, #334155 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        }

        /* Ensure grid rows don't stretch */
        #product-list { grid-auto-rows: min-content; }

        /* Side menu transition */
        #side-menu { 
            transition: transform 0.3s ease-in-out; 
            transform: translateX(-100%);
        }
        #side-menu.is-open { 
            transform: translateX(0) !important; 
        }

        /* Reusable Form Component Styles */
        .form-input {
            background-color: #1e293b; /* slate-800 */
            border: 1px solid #334155; /* slate-700 */
            color: #e2e8f0; /* slate-200 */
            border-radius: 0.5rem; /* rounded-lg */
            padding: 0.75rem 1rem; /* py-3 px-4 */
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #6366f1; /* indigo-500 */
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5); /* ring-indigo-500/50 */
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem; /* mb-2 */
            font-size: 0.875rem; /* text-sm */
            font-weight: 500; /* font-medium */
            color: #94a3b8; /* slate-400 */
        }

        /* Full-screen overlay for modals and login */
        .app-overlay {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(15, 23, 42, 0.8); /* slate-900 with opacity */
            backdrop-filter: blur(8px);
        }

        /* Styles for Skeleton Loaders */
        .skeleton-loader {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.75rem; /* space-y-3 */
            padding: 0.5rem; /* p-2 */
            overflow: hidden; /* Prevent animation overflow */
            flex-grow: 1; /* Allow it to fill parent height in flex contexts */
        }
        .skeleton-loader .row {
            display: grid;
            gap: 1rem; /* gap-4 */
            background-color: #1e293b; /* bg-slate-800 */
            padding: 0.75rem; /* p-3 */
            border-radius: 0.5rem; /* rounded-lg */
            align-items: center;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .skeleton-loader .block {
            background-color: #334155; /* bg-slate-700 */
            border-radius: 0.25rem; /* rounded */
            height: 1rem; /* Default height for text lines */
        }

        /* List Rows (Orders, Sessions) */
        .skeleton-loader.list-rows .row { grid-template-columns: repeat(12, minmax(0, 1fr)); }
        .skeleton-loader.list-rows .block:nth-child(1) { grid-column: span 2 / span 2; }
        .skeleton-loader.list-rows .block:nth-child(2) { grid-column: span 3 / span 3; }
        .skeleton-loader.list-rows .block:nth-child(3) { grid-column: span 2 / span 2; }
        .skeleton-loader.list-rows .block:nth-child(4) { grid-column: span 1 / span 1; }
        .skeleton-loader.list-rows .block:nth-child(5) { grid-column: span 2 / span 2; }
        .skeleton-loader.list-rows .block:nth-child(6) { grid-column: span 2 / span 2; }

        /* Variation Edit Modal Rows */
        .skeleton-loader.variation-edit-rows { max-height: 60vh; overflow: hidden; padding-right: 0.5rem; gap: 0.5rem; }
        .skeleton-loader.variation-edit-rows .row { grid-template-columns: repeat(12, minmax(0, 1fr)); background-color: #0f172a; padding: 0.75rem; border-radius: 0.5rem; }
        .skeleton-loader.variation-edit-rows .block { height: 1.5rem; }
        .skeleton-loader.variation-edit-rows .block:nth-child(1) { grid-column: span 4 / span 4; }
        .skeleton-loader.variation-edit-rows .block:nth-child(2) { grid-column: span 3 / span 3; }
        .skeleton-loader.variation-edit-rows .block:nth-child(3) { grid-column: span 2 / span 2; }
        .skeleton-loader.variation-edit-rows .block:nth-child(4) { grid-column: span 3 / span 3; }
        
        /* Reports Page Skeleton */
        .skeleton-loader.reports-page .kpi-row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; }
        .skeleton-loader.reports-page .kpi-block { background-color: #1e293b; border-radius: 0.75rem; padding: 1.5rem; animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        .skeleton-loader.reports-page .kpi-block .block { background-color: #334155; border-radius: 0.25rem; }
        .skeleton-loader.reports-page .kpi-block .block:first-child { height: 0.75rem; width: 50%; margin-bottom: 0.75rem; }
        .skeleton-loader.reports-page .kpi-block .block:last-child { height: 1.5rem; width: 75%; }
        .skeleton-loader.reports-page .chart-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .skeleton-loader.reports-page .chart-block { background-color: #1e293b; border-radius: 0.75rem; height: 300px; animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 font-sans antialiased overflow-hidden">

    <!-- CSRF Nonces for API Security -->
    <input type="hidden" id="jpos-login-nonce" value="<?php echo wp_create_nonce('jpos_login_nonce'); ?>">
    <input type="hidden" id="jpos-logout-nonce" value="<?php echo wp_create_nonce('jpos_logout_nonce'); ?>">
    <input type="hidden" id="jpos-checkout-nonce" value="<?php echo wp_create_nonce('jpos_checkout_nonce'); ?>">
    <input type="hidden" id="jpos-settings-nonce" value="<?php echo wp_create_nonce('jpos_settings_nonce'); ?>">
    <input type="hidden" id="jpos-drawer-nonce" value="<?php echo wp_create_nonce('jpos_drawer_nonce'); ?>">
    <input type="hidden" id="jpos-stock-nonce" value="<?php echo wp_create_nonce('jpos_stock_nonce'); ?>">
    <input type="hidden" id="jpos-refund-nonce" value="<?php echo wp_create_nonce('jpos_refund_nonce'); ?>">

    <!-- Login Screen -->
    <div id="login-screen" class="app-overlay hidden">
        <div class="w-full max-w-sm p-8 space-y-6 bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl">
            <h2 class="text-3xl font-bold text-center text-white">JPOS Login</h2>
            <form id="login-form" class="space-y-4">
                <div>
                    <label for="username" class="form-label">Username</label>
                    <input id="username" name="username" type="text" required class="form-input" autocomplete="username">
                </div>
                <div>
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" required class="form-input" autocomplete="current-password">
                </div>
                <button type="submit" class="w-full mt-2 bg-indigo-600 text-white p-3 rounded-lg font-bold hover:bg-indigo-500 transition-colors disabled:bg-slate-500">Sign In</button>
                <p id="login-error" class="text-sm text-red-400 text-center h-5"></p>
            </form>
        </div>
    </div>
    
    <!-- Drawer Modal -->
    <div id="drawer-modal" class="app-overlay hidden">
        <div class="w-full max-w-sm p-8 bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl">
            <!-- Open Drawer View -->
            <div id="drawer-open-view">
                <h2 class="text-2xl font-bold text-center text-white mb-4">Open Cash Drawer</h2>
                <form id="drawer-open-form" class="space-y-4">
                    <p class="text-sm text-slate-400 text-center">Enter the starting cash amount to begin.</p>
                    <div>
                        <label for="opening-amount" class="form-label">Starting Amount ($)</label>
                        <input id="opening-amount" type="number" step="0.01" required class="form-input" placeholder="e.g., 150.00">
                    </div>
                    <button type="submit" class="w-full mt-2 bg-green-600 text-white p-3 rounded-lg font-bold hover:bg-green-500 transition-colors">Open Drawer</button>
                </form>
            </div>
            <!-- Close Drawer View -->
            <div id="drawer-close-view" class="hidden">
                <h2 class="text-2xl font-bold text-center text-white mb-4">Close Cash Drawer</h2>
                <form id="drawer-close-form" class="space-y-4">
                     <p class="text-sm text-slate-400 text-center">Count the total cash and enter it below.</p>
                    <div>
                        <label for="closing-amount" class="form-label">Final Cash Amount ($)</label>
                        <input id="closing-amount" type="number" step="0.01" required class="form-input" placeholder="e.g., 875.50">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" id="drawer-cancel-close-btn" class="w-full bg-slate-600 p-3 rounded-lg font-bold hover:bg-slate-500">Cancel</button>
                        <button type="submit" class="w-full bg-red-600 text-white p-3 rounded-lg font-bold hover:bg-red-500">Close Drawer</button>
                    </div>
                </form>
            </div>
             <!-- Summary View -->
            <div id="drawer-summary-view" class="hidden text-center space-y-4">
                <h2 class="text-2xl font-bold text-white">Drawer Closed</h2>
                <div id="drawer-summary-content" class="text-left font-mono bg-slate-900 p-4 rounded-lg space-y-2 text-sm"></div>
                <button id="drawer-summary-ok-btn" class="w-full bg-indigo-600 text-white p-3 rounded-lg font-bold hover:bg-indigo-500">OK</button>
            </div>
        </div>
    </div>
    
    <!-- Main App Wrapper (Initially hidden) -->
    <div id="main-app" class="hidden">
        <!-- Slide-out Menu -->
        <nav id="side-menu" class="fixed top-0 left-0 h-full w-64 bg-slate-900/80 backdrop-blur-lg border-r border-slate-700 z-50">
            <div class="p-4">
                <!-- User Profile Section -->
                <div id="user-profile-section" class="mb-6 p-3 bg-slate-800/50 rounded-lg border border-slate-600">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div id="user-display-name" class="text-white font-semibold text-sm truncate">Loading...</div>
                            <div id="user-email" class="text-slate-400 text-xs truncate">Loading...</div>
                        </div>
                    </div>
                </div>
                <ul class="space-y-2">
                    <li><button id="menu-button-pos" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                        <span>Point of Sale</span>
                    </button></li>
                    <li><button id="menu-button-orders" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span>Orders</span>
                    </button></li>
                    <!-- NEW: Reports Button -->
                    <li><button id="menu-button-reports" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span>Reports</span>
                    </button></li>
                    <li><button id="menu-button-sessions" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Sessions</span>
                    </button></li>
                    <li><button id="menu-button-stock" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Stock Manager</span>
                    </button></li>
                    <li><button id="menu-button-held-carts" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"></path></svg>
                        <span>Held Carts</span>
                    </button></li>
                    <li><button id="menu-button-settings" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Settings</span>
                    </button></li>
                </ul>
            </div>
        </nav>
        <div id="menu-overlay" class="hidden fixed inset-0 bg-black/50 z-40"></div>

        <div class="flex h-screen w-full">
            <!-- POS Page -->
            <section id="pos-page" class="page-content w-full flex">
                <div class="flex-grow flex flex-col p-3 gap-3">
                    <header class="flex items-center gap-3 p-2 bg-slate-800/80 backdrop-blur-sm border border-slate-700 rounded-xl shadow-lg flex-shrink-0">
                        <button class="menu-toggle p-2 rounded-lg hover:bg-slate-700 transition-colors flex-shrink-0"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                        <div class="relative flex-grow min-w-[150px]"><div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg></div><input type="text" id="search-input" placeholder="Search..." class="w-full pl-10 p-2 rounded-lg bg-slate-700 border border-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"></div>
                        <button id="search-toggle-btn" aria-label="Toggle search type" class="p-2 rounded-lg bg-slate-700 border border-slate-600 hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-shrink-0"><svg id="search-icon-name" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg><svg id="search-icon-sku" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M10 3v14"/><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M14 3v14"/></svg></button>
                        <select id="category-filter" class="p-2 rounded-lg bg-slate-700 border border-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-shrink-0"><option value="all">All Categories</option></select>
                        <select id="tag-filter" class="p-2 rounded-lg bg-slate-700 border border-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-shrink-0"><option value="all">All Tags</option></select>
                        <div id="stock-filter" class="segmented-control flex p-1 rounded-lg bg-slate-700 border border-slate-600 flex-shrink-0">
                            <button data-value="all" data-state="active" class="px-3 py-1 text-sm rounded-md transition-colors">All</button>
                            <button data-value="instock" data-state="inactive" class="px-3 py-1 text-sm rounded-md transition-colors">In Stock</button>
                            <button data-value="outofstock" data-state="inactive" class="px-3 py-1 text-sm rounded-md transition-colors">Out of Stock</button>
                            <button data-value="private" data-state="inactive" class="px-3 py-1 text-sm rounded-md transition-colors">Private</button>
                        </div>
                        <button id="refresh-pos-btn" class="ml-2 p-2 rounded-lg bg-slate-700 border border-slate-600 hover:bg-slate-600 transition-colors flex-shrink-0 flex items-center" title="Refresh POS Data">
                            <i class="fa fa-refresh"></i>
                        </button>
                        <div class="ml-auto flex items-center gap-4 flex-shrink-0">
                             <div class="flex items-center gap-2">
                                <div id="drawer-status-indicator" class="w-3 h-3 bg-gray-500 rounded-full" title="Drawer Closed"></div>
                                <span id="header-user-display-name" class="text-sm font-medium"></span>
                            </div>
                            <button id="close-drawer-btn" class="text-xs font-bold px-3 py-1.5 rounded-md transition-colors"></button>
                            <button id="logout-btn" class="text-xs font-bold bg-slate-700 px-3 py-1.5 rounded-md hover:bg-slate-600 transition-colors">Logout</button>
                        </div>
                    </header>
                    <div class="flex-grow flex gap-3 overflow-hidden">
                        <main id="product-list" class="flex-grow p-1 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 overflow-y-auto"></main>
                        <aside class="w-80 flex-shrink-0 bg-slate-800/50 p-2 flex flex-col border-l border-slate-700 rounded-xl shadow-lg">
                            <div id="cart-items" class="flex-grow overflow-y-auto space-y-1 pr-1"></div>
                            <div class="flex flex-col gap-0 pb-1 mb-1 border-b border-slate-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-300">Total</span>
                                    <span id="cart-summary" class="text-xs text-slate-400"></span>
                                    <span id="cart-total" class="font-bold text-base">$0.00</span>
                                </div>
                                <div id="cart-discount-row"></div>
                                <div id="cart-fee-row"></div>
                            </div>
                            <div class="flex gap-1 mb-2">
                                <button id="add-discount-btn" class="flex-1 flex items-center justify-center gap-1 bg-slate-700 text-white px-2 py-1 rounded-md text-xs hover:bg-slate-600 transition-colors" title="Add Discount">
                                    <i class="fa-solid fa-percent"></i> Discount
                                </button>
                                <button id="add-fee-btn" class="flex-1 flex items-center justify-center gap-1 bg-slate-700 text-white px-2 py-1 rounded-md text-xs hover:bg-slate-600 transition-colors" title="Add Fee">
                                    <i class="fa-solid fa-plus"></i> Fee
                                </button>
                            </div>
                            <div class="border-t border-slate-700 pt-2 mt-2 flex-shrink-0">
                                <div class="flex items-center gap-1 mt-auto">
                                    <button id="hold-cart-btn" class="flex-1 px-2 py-2 bg-amber-500 hover:bg-amber-400 text-xs font-semibold rounded transition-colors">Hold Cart</button>
                                    <button id="checkout-btn" class="flex-1 px-2 py-2 bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold rounded transition-colors">Checkout</button>
                                </div>
                                <button id="clear-cart-btn" class="w-full text-slate-400 p-1 text-xs hover:bg-slate-700 rounded-md transition-colors">Clear Cart</button>
                            </div>
                        </aside>
                    </div>
                </div>
            </section>

            <!-- Fee/Discount Modal -->
            <div id="fee-discount-modal" class="app-overlay hidden">
                <div class="bg-slate-800 border border-slate-700 p-3 rounded-xl shadow-2xl w-full max-w-xs transform transition-all">
                    <h2 id="fee-discount-modal-title" class="text-xl font-bold text-center text-white mb-2">Add Fee</h2>
                    <div class="space-y-2">
                        <div>
                            <label for="fee-discount-title" class="form-label">Title (Optional)</label>
                            <input id="fee-discount-title" type="text" class="form-input text-sm py-2 px-2" placeholder="e.g., Delivery Fee">
                        </div>
                        <div>
                            <label for="fee-discount-amount" class="form-label">Amount</label>
                            <input id="fee-discount-amount" type="text" inputmode="none" readonly class="form-input text-right text-lg font-bold py-2 px-2">
                        </div>
                        <div id="fee-discount-type-selector" class="segmented-control flex p-1 rounded-lg bg-slate-700 border border-slate-600">
                            <button data-value="flat" data-state="active" class="px-2 py-1 text-xs rounded-md transition-colors">$ Flat</button>
                            <button data-value="percentage" data-state="inactive" class="px-2 py-1 text-xs rounded-md transition-colors">% Percentage</button>
                        </div>
                        <div class="grid grid-cols-3 gap-1 mt-2">
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">7</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">8</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">9</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">4</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">5</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">6</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">1</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">2</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">3</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">.</button>
                            <button class="num-pad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">0</button>
                            <button id="num-pad-backspace" class="bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">←</button>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button id="fee-discount-cancel-btn" class="w-full bg-slate-600 p-2 rounded-lg font-bold hover:bg-slate-500">Cancel</button>
                            <button id="fee-discount-apply-btn" class="w-full bg-indigo-600 text-white p-2 rounded-lg font-bold hover:bg-indigo-500 disabled:bg-slate-500 disabled:cursor-not-allowed">Apply</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Page -->
            <section id="orders-page" class="page-content w-full hidden flex flex-col p-3 gap-3">
                 <header class="flex items-center gap-4 p-2 bg-slate-800/80 backdrop-blur-sm border border-slate-700 rounded-xl shadow-lg flex-shrink-0">
                    <button class="menu-toggle p-2 rounded-lg hover:bg-slate-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h1 class="text-xl font-bold mr-auto">Order History</h1>
                    <div class="relative flex-grow min-w-[150px] max-w-[250px]">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input type="text" id="order-id-search" placeholder="Filter by Order #" class="w-full pl-10 p-2 rounded-lg bg-slate-700 border border-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
                    </div>
                    <div class="flex items-center gap-4">
                        <select id="order-date-filter" class="p-2 rounded-lg bg-slate-700 border border-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                        </select>
                        <select id="order-source-filter" class="p-2 rounded-lg bg-slate-700 border border-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all">All Orders</option>
                            <option value="pos">POS Orders</option>
                            <option value="online">Online Orders</option>
                        </select>
                        <select id="order-status-filter" class="p-2 rounded-lg bg-slate-700 border border-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all">All Statuses</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="on-hold">On Hold</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="refunded">Refunded</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                 </header>
                 <main class="flex-grow flex flex-col overflow-y-auto">
                    <div class="grid grid-cols-12 gap-4 sticky top-0 bg-slate-900 py-2 px-4 text-xs font-bold text-slate-400 uppercase border-b border-slate-700">
                        <div class="col-span-2">Order #</div><div class="col-span-2">Date</div><div class="col-span-1">Source</div><div class="col-span-2">Status</div><div class="col-span-1 text-center">Items</div><div class="col-span-2 text-right">Total</div><div class="col-span-2 text-right">Actions</div>
                    </div>
                    <div id="order-list" class="flex-grow p-2 space-y-2"></div>
                 </main>
            </section>
            
            <!-- NEW: Reports Page -->
            <section id="reports-page" class="page-content w-full hidden flex flex-col p-3 gap-3">
                 <header class="flex items-center gap-4 p-2 bg-slate-800/80 backdrop-blur-sm border border-slate-700 rounded-xl shadow-lg flex-shrink-0">
                    <button class="menu-toggle p-2 rounded-lg hover:bg-slate-700 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                    <h1 class="text-xl font-bold mr-auto">Sales Reports</h1>
                    <button id="export-pdf-btn" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-500 transition-colors font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </button>
                 </header>
                 <main id="reports-content-area" class="flex-grow overflow-y-auto p-4 space-y-6">
                 </main>
            </section>

            <!-- Sessions Page -->
            <section id="sessions-page" class="page-content w-full hidden flex flex-col p-3 gap-3">
                 <header class="flex items-center gap-4 p-2 bg-slate-800/80 backdrop-blur-sm border border-slate-700 rounded-xl shadow-lg flex-shrink-0">
                    <button class="menu-toggle p-2 rounded-lg hover:bg-slate-700 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                    <h1 class="text-xl font-bold mr-auto">Session History</h1>
                 </header>
                 <main class="flex-grow flex flex-col overflow-y-auto">
                    <div class="grid grid-cols-12 gap-4 sticky top-0 bg-slate-900 py-2 px-4 text-xs font-bold text-slate-400 uppercase border-b border-slate-700">
                        <div class="col-span-2">User</div>
                        <div class="col-span-3">Time Opened</div>
                        <div class="col-span-3">Time Closed</div>
                        <div class="col-span-1 text-right">Opening</div>
                        <div class="col-span-1 text-right">Closing</div>
                        <div class="col-span-2 text-right">Difference</div>
                    </div>
                    <div id="session-list" class="flex-grow p-2 space-y-2"></div>
                 </main>
            </section>

            <!-- Stock Manager Page -->
            <section id="stock-page" class="page-content w-full hidden flex flex-col p-3 gap-3">
                <header class="flex items-center gap-4 p-2 bg-slate-800/80 backdrop-blur-sm border border-slate-700 rounded-xl shadow-lg flex-shrink-0">
                    <button class="menu-toggle p-2 rounded-lg hover:bg-slate-700 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                    <h1 class="text-xl font-bold">Stock Manager</h1>
                    <div class="relative flex-grow min-w-[150px] max-w-[250px]"><div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg></div><input type="text" id="stock-list-filter" placeholder="Filter list by name or SKU..." class="w-full pl-10 p-2 rounded-lg bg-slate-700 border border-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"></div>
                    <select id="stock-manager-category-filter" class="p-2 rounded-lg bg-slate-700 border border-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-shrink-0"><option value="all">All Categories</option></select>
                    <select id="stock-manager-tag-filter" class="p-2 rounded-lg bg-slate-700 border border-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-shrink-0"><option value="all">All Tags</option></select>
                    <div id="stock-manager-stock-filter" class="segmented-control flex p-1 rounded-lg bg-slate-700 border border-slate-600 flex-shrink-0">
                        <button data-value="all" data-state="active" class="px-3 py-1 text-sm rounded-md transition-colors">All</button>
                        <button data-value="instock" data-state="inactive" class="px-3 py-1 text-sm rounded-md transition-colors">In Stock</button>
                        <button data-value="outofstock" data-state="inactive" class="px-3 py-1 text-sm rounded-md transition-colors">Out of Stock</button>
                        <button data-value="private" data-state="inactive" class="px-3 py-1 text-sm rounded-md transition-colors">Private</button>
                    </div>
                </header>
                <main class="flex-grow flex flex-col overflow-y-auto">
                    <div class="grid grid-cols-12 gap-4 sticky top-0 bg-slate-900 py-2 px-4 text-xs font-bold text-slate-400 uppercase border-b border-slate-700">
                        <div class="col-span-1">Image</div>
                        <div class="col-span-3">Name</div>
                        <div class="col-span-2">SKU</div>
                        <div class="col-span-1">Type</div>
                        <div class="col-span-2 text-right">Price</div>
                        <div class="col-span-2 text-right">Stock</div>
                        <div class="col-span-1 text-center">Actions</div>
                    </div>
                    <div id="stock-list" class="flex-grow p-2 space-y-2"></div>
                </main>
            </section>
            
            <!-- Settings Page -->
            <section id="settings-page" class="page-content w-full hidden flex flex-col p-3 gap-3">
                 <header class="flex items-center gap-4 p-2 bg-slate-800/80 backdrop-blur-sm border border-slate-700 rounded-xl shadow-lg flex-shrink-0">
                    <button class="menu-toggle p-2 rounded-lg hover:bg-slate-700 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                    <h1 class="text-xl font-bold mr-auto">Receipt Settings</h1>
                 </header>
                 <main class="flex-grow overflow-y-auto p-4 bg-slate-800/90 rounded-xl border border-slate-700">
                    <form id="settings-form" class="max-w-2xl mx-auto space-y-6">
                        <div><label for="setting-name" class="form-label">Store Name</label><input type="text" id="setting-name" class="form-input"></div>
                        <div><label for="setting-logo-url" class="form-label">Logo URL</label><input type="url" id="setting-logo-url" class="form-input" placeholder="https://example.com/logo.png"></div>
                        <div><label for="setting-email" class="form-label">Contact Email</label><input type="email" id="setting-email" class="form-input"></div>
                        <div><label for="setting-phone" class="form-label">Contact Phone</label><input type="text" id="setting-phone" class="form-input"></div>
                        <div><label for="setting-address" class="form-label">Store Address</label><input type="text" id="setting-address" class="form-input"></div>
                        <div><label for="setting-footer1" class="form-label">Footer Message Line 1</label><input id="setting-footer1" class="form-input" rows="2"></input></div>
                        <div><label for="setting-footer2" class="form-label">Footer Message Line 2</label><input id="setting-footer2" class="form-input" rows="2"></input></div>
                        <div class="flex items-center pt-4"><button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-500 transition-colors disabled:bg-slate-500">Save Settings</button><span id="settings-status" class="ml-4 text-sm"></span></div>
                    </form>
                 </main>
            </section>

            <!-- Held Carts Page -->
            <section id="held-carts-page" class="page-content w-full hidden flex flex-col p-3 gap-3">
                <header class="flex items-center gap-4 p-2 bg-slate-800/80 backdrop-blur-sm border border-slate-700 rounded-xl shadow-lg flex-shrink-0">
                    <button class="menu-toggle p-2 rounded-lg hover:bg-slate-700 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                    <h1 class="text-xl font-bold mr-auto">Held Carts</h1>
                </header>
                <main class="flex-grow flex flex-col overflow-y-auto">
                    <div id="held-carts-list" class="flex flex-col gap-3 p-4"></div>
                 </main>
            </section>
        </div>
    </div>

    <!-- Modals -->
    <div id="variation-modal" class="app-overlay hidden">
        <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl w-full max-w-lg transform transition-all">
            <div class="flex gap-6">
                <div class="w-1/3 flex-shrink-0">
                    <img id="modal-image" src="" alt="Product Image" class="w-full aspect-square object-cover rounded-lg placeholder-bg">
                </div>
                <div class="w-2/3 flex flex-col">
                    <h2 id="modal-product-name" class="text-2xl font-bold">Select Options</h2>
                    <p id="modal-product-sku" class="text-sm font-mono text-slate-400"></p>
                    <div id="modal-options-container" class="space-y-4 flex-grow mt-4 overflow-y-auto pr-2 max-h-80"></div>
                </div>
            </div>
            <div class="mt-6 flex justify-between items-center">
                <span id="modal-stock-status" class="text-sm text-slate-400">Select options to see stock</span>
                <div class="flex gap-3">
                    <button id="modal-cancel-btn" class="px-5 py-2 bg-slate-600 rounded-lg hover:bg-slate-500">Close</button>
                    <button id="modal-add-to-cart-btn" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 disabled:bg-slate-500 disabled:cursor-not-allowed">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>
    <div id="receipt-modal" class="app-overlay hidden">
      <div class="bg-white text-black p-6 rounded-lg shadow-2xl w-full max-w-sm transform transition-all">
        <div id="receipt-content" class="text-sm font-mono space-y-2" style="max-height: 70vh; overflow-y: auto;"></div>
        <div class="mt-6 flex gap-3">
          <button id="print-receipt-btn" class="w-full bg-blue-600 text-white p-3 rounded-lg font-bold hover:bg-blue-700 transition-colors">Print</button>
          <button id="close-receipt-btn" class="w-full bg-slate-200 text-slate-800 p-3 rounded-lg font-bold hover:bg-slate-300 transition-colors">Close</button>
        </div>
      </div>
    </div>
    <div id="stock-edit-modal" class="app-overlay hidden">
        <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl w-full max-w-4xl">
            <h2 id="stock-edit-title" class="text-2xl font-bold mb-4">Edit Stock</h2>
            <div id="stock-edit-view">
                <div class="max-h-[60vh] overflow-y-auto pr-2 space-y-2 flex flex-col flex-grow" id="stock-edit-variations-list">
                    <!-- Variation edit rows will be injected here (or skeleton loader) -->
                </div>
                 <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-slate-700">
                    <button type="button" id="stock-edit-cancel-btn" class="px-5 py-2 bg-slate-600 rounded-lg hover:bg-slate-500">Cancel</button>
                    <button type="button" id="stock-edit-save-btn" class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-500">Save All Changes</button>
                </div>
                 <p id="stock-edit-status" class="text-sm text-right h-5 mt-2"></p>
            </div>
        </div>
    </div>
    <!-- Return/Exchange Modal -->
<div id="return-modal" class="app-overlay hidden">
    <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl w-full max-w-2xl transform transition-all">
        <h2 class="text-2xl font-bold mb-1">Return or Exchange Items</h2>
        <p class="text-sm text-slate-400 mb-4">Select items and quantities to add to the cart for return.</p>
        <div id="return-items-list" class="max-h-[60vh] overflow-y-auto pr-2 space-y-2">
            <!-- Items from the original order will be injected here -->
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button id="return-modal-cancel-btn" class="px-5 py-2 bg-slate-600 rounded-lg hover:bg-slate-500">Cancel</button>
            <button id="return-modal-add-to-cart-btn" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 disabled:bg-slate-500 disabled:cursor-not-allowed">Add Selected to Cart</button>
        </div>
    </div>
</div>    
    <!-- Held Cart Details Modal -->
    <div id="held-cart-details-modal" class="app-overlay hidden">
      <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl w-full max-w-2xl transform transition-all">
        <h2 class="text-2xl font-bold mb-2">Held Cart Details</h2>
        <div id="held-cart-details-content" class="mb-6"></div>
        <div class="flex justify-end">
          <button id="held-cart-details-close" class="px-6 py-2 bg-slate-600 rounded-lg hover:bg-slate-500 text-white">Close</button>
        </div>
      </div>
    </div>
    <!-- Split Payment Modal -->
<div id="split-payment-modal" class="app-overlay hidden">
  <div class="bg-slate-800 border border-slate-700 p-6 rounded-xl shadow-2xl w-full max-w-md transform transition-all">
    <h2 class="text-2xl font-bold mb-4">Checkout</h2>
    <div id="split-payment-methods-list" class="space-y-2 mb-4"></div>
    <div id="split-payment-numpad" class="grid grid-cols-3 gap-1 mb-4">
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">7</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">8</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">9</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">4</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">5</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">6</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">1</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">2</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">3</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">.</button>
      <button class="split-numpad-btn bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">0</button>
      <button id="split-numpad-backspace" class="bg-slate-700 p-2 rounded-lg text-base font-bold hover:bg-slate-600 transition-colors">←</button>
    </div>
    <div class="flex justify-between items-center mb-4">
      <span class="font-bold text-slate-300">Total:</span>
      <span id="split-payment-total" class="font-mono text-slate-100 text-lg">$0.00</span>
    </div>
    <div class="flex justify-end gap-2">
      <button id="split-payment-cancel" class="px-4 py-2 bg-slate-600 rounded-lg hover:bg-slate-500 text-white">Cancel</button>
      <button id="split-payment-apply" class="px-4 py-2 bg-indigo-600 rounded-lg hover:bg-indigo-500 text-white font-bold">Apply</button>
    </div>
  </div>
</div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <div id="jpos-toast" style="display:none"></div>
    </body>
</html>