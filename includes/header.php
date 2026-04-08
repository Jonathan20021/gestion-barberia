<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Kyros Barber Cloud'; ?></title>

    <!-- ── Theme init: run before paint to avoid flash ── -->
    <script>
        (function() {
            var saved = localStorage.getItem('kyros-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            // Default: dark (brand identity)
            if (saved === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }

            if (localStorage.getItem('kyros-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('kyros-sidebar-collapsed');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* ════════════════════════════════════════════════════════════
           DESIGN TOKENS — light & dark
        ════════════════════════════════════════════════════════════ */
        :root {
            /* Surfaces */
            --c-bg:          #f4f4f1;
            --c-card:        #ffffff;
            --c-elevated:    #f0f0ec;
            --c-input:       #ffffff;

            /* Borders */
            --c-border:      #e5e5e0;
            --c-border-2:    #d0d0cb;

            /* Text */
            --c-text-1:      #0a0a0a;
            --c-text-2:      #3f3f46;
            --c-text-3:      #71717a;
            --c-text-4:      #a1a1aa;

            /* Sidebar */
            --c-sidebar:     #ffffff;
            --c-sidebar-bd:  #e5e5e0;

            /* Topbar */
            --c-topbar:      #ffffff;
            --c-topbar-bd:   #e5e5e0;

            /* Nav hover */
            --c-nav-hover:   rgba(0,0,0,.05);

            /* Gold */
            --c-gold:        #c9901a;
            --c-gold-2:      #b07a14;
            --c-gold-lt:     #e8b84b;
            --c-gold-bg:     rgba(201,144,26,.08);
            --c-gold-bd:     rgba(201,144,26,.22);

            /* Status */
            --c-green-bg:    rgba(22,163,74,.09);
            --c-green-text:  #15803d;
            --c-red-bg:      rgba(220,38,38,.08);
            --c-red-text:    #dc2626;
            --c-yellow-bg:   rgba(202,138,4,.09);
            --c-yellow-text: #a16207;
            --c-blue-bg:     rgba(37,99,235,.08);
            --c-blue-text:   #1d4ed8;
            --c-purple-bg:   rgba(124,58,237,.08);
            --c-purple-text: #6d28d9;

            /* Shadows */
            --shadow-xs:  0 1px 2px rgba(0,0,0,.06);
            --shadow-sm:  0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.05);
            --shadow-md:  0 4px 8px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.05);
            --shadow-lg:  0 10px 20px rgba(0,0,0,.08), 0 4px 8px rgba(0,0,0,.04);
        }

        html.dark {
            --c-bg:          #0a0a0a;
            --c-card:        #141414;
            --c-elevated:    #111111;
            --c-input:       #111111;

            --c-border:      #1c1c1c;
            --c-border-2:    #252525;

            --c-text-1:      #f0f0eb;
            --c-text-2:      #a1a1aa;
            --c-text-3:      #71717a;
            --c-text-4:      #3f3f46;

            --c-sidebar:     #0d0d0d;
            --c-sidebar-bd:  #1c1c1c;

            --c-topbar:      #0d0d0d;
            --c-topbar-bd:   #1c1c1c;

            --c-nav-hover:   rgba(255,255,255,.05);

            --c-green-bg:    rgba(52,211,153,.09);
            --c-green-text:  #34d399;
            --c-red-bg:      rgba(248,113,113,.09);
            --c-red-text:    #f87171;
            --c-yellow-bg:   rgba(251,191,36,.09);
            --c-yellow-text: #fbbf24;
            --c-blue-bg:     rgba(96,165,250,.09);
            --c-blue-text:   #60a5fa;
            --c-purple-bg:   rgba(167,139,250,.09);
            --c-purple-text: #a78bfa;

            --shadow-xs:  0 1px 2px rgba(0,0,0,.4);
            --shadow-sm:  0 1px 4px rgba(0,0,0,.5);
            --shadow-md:  0 4px 12px rgba(0,0,0,.55);
            --shadow-lg:  0 8px 28px rgba(0,0,0,.65);
        }

        /* ════════════════════════════════════════════════════════════
           BASE
        ════════════════════════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-font-smoothing: antialiased; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--c-bg);
            color: var(--c-text-1);
            transition: background .2s, color .2s;
        }
        h1,h2,h3,h4,h5,h6 { font-family: 'Sora', sans-serif; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar        { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track  { background: transparent; }
        ::-webkit-scrollbar-thumb  { background: var(--c-border-2); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--c-text-4); }

        /* ── Alpine ── */
        [x-cloak] { display: none !important; }

        /* ════════════════════════════════════════════════════════════
           SIDEBAR NAV LINKS (reusable component)
        ════════════════════════════════════════════════════════════ */
        .kyros-nav-link {
            display: flex;
            align-items: center;
            padding: 9px 12px;
            border-radius: 9px;
            text-decoration: none;
            color: var(--c-text-3);
            gap: 11px;
            font-size: .875rem;
            margin-bottom: 2px;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s, border-color .15s;
        }
        .kyros-nav-link:hover {
            background: var(--c-nav-hover);
            color: var(--c-text-2);
        }
        .kyros-nav-link.active {
            background: var(--c-gold-bg);
            border-left-color: var(--c-gold);
            color: var(--c-gold-lt);
            font-weight: 600;
        }
        html:not(.dark) .kyros-nav-link.active {
            color: var(--c-gold-2);
        }
        .kyros-nav-link svg { flex-shrink: 0; }

        .kyros-sidebar {
            width: 16rem;
            transition: width .2s ease, transform .3s ease;
        }

        .kyros-sidebar-toggle {
            width: 30px;
            height: 30px;
            border: 1px solid var(--c-border);
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            background: var(--c-elevated);
            color: var(--c-text-3);
            cursor: pointer;
            transition: all .15s;
            flex-shrink: 0;
        }

        .kyros-sidebar-toggle:hover {
            background: var(--c-border);
            color: var(--c-text-2);
        }

        .kyros-sidebar-toggle .icon-expand { display: none; }

        @media (min-width: 1024px) {
            .kyros-sidebar-collapsed .kyros-sidebar {
                width: 5rem;
            }

            .kyros-sidebar-collapsed .kyros-sidebar .kyros-brand-text,
            .kyros-sidebar-collapsed .kyros-sidebar .kyros-section-label,
            .kyros-sidebar-collapsed .kyros-sidebar .kyros-nav-label,
            .kyros-sidebar-collapsed .kyros-sidebar .kyros-user-meta,
            .kyros-sidebar-collapsed .kyros-sidebar .label-to-light,
            .kyros-sidebar-collapsed .kyros-sidebar .label-to-dark {
                display: none !important;
            }

            .kyros-sidebar-collapsed .kyros-sidebar .kyros-nav-link {
                justify-content: center;
                gap: 0;
                padding-left: 0;
                padding-right: 0;
                border-left-color: transparent;
            }

            .kyros-sidebar-collapsed .kyros-sidebar .kyros-theme-btn {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }

            .kyros-sidebar-collapsed .kyros-sidebar .kyros-theme-btn .icon-to-light,
            .kyros-sidebar-collapsed .kyros-sidebar .kyros-theme-btn .icon-to-dark {
                justify-content: center;
            }

            .kyros-sidebar-collapsed .kyros-sidebar .kyros-user-row {
                justify-content: center;
            }

            .kyros-sidebar-collapsed .kyros-sidebar .kyros-logout-link {
                display: none !important;
            }

            .kyros-sidebar-collapsed .kyros-sidebar .kyros-sidebar-footer {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }

            .kyros-sidebar-collapsed .lg\:pl-64 {
                padding-left: 5rem !important;
            }

            .kyros-sidebar-collapsed .kyros-sidebar-toggle .icon-collapse { display: none; }
            .kyros-sidebar-collapsed .kyros-sidebar-toggle .icon-expand { display: block; }
        }

        /* ════════════════════════════════════════════════════════════
           THEME TOGGLE BUTTON
        ════════════════════════════════════════════════════════════ */
        .kyros-theme-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid var(--c-border);
            background: var(--c-elevated);
            color: var(--c-text-3);
            font-size: .8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            font-family: 'Inter', sans-serif;
        }
        .kyros-theme-btn:hover {
            background: var(--c-border);
            color: var(--c-text-2);
        }
        /* Show correct icon based on current theme */
        .kyros-theme-btn .icon-to-light { display: none; }
        .kyros-theme-btn .icon-to-dark  { display: flex; }
        html.dark .kyros-theme-btn .icon-to-light { display: flex; }
        html.dark .kyros-theme-btn .icon-to-dark  { display: none; }
        .label-to-light { display: none; }
        .label-to-dark  { display: inline; }
        html.dark .label-to-light { display: inline; }
        html.dark .label-to-dark  { display: none; }

        /* ════════════════════════════════════════════════════════════
           GOLD BUTTON
        ════════════════════════════════════════════════════════════ */
        @keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

        .btn-gold {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, #c9901a 0%, #e8b84b 50%, #c9901a 100%);
            color: #0a0a0a; font-weight: 700; border: none; cursor: pointer; text-decoration: none;
            box-shadow: 0 4px 16px rgba(201,144,26,.3);
            transition: box-shadow .2s, transform .2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-gold::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent);
        }
        .btn-gold:hover::after { animation: shimmer .5s ease forwards; }
        .btn-gold:hover { box-shadow: 0 6px 24px rgba(201,144,26,.5); transform: translateY(-1px); }

        /* ════════════════════════════════════════════════════════════
           TAILWIND CLASS OVERRIDES → design tokens
        ════════════════════════════════════════════════════════════ */

        /* — Backgrounds — */
        body.bg-gray-50,
        .min-h-screen      { background-color: var(--c-bg) !important; }
        .bg-white           { background-color: var(--c-card) !important; }
        .bg-gray-50         { background-color: var(--c-bg) !important; }
        .bg-gray-100        { background-color: var(--c-elevated) !important; }
        .bg-gray-200        { background-color: var(--c-border) !important; }
        .bg-gray-700,
        .bg-gray-800,
        .bg-gray-900        { background-color: var(--c-sidebar) !important; }

        /* — Hover backgrounds — */
        .hover\:bg-white:hover        { background-color: var(--c-card) !important; }
        .hover\:bg-gray-50:hover      { background-color: var(--c-elevated) !important; }
        .hover\:bg-gray-100:hover     { background-color: var(--c-elevated) !important; }
        .hover\:bg-gray-200:hover     { background-color: var(--c-border) !important; }
        .hover\:bg-gray-700:hover     { background-color: var(--c-border-2) !important; }

        /* — Text — */
        .text-gray-900  { color: var(--c-text-1) !important; }
        .text-gray-800  { color: var(--c-text-1) !important; }
        .text-gray-700  { color: var(--c-text-2) !important; }
        .text-gray-600  { color: var(--c-text-2) !important; }
        .text-gray-500  { color: var(--c-text-3) !important; }
        .text-gray-400  { color: var(--c-text-4) !important; }
        .text-gray-300  { color: var(--c-text-4) !important; }
        .text-white     { color: var(--c-text-1) !important; }

        /* — Borders — */
        .border-gray-100 { border-color: var(--c-border) !important; }
        .border-gray-200 { border-color: var(--c-border) !important; }
        .border-gray-300 { border-color: var(--c-border-2) !important; }
        .border-gray-600,
        .border-gray-700 { border-color: var(--c-border) !important; }
        .divide-gray-100 > * + *,
        .divide-gray-200 > * + * { border-color: var(--c-border) !important; }
        .divide-y > * + * { border-color: var(--c-border) !important; }

        /* — Shadows — */
        .shadow     { box-shadow: var(--shadow-xs) !important; }
        .shadow-sm  { box-shadow: var(--shadow-sm) !important; }
        .shadow-md  { box-shadow: var(--shadow-md) !important; }
        .shadow-lg  { box-shadow: var(--shadow-lg) !important; }
        .shadow-xl  { box-shadow: var(--shadow-lg) !important; }

        /* — Primary (indigo → gold) — */
        .bg-indigo-600,
        .bg-indigo-700 { background: var(--c-gold) !important; color: #0a0a0a !important; }
        .hover\:bg-indigo-700:hover { background: var(--c-gold-2) !important; }
        .from-indigo-600 { --tw-gradient-from: var(--c-gold) !important; }
        .to-purple-600   { --tw-gradient-to:   var(--c-gold-lt) !important; }
        .from-indigo-700 { --tw-gradient-from: var(--c-gold-2) !important; }
        .to-purple-700   { --tw-gradient-to:   var(--c-gold) !important; }
        .text-indigo-600 { color: var(--c-gold) !important; }
        .text-indigo-400 { color: var(--c-gold-lt) !important; }
        .hover\:text-indigo-500:hover { color: var(--c-gold-2) !important; }
        .border-indigo-500 { border-color: var(--c-gold) !important; }
        .bg-indigo-50    { background: var(--c-gold-bg) !important; }
        .text-indigo-100 { color: var(--c-gold-lt) !important; }
        .focus\:ring-indigo-500:focus  { --tw-ring-color: rgba(201,144,26,.25) !important; }
        .focus\:border-indigo-500:focus { border-color: var(--c-gold) !important; }

        /* — Status badges — */
        .bg-green-100   { background: var(--c-green-bg)   !important; }
        .text-green-800,
        .text-green-700,
        .text-green-600 { color: var(--c-green-text) !important; }
        .bg-green-50    { background: var(--c-green-bg)   !important; }

        .bg-red-100     { background: var(--c-red-bg)    !important; }
        .text-red-800,
        .text-red-700,
        .text-red-600,
        .text-red-500   { color: var(--c-red-text)   !important; }
        .bg-red-50      { background: var(--c-red-bg)    !important; }
        .border-red-500 { border-color: rgba(220,38,38,.35) !important; }
        html.dark .border-red-500 { border-color: rgba(248,113,113,.3) !important; }

        .bg-yellow-100  { background: var(--c-yellow-bg) !important; }
        .text-yellow-800,
        .text-yellow-700,
        .text-yellow-600 { color: var(--c-yellow-text) !important; }
        .bg-yellow-50   { background: var(--c-yellow-bg) !important; }

        .bg-blue-100    { background: var(--c-blue-bg)   !important; }
        .text-blue-800,
        .text-blue-700,
        .text-blue-600  { color: var(--c-blue-text)  !important; }
        .bg-blue-50     { background: var(--c-blue-bg)   !important; }

        .bg-purple-100  { background: var(--c-purple-bg) !important; }
        .text-purple-800,
        .text-purple-700,
        .text-purple-600 { color: var(--c-purple-text) !important; }

        /* — Inputs — */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="tel"],
        input[type="date"],
        input[type="time"],
        input[type="search"],
        input[type="url"],
        select,
        textarea {
            background-color: var(--c-input)   !important;
            border-color:     var(--c-border-2) !important;
            color:            var(--c-text-1)   !important;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        input::placeholder, textarea::placeholder { color: var(--c-text-4) !important; }
        input:focus, select:focus, textarea:focus {
            border-color: var(--c-gold) !important;
            box-shadow: 0 0 0 3px rgba(201,144,26,.15) !important;
            outline: none !important;
        }

        /* — Tables — */
        thead, .thead-bg { background-color: var(--c-elevated) !important; }
        tbody tr { border-color: var(--c-border) !important; }
        tbody tr:hover { background-color: var(--c-elevated) !important; }

        /* — Topbar — */
        .sticky.top-0.bg-white { background: var(--c-topbar) !important; border-bottom-color: var(--c-topbar-bd) !important; }
    </style>
</head>
<body class="bg-gray-50">

<script>
    function toggleTheme() {
        var html = document.documentElement;
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.setItem('kyros-theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('kyros-theme', 'dark');
        }
    }

    function toggleKyrosSidebar() {
        var html = document.documentElement;
        var collapsed = html.classList.toggle('kyros-sidebar-collapsed');
        localStorage.setItem('kyros-sidebar-collapsed', collapsed ? '1' : '0');
    }
</script>
