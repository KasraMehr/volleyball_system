<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Bell, ChartNoAxesCombined, CreditCard, Home, LogOut, Volleyball } from '@lucide/vue';

const props=withDefaults(defineProps<{title:string;paymentHref?:string;performanceHref?:string;athleteId?:number|null}>(),{paymentHref:'/my-payments',performanceHref:'/my-performance',athleteId:null});
const page=usePage();
const user=computed(()=>page.props.auth?.user as {name:string;role:string;unread_notifications:number}|null);
const path=computed(()=>page.url.split('?')[0]);
const withAthlete=(href:string)=>props.athleteId&&['/athlete','/my-payments','/my-performance'].includes(href)?`${href}?athlete=${props.athleteId}`:href;
const links=computed(()=>[{label:'خانه',href:withAthlete('/athlete'),icon:Home,base:'/athlete'},{label:'شهریه',href:withAthlete(props.paymentHref),icon:CreditCard,base:'/my-payments'},{label:'پیشرفت من',href:withAthlete(props.performanceHref),icon:ChartNoAxesCombined,base:'/my-performance'},{label:'پیام‌ها',href:'/notifications',icon:Bell,base:'/notifications'}]);
</script>

<template><Head :title="title"/><div class="member-shell min-h-screen text-[#102236]" dir="rtl">
    <header class="member-header"><div class="mx-auto flex h-full max-w-6xl items-center gap-3 px-4"><Link :href="withAthlete('/athlete')" class="flex items-center gap-2"><span class="member-brand"><Volleyball :size="21"/></span><span><strong>والیبال من</strong><small>باشگاه من</small></span></Link><nav class="mr-auto hidden items-center gap-1 md:flex"><Link v-for="item in links" :key="item.href" :href="item.href" class="member-top-link" :class="path===item.base?'member-top-active':''"><component :is="item.icon" :size="15"/>{{item.label}}</Link></nav><Link href="/notifications" class="member-bell"><Bell :size="18"/><i v-if="user?.unread_notifications">{{user.unread_notifications}}</i></Link><div class="hidden text-left sm:block"><strong class="block text-[10px]">{{user?.name}}</strong><span class="text-[8px] text-[#87939a]">حساب اعضای باشگاه</span></div><Link href="/logout" method="post" as="button" class="member-logout"><LogOut :size="17"/></Link></div></header>
    <main class="member-main mx-auto w-full max-w-6xl px-3 pb-24 pt-4 sm:px-5 md:pb-8 md:pt-6"><slot/></main>
    <nav class="member-bottom md:hidden"><Link v-for="item in links" :key="item.href" :href="item.href" :class="path===item.base?'member-bottom-active':''"><component :is="item.icon" :size="20"/><span>{{item.label}}</span><i v-if="item.base==='/notifications'&&user?.unread_notifications">{{user.unread_notifications}}</i></Link></nav>
</div></template>
