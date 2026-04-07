<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Kyros Barber Cloud'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* ─── Base ─────────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body   { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #f5f5f0; }
        h1,h2,h3,h4,h5,h6 { font-family: 'Sora', sans-serif; }

        /* ─── Scrollbar ─────────────────────────────────────────────────────── */
        ::-webkit-scrollbar        { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track  { background: #111; }
        ::-webkit-scrollbar-thumb  { background: #2a2a2a; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #3a3a3a; }

        /* ─── Animations ────────────────────────────────────────────────────── */
        @keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

        /* ─── Gold button (reutilizable en páginas) ─────────────────────────── */
        .btn-gold {
            display:inline-flex; align-items:center; justify-content:center; gap:7px;
            position:relative; overflow:hidden;
            background:linear-gradient(135deg,#c9901a 0%,#e8b84b 50%,#c9901a 100%);
            color:#0a0a0a; font-weight:700; border:none; cursor:pointer; text-decoration:none;
            box-shadow:0 4px 16px rgba(201,144,26,.3); transition:box-shadow .2s,transform .2s;
            font-family:'Inter',sans-serif;
        }
        .btn-gold::after {
            content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent);
        }
        .btn-gold:hover::after { animation:shimmer .5s ease forwards; }
        .btn-gold:hover { box-shadow:0 6px 24px rgba(201,144,26,.5); transform:translateY(-1px); }

        /* ─── TAILWIND DARK REMAPPING ───────────────────────────────────────── */

        /* Backgrounds */
        .bg-white          { background-color: #141414 !important; }
        .bg-gray-50        { background-color: #111111 !important; }
        .bg-gray-100       { background-color: #161616 !important; }
        .bg-gray-200       { background-color: #1e1e1e !important; }
        .bg-gray-300       { background-color: #272727 !important; }
        .bg-gray-700       { background-color: #1e1e1e !important; }
        .bg-gray-800       { background-color: #161616 !important; }
        .bg-gray-900       { background-color: #0d0d0d !important; }

        /* Hover backgrounds */
        .hover\:bg-white:hover        { background-color: #1e1e1e !important; }
        .hover\:bg-gray-50:hover      { background-color: #161616 !important; }
        .hover\:bg-gray-100:hover     { background-color: #1a1a1a !important; }
        .hover\:bg-gray-200:hover     { background-color: #222222 !important; }
        .hover\:bg-gray-600:hover     { background-color: #222222 !important; }
        .hover\:bg-gray-700:hover     { background-color: #222222 !important; }

        /* Min-height page wrappers */
        .min-h-screen { background-color: #0a0a0a; }

        /* Text colors */
        .text-gray-900  { color: #f5f5f0 !important; }
        .text-gray-800  { color: #e4e4df !important; }
        .text-gray-700  { color: #c4c4bf !important; }
        .text-gray-600  { color: #a1a1aa !important; }
        .text-gray-500  { color: #71717a !important; }
        .text-gray-400  { color: #52525b !important; }
        .text-gray-300  { color: #3f3f46 !important; }
        .text-white     { color: #f5f5f0 !important; }

        /* Borders */
        .border-gray-100 { border-color: #1a1a1a !important; }
        .border-gray-200 { border-color: #222222 !important; }
        .border-gray-300 { border-color: #2a2a2a !important; }
        .border-gray-600 { border-color: #2a2a2a !important; }
        .border-gray-700 { border-color: #222222 !important; }

        /* Shadows → dark */
        .shadow     { box-shadow: 0 2px 8px rgba(0,0,0,.6) !important; }
        .shadow-sm  { box-shadow: 0 1px 4px rgba(0,0,0,.5) !important; }
        .shadow-md  { box-shadow: 0 4px 16px rgba(0,0,0,.6) !important; }
        .shadow-lg  { box-shadow: 0 8px 28px rgba(0,0,0,.7) !important; }
        .shadow-xl  { box-shadow: 0 12px 40px rgba(0,0,0,.8) !important; }

        /* Inputs */
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
            background-color: #111111 !important;
            border-color: #222222 !important;
            color: #f5f5f0 !important;
        }
        input::placeholder, textarea::placeholder { color: #3f3f46 !important; }
        input:focus, select:focus, textarea:focus {
            border-color: #c9901a !important;
            box-shadow: 0 0 0 3px rgba(201,144,26,.12) !important;
            outline: none !important;
        }
        .border-gray-300:focus { border-color: #c9901a !important; }

        /* Primary indigo → gold */
        .bg-indigo-600                    { background: linear-gradient(135deg,#c9901a,#e8b84b) !important; color: #0a0a0a !important; }
        .bg-indigo-700                    { background: #b07a14 !important; }
        .hover\:bg-indigo-700:hover       { background: #b07a14 !important; }
        .bg-gradient-to-r.from-indigo-600 { background: linear-gradient(135deg,#c9901a,#e8b84b) !important; color: #0a0a0a !important; }
        .from-indigo-600                  { --tw-gradient-from: #c9901a !important; }
        .to-purple-600                    { --tw-gradient-to: #e8b84b !important; }
        .from-indigo-700                  { --tw-gradient-from: #b07a14 !important; }
        .to-purple-700                    { --tw-gradient-to: #c9901a !important; }
        .text-indigo-600  { color: #c9901a !important; }
        .text-indigo-400  { color: #e8b84b !important; }
        .hover\:text-indigo-500:hover { color: #e8b84b !important; }
        .border-indigo-500 { border-color: #c9901a !important; }
        .ring-indigo-500   { --tw-ring-color: rgba(201,144,26,.35) !important; }
        .focus\:ring-indigo-500:focus { --tw-ring-color: rgba(201,144,26,.25) !important; }
        .focus\:border-indigo-500:focus { border-color: #c9901a !important; }
        .text-indigo-100 { color: #f0cc6a !important; }
        .bg-indigo-50    { background: rgba(201,144,26,.08) !important; }

        /* Status badges → dark variants */
        .bg-green-100  { background: rgba(52,211,153,.1)  !important; }
        .text-green-800 { color: #34d399 !important; }
        .text-green-600 { color: #34d399 !important; }
        .text-green-700 { color: #34d399 !important; }
        .bg-green-50   { background: rgba(52,211,153,.06) !important; }

        .bg-red-100    { background: rgba(248,113,113,.1)  !important; }
        .text-red-800  { color: #f87171 !important; }
        .text-red-600  { color: #f87171 !important; }
        .text-red-500  { color: #f87171 !important; }
        .bg-red-50     { background: rgba(248,113,113,.07) !important; }
        .border-red-500 { border-color: rgba(248,113,113,.4) !important; }
        .border-l-4.border-red-500 { border-left-color: #f87171 !important; }

        .bg-yellow-100  { background: rgba(251,191,36,.1)  !important; }
        .text-yellow-800 { color: #fbbf24 !important; }
        .text-yellow-600 { color: #fbbf24 !important; }
        .bg-yellow-50   { background: rgba(251,191,36,.07) !important; }

        .bg-blue-100   { background: rgba(96,165,250,.1)  !important; }
        .text-blue-800 { color: #60a5fa !important; }
        .text-blue-600 { color: #60a5fa !important; }
        .bg-blue-50    { background: rgba(96,165,250,.07) !important; }

        .bg-purple-100  { background: rgba(167,139,250,.1) !important; }
        .text-purple-800 { color: #a78bfa !important; }
        .text-purple-600 { color: #a78bfa !important; }

        /* Dividers */
        .divide-gray-100 > * + * { border-color: #1a1a1a !important; }
        .divide-gray-200 > * + * { border-color: #222222 !important; }

        /* Tables */
        .divide-y > * + * { border-color: #1e1e1e !important; }
        thead { background: #141414 !important; }
        tbody tr { border-color: #1e1e1e !important; }
        tbody tr:hover { background: #161616 !important; }

        /* Topbar override */
        .sticky.top-0.bg-white,
        div.sticky.bg-white {
            background: #0d0d0d !important;
            border-bottom-color: #1a1a1a !important;
        }

        /* Rounded corners keep Tailwind's */
        /* Opacity utilities keep Tailwind's */

        /* ─── Alpine x-cloak ────────────────────────────────────────────────── */
        [x-cloak] { display: none !important; }

        /* ─── Nav badge dot animation ───────────────────────────────────────── */
        @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.3} }
    </style>
</head>
<body class="bg-gray-50">
