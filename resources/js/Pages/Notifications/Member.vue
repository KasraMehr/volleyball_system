<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MemberLayout from '../../Layouts/MemberLayout.vue';
import { Bell, CalendarClock, CheckCheck, ChevronLeft, CircleDollarSign, Megaphone, MessageCircle, Sparkles } from '@lucide/vue';
type Row={id:number;type:string;title:string;message:string;action_url:string|null;sender:string|null;sent_at:string;read:boolean};
type Paginated={data:Row[];links:Array<{url:string|null;label:string;active:boolean}>;total:number;from:number;to:number};
const props=defineProps<{notifications:Paginated;summary:{unread:number;total:number;payment:number}}>();
const filter=ref<'all'|'unread'>('all'); const page=usePage();
const rows=computed(()=>filter.value==='unread'?props.notifications.data.filter(item=>!item.read):props.notifications.data);
const icon=(type:string)=>({payment_reminder:CircleDollarSign,training_reminder:CalendarClock,announcement:Megaphone}[type]||Bell);
const label=(type:string)=>({payment_reminder:'شهریه',training_reminder:'تمرین',announcement:'باشگاه'}[type]||'پیام');
const dateLabel=(value:string)=>new Intl.DateTimeFormat('fa-IR',{weekday:'short',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}).format(new Date(value.replace(' ','T')));
const readAll=()=>router.patch('/notifications/read-all',{}, {preserveScroll:true});
const open=(item:Row)=>{if(item.read){if(item.action_url)router.visit(item.action_url);return;}router.patch(`/notifications/${item.id}/read`,{}, {preserveScroll:true,onSuccess:()=>{if(item.action_url)router.visit(item.action_url);}});};
const paginationLabel=(value:string)=>value.replace('&laquo; Previous','قبلی').replace('Next &raquo;','بعدی');
</script>
<template><MemberLayout title="پیام‌های باشگاه">
    <header class="member-message-hero"><div><span><Sparkles :size="15"/>همراه باشگاه بمان</span><h1>پیام‌های باشگاه</h1><p>برنامه تمرین، شهریه و خبرهای مهم را اینجا از دست نمی‌دهی.</p></div><div class="member-unread-count"><strong>{{summary.unread}}</strong><span>پیام تازه</span></div></header>
    <div v-if="page.props.flash?.success" class="member-success">{{page.props.flash.success}}</div>
    <section class="member-inbox-toolbar"><div><button :class="{active:filter==='all'}" @click="filter='all'">همه پیام‌ها <span>{{summary.total}}</span></button><button :class="{active:filter==='unread'}" @click="filter='unread'">خوانده‌نشده <span>{{summary.unread}}</span></button></div><button v-if="summary.unread" class="member-read-all" @click="readAll"><CheckCheck :size="16"/>خواندن همه</button></section>
    <section v-if="rows.length" class="member-inbox"><button v-for="item in rows" :key="item.id" :class="{'is-unread':!item.read}" @click="open(item)"><span class="member-message-icon" :class="item.type"><component :is="icon(item.type)" :size="20"/></span><div><header><span>{{label(item.type)}}</span><i v-if="!item.read"/> <time>{{dateLabel(item.sent_at)}}</time></header><h2>{{item.title}}</h2><p>{{item.message}}</p><small v-if="item.sender">از طرف {{item.sender}}</small></div><ChevronLeft v-if="item.action_url" :size="19"/></button></section>
    <section v-else class="member-empty compact"><MessageCircle :size="31"/><h2>{{filter==='unread'?'همه پیام‌ها را خوانده‌ای':'هنوز پیامی نداری'}}</h2><p>{{filter==='unread'?'برای دیدن پیام‌های قبلی، گزینه همه پیام‌ها را انتخاب کن.':'به محض ثبت برنامه یا اطلاعیه جدید، پیام آن اینجا نمایش داده می‌شود.'}}</p></section>
    <footer v-if="notifications.total" class="member-pagination"><span>نمایش {{notifications.from||0}} تا {{notifications.to||0}} از {{notifications.total}}</span><div><Link v-for="link in notifications.links" :key="link.label" :href="link.url||'#'" :class="{active:link.active,disabled:!link.url}" v-html="paginationLabel(link.label)"/></div></footer>
</MemberLayout></template>
