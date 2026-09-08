<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Summarize any website instantly in English or Bengali using Llama 3.3 70B via OpenRouter." />
    <title>Website Summarizer — Llama 3.3 70B</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    animation: {
                        'fade-in':  'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'spin-slow': 'spin 1.2s linear infinite',
                    },
                    keyframes: {
                        fadeIn:  { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                },
            },
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <style>
        body { font-family: 'Inter', sans-serif; transition: background 0.3s, color 0.3s; }

        /* Dark mode styles */
        .dark body, body.dark-body {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
        }
        .dark .glass-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.10);
        }
        .dark .input-field {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.10);
            color: #fff;
        }
        .dark .input-field::placeholder { color: #64748b; }
        .dark .input-field:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.35); }
        .dark .page-text { color: #e2e8f0; }
        .dark .muted-text { color: #94a3b8; }
        .dark .result-bg { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.10); }

        /* Light mode styles */
        html:not(.dark) body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 50%, #f5f0ff 100%);
        }
        html:not(.dark) .glass-card {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(100,100,200,0.15);
            box-shadow: 0 8px 40px rgba(100,100,200,0.10);
        }
        html:not(.dark) .input-field {
            background: #fff;
            border-color: #c7d2fe;
            color: #1e1b4b;
        }
        html:not(.dark) .input-field::placeholder { color: #9ca3af; }
        html:not(.dark) .input-field:focus { border-color: #6d28d9; box-shadow: 0 0 0 3px rgba(109,40,217,0.20); }
        html:not(.dark) .page-text { color: #1e1b4b; }
        html:not(.dark) .muted-text { color: #6b7280; }
        html:not(.dark) .result-bg { background: #f8f7ff; border-color: #e0e7ff; }
        html:not(.dark) .summary-text { color: #374151; }

        .glow-btn:hover { box-shadow: 0 0 20px rgba(139,92,246,0.55), 0 0 40px rgba(139,92,246,0.25); }
        .lang-btn.active { background: linear-gradient(90deg,#7c3aed,#4f46e5); color:#fff; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #7c3aed55; border-radius: 99px; }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-start py-10 px-4 transition-all duration-300">

    {{-- ── Top Bar ─────────────────────────────────────────────────────────── --}}
    <div class="w-full max-w-2xl flex justify-end mb-6 animate-fade-in">
        {{-- Dark / Light toggle --}}
        <button id="theme-toggle"
                class="flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold
                       border border-violet-400/30 bg-white/10 hover:bg-white/20
                       text-violet-300 dark:text-violet-300 transition-all duration-200 shadow"
                onclick="toggleTheme()" title="Toggle dark/light mode">
            <span id="theme-icon">🌙</span>
            <span id="theme-label">Dark Mode</span>
        </button>
    </div>

    {{-- ── Header ───────────────────────────────────────────────────────────── --}}
    <div class="text-center mb-10 animate-fade-in w-full max-w-2xl">
        <div class="flex items-center justify-center gap-3 mb-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="text-xs font-bold tracking-widest text-violet-400 uppercase">AI Powered</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold page-text leading-tight">
            Website
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-500 to-indigo-400">Summarizer</span>
        </h1>
        <p class="mt-3 muted-text text-sm md:text-base max-w-md mx-auto">
            Powered by <span class="text-violet-500 font-semibold">Llama 3.3 70B</span> via
            <span class="text-indigo-400 font-semibold">OpenRouter</span> — paste any URL and get an instant summary.
        </p>
    </div>

    {{-- ── Main Content ─────────────────────────────────────────────────────── --}}
    <div class="w-full max-w-2xl space-y-5 animate-slide-up">

        {{-- Error Alert --}}
        @if(session('error'))
            <div class="flex items-start gap-3 rounded-xl border border-red-400/40 bg-red-500/10 px-5 py-4 text-red-400 text-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Form Card --}}
        <div class="glass-card rounded-2xl p-7 shadow-xl">

            {{-- Language Selector --}}
            <div class="flex items-center gap-2 mb-5">
                <span class="muted-text text-xs font-semibold uppercase tracking-wider mr-1">Summarize in:</span>
                <button type="button" id="lang-en"
                        class="lang-btn {{ ($language ?? 'english') === 'english' ? 'active' : '' }}
                               px-4 py-1.5 rounded-full text-xs font-semibold border border-violet-500/30
                               hover:bg-violet-600/20 transition-all duration-200"
                        onclick="setLang('english')">
                    🇬🇧 English
                </button>
                <button type="button" id="lang-bn"
                        class="lang-btn {{ ($language ?? 'english') === 'bengali' ? 'active' : '' }}
                               px-4 py-1.5 rounded-full text-xs font-semibold border border-violet-500/30
                               hover:bg-violet-600/20 transition-all duration-200"
                        onclick="setLang('bengali')">
                    🇧🇩 বাংলা
                </button>
            </div>

            <label class="block muted-text text-sm font-medium mb-2" for="url-input">
                Enter a website URL
            </label>

            <form action="{{ route('summarize') }}" method="POST" id="summarize-form">
                @csrf
                <input type="hidden" name="language" id="language-input" value="{{ $language ?? 'english' }}" />

                <div class="flex flex-col sm:flex-row gap-3">
                    <input
                        id="url-input"
                        type="url"
                        name="url"
                        value="{{ $url ?? ($searched_url ?? '') }}"
                        placeholder="https://example.com"
                        required
                        class="input-field flex-1 rounded-xl border px-4 py-3 text-sm outline-none transition-all duration-200"
                    />
                    <button id="submit-btn" type="submit"
                            class="glow-btn flex items-center justify-center gap-2 rounded-xl
                                   bg-gradient-to-r from-violet-600 to-indigo-600
                                   hover:from-violet-500 hover:to-indigo-500
                                   text-white font-semibold text-sm px-6 py-3
                                   transition-all duration-200 whitespace-nowrap shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span id="btn-label">Summarize</span>
                    </button>
                </div>

                @error('url')
                    <p class="mt-2 text-red-400 text-xs">{{ $message }}</p>
                @enderror
            </form>
        </div>

        {{-- Results Card --}}
        @isset($summary)
            <div class="glass-card rounded-2xl p-7 shadow-xl animate-slide-up">

                {{-- Card Header --}}
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="page-text font-bold text-base">
                                Summary
                                <span class="ml-1 text-xs font-medium px-2 py-0.5 rounded-full
                                    {{ ($language ?? 'english') === 'bengali'
                                        ? 'bg-green-500/20 text-green-400'
                                        : 'bg-indigo-500/20 text-indigo-400' }}">
                                    {{ ($language ?? 'english') === 'bengali' ? '🇧🇩 বাংলা' : '🇬🇧 English' }}
                                </span>
                            </h2>
                            @isset($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                   class="text-violet-400 hover:text-violet-300 text-xs truncate max-w-xs inline-block transition-colors">
                                    {{ $url }}
                                </a>
                            @endisset
                        </div>
                    </div>

                    {{-- Copy Button --}}
                    <button id="copy-btn"
                            class="flex items-center gap-1.5 text-xs muted-text hover:text-violet-400 transition-colors px-3 py-1.5 rounded-lg border border-violet-500/20 hover:border-violet-500/50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Copy
                    </button>
                </div>

                {{-- Summary Body --}}
                <div class="result-bg rounded-xl p-5 border">
                    <p id="summary-text"
                       class="summary-text dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line
                              {{ ($language ?? 'english') === 'bengali' ? 'font-normal' : '' }}">{{ $summary }}</p>
                </div>

                {{-- Word Count --}}
                <p class="mt-3 text-xs muted-text text-right">
                    {{ str_word_count(strip_tags($summary)) }} words
                </p>
            </div>
        @endisset

    </div>

    {{-- ── Footer ───────────────────────────────────────────────────────────── --}}
    <p class="mt-14 muted-text text-xs text-center">
        Built with <span class="text-violet-400">Laravel</span> &middot;
        <span class="text-indigo-400">OpenRouter</span> &middot;
        <span class="text-emerald-400">Llama 3.3 70B</span>
    </p>

    <script>
        // ── Theme ──────────────────────────────────────────────────────────────
        const root  = document.documentElement;
        const icon  = document.getElementById('theme-icon');
        const label = document.getElementById('theme-label');

        // Persist theme across page loads
        (function () {
            const saved = localStorage.getItem('theme') || 'dark';
            if (saved === 'light') {
                root.classList.remove('dark');
                icon.textContent  = '☀️';
                label.textContent = 'Light Mode';
            } else {
                root.classList.add('dark');
                icon.textContent  = '🌙';
                label.textContent = 'Dark Mode';
            }
        })();

        function toggleTheme() {
            if (root.classList.contains('dark')) {
                root.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                icon.textContent  = '☀️';
                label.textContent = 'Light Mode';
            } else {
                root.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                icon.textContent  = '🌙';
                label.textContent = 'Dark Mode';
            }
        }

        // ── Language selector ──────────────────────────────────────────────────
        function setLang(lang) {
            document.getElementById('language-input').value = lang;

            const btnEn = document.getElementById('lang-en');
            const btnBn = document.getElementById('lang-bn');

            if (lang === 'english') {
                btnEn.classList.add('active');
                btnBn.classList.remove('active');
                document.getElementById('btn-label').textContent = 'Summarize';
            } else {
                btnBn.classList.add('active');
                btnEn.classList.remove('active');
                document.getElementById('btn-label').textContent = 'সারসংক্ষেপ করুন';
            }
        }

        // ── Submit loading state ───────────────────────────────────────────────
        document.getElementById('summarize-form').addEventListener('submit', function () {
            const btn   = document.getElementById('submit-btn');
            const lang  = document.getElementById('language-input').value;
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                ${lang === 'bengali' ? 'অপেক্ষা করুন…' : 'Summarizing…'}
            `;
        });

        // ── Copy to clipboard ──────────────────────────────────────────────────
        const copyBtn = document.getElementById('copy-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                const text = document.getElementById('summary-text');
                if (text) {
                    navigator.clipboard.writeText(text.innerText).then(() => {
                        copyBtn.innerHTML = `
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg> Copied!
                        `;
                        setTimeout(() => {
                            copyBtn.innerHTML = `
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg> Copy
                            `;
                        }, 2000);
                    });
                }
            });
        }
    </script>

</body>
</html>
