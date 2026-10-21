<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Bell, BellRing, CalendarClock, CheckCheck, CheckCircle2, ChevronLeft, CircleDollarSign, Megaphone, Plus, Send, UsersRound, X } from '@lucide/vue';

type NotificationRow={id:number;type:string;title:string;message:string;action_url:string|null;sender:string|null;sent_at:string;read:boolean};
type Paginated={data:NotificationRow[];links:Array<{url:string|null;label:string;active:boolean}>;total:number;from:number;to:number};
const props=defineProps<{notifications:Paginated;summary:{unread:number;total:number;payment:number};permissions:{announce:boolean;paymentReminder:boolean;trainingReminder:boolean};teams:Array<{id:number;name:string}>;roles:Array<{value:string;label:string}>;automation:{overdueInvoices:number;upcomingSessions:number}}> ();
const page=usePage();
const successMessage=computed(()=>page.props.flash?.success as string|null);
const showCompose=ref(false);
const form=useForm({title:'',message:'',audience:'all',role:'athlete',team_id:props.teams[0]?.id||null});
const sendAnnouncement=()=>form.post('/notifications/announce',{onSuccess:()=>{showCompose.value=false;form.reset();}});
const paymentReminders=()=>router.post('/notifications/payment-reminders');
const trainingReminders=()=>router.post('/notifications/training-reminders');
const readAll=()=>router.patch('/notifications/read-all');
const read=(item:NotificationRow)=>router.patch(`/notifications/${item.id}/read`);
const icon=(type:string)=>({payment_reminder:CircleDollarSign,training_reminder:CalendarClock,announcement:Megaphone}[type]||Bell);
const typeLabel=(type:string)=>({payment_reminder:'امور مالی',training_reminder:'برنامه تمرین',announcement:'اطلاعیه باشگاه'}[type]||'اعلان');
const dateLabel=(value:string)=>new Intl.DateTimeFormat('fa-IR',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}).format(new Date(value.replace(' ','T')));
const paginationLabel=(label:string)=>label.replace('&laquo; Previous','قبلی').replace('Next &raquo;','بعدی');
watch(()=>form.audience,(value)=>{if(value==='team'&&!form.team_id)form.team_id=props.teams[0]?.id||null;});
</script>

<template>
<AppLayout title="اعلان‌ها و یادآوری‌ها" eyebrow="ارتباط منظم با اعضای باشگاه">
    <div v-if="successMessage" class="mb-4 flex items-center gap-2 border border-[#bcd9ca] bg-[#edf7f1] px-4 py-3 text-[11px] font-bold text-[#236b54]"><CheckCircle2 :size="16"/>{{successMessage}}</div>
    <section class="notification-hero"><div><p class="section-kicker !text-[#f4a475]">مرکز ارتباطات</p><h2 class="mt-2 text-xl font-black text-white">هیچ پیگیری مهمی فراموش نمی‌شود</h2><p class="mt-2 max-w-xl text-[10px] leading-6 text-[#aec0cc]">اعلان‌های داخلی باشگاه، یادآوری شهریه و برنامه تمرین در یک صندوق متمرکز در دسترس همه نقش‌ها قرار دارد.</p></div><BellRing :size="72" class="text-white/10" stroke-width="1"/></section>

    <div class="mt-5 grid gap-3 sm:grid-cols-3"><article class="access-stat"><div><span>خوانده‌نشده</span><strong>{{summary.unread}}</strong></div><BellRing :size="22"/></article><article class="access-stat"><div><span>همه اعلان‌ها</span><strong>{{summary.total}}</strong></div><Bell :size="22"/></article><article class="access-stat"><div><span>یادآوری مالی</span><strong>{{summary.payment}}</strong></div><CircleDollarSign :size="22"/></article></div>

    <div v-if="permissions.announce||permissions.paymentReminder||permissions.trainingReminder" class="mt-5 grid gap-3 lg:grid-cols-3">
        <button v-if="permissions.announce" class="notification-action" @click="showCompose=true"><span class="quick-icon"><Megaphone :size="20"/></span><span class="flex-1 text-right"><strong>ارسال اطلاعیه</strong><small>برای همه، یک نقش یا اعضای تیم</small></span><Plus :size="17"/></button>
        <button v-if="permissions.paymentReminder" class="notification-action" :disabled="!automation.overdueInvoices" @click="paymentReminders"><span class="quick-icon"><CircleDollarSign :size="20"/></span><span class="flex-1 text-right"><strong>یادآوری شهریه</strong><small>{{automation.overdueInvoices}} صورتحساب معوق آماده پیگیری</small></span><Send :size="17"/></button>
        <button v-if="permissions.trainingReminder" class="notification-action" :disabled="!automation.upcomingSessions" @click="trainingReminders"><span class="quick-icon"><CalendarClock :size="20"/></span><span class="flex-1 text-right"><strong>یادآوری تمرین</strong><small>{{automation.upcomingSessions}} جلسه در سه روز آینده</small></span><Send :size="17"/></button>
    </div>

    <section class="panel-flat mt-5"><div class="panel-head"><div><h3>صندوق اعلان‌های من</h3><p>پیام‌ها و یادآوری‌های مرتبط با حساب شما</p></div><button v-if="summary.unread" class="small-action" @click="readAll"><CheckCheck :size="14"/>خواندن همه</button></div>
        <div class="divide-y divide-[#e5e8e8]"><button v-for="item in notifications.data" :key="item.id" class="notification-row" :class="{'notification-unread':!item.read}" @click="read(item)"><div class="notification-type"><component :is="icon(item.type)" :size="19"/></div><div class="min-w-0 flex-1 text-right"><div class="flex flex-wrap items-center gap-2"><span class="text-[8px] font-black text-[#d5652b]">{{typeLabel(item.type)}}</span><span v-if="!item.read" class="size-1.5 rounded-full bg-[#e87532]"/></div><strong>{{item.title}}</strong><p>{{item.message}}</p><small>{{dateLabel(item.sent_at)}}<template v-if="item.sender"> · توسط {{item.sender}}</template></small></div><ChevronLeft v-if="item.action_url" :size="18" class="text-[#859198]"/></button><div v-if="!notifications.data.length" class="p-12 text-center"><Bell :size="28" class="mx-auto text-[#a5adb1]"/><p class="mt-3 text-[10px] text-[#879198]">هنوز اعلانی برای شما ثبت نشده است.</p></div></div>
        <footer v-if="notifications.total" class="flex flex-col gap-3 border-t border-[#e2e5e6] px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-[9px] text-[#7d8990]">نمایش {{notifications.from||0}} تا {{notifications.to||0}} از {{notifications.total}}</p><div class="flex gap-1"><Link v-for="link in notifications.links" :key="link.label" :href="link.url||'#'" class="pagination-link" :class="{'pagination-active':link.active,'pointer-events-none opacity-40':!link.url}" v-html="paginationLabel(link.label)"/></div></footer>
    </section>

    <div v-if="showCompose" class="modal-shell" @click.self="showCompose=false"><section class="modal-card sm:max-w-xl"><header class="modal-head"><div><p class="section-kicker">پیام باشگاه</p><h2>ارسال اطلاعیه جدید</h2></div><button @click="showCompose=false"><X :size="20"/></button></header><form class="space-y-4 p-5" @submit.prevent="sendAnnouncement"><label class="form-label">عنوان<input v-model="form.title" class="form-input" placeholder="عنوان کوتاه و مشخص"><small v-if="form.errors.title">{{form.errors.title}}</small></label><label class="form-label">متن پیام<textarea v-model="form.message" class="form-textarea" rows="5" placeholder="متن کامل اطلاعیه"></textarea><small v-if="form.errors.message">{{form.errors.message}}</small></label><div class="grid gap-4 sm:grid-cols-2"><label class="form-label">مخاطبان<select v-model="form.audience" class="form-input"><option value="all">همه کاربران</option><option value="role">یک نقش خاص</option><option value="team">اعضای یک تیم</option></select></label><label v-if="form.audience==='role'" class="form-label">نقش<select v-model="form.role" class="form-input"><option v-for="role in roles" :key="role.value" :value="role.value">{{role.label}}</option></select></label><label v-if="form.audience==='team'" class="form-label">تیم<select v-model="form.team_id" class="form-input"><option v-for="team in teams" :key="team.id" :value="team.id">{{team.name}}</option></select></label></div><div class="flex justify-end gap-2 border-t border-[#e2e5e6] pt-4"><button type="button" class="secondary-button" @click="showCompose=false">انصراف</button><button class="primary-sport-button" :disabled="form.processing"><Send :size="15"/>{{form.processing?'در حال ارسال...':'ارسال اطلاعیه'}}</button></div></form></section></div>
</AppLayout>
</template>
