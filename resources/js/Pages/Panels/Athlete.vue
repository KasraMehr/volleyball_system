<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import MemberLayout from '../../Layouts/MemberLayout.vue';
import { ArrowLeft, Bell, CalendarDays, Camera, Check, CheckCircle2, ChevronLeft, CircleDollarSign, Clock3, CreditCard, Flame, MapPin, Medal, ShieldCheck, Sparkles, Target, TrendingUp, Upload, Volleyball } from '@lucide/vue';

type Attendance={status:string;date:string};
type Athlete={id:number;name:string;initials:string;membership_number:string;jersey_number:number|null;position:string|null;team:string|null;team_color:string|null;coach:string|null;is_active:boolean;photo_url:string|null;attendance_rate:number;sessions_count:number;streak:number;recent_attendance:Attendance[];assessment:{date:string;height_cm:number|null;vertical_jump_cm:number|null;spike_reach_cm:number|null;block_reach_cm:number|null;jump_change:number|null;coach_notes:string|null}|null};
type Invoice={title:string;outstanding:number;due_date:string};
type Session={date:string;starts_at:string;location:string|null;title:string};
type Profile={id:number;name:string;photo_url:string|null};
const props=defineProps<{viewer:{mode:string;name:string};profiles:Profile[];athlete:Athlete|null;invoice:Invoice|null;nextSession:Session|null;paymentHref:string;performanceHref:string}>();
const page=usePage();const success=computed(()=>page.props.flash?.success as string|null);
const fileInput=ref<HTMLInputElement|null>(null);const preview=ref<string|null>(null);const photoForm=useForm<{photo:File|null}>({photo:null});
const money=(value:number)=>new Intl.NumberFormat('fa-IR').format(value);
const dateLabel=(date:string)=>new Intl.DateTimeFormat('fa-IR',{weekday:'long',day:'numeric',month:'long'}).format(new Date(`${date}T12:00:00`));
const selectProfile=(id:number)=>router.get('/athlete',{athlete:id},{preserveState:false});
const choosePhoto=()=>fileInput.value?.click();
const uploadPhoto=(event:Event)=>{const file=(event.target as HTMLInputElement).files?.[0];if(!file||!props.athlete)return;preview.value=URL.createObjectURL(file);photoForm.photo=file;photoForm.post(`/athletes/${props.athlete.id}/photo`,{forceFormData:true,preserveScroll:true,onSuccess:()=>{photoForm.reset();}});};
const isGuardian=computed(()=>props.viewer.mode==='guardian');
const greeting=computed(()=>isGuardian.value?`همراه مسیر ${props.athlete?.name||'ورزشکار'} باشید`:`سلام ${props.athlete?.name.split(' ')[0]||''} 👋`);
const attendanceClass=(status:string)=>status==='absent'?'member-dot-absent':status==='excused'?'member-dot-excused':status==='late'?'member-dot-late':'member-dot-present';
</script>

<template><MemberLayout title="خانه ورزشکار" :payment-href="paymentHref" :performance-href="performanceHref">
    <section v-if="!athlete" class="member-empty"><Volleyball :size="36"/><h2>هنوز پرونده‌ای متصل نشده</h2><p>برای فعال‌شدن خانه ورزشکار، مدیر باشگاه باید این حساب را به پرونده ورزشی متصل کند.</p></section>
    <template v-else>
        <div v-if="success" class="member-success"><CheckCircle2 :size="17"/>{{success}}</div>
        <section v-if="isGuardian&&profiles.length>1" class="member-profile-switch"><span>مشاهده پرونده</span><button v-for="profile in profiles" :key="profile.id" :class="profile.id===athlete.id?'active':''" @click="selectProfile(profile.id)"><img v-if="profile.photo_url" :src="profile.photo_url"><span v-else>{{profile.name.slice(0,1)}}</span>{{profile.name}}</button></section>

        <section class="member-hero" :style="{'--team-color':athlete.team_color||'#e87532'}">
            <div class="member-hero-shape"/><div class="member-photo-wrap"><div class="member-photo"><img v-if="preview||athlete.photo_url" :src="preview||athlete.photo_url!" :alt="athlete.name"><span v-else>{{athlete.initials}}</span><div v-if="photoForm.processing" class="member-photo-loading"><Upload :size="18"/></div></div><button class="member-photo-button" :disabled="photoForm.processing" title="تغییر عکس" @click="choosePhoto"><Camera :size="15"/></button><input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="uploadPhoto"></div>
            <div class="relative z-10 min-w-0 flex-1"><p class="member-greeting">{{greeting}}</p><h1>{{athlete.name}}</h1><div class="member-tags"><span><Volleyball :size="13"/>{{athlete.team||'بدون تیم'}}</span><span v-if="athlete.position">{{athlete.position}}</span><span v-if="athlete.jersey_number">شماره {{athlete.jersey_number}}</span></div><p class="member-code">{{athlete.membership_number}}</p></div>
            <div class="member-active"><Check :size="14"/>عضویت فعال</div>
        </section>
        <p v-if="photoForm.errors.photo" class="member-photo-error">{{photoForm.errors.photo}}</p>

        <section class="member-focus-grid">
            <article class="member-next"><div class="member-card-icon"><CalendarDays :size="21"/></div><div class="min-w-0 flex-1"><span>تمرین بعدی</span><template v-if="nextSession"><h2>{{dateLabel(nextSession.date)}} · {{nextSession.starts_at}}</h2><p><MapPin :size="13"/>{{nextSession.location||'محل تمرین اعلام می‌شود'}} · {{nextSession.title}}</p></template><template v-else><h2>برنامه تازه‌ای ثبت نشده</h2><p>اعلان برنامه تمرین بعدی برایتان ارسال می‌شود.</p></template></div><ChevronLeft :size="20"/></article>
            <article class="member-fee" :class="{'member-fee-clear':!invoice}"><div><span>{{invoice?'شهریه قابل پرداخت':'وضعیت شهریه'}}</span><h2>{{invoice?`${money(invoice.outstanding)} تومان`:'تسویه کامل'}}</h2><p>{{invoice?`سررسید ${invoice.due_date}`:'بدهی فعالی ندارید'}}</p></div><Link :href="paymentHref">{{invoice?'پرداخت شهریه':'سوابق پرداخت'}}<ArrowLeft :size="15"/></Link></article>
        </section>

        <section class="member-section"><header><div><span>حال خوب ورزشی</span><h2>این ماه چطور پیش رفتی؟</h2></div><Link :href="performanceHref">جزئیات پیشرفت<ChevronLeft :size="15"/></Link></header><div class="member-stats"><article><span class="member-stat-icon green"><CheckCircle2 :size="19"/></span><strong>{{athlete.attendance_rate}}٪</strong><small>نرخ حضور</small></article><article><span class="member-stat-icon orange"><Flame :size="19"/></span><strong>{{athlete.streak}}</strong><small>حضور پیاپی</small></article><article><span class="member-stat-icon blue"><TrendingUp :size="19"/></span><strong>{{athlete.assessment?.vertical_jump_cm??'—'}}<i v-if="athlete.assessment?.vertical_jump_cm"> cm</i></strong><small>پرش عمودی</small></article><article><span class="member-stat-icon purple"><Medal :size="19"/></span><strong>{{athlete.assessment?.jump_change!=null?(athlete.assessment.jump_change>0?'+':'')+athlete.assessment.jump_change:'—'}}</strong><small>تغییر پرش</small></article></div></section>

        <div class="member-content-grid">
            <section class="member-section"><header><div><span>حضور در تمرین</span><h2>{{athlete.sessions_count}} جلسه ثبت‌شده</h2></div><strong class="member-rate">{{athlete.attendance_rate}}٪</strong></header><div v-if="athlete.recent_attendance.length" class="member-attendance"><div v-for="(item,index) in athlete.recent_attendance" :key="index"><i :class="attendanceClass(item.status)"><Check v-if="item.status==='present'" :size="14"/><Clock3 v-else-if="item.status==='late'" :size="13"/></i><span>{{new Intl.DateTimeFormat('fa-IR',{day:'numeric',month:'short'}).format(new Date(`${item.date}T12:00:00`))}}</span></div></div><p v-else class="member-muted">هنوز سابقه حضوری برای این ورزشکار ثبت نشده است.</p><div class="member-legend"><span><i class="member-dot-present"/>حاضر</span><span><i class="member-dot-late"/>تأخیر</span><span><i class="member-dot-absent"/>غایب</span></div></section>

            <section class="member-goal"><div class="member-goal-icon"><Target :size="23"/></div><span>هدف بعدی با مربی {{athlete.coach||''}}</span><h2>{{athlete.assessment?.coach_notes||'با استمرار در تمرین‌ها، رکورد بعدی نزدیک‌تر از همیشه است.'}}</h2><div><Sparkles :size="15"/>{{athlete.assessment?.jump_change&&athlete.assessment.jump_change>0?`${athlete.assessment.jump_change} سانتی‌متر پیشرفت پرش`:'مسیر پیشرفت فعال است'}}</div></section>
        </div>

        <section class="member-actions"><Link :href="performanceHref"><span class="blue"><TrendingUp :size="20"/></span><div><strong>رکوردها و پیشرفت</strong><small>نمودار پرش، اسپک و دفاع</small></div><ChevronLeft :size="18"/></Link><Link :href="paymentHref"><span class="orange"><CreditCard :size="20"/></span><div><strong>شهریه و پرداخت‌ها</strong><small>صورتحساب‌ها و رسیدها</small></div><ChevronLeft :size="18"/></Link><Link href="/notifications"><span class="green"><Bell :size="20"/></span><div><strong>پیام‌های باشگاه</strong><small>تمرین، اطلاعیه و یادآوری‌ها</small></div><ChevronLeft :size="18"/></Link></section>
        <div class="member-trust"><ShieldCheck :size="15"/>اطلاعات این پرونده فقط برای ورزشکار، والد متصل و افراد مجاز باشگاه نمایش داده می‌شود.</div>
    </template>
</MemberLayout></template>
