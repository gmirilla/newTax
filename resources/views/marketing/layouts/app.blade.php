<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AccountTaxNG — Accounting & Tax Compliance for Nigerian SMEs')</title>
    <meta name="description" content="@yield('meta_description', 'Cloud-based accounting and tax compliance platform built for Nigerian SMEs. Automate VAT, WHT, PAYE and manage your books with ease.')">
    <meta name="keywords" content="accounting software Nigeria, tax compliance software Nigeria, VAT automation Nigeria, SME accounting platform, Nigerian payroll software, NRS compliance">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'AccountTaxNG — Accounting & Tax Compliance for Nigerian SMEs')">
    <meta property="og:description" content="@yield('meta_description', 'Cloud-based accounting and tax compliance platform built for Nigerian SMEs.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="AccountTaxNG">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Manrope:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        dark:       '#0A1A2F',
                        gold:       '#D4AF37',
                        'gold-lt':  '#E8C84A',
                        'gold-dk':  '#B8960F',
                        bg:         '#F5F7FA',
                    }
                },
                fontFamily: {
                    sans:    ['Inter', 'system-ui', 'sans-serif'],
                    display: ['Manrope', 'Inter', 'system-ui', 'sans-serif'],
                },
                animation: {
                    'fade-up': 'fadeUp 0.6s ease-out forwards',
                },
                keyframes: {
                    fadeUp: {
                        '0%':   { opacity: '0', transform: 'translateY(20px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                },
            }
        }
    }
    </script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .gradient-hero { background: linear-gradient(135deg, #9f9f9f 0%, #0d2444 60%, #0A1A2F 100%); }
        .gradient-gold  { background: linear-gradient(135deg, #D4AF37, #E8C84A); }
        .gradient-text  { background: linear-gradient(135deg, #D4AF37, #E8C84A); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .section-badge  { display: inline-flex; align-items: center; gap: 6px; background: rgba(212,175,55,0.12); border: 1px solid rgba(212,175,55,0.3); color: #D4AF37; font-size: 12px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; padding: 4px 14px; border-radius: 999px; }
        .card-hover     { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(10,26,47,0.12); }
        .btn-gold       { background: linear-gradient(135deg, #D4AF37, #C9A227); color: #0A1A2F; font-weight: 700; transition: all 0.2s; }
        .btn-gold:hover { background: linear-gradient(135deg, #E8C84A, #D4AF37); box-shadow: 0 8px 20px rgba(212,175,55,0.35); transform: translateY(-1px); }
        .btn-outline-white { border: 1.5px solid rgba(255,255,255,0.4); color: white; transition: all 0.2s; }
        .btn-outline-white:hover { border-color: #D4AF37; color: #D4AF37; }
        .btn-outline-dark { border: 1.5px solid #0A1A2F; color: #0A1A2F; transition: all 0.2s; }
        .btn-outline-dark:hover { background: #0A1A2F; color: white; }
        .feature-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: rgba(10,26,47,0.06); }
        .grid-bg { background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 40px 40px; }
        .dot-pattern { background-image: radial-gradient(rgba(212,175,55,0.15) 1px, transparent 1px); background-size: 24px 24px; }
        .prose-dark p { color: #475569; line-height: 1.75; }
    </style>

    @stack('head')
</head>
<body class="bg-[#F5F7FA] text-[#1E293B] font-sans antialiased">

    @include('marketing.partials.nav')

    <main>
        @yield('content')
    </main>

    @include('marketing.partials.footer')

    @stack('scripts')
</body>
</html>
