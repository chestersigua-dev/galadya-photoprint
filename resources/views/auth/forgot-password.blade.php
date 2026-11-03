<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galadya Photoprint - Reset Password</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold tracking-wider text-white">RESET PASSWORD</h1>
            <p class="text-xs text-amber-400 font-medium tracking-widest uppercase mt-1">Galadya Photoprint Terminal Security</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl">
            @if (session('status'))
                <div class="mb-5 bg-emerald-950/60 border border-emerald-800 text-emerald-300 px-4 py-3 rounded-xl text-xs">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 bg-rose-950/60 border border-rose-800 text-rose-300 px-4 py-3 rounded-xl text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <p class="text-xs text-slate-400 mb-5 leading-relaxed">
                Enter your staff email address and we will send a password reset signed link to your inbox.
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wider">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition"
                        placeholder="operator@galadya.com"
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 transition cursor-pointer"
                >
                    Send Password Reset Link
                </button>

                <div class="text-center pt-2">
                    <a href="{{ route('login') }}" class="text-xs text-slate-400 hover:text-white transition">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

