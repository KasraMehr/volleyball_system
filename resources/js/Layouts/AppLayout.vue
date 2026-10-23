<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Bell,
    ChartNoAxesCombined,
    ClipboardCheck,
    Dumbbell,
    LayoutDashboard,
    History,
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
const user = computed(() => page.props.auth?.user as { name?: string; role?: string; role_label?: string; unread_notifications?: number } | null);
const currentPath = computed(() => page.url.split('?')[0]);

const modules = [
    { label: 'نمای باشگاه', icon: LayoutDashboard, href: '/dashboard', roles: ['club_admin', 'finance', 'coach', 'athlete', 'guardian'] },
    { label: 'کاربران و دسترسی', icon: UserCog, href: '/users', roles: ['club_admin'] },
    { label: 'ورزشکاران', icon: UsersRound, href: '/athletes', roles: ['club_admin', 'coach', 'finance'] },
    { label: 'تیم‌ها و رده‌ها', icon: Volleyball, href: '/teams', roles: ['club_admin'] },
    { label: 'حضور و غیاب', icon: ClipboardCheck, href: '/attendance', roles: ['club_admin', 'coach'] },
    { label: 'برنامه تمرینی', icon: Dumbbell, href: '/training-plans', roles: ['club_admin', 'coach'] },
    { label: 'شهریه و پرداخت', icon: WalletCards, href: '/finance', roles: ['club_admin', 'finance'] },
    { label: 'شهریه من', icon: WalletCards, href: '/my-payments', roles: ['athlete', 'guardian'] },
    { label: 'ارزیابی عملکرد', icon: ChartNoAxesCombined, href: '/assessments', roles: ['club_admin', 'coach'] },
    { label: 'رکوردهای من', icon: ChartNoAxesCombined, href: '/my-performance', roles: ['athlete', 'guardian'] },
    { label: 'گزارش‌ها', icon: Activity, href: '/reports', roles: ['club_admin', 'coach', 'finance'] },
    { label: 'اعلان‌ها', icon: Bell, href: '/notifications', roles: ['club_admin', 'finance', 'coach', 'athlete', 'guardian'] },
    { label: 'تاریخچه فعالیت', icon: History, href: '/activity-log', roles: ['club_admin'] },
    { label: 'تنظیمات باشگاه', icon: Settings, href: '/settings', roles: ['club_admin'] },
];

const panels = [
    { label: 'مدیر باشگاه', key: 'manager', href: '/dashboard' },
    { label: 'مربی', key: 'coach', href: '/coach' },
    { label: 'مسئول مالی', key: 'finance', href: '/finance' },
    { label: 'ورزشکار', key: 'athlete', href: '/athlete' },
];

const visibleModules = computed(() => modules.filter(m => m.roles.includes(user.value?.role || '')));

const mobileModules = computed(() => {
    const role = user.value?.role || '';
    const paths: Record<string, string[]> = {
        club_admin: ['/dashboard', '/athletes', '/attendance', '/finance', '/notifications'],
        coach: ['/coach', '/athletes', '/attendance', '/training-plans', '/notifications'],
        finance: ['/dashboard', '/athletes', '/finance', '/reports', '/notifications'],
        athlete: ['/athlete', '/my-payments', '/my-performance', '/notifications'],
        guardian: ['/athlete', '/my-payments', '/my-performance', '/notifications'],
    };
    return (paths[role] || ['/dashboard', '/notifications'])
        .map(path => modules.find(m => m.href === path))
        .filter((m): m is typeof modules[number] => Boolean(m));
});

const unread = computed(() => {
    const n = user.value?.unread_notifications || 0;
    return n > 9 ? '9+' : String(n);
});

const initials = computed(() => (user.value?.name || '؟').trim().slice(0, 1));
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-slate-100 text-slate-800" dir="rtl">
        <!-- Overlay -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            @click="mobileMenuOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 right-0 z-50 flex w-[276px] flex-col bg-[#0b1e3d] text-slate-100 shadow-2xl transition-transform duration-300 lg:translate-x-0 lg:shadow-none"
            :class="mobileMenuOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <!-- Brand -->
            <div class="flex h-20 items-center gap-3 border-b border-white/10 px-5">
                <div class="flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-500 text-[#0b1e3d] shadow-lg shadow-amber-400/20">
                    <Volleyball :size="24" stroke-width="2" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-extrabold leading-tight text-white">والیبال من</p>
                    <p class="truncate text-xs text-sky-300/70">هیئت والیبال استان</p>
                </div>
                <button class="rounded-lg p-1.5 text-slate-300 hover:bg-white/10 lg:hidden" @click="mobileMenuOpen = false">
                    <X :size="20" />
                </button>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto py-4 [scrollbar-width:thin] [scrollbar-color:theme(colors.slate.700)_transparent]">
                <div>
                    <p class="mb-2 px-5 text-[11px] font-bold tracking-wide text-sky-400/60">عملیات روزانه</p>
                    <nav class="divide-y divide-white/[0.06] border-y border-white/[0.06]">
                        <Link
                            v-for="item in visibleModules"
                            :key="item.label"
                            :href="item.href"
                            class="group relative flex items-center gap-3 px-5 py-3 text-[13px] font-medium transition"
                            :class="currentPath === item.href
                                ? 'bg-sky-400/10 text-white'
                                : 'text-slate-300/90 hover:bg-white/[0.04] hover:text-white'"
                        >
                            <span
                                class="absolute inset-y-0 right-0 w-[3px] bg-amber-400 transition-all"
                                :class="currentPath === item.href ? 'opacity-100' : 'opacity-0 group-hover:opacity-30'"
                            />
                            <component
                                :is="item.icon"
                                :size="18"
                                stroke-width="1.8"
                                :class="currentPath === item.href ? 'text-amber-400' : 'text-sky-400/60 group-hover:text-sky-300'"
                            />
                            <span class="flex-1">{{ item.label }}</span>
                        </Link>
                    </nav>
                </div>

                <div v-if="user?.role === 'club_admin'">
                    <p class="mb-2 px-5 text-[11px] font-bold tracking-wide text-sky-400/60">پیش‌نمایش نقش‌ها</p>
                    <nav class="divide-y divide-white/[0.06] border-y border-white/[0.06]">
                        <Link
                            v-for="panel in panels"
                            :key="panel.key"
                            :href="panel.href"
                            class="flex items-center gap-3 px-5 py-2.5 text-[13px] transition"
                            :class="props.active === panel.key
                                ? 'bg-amber-400/10 font-bold text-amber-300'
                                : 'text-slate-300/80 hover:bg-white/[0.04] hover:text-white'"
                        >
                            <span
                                class="size-2 rounded-full"
                                :class="props.active === panel.key ? 'bg-amber-400' : 'bg-slate-600'"
                            />
                            <span>{{ panel.label }}</span>
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- User card -->
            <div class="m-3 rounded-2xl border border-white/10 bg-white/[0.04] p-3">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-amber-500 text-sm font-extrabold text-[#0b1e3d]">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-white">{{ user?.name }}</p>
                        <p class="truncate text-xs text-sky-300/70">{{ user?.role_label }}</p>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="rounded-lg p-2 text-slate-300 transition hover:bg-red-500/20 hover:text-red-300"
                        title="خروج"
                    >
                        <LogOut :size="18" />
                    </Link>
                </div>
                <div class="mt-3 flex items-center gap-2 border-t border-white/10 pt-3 text-[11px] text-sky-300/60">
                    <ShieldCheck :size="14" class="text-sky-400" /> اتصال امن · نسخه آزمایشی
                </div>
            </div>
        </aside>

        <div class="lg:mr-[276px]">
            <!-- Header -->
            <header class="sticky top-0 z-30 flex h-20 items-center gap-3 border-b border-slate-200/80 bg-slate-100/80 px-4 backdrop-blur-xl md:px-8">
                <button
                    class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm transition hover:bg-slate-50 lg:hidden"
                    @click="mobileMenuOpen = true"
                >
                    <Menu :size="20" />
                </button>

                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-amber-600">{{ eyebrow || 'مرکز عملیات باشگاه' }}</p>
                    <h1 class="mt-0.5 truncate text-xl font-extrabold text-[#0b1e3d]">{{ title }}</h1>
                </div>

                <div class="relative hidden w-full max-w-xs md:block">
                    <Search class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400" :size="17" />
                    <input
                        class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-3 pr-10 text-sm shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-600 focus:ring-4 focus:ring-sky-600/10"
                        placeholder="جست‌وجو در باشگاه..."
                    />
                </div>

                <Link
                    href="/notifications"
                    class="relative rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-[#0b1e3d]"
                >
                    <Bell :size="19" />
                    <span
                        v-if="user?.unread_notifications"
                        class="absolute -left-1.5 -top-1.5 flex min-w-[20px] items-center justify-center rounded-full border-2 border-slate-100 bg-amber-500 px-1 text-[10px] font-bold leading-4 text-white"
                    >{{ unread }}</span>
                </Link>
                <Link
                    v-if="user?.role === 'club_admin'"
                    href="/settings"
                    class="hidden rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-[#0b1e3d] sm:block"
                >
                    <Settings :size="19" />
                </Link>
            </header>

            <main class="mx-auto max-w-[1540px] p-4 pb-28 md:p-8 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- Mobile bottom nav -->
        <nav
            class="fixed inset-x-3 bottom-3 z-[45] flex items-center justify-around rounded-3xl border border-white/60 bg-white/90 p-1.5 shadow-xl shadow-[#0b1e3d]/15 backdrop-blur-xl pb-[max(0.375rem,env(safe-area-inset-bottom))] lg:hidden"
            aria-label="ناوبری اصلی موبایل"
        >
            <Link
                v-for="item in mobileModules"
                :key="item.href"
                :href="item.href"
                class="relative flex min-w-0 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-1 py-2 text-[11px] font-semibold transition"
                :class="currentPath === item.href
                    ? 'bg-[#0b1e3d] text-white shadow-md'
                    : 'text-slate-500 active:bg-slate-100'"
            >
                <component :is="item.icon" :size="20" stroke-width="1.8" />
                <span class="max-w-full truncate">{{ item.label }}</span>
                <span
                    v-if="item.href === '/notifications' && user?.unread_notifications"
                    class="absolute left-1/2 top-0.5 ml-2 flex min-w-[16px] items-center justify-center rounded-full bg-amber-500 px-1 text-[9px] font-bold leading-4 text-white"
                >{{ unread }}</span>
            </Link>
        </nav>
    </div>
</template>
