<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { CheckCircle2, Pencil, Plus, ToggleLeft, ToggleRight, UsersRound, Volleyball, X } from '@lucide/vue';

type Coach = { id: number; name: string };
type Team = { id: number; name: string; code: string; gender: string; age_group: string | null; color: string; capacity: number; is_active: boolean; athletes_count: number; coaches: Coach[] };
const props = defineProps<{ teams: Team[]; coaches: Coach[] }>();
const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string | null);
const showForm = ref(false);
const editing = ref<Team | null>(null);
const emptyForm = () => ({ name: '', code: '', gender: 'male', age_group: '', color: '#1f7795', capacity: 24, coach_ids: [] as number[] });
const form = useForm(emptyForm());
const openCreate = () => { editing.value = null; form.defaults(emptyForm()); form.reset(); form.clearErrors(); showForm.value = true; };
const openEdit = (team: Team) => { editing.value = team; form.name = team.name; form.code = team.code; form.gender = team.gender; form.age_group = team.age_group || ''; form.color = team.color; form.capacity = team.capacity; form.coach_ids = team.coaches.map(c => c.id); form.clearErrors(); showForm.value = true; };
const submit = () => { const options = { preserveScroll: true, onSuccess: () => { showForm.value = false; editing.value = null; } }; editing.value ? form.put(`/teams/${editing.value.id}`, options) : form.post('/teams', options); };
const toggle = (team: Team) => router.patch(`/teams/${team.id}/toggle`, {}, { preserveScroll: true });
</script>

<template>
    <AppLayout title="تیم‌ها و رده‌های باشگاه" eyebrow="سازماندهی فنی" active="manager">
        <div v-if="successMessage" class="mb-4 flex items-center gap-2 border border-[#bcd9ca] bg-[#edf7f1] px-4 py-3 text-[11px] font-bold text-[#236b54]"><CheckCircle2 :size="16" />{{ successMessage }}</div>
        <header class="mb-5 flex flex-col gap-3 bg-[#0b2a44] p-5 text-white sm:flex-row sm:items-center sm:justify-between"><div><p class="text-[9px] font-black text-[#f29a66]">ساختار تیمی باشگاه</p><h2 class="mt-2 text-lg font-black">{{ teams.length }} تیم ثبت شده</h2><p class="mt-2 text-[10px] text-[#aec0cc]">ظرفیت، کادر فنی و اعضای هر رده را یکجا مدیریت کنید.</p></div><button class="primary-sport-button" @click="openCreate"><Plus :size="17" /> تیم جدید</button></header>
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="team in teams" :key="team.id" class="team-card" :class="{ 'opacity-60': !team.is_active }">
                <div class="team-color" :style="{ background: team.color }" />
                <header class="flex items-start justify-between gap-3"><div class="flex items-center gap-3"><div class="team-emblem" :style="{ color: team.color }"><Volleyball :size="21" /></div><div><h3>{{ team.name }}</h3><p>{{ team.code }} · {{ team.age_group || 'رده آزاد' }}</p></div></div><span :class="team.is_active ? 'tag-ok' : 'tag-danger'">{{ team.is_active ? 'فعال' : 'متوقف' }}</span></header>
                <div class="my-5 grid grid-cols-2 divide-x divide-x-reverse divide-[#e2e5e6] border-y border-[#e2e5e6] py-3"><div><span>بازیکن</span><strong>{{ team.athletes_count }}</strong></div><div class="pr-4"><span>ظرفیت</span><strong>{{ team.capacity }}</strong></div></div>
                <div class="min-h-12"><p class="text-[9px] font-bold text-[#849098]">کادر فنی</p><div class="mt-2 flex flex-wrap gap-1"><span v-for="coach in team.coaches" :key="coach.id" class="team-chip">{{ coach.name }}</span><span v-if="!team.coaches.length" class="text-[9px] text-[#a0a8ad]">مربی تعیین نشده</span></div></div>
                <footer class="mt-4 flex items-center justify-between border-t border-[#e5e7e7] pt-3"><button class="secondary-button" @click="openEdit(team)"><Pencil :size="14" /> ویرایش</button><button class="p-1" @click="toggle(team)"><ToggleRight v-if="team.is_active" :size="25" class="text-[#247c62]" /><ToggleLeft v-else :size="25" class="text-[#9aa3a8]" /></button></footer>
            </article>
        </section>

        <div v-if="showForm" class="fixed inset-0 z-[70] flex items-end justify-center bg-[#07192b]/50 sm:items-center sm:p-5" @click.self="showForm = false">
            <section class="max-h-[92vh] w-full overflow-y-auto bg-[#faf9f6] sm:max-w-xl"><header class="flex items-center justify-between border-b border-[#dfe3e6] px-5 py-4"><div><p class="section-kicker">ساختار فنی</p><h2 class="mt-1 text-base font-black">{{ editing ? 'ویرایش تیم' : 'ایجاد تیم جدید' }}</h2></div><button class="p-2 text-[#74818a]" @click="showForm = false"><X :size="20" /></button></header>
                <form class="space-y-5 p-5" @submit.prevent="submit"><div class="grid gap-4 sm:grid-cols-2"><label class="form-label">نام تیم<input v-model="form.name" class="form-input"><small v-if="form.errors.name">{{ form.errors.name }}</small></label><label class="form-label">کد تیم<input v-model="form.code" class="form-input" dir="ltr"><small v-if="form.errors.code">{{ form.errors.code }}</small></label><label class="form-label">رده سنی<input v-model="form.age_group" class="form-input" placeholder="مثلاً زیر ۱۷ سال"></label><label class="form-label">جنسیت<select v-model="form.gender" class="form-input"><option value="male">پسران</option><option value="female">دختران</option><option value="mixed">مختلط</option></select></label><label class="form-label">ظرفیت<input v-model="form.capacity" type="number" min="1" max="100" class="form-input"></label><label class="form-label">رنگ تیم<input v-model="form.color" type="color" class="form-input p-1"></label></div>
                    <div class="border border-[#dfe3e6] bg-white p-4"><p class="mb-3 text-[10px] font-black text-[#344957]">مربیان این تیم</p><div class="grid gap-2 sm:grid-cols-2"><label v-for="coach in coaches" :key="coach.id" class="flex items-center gap-2 border border-[#e2e5e6] px-3 py-2 text-[10px] font-bold"><input v-model="form.coach_ids" type="checkbox" :value="coach.id" class="accent-[#e87532]">{{ coach.name }}</label></div></div>
                    <div class="flex justify-end gap-2 border-t border-[#e2e5e6] pt-4"><button type="button" class="secondary-button" @click="showForm = false">انصراف</button><button class="primary-sport-button" :disabled="form.processing">{{ form.processing ? 'در حال ثبت...' : 'ذخیره تیم' }}</button></div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
