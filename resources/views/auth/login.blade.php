<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galadya Photoprint - Staff Sign In</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md" x-data="{
        fillCredentials(email, pass) {
            $refs.emailInput.value = email;
            $refs.passwordInput.value = pass;
        }
    }">
        <!-- Branding Card -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 font-bold text-2xl shadow-xl shadow-amber-500/10 mb-4 ring-4 ring-amber-500/20">
                GP
            </div>
            <h1 class="text-2xl font-bold tracking-wider text-white">GALADYA PHOTOPRINT</h1>
            <p class="text-xs text-amber-400 font-medium tracking-widest uppercase mt-1">Self-Shoot Studio Management & High-Res Printing</p>
        </div>

        <!-- Login Container -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/50">
            @if ($errors->any())
                <div class="mb-5 bg-rose-950/60 border border-rose-800 text-rose-300 px-4 py-3 rounded-xl text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('info'))
                <div class="mb-5 bg-sky-950/60 border border-sky-800 text-sky-300 px-4 py-3 rounded-xl text-xs">
                    {{ session('info') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        x-ref="emailInput"
                        value="{{ old('email', 'admin@galadya.com') }}" 
                        required 
                        autofocus
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition"
                        placeholder="operator@galadya.com"
                    >
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-amber-400 hover:text-amber-300 transition">Forgot password?</a>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        x-ref="passwordInput"
                        value="password123"
                        required 
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition"
                    >
                </div>

                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-400">
                        <span class="text-xs text-slate-400">Remember my session on this terminal</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 transition transform active:scale-[0.99] cursor-pointer"
                >
                    Sign In to Terminal
                </button>
            </form>

            <!-- Quick One-Click Demo Role Login Fillers -->
            <div class="mt-6 pt-5 border-t border-slate-800">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2 text-center">Quick Role Fill for Testing:</p>
                <div class="grid grid-cols-3 gap-2">
                    <button 
                        type="button" 
                        @click="fillCredentials('admin@galadya.com', 'password123')"
                        class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-medium transition text-center"
                    >
                        Super Admin
                    </button>
                    <button 
                        type="button" 
                        @click="fillCredentials('manager@galadya.com', 'password123')"
                        class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-medium transition text-center"
                    >
                        Manager
                    </button>
                    <button 
                        type="button" 
                        @click="fillCredentials('photographer@galadya.com', 'password123')"
                        class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-medium transition text-center"
                    >
                        Operator
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; {{ date('Y') }} Galadya Photoprint Studio. Philippine Standard Time.
        </p>
    </div>
</body>
</html>

