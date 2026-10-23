<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MemberLayout from '../../Layouts/MemberLayout.vue';
import { ArrowLeft, CalendarDays, Check, CheckCircle2, CreditCard, ReceiptText, ShieldCheck, WalletCards } from '@lucide/vue';
type Invoice={id:number;title:string;payable:number;paid:number;outstanding:number;due_date:string;status:string;payment_token:string;athlete:{name:string;membership_number:string};team:string|null};
type Profile={id:number;name:string;photo_url:string|null};
const props=defineProps<{invoices:Invoice[];summary:{total:number;paid:number;outstanding:number};viewer:{mode:string};profiles:Profile[];selectedAthlete:{id:number;name:string}|null}>();
const money=(value:number)=>new Intl.NumberFormat('fa-IR').format(value);
const dateLabel=(value:string)=>new Intl.DateTimeFormat('fa-IR',{day:'numeric',month:'long',year:'numeric'}).format(new Date(`${value}T12:00:00`));
const status=(value:string)=>({paid:{label:'تسویه‌شده',class:'paid'},partial:{label:'پرداخت بخشی',class:'partial'},unpaid:{label:'پرداخت‌نشده',class:'unpaid'},overdue:{label:'سررسید گذشته',class:'overdue'}}[value]||{label:value,class:'unpaid'});
const selectProfile=(id:number)=>router.get('/my-payments',{athlete:id},{preserveState:false});
const paidPercent=(invoice:Invoice)=>invoice.payable?Math.min(100,Math.round(invoice.paid/invoice.payable*100)):100;
const isGuardian=computed(()=>props.viewer.mode==='guardian');
</script>

<template><MemberLayout title="شهریه و پرداخت‌ها" :athlete-id="selectedAthlete?.id">
    <section v-if="!selectedAthlete" class="member-empty"><WalletCards :size="36"/><h2>پرونده‌ای برای پرداخت متصل نیست</h2><p>پس از اتصال پرونده ورزشکار، صورتحساب‌ها و رسیدها در این صفحه نمایش داده می‌شوند.</p></section>
    <template v-else>
        <section v-if="isGuardian&&profiles.length>1" class="member-profile-switch"><span>شهریه کدام ورزشکار؟</span><button v-for="profile in profiles" :key="profile.id" :class="profile.id===selectedAthlete.id?'active':''" @click="selectProfile(profile.id)"><img v-if="profile.photo_url" :src="profile.photo_url"><span v-else>{{profile.name.slice(0,1)}}</span>{{profile.name}}</button></section>
        <header class="member-page-head"><div><span>حساب مالی {{selectedAthlete.name}}</span><h1>شهریه و پرداخت‌ها</h1><p>مانده‌ها، سررسیدها و سوابق پرداخت در یک نگاه</p></div><span class="member-page-icon orange"><WalletCards :size="25"/></span></header>
        <section class="member-money-summary"><article class="primary"><span>مانده قابل پرداخت</span><strong>{{money(summary.outstanding)}} <small>تومان</small></strong><p v-if="summary.outstanding">صورتحساب‌های باز را می‌توانید آنلاین پرداخت کنید.</p><p v-else><CheckCircle2 :size="14"/>همه شهریه‌ها تسویه شده‌اند.</p></article><article><span class="member-summary-icon green"><Check :size="18"/></span><div><small>پرداخت‌شده</small><strong>{{money(summary.paid)}}</strong><em>تومان</em></div></article><article><span class="member-summary-icon blue"><ReceiptText :size="18"/></span><div><small>کل صورتحساب</small><strong>{{money(summary.total)}}</strong><em>تومان</em></div></article></section>
        <section class="member-list-head"><div><span>صورتحساب‌ها</span><h2>{{invoices.length}} مورد برای {{selectedAthlete.name}}</h2></div><ShieldCheck :size="19"/></section>
        <div v-if="invoices.length" class="member-invoices"><article v-for="invoice in invoices" :key="invoice.id" class="member-invoice" :class="`is-${status(invoice.status).class}`"><div class="member-invoice-top"><div><span class="member-invoice-status">{{status(invoice.status).label}}</span><h2>{{invoice.title}}</h2><p>{{invoice.team||'باشگاه والیبال'}} · {{invoice.athlete.membership_number}}</p></div><div class="member-invoice-amount"><strong>{{money(invoice.outstanding||invoice.payable)}}</strong><small>تومان {{invoice.outstanding?'مانده':'پرداخت‌شده'}}</small></div></div><div class="member-invoice-progress"><i :style="{width:`${paidPercent(invoice)}%`}"/></div><div class="member-invoice-meta"><span><CalendarDays :size="14"/>سررسید {{dateLabel(invoice.due_date)}}</span><span>{{paidPercent(invoice)}}٪ پرداخت شده</span></div><a v-if="invoice.outstanding" :href="`/pay/${invoice.payment_token}`" class="member-pay-button"><CreditCard :size="17"/>پرداخت امن {{money(invoice.outstanding)}} تومان<ArrowLeft :size="16"/></a><div v-else class="member-paid-note"><CheckCircle2 :size="17"/>این صورتحساب با موفقیت تسویه شده است</div></article></div>
        <section v-else class="member-empty compact"><ReceiptText :size="30"/><h2>هنوز صورتحسابی ثبت نشده</h2><p>به محض صدور شهریه جدید، از طریق پیام‌های باشگاه هم مطلع می‌شوید.</p><Link href="/notifications">رفتن به پیام‌ها</Link></section>
        <div class="member-trust"><ShieldCheck :size="15"/>اطلاعات پرداخت فقط برای شما و مسئول مالی مجاز باشگاه قابل مشاهده است.</div>
    </template>
</MemberLayout></template>
