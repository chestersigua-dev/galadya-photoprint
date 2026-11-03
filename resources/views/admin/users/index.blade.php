@extends('layouts.app')

@section('title', 'User & Role Management')

@section('content')
<div class="space-y-6 pt-2" x-data="{ showNewUserModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
                <span class="text-xs font-bold text-purple-600 uppercase tracking-widest">Access Control (RBAC)</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 mt-1">Staff Users & Role Permissions</h1>
            <p class="text-xs text-slate-500">Manage studio operators, managers, permissions, and terminal authentication.</p>
        </div>

        <button 
            type="button" 
            @click="showNewUserModal = true"
            class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 via-pink-500 to-yellow-400 hover:opacity-95 text-white font-bold px-4 py-2.5 rounded-xl shadow-md shadow-purple-600/25 text-xs transition"
        >
            <i data-lucide="user-plus" class="w-4 h-4 text-yellow-200"></i>
            <span>Add Staff User</span>
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-purple-50/70 border-b border-purple-100 text-[11px] uppercase tracking-wider text-purple-900/70 font-semibold">
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Role</th>
                        <th class="py-3.5 px-6">Permissions Module Access</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-purple-50 text-xs text-slate-700">
                    @foreach ($users as $user)
                        <tr class="hover:bg-purple-50/40 transition">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar_url }}" class="w-9 h-9 rounded-full ring-2 ring-purple-200 object-cover">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-semibold">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $user->role?->slug === 'super_admin' ? 'bg-gradient-to-r from-[#1c053a] to-purple-800 text-yellow-300 border border-purple-500/30' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $user->role?->name ?? 'Staff' }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1 max-w-sm">
                                    @if($user->role?->slug === 'super_admin')
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">All Modules Unrestricted</span>
                                    @else
                                        @foreach ($user->role?->permissions ?? [] as $perm)
                                            <span class="text-[10px] px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-100 font-mono">
                                                {{ $perm->slug }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if($user->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Deactivated
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                        @csrf
                                        <button 
                                            type="submit" 
                                            class="text-[11px] font-semibold {{ $user->is_active ? 'text-rose-600 hover:text-rose-800' : 'text-emerald-600 hover:text-emerald-800' }}"
                                        >
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Current User</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: New User -->
    <div 
        x-show="showNewUserModal" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-purple-200" @click.outside="showNewUserModal = false">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-purple-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                    <h3 class="text-sm font-bold text-slate-900">Add New Staff Terminal Account</h3>
                </div>
                <button @click="showNewUserModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Full Name</label>
                    <input type="text" name="name" required placeholder="Staff Name" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Email Address</label>
                    <input type="email" name="email" required placeholder="staff@galadya.com" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Initial Password</label>
                    <input type="password" name="password" required placeholder="Min 8 characters" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Role Assignment</label>
                    <select name="role_id" required class="w-full px-3 py-1.5 rounded-lg border border-purple-200 text-xs focus:outline-none focus:border-purple-500">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }} ({{ $role->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="pt-3 flex items-center justify-end gap-2 border-t border-purple-100">
                    <button type="button" @click="showNewUserModal = false" class="px-3 py-1.5 text-xs text-slate-600 font-medium hover:text-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-95 text-white font-bold text-xs rounded-xl shadow-xs">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
