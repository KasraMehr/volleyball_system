<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Building2, CalendarRange, CheckCircle2, CirclePower, MapPin, Plus, Save, Settings2, UsersRound, X } from '@lucide/vue';

type Club = { name:string; province:string|null; city:string|null; phone:string|null; email:string|null; website:string|null; registration_number:string|null; address:string|null };
type Venue = { id:number; name:string; address:string|null; capacity:number|null; floor_type:string|null; is_active:boolean };
type Season = { id:number; title:string; starts_on:string; ends_on:string; is_active:boolean };
const props = defineProps<{club:Club;venues:Venue[];seasons:Season[]}>();
const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string|null);
const showVenue = ref(false);
const showSeason = ref(false);
const clubForm = useForm({...props.club});
const venueForm = useForm({name:'',address:'',capacity:null as number|null,floor_type:'کف‌پوش استاندارد'});
const seasonForm = useForm({title:'',starts_on:'',ends_on:'',is_active:true});
const saveClub = () => clubForm.put('/settings/club');
const addVenue = () => venueForm.post('/settings/venues', {onSuccess:()=>{showVenue.value=false;venueForm.reset();}});
const addSeason = () => seasonForm.post('/settings/seasons', {onSuccess:()=>{showSeason.value=false;seasonForm.reset();}});
const toggleVenue = (venue:Venue) => router.patch(`/settings/venues/${venue.id}/toggle`);
const activateSeason = (season:Season) => router.patch(`/settings/seasons/${season.id}/activate`);
const dateLabel = (value:string) => new Intl.DateTimeFormat('fa-IR',{year:'numeric',month:'long',day:'numeric'}).format(new Date(`${value}T12:00:00`));
</script>

<template>
    <AppLayout title="تنظیمات باشگاه" eyebrow="پیکربندی پایه سامانه">
        <div v-if="successMessage" class="mb-4 flex items-center gap-2 border border-[#bcd9ca] bg-[#edf7f1] px-4 py-3 text-[11px] font-bold text-[#236b54]"><CheckCircle2 :size="16"/>{{ successMessage }}</div>

        <section class="settings-hero">
            <div><p class="section-kicker !text-[#f2a070]">هویت باشگاه</p><h2 class="mt-2 text-xl font-black text-white">مرکز تنظیمات والیبال من</h2><p class="mt-2 text-[10px] leading-6 text-[#aec0cc]">اطلاعاتی که در رسیدها، گزارش‌ها و برنامه تمرین نمایش داده می‌شود از این بخش مدیریت می‌گردد.</p></div>
            <Settings2 :size="66" class="text-white/10" stroke-width="1"/>
        </section>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.15fr_.85fr]">
            <section class="panel-flat">
                <div class="panel-head"><div><h3>مشخصات باشگاه</h3><p>اطلاعات رسمی و راه‌های ارتباطی</p></div><Building2 :size="20" class="text-[#d5652b]"/></div>
                <form class="space-y-5 p-5" @submit.prevent="saveClub">
                    <div class="grid gap-4 sm:grid-cols-2"><label class="form-label sm:col-span-2">نام رسمی باشگاه<input v-model="clubForm.name" class="form-input"><small v-if="clubForm.errors.name">{{ clubForm.errors.name }}</small></label><label class="form-label">استان<input v-model="clubForm.province" class="form-input"></label><label class="form-label">شهر<input v-model="clubForm.city" class="form-input"></label><label class="form-label">تلفن<input v-model="clubForm.phone" class="form-input" dir="ltr"></label><label class="form-label">ایمیل<input v-model="clubForm.email" type="email" class="form-input" dir="ltr"><small v-if="clubForm.errors.email">{{ clubForm.errors.email }}</small></label><label class="form-label">وب‌سایت<input v-model="clubForm.website" class="form-input" dir="ltr" placeholder="https://"><small v-if="clubForm.errors.website">{{ clubForm.errors.website }}</small></label><label class="form-label">شماره ثبت<input v-model="clubForm.registration_number" class="form-input"></label></div>
                    <label class="form-label">نشانی کامل<textarea v-model="clubForm.address" class="form-textarea" rows="3"></textarea></label>
                    <div class="flex justify-end border-t border-[#e2e5e6] pt-4"><button class="primary-sport-button" :disabled="clubForm.processing"><Save :size="15"/>{{ clubForm.processing?'در حال ذخیره...':'ذخیره مشخصات' }}</button></div>
                </form>
            </section>

            <div class="space-y-5">
                <section class="panel-flat"><div class="panel-head"><div><h3>خلاصه تنظیمات</h3><p>وضعیت جاری باشگاه</p></div><span class="counter-badge">۳</span></div><div class="grid grid-cols-3 divide-x divide-x-reverse divide-[#e4e7e7]"><div class="settings-metric"><MapPin :size="18"/><strong>{{ venues.filter(v=>v.is_active).length }}</strong><span>سالن فعال</span></div><div class="settings-metric"><CalendarRange :size="18"/><strong>{{ seasons.filter(s=>s.is_active).length }}</strong><span>فصل فعال</span></div><div class="settings-metric"><UsersRound :size="18"/><strong>{{ club.city||'—' }}</strong><span>شهر</span></div></div></section>
                <section class="border border-[#d9dfe1] bg-[#f8f7f3] p-5"><p class="text-[9px] font-black text-[#d5652b]">پیشنهاد راه‌اندازی</p><h3 class="mt-2 text-xs font-black">اطلاعات واقعی هیئت را وارد کنید</h3><p class="mt-2 text-[9px] leading-6 text-[#74818a]">نام، شماره تماس و نشانی در گزارش‌ها و رسیدهای رسمی چاپ می‌شود. پیش از ارائه نهایی، مقادیر آزمایشی را جایگزین کنید.</p></section>
            </div>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-2">
            <section class="panel-flat"><div class="panel-head"><div><h3>سالن‌های تمرین</h3><p>سالن‌های قابل انتخاب هنگام ساخت جلسه</p></div><button class="small-action" @click="showVenue=true"><Plus :size="14"/> سالن جدید</button></div><div class="divide-y divide-[#e5e8e8]"><article v-for="venue in venues" :key="venue.id" class="setting-row"><div class="setting-icon"><MapPin :size="18"/></div><div class="min-w-0 flex-1"><div class="flex items-center gap-2"><strong>{{ venue.name }}</strong><span :class="venue.is_active?'tag-ok':'status-tag'">{{ venue.is_active?'فعال':'غیرفعال' }}</span></div><p>{{ venue.address||'بدون نشانی' }} · {{ venue.floor_type||'نوع کف ثبت نشده' }}<template v-if="venue.capacity"> · ظرفیت {{ venue.capacity }} نفر</template></p></div><button class="account-toggle" :class="venue.is_active?'text-[#24745d]':'text-[#9aa2a6]'" @click="toggleVenue(venue)"><CirclePower :size="19"/></button></article><p v-if="!venues.length" class="p-10 text-center text-[10px] text-[#879198]">هنوز سالنی ثبت نشده است.</p></div></section>

            <section class="panel-flat"><div class="panel-head"><div><h3>فصل‌های ورزشی</h3><p>فصل فعال برای برنامه‌ریزی و گزارش‌گیری</p></div><button class="small-action" @click="showSeason=true"><Plus :size="14"/> فصل جدید</button></div><div class="divide-y divide-[#e5e8e8]"><article v-for="season in seasons" :key="season.id" class="setting-row"><div class="setting-icon"><CalendarRange :size="18"/></div><div class="min-w-0 flex-1"><div class="flex items-center gap-2"><strong>{{ season.title }}</strong><span v-if="season.is_active" class="tag-ok">فصل جاری</span></div><p>{{ dateLabel(season.starts_on) }} تا {{ dateLabel(season.ends_on) }}</p></div><button v-if="!season.is_active" class="small-action" @click="activateSeason(season)">انتخاب فصل</button><CheckCircle2 v-else :size="20" class="text-[#24745d]"/></article><p v-if="!seasons.length" class="p-10 text-center text-[10px] text-[#879198]">هنوز فصلی ثبت نشده است.</p></div></section>
        </div>

        <div v-if="showVenue" class="modal-shell" @click.self="showVenue=false"><section class="modal-card sm:max-w-lg"><header class="modal-head"><div><p class="section-kicker">فضای تمرین</p><h2>ثبت سالن جدید</h2></div><button @click="showVenue=false"><X :size="20"/></button></header><form class="space-y-4 p-5" @submit.prevent="addVenue"><label class="form-label">نام سالن<input v-model="venueForm.name" class="form-input"><small v-if="venueForm.errors.name">{{ venueForm.errors.name }}</small></label><label class="form-label">نشانی<input v-model="venueForm.address" class="form-input"></label><div class="grid gap-4 sm:grid-cols-2"><label class="form-label">ظرفیت<input v-model="venueForm.capacity" type="number" class="form-input"></label><label class="form-label">نوع کف<input v-model="venueForm.floor_type" class="form-input"></label></div><div class="flex justify-end gap-2 border-t border-[#e2e5e6] pt-4"><button type="button" class="secondary-button" @click="showVenue=false">انصراف</button><button class="primary-sport-button">ثبت سالن</button></div></form></section></div>
        <div v-if="showSeason" class="modal-shell" @click.self="showSeason=false"><section class="modal-card sm:max-w-lg"><header class="modal-head"><div><p class="section-kicker">تقویم باشگاه</p><h2>ثبت فصل ورزشی</h2></div><button @click="showSeason=false"><X :size="20"/></button></header><form class="space-y-4 p-5" @submit.prevent="addSeason"><label class="form-label">عنوان فصل<input v-model="seasonForm.title" class="form-input" placeholder="مثلاً فصل ۱۴۰۶–۱۴۰۷"><small v-if="seasonForm.errors.title">{{ seasonForm.errors.title }}</small></label><div class="grid gap-4 sm:grid-cols-2"><label class="form-label">تاریخ شروع<input v-model="seasonForm.starts_on" type="date" class="form-input" dir="ltr"></label><label class="form-label">تاریخ پایان<input v-model="seasonForm.ends_on" type="date" class="form-input" dir="ltr"><small v-if="seasonForm.errors.ends_on">{{ seasonForm.errors.ends_on }}</small></label></div><label class="flex items-center gap-2 text-[10px] font-bold"><input v-model="seasonForm.is_active" type="checkbox"> این فصل به‌عنوان فصل جاری انتخاب شود</label><div class="flex justify-end gap-2 border-t border-[#e2e5e6] pt-4"><button type="button" class="secondary-button" @click="showSeason=false">انصراف</button><button class="primary-sport-button">ثبت فصل</button></div></form></section></div>
    </AppLayout>
</template>
