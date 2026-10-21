<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { CalendarCheck, CheckCircle2, ChevronLeft, ClipboardCheck, Clock3, MapPin, Plus, UsersRound, X } from '@lucide/vue';

type Team = { id: number; name: string; color: string };
type Session = { id: number; title: string; session_date: string; starts_at: string; ends_at: string | null; location: string | null; status: string; team: Team; coach: string | null; records_count: number; present_count: number; absent_count: number; unmarked_count: number };
type Paginated = { data: Session[]; links: { url: string | null; label: string; active: boolean }[]; total: number; from: number; to: number };
type AtRisk = { id: number; name: string; team: string | null; absent_count: number };
const props = defineProps<{ sessions: Paginated; teams: Team[]; venues: Array<{id:number;name:string}>; filters: { team?: string; period?: string }; summary: { month_sessions: number; attendance_rate: number; open_sessions: number }; atRisk: AtRisk[] }>();
const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string | null);
const showCreate = ref(false);
const teamFilter = ref(props.filters.team || '');
const periodFilter = ref(props.filters.period || '');
const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const form = useForm({ team_id: props.teams[0]?.id || null, title: 'تمرین تیمی', session_date: today, starts_at: '17:30', ends_at: '19:00', location: props.venues[0]?.name || '', notes: '' });
const applyFilters = () => router.get('/attendance', { team: teamFilter.value || undefined, period: periodFilter.value || undefined }, { preserveState: true, replace: true });
const submit = () => form.post('/attendance', { onSuccess: () => { showCreate.value = false; form.reset(); } });
const dateLabel = (value: string) => new Intl.DateTimeFormat('fa-IR', { weekday: 'short', day: 'numeric', month: 'long' }).format(new Date(`${value}T12:00:00`));
const statusLabel = (value: string) => ({ scheduled: 'باز برای ثبت', completed: 'نهایی شده', canceled: 'لغو شده' }[value] || value);
const paginationLabel = (label: string) => label.replace('&laquo; Previous', 'قبلی').replace('Next &raquo;', 'بعدی');
</script>

<template>
    <AppLayout title="حضور و غیاب تمرین‌ها" eyebrow="عملیات روزانه مربیان" active="manager">
        <div v-if="successMessage" class="mb-4 flex items-center gap-2 border border-[#bcd9ca] bg-[#edf7f1] px-4 py-3 text-[11px] font-bold text-[#236b54]"><CheckCircle2 :size="16" />{{ successMessage }}</div>
        <section class="mb-5 grid gap-4 sm:grid-cols-3">
            <article class="access-stat"><div><span>جلسه‌های این ماه</span><strong>{{ summary.month_sessions }}</strong></div><CalendarCheck :size="22" /></article>
            <article class="access-stat"><div><span>نرخ حضور ماه</span><strong>{{ summary.attendance_rate }}٪</strong></div><UsersRound :size="22" /></article>
            <article class="access-stat"><div><span>جلسه نیازمند ثبت</span><strong>{{ summary.open_sessions }}</strong></div><ClipboardCheck :size="22" /></article>
        </section>

        <section class="panel-flat">
            <header class="users-toolbar"><div class="flex flex-1 flex-col gap-2 sm:flex-row"><select v-model="teamFilter" class="table-filter sm:max-w-52" @change="applyFilters"><option value="">همه تیم‌ها</option><option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option></select><select v-model="periodFilter" class="table-filter sm:max-w-44" @change="applyFilters"><option value="">همه جلسه‌ها</option><option value="today">امروز</option><option value="upcoming">پیش رو</option><option value="past">گذشته</option></select></div><button class="primary-sport-button" @click="showCreate = true"><Plus :size="17" /> جلسه جدید</button></header>
            <div class="divide-y divide-[#e4e7e7]">
                <Link v-for="session in sessions.data" :key="session.id" :href="`/attendance/${session.id}`" class="attendance-session-row">
                    <div class="session-date"><strong>{{ dateLabel(session.session_date) }}</strong><span>{{ session.starts_at }} تا {{ session.ends_at || '—' }}</span></div>
                    <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h3>{{ session.title }}</h3><span class="team-chip"><i class="size-1.5 rounded-full" :style="{ background: session.team.color }" />{{ session.team.name }}</span></div><p class="mt-2 flex flex-wrap items-center gap-3"><span><MapPin :size="12" />{{ session.location || 'بدون سالن' }}</span><span v-if="session.coach">مربی: {{ session.coach }}</span></p></div>
                    <div class="session-counts"><span class="text-[#24745d]">{{ session.present_count }} حاضر</span><span class="text-[#b64732]">{{ session.absent_count }} غایب</span><span v-if="session.unmarked_count" class="text-[#aa641e]">{{ session.unmarked_count }} ثبت‌نشده</span></div>
                    <span :class="session.status === 'completed' ? 'tag-ok' : 'tag-warn'">{{ statusLabel(session.status) }}</span><ChevronLeft :size="18" class="text-[#879198]" />
                </Link>
                <div v-if="!sessions.data.length" class="p-12 text-center text-[10px] text-[#879198]">جلسه‌ای با این فیلتر پیدا نشد.</div>
            </div>
            <footer class="flex flex-col gap-3 border-t border-[#e2e5e6] px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-[9px] text-[#7d8990]">نمایش {{ sessions.from || 0 }} تا {{ sessions.to || 0 }} از {{ sessions.total }} جلسه</p><div class="flex gap-1"><Link v-for="link in sessions.links" :key="link.label" :href="link.url || '#'" class="pagination-link" :class="{ 'pagination-active': link.active, 'pointer-events-none opacity-40': !link.url }" v-html="paginationLabel(link.label)" /></div></footer>
        </section>

        <section class="panel-flat mt-5"><header class="panel-head"><div><h3>نیازمند پیگیری</h3><p>بیشترین غیبت ثبت‌شده در ۳۰ روز اخیر</p></div><span class="counter-badge">{{ atRisk.length }}</span></header><div class="grid divide-y divide-[#e5e7e7] md:grid-cols-2 md:divide-x md:divide-x-reverse md:divide-y-0 xl:grid-cols-5"><Link v-for="athlete in atRisk" :key="athlete.id" :href="`/athletes/${athlete.id}`" class="flex items-center gap-3 p-4"><div class="user-initial">{{ athlete.name.slice(0, 1) }}</div><div class="min-w-0 flex-1"><strong class="block truncate text-[10px]">{{ athlete.name }}</strong><span class="mt-1 block truncate text-[8px] text-[#879198]">{{ athlete.team }}</span></div><span class="tag-danger">{{ athlete.absent_count }} غیبت</span></Link><p v-if="!atRisk.length" class="p-5 text-[10px] text-[#879198]">غیبت ثبت‌شده‌ای برای پیگیری وجود ندارد.</p></div></section>

        <div v-if="showCreate" class="fixed inset-0 z-[70] flex items-end justify-center bg-[#07192b]/50 sm:items-center sm:p-5" @click.self="showCreate = false"><section class="max-h-[92vh] w-full overflow-y-auto bg-[#faf9f6] sm:max-w-xl"><header class="flex items-center justify-between border-b border-[#dfe3e6] px-5 py-4"><div><p class="section-kicker">برنامه تمرین</p><h2 class="mt-1 text-base font-black">ساخت جلسه حضور و غیاب</h2></div><button class="p-2 text-[#74818a]" @click="showCreate = false"><X :size="20" /></button></header>
            <form class="space-y-5 p-5" @submit.prevent="submit"><div class="grid gap-4 sm:grid-cols-2"><label class="form-label">تیم<select v-model="form.team_id" class="form-input"><option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option></select><small v-if="form.errors.team_id">{{ form.errors.team_id }}</small></label><label class="form-label">عنوان جلسه<input v-model="form.title" class="form-input"><small v-if="form.errors.title">{{ form.errors.title }}</small></label><label class="form-label">تاریخ<input v-model="form.session_date" type="date" class="form-input" dir="ltr"></label><label class="form-label">سالن<select v-if="venues.length" v-model="form.location" class="form-input"><option v-for="venue in venues" :key="venue.id" :value="venue.name">{{ venue.name }}</option></select><input v-else v-model="form.location" class="form-input" placeholder="ابتدا سالن را در تنظیمات تعریف کنید"></label><label class="form-label">شروع<input v-model="form.starts_at" type="time" class="form-input" dir="ltr"></label><label class="form-label">پایان<input v-model="form.ends_at" type="time" class="form-input" dir="ltr"><small v-if="form.errors.ends_at">{{ form.errors.ends_at }}</small></label></div><label class="form-label">توضیحات تمرین<textarea v-model="form.notes" class="form-textarea" rows="3"></textarea></label><div class="flex justify-end gap-2 border-t border-[#e2e5e6] pt-4"><button type="button" class="secondary-button" @click="showCreate = false">انصراف</button><button class="primary-sport-button" :disabled="form.processing">{{ form.processing ? 'در حال ساخت...' : 'ساخت جلسه و لیست بازیکنان' }}</button></div></form>
        </section></div>
    </AppLayout>
</template>
