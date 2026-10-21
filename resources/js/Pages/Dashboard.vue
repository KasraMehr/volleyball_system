<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    CircleAlert,
    Clock3,
    CreditCard,
    MapPin,
    TrendingDown,
    TrendingUp,
    UserRoundPlus,
    UsersRound,
} from '@lucide/vue';

type Session = { id: number; time: string; team: string; coach: string | null; hall: string | null; athletes_count: number; color: string };
type Alert = { title: string; meta: string; level: string; href: string };
defineProps<{ stats: { athletes: number; teams: number; today_sessions: number; attendance_rate: number }; todaySessions: Session[]; alerts: Alert[]; teamRates: { name: string; rate: number }[] }>();
const todayLabel = new Intl.DateTimeFormat('fa-IR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());
</script>

<template>
    <AppLayout title="مرکز عملیات باشگاه" :eyebrow="`مدیریت باشگاه · ${todayLabel}`" active="manager">
        <section class="scoreboard mb-5">
            <div class="scoreboard-court" />
            <div class="relative z-10 flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-5 flex items-center gap-2 text-[11px] font-bold text-[#ef8b4a]"><span class="size-2 bg-[#ef8b4a]" /> وضعیت امروز باشگاه</div>
                    <h2 class="max-w-2xl text-2xl font-black leading-[1.55] text-white md:text-3xl">{{ stats.today_sessions }} جلسه تمرینی؛<br class="hidden sm:block"> برنامه امروز باشگاه آماده است.</h2>
                    <p class="mt-3 text-xs leading-6 text-[#b9c8d5]">نمای زنده از اطلاعات ثبت‌شده در سامانه</p>
                </div>
                <div class="grid grid-cols-3 border border-white/15 bg-[#0c2b46]">
                    <div class="score-cell"><strong>{{ stats.athletes }}</strong><span>ورزشکار فعال</span></div>
                    <div class="score-cell border-x border-white/15"><strong>{{ stats.today_sessions }}</strong><span>جلسه امروز</span></div>
                    <div class="score-cell"><strong>{{ stats.teams }}</strong><span>تیم فعال</span></div>
                </div>
            </div>
        </section>

        <section class="mb-5 grid grid-cols-2 border border-[#d9dfe1] bg-white lg:grid-cols-4">
            <div class="metric-strip">
                <span class="metric-kicker">ورزشکار فعال</span><div class="flex items-end justify-between gap-2"><strong>{{ stats.athletes }}</strong><span class="metric-up">پرونده واقعی</span></div>
            </div>
            <div class="metric-strip border-r border-[#e2e5e6]">
                <span class="metric-kicker">نرخ حضور ماه</span><div class="flex items-end justify-between gap-2"><strong>{{ stats.attendance_rate }}٪</strong><span class="metric-up"><TrendingUp :size="12" /> ثبت‌شده</span></div>
            </div>
            <div class="metric-strip border-t border-[#e2e5e6] lg:border-r lg:border-t-0">
                <span class="metric-kicker">تیم فعال</span><div class="flex items-end justify-between gap-2"><strong>{{ stats.teams }}</strong><span class="text-[10px] text-[#7a8790]">رده</span></div>
            </div>
            <div class="metric-strip border-r border-t border-[#e2e5e6] lg:border-t-0">
                <span class="metric-kicker">جلسه امروز</span><div class="flex items-end justify-between gap-2"><strong>{{ stats.today_sessions }}</strong><span class="text-[10px] text-[#7a8790]">جلسه</span></div>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.45fr_0.8fr]">
            <article class="panel-flat">
                <header class="panel-head">
                    <div><h3>برنامه سالن‌ها</h3><p>جلسات باقی‌مانده امروز</p></div>
                    <Link href="/attendance" class="text-link">تقویم کامل <ArrowLeft :size="14" /></Link>
                </header>
                <div class="schedule-list">
                    <Link v-for="item in todaySessions" :key="item.id" :href="`/attendance/${item.id}`" class="schedule-row">
                        <div class="schedule-time"><strong>{{ item.time }}</strong><span>امروز</span></div>
                        <span class="h-11 w-[3px]" :style="{ background: item.color }" />
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-extrabold text-[#102236]">{{ item.team }}</p><p class="mt-1 text-[11px] text-[#75828c]">مربی {{ item.coach }}</p></div>
                        <div class="hidden items-center gap-1.5 text-[11px] text-[#596975] sm:flex"><MapPin :size="14" /> {{ item.hall }}</div>
                        <span class="status-tag">{{ item.athletes_count }} نفر</span>
                        <span class="row-action"><ChevronLeft :size="17" /></span>
                    </Link>
                    <p v-if="!todaySessions.length" class="py-10 text-center text-[10px] text-[#879198]">برای امروز جلسه‌ای ثبت نشده است.</p>
                </div>
            </article>

            <article class="panel-flat">
                <header class="panel-head"><div><h3>نیازمند اقدام</h3><p>موارد مهم امروز</p></div><span class="counter-badge">{{ alerts.length }}</span></header>
                <div class="divide-y divide-[#e7e9e9] px-5">
                    <Link v-for="alert in alerts" :key="alert.title" :href="alert.href" class="flex gap-3 py-5">
                        <CircleAlert :size="18" :class="alert.level === 'urgent' ? 'text-[#d75436]' : 'text-[#df762f]'" class="mt-0.5 shrink-0" />
                        <div><p class="text-xs font-extrabold text-[#172b3d]">{{ alert.title }}</p><p class="mt-1.5 text-[10px] leading-5 text-[#7a8790]">{{ alert.meta }}</p></div>
                    </Link>
                </div>
            </article>
        </section>

        <section class="mt-5 grid gap-5 lg:grid-cols-[1fr_1fr_1fr]">
            <Link href="/athletes" class="quick-action"><div class="quick-icon"><UserRoundPlus :size="20" /></div><div class="flex-1"><strong>ثبت ورزشکار</strong><span>ایجاد پرونده و عضویت در تیم</span></div><ChevronLeft :size="18" /></Link>
            <Link href="/finance" class="quick-action"><div class="quick-icon"><CreditCard :size="20" /></div><div class="flex-1"><strong>ثبت دریافت</strong><span>پرداخت دستی یا کارت‌به‌کارت</span></div><ChevronLeft :size="18" /></Link>
            <Link href="/attendance" class="quick-action"><div class="quick-icon"><CalendarDays :size="20" /></div><div class="flex-1"><strong>جلسه جدید</strong><span>برنامه تمرین و حضور و غیاب</span></div><ChevronLeft :size="18" /></Link>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-[1.3fr_0.7fr]">
            <article class="panel-flat p-5 md:p-6">
                <div class="mb-5 flex items-center justify-between"><div><h3 class="text-sm font-black">کیفیت حضور تیم‌ها</h3><p class="mt-1 text-[10px] text-[#7a8790]">براساس جلسه‌های ثبت‌شده</p></div><span class="text-[10px] font-bold text-[#247c62]">میانگین باشگاه {{ stats.attendance_rate }}٪</span></div>
                <div class="space-y-4">
                    <div v-for="team in teamRates" :key="team.name" class="grid grid-cols-[100px_1fr_38px] items-center gap-3 text-[11px]"><span class="truncate font-bold">{{ team.name }}</span><div class="h-2 bg-[#edf0f0]"><div class="h-full bg-[#1d6f8d]" :style="{width: team.rate + '%'}" /></div><span class="text-left font-black">{{ team.rate }}٪</span></div>
                </div>
            </article>
            <article class="panel-flat flex flex-col justify-between bg-[#fff8f1] p-5 md:p-6">
                <div><div class="mb-4 flex size-10 items-center justify-center bg-[#f4dfca] text-[#d86627]"><Clock3 :size="19" /></div><h3 class="text-sm font-black">خلاصه عملیات روز</h3><p class="mt-2 text-[11px] leading-6 text-[#66747e]">آمار جلسه‌ها و حضور ورزشکاران بلافاصله بعد از ثبت مربی در داشبورد به‌روز می‌شود.</p></div>
                <div class="mt-6 flex items-center gap-2 text-[10px] font-bold text-[#247c62]"><CheckCircle2 :size="15" /> اطلاعات حضور متصل است</div>
            </article>
        </section>
    </AppLayout>
</template>
