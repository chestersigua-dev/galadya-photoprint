<!DOCTYPE html>
<html lang="en" class="h-full bg-[#18042e]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galadya Photoprint - Reset Password</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-purple-100 flex items-center justify-center p-4 relative overflow-hidden bg-gradient-to-br from-[#1b0336] via-[#2a084e] to-[#120224]">
    
    <!-- Ambient Studio Glows -->
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-purple-600/25 blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 -right-32 w-96 h-96 rounded-full bg-pink-500/20 blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center p-3 rounded-3xl bg-white shadow-2xl shadow-purple-950 mb-3 border-2 border-purple-300/40">
                <img src="{{ asset('images/logo 1.png') }}" alt="Galadya Studio" class="h-14 w-auto object-contain">
            </div>
            <h1 class="text-2xl font-black tracking-wider text-white">RESET PASSWORD</h1>
            <p class="text-xs text-yellow-300 font-bold tracking-widest uppercase mt-1">Galadya Photoprint Terminal Security</p>
        </div>

        <div class="bg-[#240845]/90 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-md">
            @if (session('status'))
                <div class="mb-5 bg-emerald-950/80 border border-emerald-500 text-emerald-200 px-4 py-3 rounded-xl text-xs">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 bg-rose-950/80 border border-rose-600 text-rose-200 px-4 py-3 rounded-xl text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <p class="text-xs text-purple-200 mb-5 leading-relaxed">
                Enter your staff email address and we will send a password reset signed link to your inbox.
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-purple-200 mb-1.5 uppercase tracking-wider">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                        class="w-full px-4 py-2.5 rounded-xl bg-[#140226] border border-purple-600/50 text-white placeholder-purple-400 text-sm focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/40 transition"
                        placeholder="operator@galadya.com"
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 hover:opacity-95 text-white font-black text-sm shadow-lg shadow-purple-900/60 transition cursor-pointer tracking-wider uppercase"
                >
                    Send Password Reset Link
                </button>

                <div class="text-center pt-2">
                    <a href="{{ route('login') }}" class="text-xs text-yellow-300 hover:text-yellow-200 font-semibold transition">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

