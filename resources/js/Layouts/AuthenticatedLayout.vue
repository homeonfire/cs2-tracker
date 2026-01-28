<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <div class="flex h-screen bg-[#0b0c10] text-gray-300 font-sans overflow-hidden selection:bg-indigo-500 selection:text-white">
        
        <aside class="w-72 flex-shrink-0 bg-[#0b0c10] border-r border-white/5 flex flex-col relative z-20">
            
            <div class="h-20 flex items-center px-8">
                <Link :href="route('inventories.index')" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:shadow-indigo-500/40 transition duration-500">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div>
                        <span class="font-black text-xl tracking-tight text-white block leading-none">CS2</span>
                        <span class="font-bold text-xs tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">TRACKER</span>
                    </div>
                </Link>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-8 overflow-y-auto scrollbar-hide">
                
                <div>
                    <p class="px-4 text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3">Аналитика</p>
                    <div class="space-y-1">
                        <Link :href="route('inventories.index')" 
                              class="group flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 relative overflow-hidden"
                              :class="route().current('inventories.index') 
                                ? 'text-white bg-indigo-600/10 shadow-[0_0_20px_rgba(79,70,229,0.15)]' 
                                : 'text-gray-400 hover:text-white hover:bg-white/5'">
                            <div v-if="route().current('inventories.index')" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-500 rounded-r-full shadow-[0_0_10px_#6366f1]"></div>
                            <svg class="w-5 h-5 transition-colors" :class="route().current('inventories.index') ? 'text-indigo-400' : 'text-gray-500 group-hover:text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            <span>Портфели</span>
                        </Link>
                    </div>
                </div>

                <div>
                    <p class="px-4 text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3">Маркет</p>
                    <div class="space-y-1">
                        <Link :href="route('market.index')" 
                              class="group flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 relative overflow-hidden"
                              :class="route().current('market.index') 
                                ? 'text-white bg-emerald-600/10 shadow-[0_0_20px_rgba(16,185,129,0.15)]' 
                                : 'text-gray-400 hover:text-white hover:bg-white/5'">
                            <div v-if="route().current('market.index')" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-emerald-500 rounded-r-full shadow-[0_0_10px_#10b981]"></div>
                            <svg class="w-5 h-5 transition-colors" :class="route().current('market.index') ? 'text-emerald-400' : 'text-gray-500 group-hover:text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <span>База Скинов</span>
                        </Link>

                        <Link :href="route('wishlist.index')" 
                              class="group flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 relative overflow-hidden"
                              :class="route().current('wishlist.index') 
                                ? 'text-white bg-pink-600/10 shadow-[0_0_20px_rgba(236,72,153,0.15)]' 
                                : 'text-gray-400 hover:text-white hover:bg-white/5'">
                            <div v-if="route().current('wishlist.index')" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-pink-500 rounded-r-full shadow-[0_0_10px_#ec4899]"></div>
                            <svg class="w-5 h-5 transition-colors" :class="route().current('wishlist.index') ? 'text-pink-400' : 'text-gray-500 group-hover:text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            <span>Избранное</span>
                        </Link>
                    </div>
                </div>

                <div>
                    <p class="px-4 text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3">Инструменты</p>
                    <div class="space-y-1">
                        <Link :href="route('simulator.index')" 
                              class="group flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 relative overflow-hidden"
                              :class="route().current('simulator.index') 
                                ? 'text-white bg-orange-600/10 shadow-[0_0_20px_rgba(234,88,12,0.15)]' 
                                : 'text-gray-400 hover:text-white hover:bg-white/5'">
                            <div v-if="route().current('simulator.index')" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-orange-500 rounded-r-full shadow-[0_0_10px_#ea580c]"></div>
                            <svg class="w-5 h-5 transition-colors" :class="route().current('simulator.index') ? 'text-orange-400' : 'text-gray-500 group-hover:text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>Симулятор</span>
                        </Link>
                    </div>
                </div>

            </nav>

            <div class="p-4 mt-auto">
                <Link :href="route('profile.edit')" class="group block w-full">
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-[#15171c] border border-white/5 hover:border-indigo-500/30 hover:bg-[#1a1c23] transition-all duration-300 shadow-xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold shadow-lg relative z-10 group-hover:scale-105 transition-transform">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="overflow-hidden relative z-10">
                            <div class="text-sm font-bold text-white truncate group-hover:text-indigo-400 transition">{{ user.name }}</div>
                            <div class="text-[10px] text-gray-500 truncate font-mono">Настройки профиля</div>
                        </div>
                        <div class="absolute right-3 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                    </div>
                </Link>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#0f1115] relative">
            <div class="absolute top-0 left-0 right-0 h-[500px] bg-indigo-900/10 blur-[120px] pointer-events-none"></div>
            <header v-if="$slots.header" class="px-8 py-8 flex items-center justify-between relative z-10">
                <slot name="header" />
            </header>
            <div class="flex-1 overflow-y-auto px-8 pb-10 scrollbar-hide relative z-10">
                <div class="w-full h-full">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>