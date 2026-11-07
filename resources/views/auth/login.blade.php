<!DOCTYPE html>
<html lang="en" class="h-full bg-[#18042e]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galadya Photoprint - Staff Sign In</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-purple-100 flex items-center justify-center p-4 relative overflow-hidden bg-gradient-to-br from-[#1b0336] via-[#2a084e] to-[#120224]">
    
    <!-- Ambient Studio Glows (Purple, Lavender, Pink, Yellow) -->
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-purple-600/25 blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 -right-32 w-96 h-96 rounded-full bg-pink-500/20 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 left-1/3 w-80 h-80 rounded-full bg-yellow-400/15 blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10" x-data="{
        fillCredentials(email, pass) {
            $refs.emailInput.value = email;
            $refs.passwordInput.value = pass;
        }
    }">
        <!-- Studio Branding -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center p-3 rounded-3xl bg-white shadow-2xl shadow-purple-950 mb-3 border-2 border-purple-300/40 transform hover:scale-105 transition">
                <img src="{{ asset('images/logo 1.png') }}" alt="Galadya Studio" class="h-16 w-auto object-contain">
            </div>
            <h1 class="text-2xl font-black tracking-wider text-white">GALADYA PHOTOPRINT</h1>
            <p class="text-xs text-yellow-300 font-bold tracking-widest uppercase mt-1">Self-Shoot Studio Management & High-Res Printing</p>
        </div>

        <!-- Login Container -->
        <div class="bg-[#240845]/90 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/60 backdrop-blur-md">
            @if ($errors->any())
                <div class="mb-5 bg-rose-950/80 border border-rose-600 text-rose-200 px-4 py-3 rounded-xl text-xs shadow-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('info'))
                <div class="mb-5 bg-purple-900/60 border border-purple-400 text-purple-200 px-4 py-3 rounded-xl text-xs">
                    {{ session('info') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-purple-200 mb-1.5 uppercase tracking-wider">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        x-ref="emailInput"
                        value="{{ old('email', 'admin@galadya.com') }}" 
                        required 
                        autofocus
                        class="w-full px-4 py-2.5 rounded-xl bg-[#140226] border border-purple-600/50 text-white placeholder-purple-400 text-sm focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/40 transition"
                        placeholder="operator@galadya.com"
                    >
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-purple-200 uppercase tracking-wider">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-yellow-300 hover:text-yellow-200 font-semibold transition">Forgot password?</a>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        x-ref="passwordInput"
                        value="password123"
                        required 
                        class="w-full px-4 py-2.5 rounded-xl bg-[#140226] border border-purple-600/50 text-white placeholder-purple-400 text-sm focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/40 transition"
                    >
                </div>

                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-purple-600 bg-[#140226] text-pink-500 focus:ring-pink-400">
                        <span class="text-xs text-purple-200">Remember my session on this terminal</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 hover:opacity-95 text-white font-black text-sm shadow-lg shadow-purple-900/60 transition transform active:scale-[0.99] cursor-pointer tracking-wider uppercase"
                >
                    Sign In to Terminal
                </button>
            </form>

            <!-- Quick One-Click Demo Role Login Fillers -->
            <div class="mt-6 pt-5 border-t border-purple-700/40">
                <p class="text-[11px] font-bold text-yellow-300 uppercase tracking-wider mb-2 text-center">Quick Role Fill for Testing:</p>
                <div class="grid grid-cols-3 gap-2">
                    <button 
                        type="button" 
                        @click="fillCredentials('admin@galadya.com', 'password123')"
                        class="px-2 py-1.5 rounded-lg bg-purple-900/60 hover:bg-purple-800 text-purple-100 border border-purple-500/40 text-[11px] font-bold transition text-center"
                    >
                        Super Admin
                    </button>
                    <button 
                        type="button" 
                        @click="fillCredentials('manager@galadya.com', 'password123')"
                        class="px-2 py-1.5 rounded-lg bg-purple-900/60 hover:bg-purple-800 text-purple-100 border border-purple-500/40 text-[11px] font-bold transition text-center"
                    >
                        Manager
                    </button>
                    <button 
                        type="button" 
                        @click="fillCredentials('photographer@galadya.com', 'password123')"
                        class="px-2 py-1.5 rounded-lg bg-purple-900/60 hover:bg-purple-800 text-purple-100 border border-purple-500/40 text-[11px] font-bold transition text-center"
                    >
                        Operator
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-purple-300/80 mt-6">
            &copy; {{ date('Y') }} Galadya Photoprint Studio. Philippine Standard Time.
        </p>
    </div>
</body>
</html>

