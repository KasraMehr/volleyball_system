<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Bell,
    ChartNoAxesCombined,
    ClipboardCheck,
    LayoutDashboard,
    LogOut,
    Menu,
    Search,
    Settings,
    ShieldCheck,
    UserCog,
    UsersRound,
    Volleyball,
    WalletCards,
    X,
} from '@lucide/vue';

const props = withDefaults(defineProps<{
    title: string;
    eyebrow?: string;
    active?: 'manager' | 'coach' | 'finance' | 'athlete';
}>(), { active: 'manager' });

const mobileMenuOpen = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user as { name?: string; role?: string; role_label?: string; unread_notifications?:number } | null);
const currentPath = computed(() => page.url.split('?')[0]);

const modules = [
    { label: 'نمای باشگاه', icon: LayoutDashboard, href: '/dashboard', roles: ['club_admin', 'finance', 'coach', 'athlete', 'guardian'] },
    { label: 'کاربران و دسترسی', icon: UserCog, href: '/users', roles: ['club_admin'] },
    { label: 'ورزشکاران', icon: UsersRound, href: '/athletes', roles: ['club_admin', 'coach', 'finance'] },
    { label: 'تیم‌ها و رده‌ها', icon: Volleyball, href: '/teams', roles: ['club_admin'] },
    { label: 'حضور و غیاب', icon: ClipboardCheck, href: '/attendance', roles: ['club_admin', 'coach'] },
    { label: 'شهریه و پرداخت', icon: WalletCards, href: '/finance', roles: ['club_admin', 'finance'] },
    { label: 'شهریه من', icon: WalletCards, href: '/my-payments', roles: ['athlete', 'guardian'] },
    { label: 'ارزیابی عملکرد', icon: ChartNoAxesCombined, href: '/assessments', roles: ['club_admin', 'coach'] },
    { label: 'رکوردهای من', icon: ChartNoAxesCombined, href: '/my-performance', roles: ['athlete', 'guardian'] },
    { label: 'گزارش‌ها', icon: Activity, href: '/reports', roles: ['club_admin', 'coach', 'finance'] },
    { label: 'اعلان‌ها', icon: Bell, href: '/notifications', roles: ['club_admin', 'finance', 'coach', 'athlete', 'guardian'] },
    { label: 'تنظیمات باشگاه', icon: Settings, href: '/settings', roles: ['club_admin'] },
];

const panels = [
    { label: 'مدیر باشگاه', key: 'manager', href: '/dashboard' },
    { label: 'مربی', key: 'coach', href: '/coach' },
    { label: 'مسئول مالی', key: 'finance', href: '/finance' },
    { label: 'ورزشکار', key: 'athlete', href: '/athlete' },
];
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-[#f4f3ef] text-[#102236]" dir="rtl">
        <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 bg-[#07192b]/45 lg:hidden" @click="mobileMenuOpen = false" />

        <aside
            class="fixed inset-y-0 right-0 z-50 flex w-[264px] flex-col border-l border-[#dfe3e6] bg-[#faf9f6] transition-transform duration-200 lg:translate-x-0"
            :class="mobileMenuOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <div class="flex h-[82px] items-center gap-3 border-b border-[#e2e4e5] px-5">
                <div class="brand-mark"><Volleyball :size="23" stroke-width="1.8" /></div>
                <div class="min-w-0 flex-1">
                    <p class="text-[17px] font-black tracking-tight text-[#0a2238]">والیبال من</p>
                    <p class="mt-0.5 truncate text-[10px] font-medium text-[#78838e]">هیئت والیبال استان</p>
                </div>
                <button class="p-1 text-slate-500 lg:hidden" @click="mobileMenuOpen = false"><X :size="20" /></button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-5">
                <p class="menu-label">عملیات روزانه</p>
                <nav class="space-y-1">
                    <component
                        :is="item.href === '#' ? 'button' : Link"
                        v-for="item in modules.filter(module => module.roles.includes(user?.role || ''))"
                        :key="item.label"
                        :href="item.href === '#' ? undefined : item.href"
                        class="nav-row"
                        :class="item.href !== '#' && currentPath === item.href ? 'nav-row-active' : ''"
                    >
                        <component :is="item.icon" :size="18" stroke-width="1.8" />
                        <span class="flex-1">{{ item.label }}</span>
                        <span v-if="item.count" class="nav-count">{{ item.count }}</span>
                    </component>
                </nav>

                <div v-if="user?.role === 'club_admin'" class="my-5 border-t border-[#e2e4e5]" />
                <p v-if="user?.role === 'club_admin'" class="menu-label">پیش‌نمایش نقش‌ها</p>
                <nav v-if="user?.role === 'club_admin'" class="space-y-1">
                    <Link
                        v-for="panel in panels"
                        :key="panel.key"
                        :href="panel.href"
                        class="role-row"
                        :class="props.active === panel.key ? 'role-row-active' : ''"
                    >
                        <span class="role-dot" />
                        <span>{{ panel.label }}</span>
                    </Link>
                </nav>
            </div>

            <div class="border-t border-[#e2e4e5] p-3">
                <div class="mb-2 flex items-center gap-3 px-2 py-2">
                    <div class="flex size-9 items-center justify-center bg-[#dce8f1] text-xs font-black text-[#0a375a]">م‌س</div>
                    <div class="min-w-0 flex-1"><p class="truncate text-xs font-bold">{{ user?.name }}</p><p class="mt-0.5 text-[10px] text-[#7b8790]">{{ user?.role_label }}</p></div>
                    <Link href="/logout" method="post" as="button" class="p-2 text-[#7b8790] hover:text-[#e06220]" title="خروج"><LogOut :size="17" /></Link>
                </div>
                <div class="flex items-center gap-2 border-t border-[#e7e8e8] px-2 pt-3 text-[10px] text-[#7b8790]"><ShieldCheck :size="14" class="text-[#247c62]" /> اتصال امن · نسخه آزمایشی</div>
            </div>
        </aside>

        <div class="lg:mr-[264px]">
            <header class="sticky top-0 z-30 flex h-[82px] items-center gap-3 border-b border-[#dfe3e6] bg-[#faf9f6]/95 px-4 backdrop-blur md:px-7">
                <button class="border border-[#d8dddf] bg-white p-2 lg:hidden" @click="mobileMenuOpen = true"><Menu :size="20" /></button>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold text-[#df6d2b]">{{ eyebrow || 'مرکز عملیات باشگاه' }}</p>
                    <h1 class="mt-1 truncate text-lg font-black tracking-tight text-[#0a2238]">{{ title }}</h1>
                </div>
                <div class="relative hidden w-full max-w-xs md:block">
                    <Search class="absolute right-3 top-1/2 -translate-y-1/2 text-[#86919a]" :size="17" />
                    <input class="search-field" placeholder="جست‌وجو در باشگاه..." />
                </div>
                <Link href="/notifications" class="header-action relative"><Bell :size="19" /><span v-if="user?.unread_notifications" class="absolute -left-1 -top-1 flex size-4 items-center justify-center rounded-full bg-[#e5662b] text-[7px] font-black text-white">{{ user.unread_notifications > 9 ? '9+' : user.unread_notifications }}</span></Link>
                <Link v-if="user?.role === 'club_admin'" href="/settings" class="header-action"><Settings :size="19" /></Link>
            </header>

            <main class="mx-auto max-w-[1540px] p-4 md:p-7 xl:p-8"><slot /></main>
        </div>
    </div>
</template>
