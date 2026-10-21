<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { CheckCircle2, ChevronLeft, Filter, Pencil, Plus, Search, ShieldCheck, ToggleLeft, ToggleRight, UserRound, UsersRound, X } from '@lucide/vue';

type Team = { id: number; name: string; color: string };
type Guardian = { id: number; name: string; phone: string | null; relationship?: string };
type Athlete = { id: number; membership_number: string; first_name: string; last_name: string; full_name: string; national_code: string | null; phone: string | null; birth_date: string | null; gender: string; position: string | null; jersey_number: number | null; is_active: boolean; emergency_name: string | null; emergency_phone: string | null; medical_notes: string | null; teams: Team[]; guardians: Guardian[] };
type Paginated = { data: Athlete[]; links: { url: string | null; label: string; active: boolean }[]; total: number; from: number; to: number };

const props = defineProps<{ athletes: Paginated; teams: Team[]; guardians: Guardian[]; filters: { search?: string; team?: string; status?: string }; canManage: boolean }>();
const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string | null);
const showForm = ref(false);
const editing = ref<Athlete | null>(null);
const search = ref(props.filters.search || '');
const teamFilter = ref(props.filters.team || '');
const statusFilter = ref(props.filters.status || '');

const emptyForm = () => ({ first_name: '', last_name: '', national_code: '', phone: '', birth_date: '', gender: 'male', position: '', jersey_number: null as number | null, emergency_name: '', emergency_phone: '', medical_notes: '', team_id: props.teams[0]?.id || null, guardian_id: null as number | null, guardian_relationship: '' });
const form = useForm(emptyForm());
const positions = ['پاسور', 'قدرتی‌زن', 'سرعتی‌زن', 'پشت‌خط‌زن', 'دریافت‌کننده', 'لیبرو'];

const applyFilters = () => router.get('/athletes', { search: search.value || undefined, team: teamFilter.value || undefined, status: statusFilter.value || undefined }, { preserveState: true, replace: true });
const openCreate = () => { editing.value = null; form.defaults(emptyForm()); form.reset(); form.clearErrors(); showForm.value = true; };
const openEdit = (athlete: Athlete) => {
    editing.value = athlete;
    form.first_name = athlete.first_name; form.last_name = athlete.last_name; form.national_code = athlete.national_code || '';
    form.phone = athlete.phone || ''; form.birth_date = athlete.birth_date || ''; form.gender = athlete.gender; form.position = athlete.position || '';
    form.jersey_number = athlete.jersey_number; form.emergency_name = athlete.emergency_name || ''; form.emergency_phone = athlete.emergency_phone || '';
    form.medical_notes = athlete.medical_notes || ''; form.team_id = athlete.teams[0]?.id || null; form.guardian_id = athlete.guardians[0]?.id || null;
    form.guardian_relationship = athlete.guardians[0]?.relationship || ''; form.clearErrors(); showForm.value = true;
};
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { showForm.value = false; editing.value = null; form.reset(); } };
    editing.value ? form.put(`/athletes/${editing.value.id}`, options) : form.post('/athletes', options);
};
const toggle = (athlete: Athlete) => router.patch(`/athletes/${athlete.id}/toggle`, {}, { preserveScroll: true });
const paginationLabel = (label: string) => label.replace('&laquo; Previous', 'قبلی').replace('Next &raquo;', 'بعدی');
</script>

<template>
    <AppLayout title="ورزشکاران باشگاه" eyebrow="پرونده و عضویت تیمی" active="manager">
        <div v-if="successMessage" class="mb-4 flex items-center gap-2 border border-[#bcd9ca] bg-[#edf7f1] px-4 py-3 text-[11px] font-bold text-[#236b54]"><CheckCircle2 :size="16" />{{ successMessage }}</div>

        <section class="mb-5 grid gap-4 sm:grid-cols-3">
            <article class="access-stat"><div><span>کل پرونده‌ها</span><strong>{{ athletes.total }}</strong></div><UsersRound :size="22" /></article>
            <article class="access-stat"><div><span>نمایش در صفحه</span><strong>{{ athletes.data.length }}</strong></div><UserRound :size="22" /></article>
            <article class="access-stat"><div><span>تیم‌های در دسترس</span><strong>{{ teams.length }}</strong></div><ShieldCheck :size="22" /></article>
        </section>

        <section class="panel-flat">
            <header class="users-toolbar">
                <form class="flex flex-1 flex-col gap-2 md:flex-row" @submit.prevent="applyFilters">
                    <div class="relative min-w-0 flex-1"><Search class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8a959b]" :size="16" /><input v-model="search" class="table-filter pr-10" placeholder="نام، کد عضویت یا شماره تماس"></div>
                    <select v-model="teamFilter" class="table-filter md:max-w-44" @change="applyFilters"><option value="">همه تیم‌ها</option><option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option></select>
                    <div class="relative"><Filter class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8a959b]" :size="15" /><select v-model="statusFilter" class="table-filter min-w-36 pr-9" @change="applyFilters"><option value="">همه وضعیت‌ها</option><option value="active">فعال</option><option value="inactive">غیرفعال</option></select></div>
                    <button class="secondary-button justify-center">جست‌وجو</button>
                </form>
                <button v-if="canManage" class="primary-sport-button" @click="openCreate"><Plus :size="17" /> ثبت ورزشکار</button>
            </header>

            <div class="overflow-x-auto">
                <table class="sport-table athletes-table">
                    <thead><tr><th>ورزشکار</th><th>کد عضویت</th><th>تیم</th><th>پست / پیراهن</th><th>سرپرست</th><th>وضعیت</th><th /></tr></thead>
                    <tbody>
                        <tr v-for="athlete in athletes.data" :key="athlete.id">
                            <td><div class="flex items-center gap-3"><div class="athlete-list-avatar">{{ athlete.first_name.slice(0, 1) }}{{ athlete.last_name.slice(0, 1) }}</div><div><strong class="block">{{ athlete.full_name }}</strong><span class="mt-1 block text-[9px] text-[#8a959b]" dir="ltr">{{ athlete.phone || 'بدون شماره تماس' }}</span></div></div></td>
                            <td><span class="membership-code">{{ athlete.membership_number }}</span></td>
                            <td><div class="flex flex-wrap gap-1"><span v-for="team in athlete.teams" :key="team.id" class="team-chip"><i class="size-1.5 rounded-full" :style="{ background: team.color }" />{{ team.name }}</span></div></td>
                            <td><strong>{{ athlete.position || 'تعیین نشده' }}</strong><span v-if="athlete.jersey_number" class="mr-2 text-[#d76529]">#{{ athlete.jersey_number }}</span></td>
                            <td>{{ athlete.guardians[0]?.name || 'ثبت نشده' }}</td>
                            <td><span :class="athlete.is_active ? 'tag-ok' : 'tag-danger'">{{ athlete.is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                            <td><div class="flex items-center gap-1"><Link :href="`/athletes/${athlete.id}`" class="row-action" title="مشاهده پرونده"><ChevronLeft :size="16" /></Link><button v-if="canManage" class="row-action" title="ویرایش" @click="openEdit(athlete)"><Pencil :size="14" /></button><button v-if="canManage" class="p-1" @click="toggle(athlete)"><ToggleRight v-if="athlete.is_active" :size="23" class="text-[#247c62]" /><ToggleLeft v-else :size="23" class="text-[#9aa3a8]" /></button></div></td>
                        </tr>
                        <tr v-if="!athletes.data.length"><td colspan="7" class="py-14 text-center text-[#8a959b]">ورزشکاری با این فیلتر پیدا نشد.</td></tr>
                    </tbody>
                </table>
            </div>
            <footer class="flex flex-col gap-3 border-t border-[#e2e5e6] px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-[9px] text-[#7d8990]">نمایش {{ athletes.from || 0 }} تا {{ athletes.to || 0 }} از {{ athletes.total }} پرونده</p><div class="flex flex-wrap gap-1"><Link v-for="link in athletes.links" :key="link.label" :href="link.url || '#'" class="pagination-link" :class="{ 'pagination-active': link.active, 'pointer-events-none opacity-40': !link.url }" v-html="paginationLabel(link.label)" /></div></footer>
        </section>

        <div v-if="showForm" class="fixed inset-0 z-[70] flex items-end justify-center bg-[#07192b]/50 sm:items-center sm:p-5" @click.self="showForm = false">
            <section class="max-h-[94vh] w-full overflow-y-auto bg-[#faf9f6] sm:max-w-3xl">
                <header class="flex items-center justify-between border-b border-[#dfe3e6] px-5 py-4"><div><p class="section-kicker">پرونده عضویت</p><h2 class="mt-1 text-base font-black">{{ editing ? 'ویرایش ورزشکار' : 'ثبت ورزشکار جدید' }}</h2></div><button class="p-2 text-[#74818a]" @click="showForm = false"><X :size="20" /></button></header>
                <form class="space-y-5 p-5" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="form-label">نام<input v-model="form.first_name" class="form-input"><small v-if="form.errors.first_name">{{ form.errors.first_name }}</small></label>
                        <label class="form-label">نام خانوادگی<input v-model="form.last_name" class="form-input"><small v-if="form.errors.last_name">{{ form.errors.last_name }}</small></label>
                        <label class="form-label">کد ملی<input v-model="form.national_code" class="form-input" maxlength="10" dir="ltr"><small v-if="form.errors.national_code">{{ form.errors.national_code }}</small></label>
                        <label class="form-label">شماره تماس<input v-model="form.phone" class="form-input" dir="ltr"><small v-if="form.errors.phone">{{ form.errors.phone }}</small></label>
                        <label class="form-label">تاریخ تولد<input v-model="form.birth_date" type="date" class="form-input" dir="ltr"><small v-if="form.errors.birth_date">{{ form.errors.birth_date }}</small></label>
                        <label class="form-label">جنسیت<select v-model="form.gender" class="form-input"><option value="male">پسر</option><option value="female">دختر</option></select></label>
                        <label class="form-label">تیم<select v-model="form.team_id" class="form-input"><option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option></select><small v-if="form.errors.team_id">{{ form.errors.team_id }}</small></label>
                        <label class="form-label">پست تخصصی<select v-model="form.position" class="form-input"><option value="">تعیین نشده</option><option v-for="position in positions" :key="position">{{ position }}</option></select></label>
                        <label class="form-label">شماره پیراهن<input v-model="form.jersey_number" type="number" min="1" max="99" class="form-input"></label>
                    </div>
                    <div class="border-r-2 border-[#e87532] bg-white p-4"><p class="mb-4 text-[10px] font-black text-[#20384a]">سرپرست و تماس اضطراری</p><div class="grid gap-4 sm:grid-cols-2"><label class="form-label">حساب سرپرست<select v-model="form.guardian_id" class="form-input"><option :value="null">بدون اتصال</option><option v-for="guardian in guardians" :key="guardian.id" :value="guardian.id">{{ guardian.name }}</option></select></label><label class="form-label">نسبت<input v-model="form.guardian_relationship" class="form-input" placeholder="مثلاً پدر"></label><label class="form-label">نام تماس اضطراری<input v-model="form.emergency_name" class="form-input"></label><label class="form-label">شماره اضطراری<input v-model="form.emergency_phone" class="form-input" dir="ltr"></label></div></div>
                    <label class="form-label">ملاحظات پزشکی<textarea v-model="form.medical_notes" class="form-textarea" rows="3" placeholder="حساسیت، آسیب‌دیدگی یا نکته ضروری برای مربی"></textarea></label>
                    <div class="flex justify-end gap-2 border-t border-[#e2e5e6] pt-4"><button type="button" class="secondary-button" @click="showForm = false">انصراف</button><button class="primary-sport-button" :disabled="form.processing">{{ form.processing ? 'در حال ثبت...' : (editing ? 'ذخیره تغییرات' : 'ایجاد پرونده') }}</button></div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
