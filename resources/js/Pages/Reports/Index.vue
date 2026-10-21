<script setup lang="ts">
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Activity, AlertTriangle, Banknote, CalendarCheck, Download, FileText, Printer, Ruler, TrendingUp, UsersRound } from '@lucide/vue';

type TeamRow = { id:number; name:string; athletes:number; attendance_rate:number|null; collected:number|null; outstanding:number|null; avg_jump:number|null; assessments:number|null };
const props = defineProps<{
    filters:{ from:string; to:string; team:number|null };
    teams:Array<{id:number;name:string}>;
    permissions:{attendance:boolean;finance:boolean;performance:boolean};
    summary:{ attendance:null|{sessions:number;marked:number;present:number;absent:number;excused:number;rate:number}; finance:null|{charged:number;collected:number;outstanding:number;rate:number}; performance:null|{count:number;athletes:number;avg_jump:number;avg_spike:number} };
    teamBreakdown:TeamRow[];
    alerts:{unmarked_sessions:number|null;overdue_invoices:number|null;stale_assessments:number|null};
}>();

const form = reactive({ from: props.filters.from, to: props.filters.to, team: props.filters.team || '' });
const query = computed(() => new URLSearchParams({ from: props.filters.from, to: props.filters.to, ...(props.filters.team ? { team: String(props.filters.team) } : {}) }).toString());
const money = (value:number|null) => value === null ? '—' : new Intl.NumberFormat('fa-IR').format(value) + ' تومان';
const number = (value:number|null|undefined) => value === null || value === undefined ? '—' : new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 1 }).format(value);
const applyFilters = () => router.get('/reports', form, { preserveState: true, preserveScroll: true });
const reports = computed(() => [
    props.permissions.attendance && { type:'attendance', title:'حضور و غیاب', text:'ریز وضعیت هر ورزشکار در جلسات تمرینی', icon:CalendarCheck, accent:'#1f7795' },
    props.permissions.finance && { type:'finance', title:'شهریه و پرداخت', text:'صورتحساب‌ها، مبالغ وصول شده و مانده', icon:Banknote, accent:'#247c62' },
    props.permissions.performance && { type:'performance', title:'رکوردهای ورزشی', text:'قد، وزن، پرش، اسپک، دفاع و چابکی', icon:Ruler, accent:'#e87532' },
].filter(Boolean) as Array<{type:string;title:string;text:string;icon:any;accent:string}>);
</script>

<template>
    <AppLayout title="گزارش‌های مدیریتی" eyebrow="تصویر دقیق از عملکرد باشگاه">
        <section class="report-hero">
            <div class="relative z-10 max-w-2xl"><p class="section-kicker !text-[#f4a475]">مرکز گزارش</p><h2 class="mt-3 text-xl font-black text-white md:text-2xl">تصمیم‌گیری بر پایه عددهای واقعی باشگاه</h2><p class="mt-3 max-w-xl text-[10px] leading-6 text-[#adc0cd]">وضعیت تمرین، وصول شهریه و پیشرفت فنی تیم‌ها را در یک بازه مشخص ببینید و برای ارائه رسمی خروجی بگیرید.</p></div>
            <Activity class="absolute -left-5 -bottom-10 text-white/5" :size="190" stroke-width="1" />
        </section>

        <form class="mt-5 grid gap-3 border border-[#d9dfe1] bg-white p-4 md:grid-cols-[1fr_1fr_1.2fr_auto] md:items-end" @submit.prevent="applyFilters">
            <label class="form-label">از تاریخ<input v-model="form.from" type="date" class="form-input" /></label>
            <label class="form-label">تا تاریخ<input v-model="form.to" type="date" class="form-input" /></label>
            <label class="form-label">تیم<select v-model="form.team" class="form-input"><option value="">همه تیم‌های مجاز</option><option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option></select></label>
            <button class="primary-sport-button h-[41px]" type="submit"><TrendingUp :size="16" /> به‌روزرسانی گزارش</button>
        </form>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article v-if="summary.attendance" class="report-stat"><CalendarCheck :size="21"/><span>نرخ حضور</span><strong>{{ number(summary.attendance.rate) }}٪</strong><small>{{ number(summary.attendance.sessions) }} جلسه · {{ number(summary.attendance.marked) }} ثبت</small></article>
            <article v-if="summary.finance" class="report-stat"><Banknote :size="21"/><span>وصول در بازه</span><strong class="!text-[17px]">{{ money(summary.finance.collected) }}</strong><small>{{ number(summary.finance.rate) }}٪ نسبت به شهریه‌های بازه</small></article>
            <article v-if="summary.finance" class="report-stat report-stat-warn"><AlertTriangle :size="21"/><span>مانده کل وصول</span><strong class="!text-[17px]">{{ money(summary.finance.outstanding) }}</strong><small>{{ number(alerts.overdue_invoices) }} صورتحساب معوق</small></article>
            <article v-if="summary.performance" class="report-stat"><Ruler :size="21"/><span>میانگین پرش عمودی</span><strong>{{ number(summary.performance.avg_jump) }} <small>cm</small></strong><small>{{ number(summary.performance.athletes) }} ورزشکار ارزیابی‌شده</small></article>
            <article v-if="permissions.attendance && !summary.finance" class="report-stat report-stat-warn"><AlertTriangle :size="21"/><span>جلسه نیازمند تکمیل</span><strong>{{ number(alerts.unmarked_sessions) }}</strong><small>جلسه تا امروز</small></article>
            <article v-if="permissions.performance && !summary.finance" class="report-stat"><UsersRound :size="21"/><span>ارزیابی نیازمند تمدید</span><strong>{{ number(alerts.stale_assessments) }}</strong><small>بدون رکورد تازه در ۶۰ روز</small></article>
        </div>

        <section class="mt-5 panel-flat"><div class="panel-head"><div><h3>مقایسه تیم‌ها</h3><p>خلاصه شاخص‌های عملیاتی در بازه انتخاب‌شده</p></div><span class="counter-badge">{{ teamBreakdown.length }}</span></div>
            <div class="overflow-x-auto"><table class="sport-table report-table"><thead><tr><th>تیم</th><th>ورزشکار فعال</th><th v-if="permissions.attendance">نرخ حضور</th><th v-if="permissions.finance">وصول بازه</th><th v-if="permissions.finance">مانده کل</th><th v-if="permissions.performance">میانگین پرش</th><th v-if="permissions.performance">تعداد ارزیابی</th></tr></thead>
                <tbody><tr v-for="team in teamBreakdown" :key="team.id"><td><strong>{{ team.name }}</strong></td><td>{{ number(team.athletes) }} نفر</td><td v-if="permissions.attendance"><span class="rate-pill">{{ number(team.attendance_rate) }}٪</span></td><td v-if="permissions.finance">{{ money(team.collected) }}</td><td v-if="permissions.finance" class="text-[#b64732]">{{ money(team.outstanding) }}</td><td v-if="permissions.performance">{{ number(team.avg_jump) }} cm</td><td v-if="permissions.performance">{{ number(team.assessments) }}</td></tr><tr v-if="!teamBreakdown.length"><td colspan="7" class="!py-10 text-center">تیمی برای نمایش وجود ندارد.</td></tr></tbody>
            </table></div>
        </section>

        <section class="mt-5"><div class="mb-3"><p class="section-kicker">خروجی رسمی</p><h3 class="mt-1 text-sm font-black">گزارش موردنظر را دریافت کنید</h3></div>
            <div class="grid gap-3 lg:grid-cols-3"><article v-for="report in reports" :key="report.type" class="report-export-card" :style="{borderTopColor:report.accent}">
                <div class="report-export-icon" :style="{color:report.accent}"><component :is="report.icon" :size="22" /></div><div class="flex-1"><h4>{{ report.title }}</h4><p>{{ report.text }}</p></div>
                <div class="mt-4 flex gap-2 border-t border-[#e4e7e7] pt-4"><a class="small-action" :href="`/reports/export/${report.type}?${query}`"><Download :size="14" /> فایل Excel/CSV</a><a class="small-action" target="_blank" :href="`/reports/print/${report.type}?${query}`"><Printer :size="14" /> چاپ یا PDF</a></div>
            </article></div>
        </section>

        <section class="mt-5 grid gap-3 md:grid-cols-3">
            <div v-if="alerts.unmarked_sessions !== null" class="report-alert"><CalendarCheck :size="18"/><div><strong>{{ number(alerts.unmarked_sessions) }} جلسه تکمیل‌نشده</strong><span>حضور و غیاب جلسات گذشته را بررسی کنید.</span></div></div>
            <div v-if="alerts.overdue_invoices !== null" class="report-alert"><Banknote :size="18"/><div><strong>{{ number(alerts.overdue_invoices) }} شهریه معوق</strong><span>نیازمند پیگیری توسط مسئول مالی.</span></div></div>
            <div v-if="alerts.stale_assessments !== null" class="report-alert"><FileText :size="18"/><div><strong>{{ number(alerts.stale_assessments) }} پرونده بدون رکورد تازه</strong><span>ارزیابی جدید ورزشی ثبت نشده است.</span></div></div>
        </section>
    </AppLayout>
</template>
