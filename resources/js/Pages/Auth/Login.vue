<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff, LockKeyhole, Mail, ShieldCheck, Volleyball } from '@lucide/vue';
import { ref } from 'vue';

const showPassword = ref(false);
const form = useForm({ email: 'admin@volleyball.ir', password: 'password', remember: true });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="ورود به سامانه" />
    <main class="grid min-h-screen bg-[#f4f3ef] lg:grid-cols-[1fr_480px]" dir="rtl">
        <section class="relative hidden overflow-hidden bg-[#092740] p-16 lg:flex lg:flex-col lg:justify-between">
            <div class="login-court" />
            <div class="relative z-10 flex items-center gap-3"><div class="brand-mark brand-mark-lg"><Volleyball :size="29" /></div><div><p class="text-xl font-black text-white">والیبال من</p><p class="text-[10px] text-[#a9bac6]">هیئت والیبال استان</p></div></div>
            <div class="relative z-10 max-w-2xl">
                <p class="section-kicker">سامانه عملیاتی باشگاه</p>
                <h1 class="mt-5 text-4xl font-black leading-[1.55] text-white">از ثبت حضور کنار زمین<br>تا تحلیل رشد ورزشکار.</h1>
                <p class="mt-5 max-w-lg text-sm leading-8 text-[#b7c5d0]">یک فضای یکپارچه برای مربی، مدیر، مسئول مالی و خانواده ورزشکار.</p>
                <div class="mt-10 grid max-w-xl grid-cols-3 border border-white/15"><div class="login-stat"><strong>۲۸۶</strong><span>ورزشکار</span></div><div class="login-stat border-x border-white/15"><strong>۱۲</strong><span>تیم فعال</span></div><div class="login-stat"><strong>۸۹٪</strong><span>نرخ حضور</span></div></div>
            </div>
            <div class="relative z-10 flex items-center gap-2 text-[10px] text-[#91a5b4]"><ShieldCheck :size="14" class="text-[#e98343]" /> نسخه نمایشی امن · مهر ۱۴۰۵</div>
        </section>

        <section class="flex items-center justify-center p-5 sm:p-10">
            <div class="w-full max-w-[370px]">
                <div class="mb-10 flex items-center gap-3 lg:hidden"><div class="brand-mark"><Volleyball :size="23" /></div><div><p class="text-lg font-black">والیبال من</p><p class="text-[10px] text-[#74818a]">هیئت والیبال استان</p></div></div>
                <p class="section-kicker">ورود اعضای باشگاه</p>
                <h2 class="mt-3 text-2xl font-black text-[#102236]">خوش آمدید</h2>
                <p class="mt-2 text-xs leading-6 text-[#74818a]">برای ادامه، اطلاعات حساب کاربری خود را وارد کنید.</p>
                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div><label class="mb-2 block text-[10px] font-black text-[#41525e]">ایمیل</label><div class="relative"><Mail class="absolute right-4 top-1/2 -translate-y-1/2 text-[#8b969e]" :size="17" /><input v-model="form.email" type="email" class="field" autocomplete="email"></div><p v-if="form.errors.email" class="mt-2 text-[10px] font-bold text-[#b84934]">{{ form.errors.email }}</p></div>
                    <div><div class="mb-2 flex items-center justify-between"><label class="text-[10px] font-black text-[#41525e]">رمز عبور</label><span class="text-[9px] text-[#8a949a]">بازیابی رمز در نسخه بعدی</span></div><div class="relative"><LockKeyhole class="absolute right-4 top-1/2 -translate-y-1/2 text-[#8b969e]" :size="17" /><input v-model="form.password" :type="showPassword ? 'text' : 'password'" class="field pl-12" autocomplete="current-password"><button type="button" class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8b969e]" @click="showPassword=!showPassword"><EyeOff v-if="showPassword" :size="17"/><Eye v-else :size="17"/></button></div></div>
                    <label class="flex items-center gap-2 text-[10px] font-bold text-[#66747e]"><input v-model="form.remember" type="checkbox" class="size-4 accent-[#e87532]"> مرا به خاطر بسپار</label>
                    <button class="primary-sport-button w-full py-3" :disabled="form.processing"><span>{{ form.processing ? 'در حال ورود...' : 'ورود به باشگاه' }}</span><ArrowLeft :size="17" /></button>
                </form>
                <div class="mt-7 border-r-2 border-[#e87532] bg-white px-4 py-3 text-[10px] leading-6 text-[#64727c] shadow-[0_5px_20px_rgba(20,38,52,.05)]"><strong class="text-[#102236]">حساب نمایشی</strong><br><span dir="ltr">admin@volleyball.ir · password</span></div>
            </div>
        </section>
    </main>
</template>
