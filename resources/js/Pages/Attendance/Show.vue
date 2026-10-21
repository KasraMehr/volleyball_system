<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { ArrowRight, Check, CheckCircle2, Clock3, MapPin, Save, ShieldCheck, UserX } from '@lucide/vue';
type RecordRow = { id: number; athlete_id: number; name: string; number: number | null; position: string | null; status: string; arrival_time: string | null; note: string | null };
type Session = { id: number; title: string; session_date: string; starts_at: string; ends_at: string | null; location: string | null; status: string; notes: string | null; team: { id: number; name: string; color: string }; records_count: number; present_count: number; absent_count: number; unmarked_count: number; records: RecordRow[] };
const props = defineProps<{ session: Session }>();
const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string | null);
const form = useForm({ records: props.session.records.map(record => ({ athlete_id: record.athlete_id, status: record.status, arrival_time: record.arrival_time?.slice(0, 5) || '', note: record.note || '' })) });
const statuses = [{ value: 'present', label: 'حاضر' }, { value: 'late', label: 'تأخیر' }, { value: 'absent', label: 'غایب' }, { value: 'excused', label: 'موجه' }, { value: 'unmarked', label: 'نامشخص' }];
const stats = computed(() => ({ present: form.records.filter(r => ['present', 'late'].includes(r.status)).length, absent: form.records.filter(r => r.status === 'absent').length, excused: form.records.filter(r => r.status === 'excused').length, unmarked: form.records.filter(r => r.status === 'unmarked').length }));
const markAllPresent = () => form.records.forEach(record => { record.status = 'present'; record.arrival_time = ''; });
const save = () => form.put(`/attendance/${props.session.id}/records`, { preserveScroll: true });
const complete = () => router.patch(`/attendance/${props.session.id}/complete`, {}, { preserveScroll: true });
const statusClass = (value: string) => ({ present: 'attendance-present', late: 'attendance-late', absent: 'attendance-absent', excused: 'attendance-excused', unmarked: 'attendance-unmarked' }[value]);
const dateLabel = new Intl.DateTimeFormat('fa-IR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(`${props.session.session_date}T12:00:00`));
</script>

<template>
    <AppLayout :title="session.title" eyebrow="ثبت حضور و غیاب" active="manager">
        <Link href="/attendance" class="mb-4 inline-flex items-center gap-2 text-[10px] font-black text-[#657580]"><ArrowRight :size="15" /> بازگشت به جلسه‌ها</Link>
        <div v-if="successMessage" class="mb-4 flex items-center gap-2 border border-[#bcd9ca] bg-[#edf7f1] px-4 py-3 text-[11px] font-bold text-[#236b54]"><CheckCircle2 :size="16" />{{ successMessage }}</div>
        <section class="coach-hero mb-5 text-white"><div class="relative z-10 flex flex-col gap-5 md:flex-row md:items-center"><div class="flex-1"><p class="text-[9px] font-black text-[#f29a66]">{{ session.team.name }}</p><h2 class="mt-2 text-xl font-black">{{ dateLabel }}</h2><div class="mt-3 flex flex-wrap gap-4 text-[10px] text-[#aec0cc]"><span class="flex items-center gap-1"><Clock3 :size="13" />{{ session.starts_at }} تا {{ session.ends_at || '—' }}</span><span class="flex items-center gap-1"><MapPin :size="13" />{{ session.location || 'بدون سالن' }}</span></div></div><div class="grid grid-cols-4 gap-2 text-center"><div class="attendance-stat"><strong>{{ stats.present }}</strong><span>حاضر</span></div><div class="attendance-stat"><strong>{{ stats.absent }}</strong><span>غایب</span></div><div class="attendance-stat"><strong>{{ stats.excused }}</strong><span>موجه</span></div><div class="attendance-stat"><strong>{{ stats.unmarked }}</strong><span>نامشخص</span></div></div></div></section>

        <section class="panel-flat"><header class="users-toolbar"><div><p class="section-kicker">لیست تیم</p><h3 class="mt-1 text-sm font-black">{{ session.records.length }} ورزشکار</h3></div><div class="flex flex-wrap gap-2"><button class="secondary-button" @click="markAllPresent"><Check :size="15" /> همه حاضر</button><button class="primary-sport-button" :disabled="form.processing" @click="save"><Save :size="15" /> ذخیره حضور</button><button v-if="session.status !== 'completed'" class="secondary-button" :disabled="stats.unmarked > 0" :title="stats.unmarked ? 'ابتدا وضعیت همه را ثبت کنید' : ''" @click="complete"><ShieldCheck :size="15" /> بستن جلسه</button></div></header>
            <div class="overflow-x-auto"><table class="sport-table attendance-table"><thead><tr><th>ورزشکار</th><th>وضعیت</th><th>زمان ورود</th><th>یادداشت مربی</th></tr></thead><tbody><tr v-for="(record, index) in form.records" :key="record.athlete_id"><td><div class="flex items-center gap-3"><div class="jersey-no">{{ session.records[index].number || '—' }}</div><div><strong class="block">{{ session.records[index].name }}</strong><span class="mt-1 block text-[9px] text-[#8a959b]">{{ session.records[index].position || 'بدون پست' }}</span></div></div></td><td><div class="flex gap-1"><button v-for="status in statuses" :key="status.value" class="attendance-choice" :class="record.status === status.value ? statusClass(status.value) : ''" @click="record.status = status.value">{{ status.label }}</button></div></td><td><input v-if="record.status === 'late'" v-model="record.arrival_time" type="time" class="compact-input" dir="ltr"><span v-else class="text-[#a0a8ad]">—</span></td><td><input v-model="record.note" class="compact-input min-w-44" placeholder="اختیاری"></td></tr></tbody></table></div>
            <footer class="flex flex-col gap-3 border-t border-[#e2e5e6] bg-[#fafaf8] px-5 py-4 text-[9px] text-[#6f7c84] sm:flex-row sm:items-center sm:justify-between"><span>تغییرات پس از زدن «ذخیره حضور» ثبت می‌شوند.</span><span v-if="session.status === 'completed'" class="flex items-center gap-1 font-black text-[#24745d]"><CheckCircle2 :size="14" /> این جلسه نهایی شده و همچنان قابل اصلاح است.</span></footer>
        </section>
    </AppLayout>
</template>
