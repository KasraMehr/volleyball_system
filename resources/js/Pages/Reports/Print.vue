<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowRight, Printer, Volleyball } from '@lucide/vue';
defineProps<{ title:string; type:string; headers:string[]; rows:Array<Array<string|number>>; range:{from:string;to:string}; club:string; generatedAt:string }>();
</script>
<template>
    <Head :title="title" />
    <div class="min-h-screen bg-[#ececea] p-4 text-[#102236] md:p-8" dir="rtl">
        <div class="print-controls mx-auto mb-4 flex max-w-[1120px] items-center justify-between"><a href="/reports" class="secondary-button"><ArrowRight :size="15"/> بازگشت به گزارش‌ها</a><button class="primary-sport-button" @click="window.print()"><Printer :size="15"/> چاپ یا ذخیره PDF</button></div>
        <main class="report-sheet mx-auto max-w-[1120px] bg-white p-7 md:p-10">
            <header class="flex items-start justify-between gap-5 border-b-2 border-[#102b42] pb-6"><div><p class="text-[10px] font-bold text-[#df6d2b]">گزارش رسمی باشگاه</p><h1 class="mt-2 text-xl font-black">{{ title }}</h1><p class="mt-2 text-[10px] text-[#75828b]">از {{ range.from }} تا {{ range.to }}</p></div><div class="flex items-center gap-3 text-left"><div><strong class="block text-xs">{{ club }}</strong><span class="mt-1 block text-[8px] text-[#849098]">سامانه والیبال من</span></div><div class="brand-mark"><Volleyball :size="22"/></div></div></header>
            <div class="mt-6 overflow-x-auto"><table class="print-report-table"><thead><tr><th v-for="header in headers" :key="header">{{ header }}</th></tr></thead><tbody><tr v-for="(row,index) in rows" :key="index"><td v-for="(cell,column) in row" :key="column">{{ cell }}</td></tr><tr v-if="!rows.length"><td :colspan="headers.length" class="!py-12 text-center">داده‌ای در این بازه ثبت نشده است.</td></tr></tbody></table></div>
            <footer class="mt-8 flex justify-between border-t border-[#dfe3e5] pt-4 text-[8px] text-[#879198]"><span>تعداد ردیف‌ها: {{ rows.length }}</span><span>زمان تهیه: {{ generatedAt }}</span></footer>
        </main>
    </div>
</template>
