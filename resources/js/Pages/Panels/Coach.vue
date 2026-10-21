<script setup lang="ts">
import AppLayout from '../../Layouts/AppLayout.vue';
import { ArrowLeft, BarChart3, CalendarClock, ChevronLeft, ClipboardCheck, Ruler, UsersRound } from '@lucide/vue';

type Team = { id: number; name: string; athletes_count: number; is_head_coach: boolean };
type Athlete = { id: number; name: string; number: number | null; position: string | null; team: string | null };
const props = defineProps<{ coach?: { name: string }; teams?: Team[]; athletes?: Athlete[] }>();
const attendance = ['۹۴٪', '۹۱٪', '۸۷٪', '۹۶٪', '۹۲٪', '۸۹٪'];
</script>

<template>
    <AppLayout title="پنل مربی" :eyebrow="`${props.teams?.[0]?.name || 'تیم مربی'} · ${props.coach?.name || 'مربی'}`" active="coach">
        <section class="coach-hero mb-5">
            <div class="flex flex-col gap-7 md:flex-row md:items-end md:justify-between">
                <div><p class="section-kicker">تمرین بعدی</p><h2 class="mt-3 text-2xl font-black text-white">امروز · ساعت ۱۷:۳۰</h2><p class="mt-2 text-xs text-[#bdcad4]">سالن شماره ۲ · ۹۰ دقیقه · تمرین دریافت و حمله</p></div>
                <button class="primary-sport-button"><ClipboardCheck :size="19" /> شروع حضور و غیاب <ArrowLeft :size="17" /></button>
            </div>
            <div class="mt-7 grid grid-cols-3 border-t border-white/15 pt-5">
                <div><strong class="coach-number">{{ props.teams?.reduce((sum, team) => sum + team.athletes_count, 0) || 0 }}</strong><span class="coach-label">ورزشکار در دسترس</span></div>
                <div class="border-x border-white/15 px-4"><strong class="coach-number">۹۱٪</strong><span class="coach-label">حضور این ماه</span></div>
                <div class="pr-4"><strong class="coach-number">۴</strong><span class="coach-label">نیازمند پیگیری</span></div>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
            <article class="panel-flat">
                <header class="panel-head"><div><h3>بازیکنان تیم</h3><p>فهرست فعال فصل ۱۴۰۵</p></div><button class="text-link">مشاهده همه <ArrowLeft :size="14" /></button></header>
                <div class="overflow-x-auto">
                    <table class="sport-table">
                        <thead><tr><th>بازیکن</th><th>پست</th><th>حضور</th><th>وضعیت</th><th /></tr></thead>
                        <tbody><tr v-for="(athlete, index) in props.athletes || []" :key="athlete.id"><td><div class="flex items-center gap-3"><span class="jersey-no">{{ athlete.number || '—' }}</span><strong>{{ athlete.name }}</strong></div></td><td>{{ athlete.position || 'ثبت نشده' }}</td><td class="font-black">{{ attendance[index % attendance.length] }}</td><td><span :class="index === 2 ? 'tag-warn' : 'tag-ok'">{{ index === 2 ? 'پیگیری' : 'آماده' }}</span></td><td><button class="row-action"><ChevronLeft :size="16" /></button></td></tr></tbody>
                    </table>
                </div>
            </article>

            <div class="space-y-5">
                <article class="panel-flat p-5"><p class="section-kicker">برنامه این هفته</p><div class="mt-4 space-y-4"><div class="mini-plan"><span>شنبه</span><strong>تمرین تکنیکی</strong><small>۱۷:۳۰</small></div><div class="mini-plan mini-plan-active"><span>دوشنبه</span><strong>دریافت و حمله</strong><small>۱۷:۳۰</small></div><div class="mini-plan"><span>چهارشنبه</span><strong>بازی تمرینی</strong><small>۱۸:۰۰</small></div></div></article>
                <article class="panel-flat p-5"><div class="flex items-start gap-3"><div class="quick-icon"><Ruler :size="19" /></div><div><h3 class="text-xs font-black">ارزیابی‌های عقب‌افتاده</h3><p class="mt-1.5 text-[10px] leading-5 text-[#74818a]">رکورد پرش ۴ بازیکن بیش از ۶۰ روز است که ثبت نشده.</p><button class="mt-3 text-[10px] font-black text-[#d96528]">ثبت رکوردها</button></div></div></article>
            </div>
        </section>

        <section class="mt-5 grid gap-5 md:grid-cols-3">
            <article class="coach-action"><UsersRound :size="20" /><strong>ترکیب تیم</strong><span>پست‌ها و شماره پیراهن</span></article>
            <article class="coach-action"><BarChart3 :size="20" /><strong>روند عملکرد</strong><span>مقایسه رکوردهای بازیکنان</span></article>
            <article class="coach-action"><CalendarClock :size="20" /><strong>برنامه تمرین</strong><span>جلسات و اهداف هفتگی</span></article>
        </section>
    </AppLayout>
</template>
