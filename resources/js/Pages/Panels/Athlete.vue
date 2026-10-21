<script setup lang="ts">
import AppLayout from '../../Layouts/AppLayout.vue';
import { ArrowLeft, CalendarDays, CheckCircle2, ChevronLeft, CreditCard, TrendingUp } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';

type Athlete = { id: number; name: string; initials: string; membership_number: string; jersey_number: number | null; position: string | null; team: string | null; is_active: boolean; assessment: { date: string; height_cm: number | null; vertical_jump_cm: number | null; spike_reach_cm: number | null; block_reach_cm: number | null } | null };
type Invoice = { title: string; outstanding: number; due_date: string };
defineProps<{ athlete: Athlete | null; invoice: Invoice | null; paymentHref: string; performanceHref: string }>();
const money = (value: number) => new Intl.NumberFormat('fa-IR').format(value);
</script>

<template>
    <AppLayout title="پرونده ورزشکار" :eyebrow="athlete ? `نمای ورزشکار · ${athlete.name}` : 'نمای ورزشکار'" active="athlete">
        <section v-if="!athlete" class="panel-flat p-10 text-center"><h2 class="text-base font-black">پرونده‌ای به این حساب متصل نشده است</h2><p class="mt-2 text-[10px] text-[#7d8990]">مدیر باشگاه باید ورزشکار را به حساب شما متصل کند.</p></section>
        <template v-else>
        <section class="athlete-pass mb-5">
            <div v-if="athlete.jersey_number" class="athlete-number">{{ athlete.jersey_number }}</div>
            <div class="athlete-avatar">{{ athlete.initials }}</div>
            <div class="min-w-0 flex-1"><p class="text-[10px] font-bold text-[#ef8a4b]">پاسپورت ورزشی · {{ athlete.membership_number }}</p><h2 class="mt-2 text-2xl font-black text-white">{{ athlete.name }}</h2><div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-[11px] text-[#b9c6cf]"><span>{{ athlete.team || 'بدون تیم' }}</span><span>{{ athlete.position || 'پست تعیین نشده' }}</span></div></div>
            <div class="flex flex-col items-end gap-2"><div class="athlete-status"><CheckCircle2 :size="16" /> {{ athlete.is_active ? 'عضویت فعال' : 'عضویت غیرفعال' }}</div><Link :href="`/athletes/${athlete.id}`" class="text-[9px] font-black text-[#f29a66]">مشاهده اطلاعات پرونده</Link></div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1fr_0.72fr]">
            <div class="space-y-5">
                <article class="panel-flat p-5 md:p-6">
                    <div class="mb-6 flex items-center justify-between"><div><p class="section-kicker">رکوردهای بدنی</p><h3 class="mt-1 text-sm font-black">آخرین ارزیابی · {{ athlete.assessment?.date || 'ثبت نشده' }}</h3></div><Link :href="performanceHref" class="text-link">تاریخچه <ArrowLeft :size="14" /></Link></div>
                    <div class="grid grid-cols-2 gap-px bg-[#e1e4e4] border border-[#e1e4e4] md:grid-cols-4">
                        <div class="record-cell"><span>قد</span><strong>{{ athlete.assessment?.height_cm ?? '—' }}</strong><small>سانتی‌متر</small></div>
                        <div class="record-cell"><span>پرش</span><strong>{{ athlete.assessment?.vertical_jump_cm ?? '—' }}</strong><small>سانتی‌متر</small></div>
                        <div class="record-cell"><span>دسترسی اسپک</span><strong>{{ athlete.assessment?.spike_reach_cm ?? '—' }}</strong><small>سانتی‌متر</small></div>
                        <div class="record-cell"><span>دسترسی دفاع</span><strong>{{ athlete.assessment?.block_reach_cm ?? '—' }}</strong><small>سانتی‌متر</small></div>
                    </div>
                    <div class="mt-5 border-r-2 border-[#e87532] pr-4"><p class="text-[11px] font-bold">پرش نسبت به ارزیابی قبل ۴ سانتی‌متر بهتر شده است.</p><p class="mt-1 text-[10px] text-[#77838b]">ثبت‌شده توسط علی محمدی</p></div>
                </article>

                <article class="panel-flat p-5 md:p-6"><div class="flex items-center justify-between"><div><p class="section-kicker">حضور در تمرین</p><h3 class="mt-1 text-sm font-black">مهرماه ۱۴۰۵</h3></div><strong class="text-2xl font-black text-[#1c6e89]">۹۴٪</strong></div><div class="mt-6 flex gap-2"><span v-for="(s,i) in [1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1]" :key="i" class="h-8 flex-1" :class="s ? 'bg-[#1f7795]' : 'bg-[#df6c43]'" /></div><div class="mt-3 flex justify-between text-[10px] text-[#7c8890]"><span>۱۵ جلسه برگزارشده</span><span>۱ غیبت</span></div></article>
            </div>

            <div class="space-y-5">
                <article class="panel-flat p-5"><div class="flex items-start gap-3"><div class="quick-icon"><CalendarDays :size="19" /></div><div class="flex-1"><p class="section-kicker">تمرین بعدی</p><h3 class="mt-1 text-sm font-black">امروز، ساعت ۱۷:۳۰</h3><p class="mt-1 text-[10px] text-[#78858d]">سالن شماره ۲ · لباس آبی</p></div><ChevronLeft :size="17" /></div></article>
                <article class="panel-flat p-5"><div class="flex items-start gap-3"><div class="quick-icon"><CreditCard :size="19" /></div><div class="flex-1"><p class="section-kicker">{{ invoice?.title || 'وضعیت شهریه' }}</p><h3 class="mt-1 text-sm font-black">{{ invoice ? `${money(invoice.outstanding)} تومان مانده` : 'بدهی فعالی ندارید' }}</h3><p v-if="invoice" class="mt-1 text-[10px] text-[#c24e35]">سررسید: {{ invoice.due_date }}</p></div></div><Link :href="paymentHref" class="secondary-button mt-5 w-full justify-center">{{ invoice ? 'مشاهده و پرداخت شهریه' : 'مشاهده سوابق پرداخت' }}</Link></article>
                <article class="panel-flat p-5"><p class="section-kicker">هدف مربی</p><p class="mt-3 text-xs font-bold leading-6">بهبود زمان گام آخر و افزایش ارتفاع دسترسی اسپک تا ۳۰۵ سانتی‌متر</p><div class="mt-4 flex items-center gap-2 text-[10px] text-[#247c62]"><TrendingUp :size="14" /> پیشرفت مناسب</div></article>
            </div>
        </section>
        </template>
    </AppLayout>
</template>
