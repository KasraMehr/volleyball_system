<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { CheckCircle2, ChevronLeft, CircleUserRound, Filter, Plus, Search, ShieldCheck, ToggleLeft, ToggleRight, UsersRound, X } from '@lucide/vue';

type Role = { value: string; label: string };
type Team = { id: number; name: string };
type UserRow = { id: number; name: string; email: string; phone: string | null; role: string; role_label: string; is_active: boolean; can_toggle: boolean; teams: string[] };
type PaginatedUsers = { data: UserRow[]; links: { url: string | null; label: string; active: boolean }[]; total: number; from: number; to: number };

const props = defineProps<{ users: PaginatedUsers; roles: Role[]; teams: Team[]; filters: { search?: string; role?: string } }>();
const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string | null);
const showCreate = ref(false);
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');

const form = useForm({ name: '', email: '', phone: '', role: 'coach', password: '', team_ids: [] as number[] });

const applyFilters = () => router.get('/users', { search: search.value || undefined, role: roleFilter.value || undefined }, { preserveState: true, replace: true });
const submit = () => form.post('/users', { preserveScroll: true, onSuccess: () => { form.reset(); form.role = 'coach'; showCreate.value = false; } });
const toggle = (user: UserRow) => router.patch(`/users/${user.id}/toggle`, {}, { preserveScroll: true });
const roleClass = (role: string) => ({ club_admin: 'role-admin', finance: 'role-finance', coach: 'role-coach', athlete: 'role-athlete', guardian: 'role-guardian' }[role] || 'role-athlete');
const paginationLabel = (label: string) => label.replace('&laquo; Previous', 'قبلی').replace('Next &raquo;', 'بعدی');
</script>

<template>
    <AppLayout title="کاربران و دسترسی‌ها" eyebrow="تنظیم حساب‌های باشگاه" active="manager">
        <div v-if="successMessage" class="mb-4 flex items-center gap-2 border border-[#bcd9ca] bg-[#edf7f1] px-4 py-3 text-[11px] font-bold text-[#236b54]"><CheckCircle2 :size="16" />{{ successMessage }}</div>

        <section class="mb-5 grid gap-4 sm:grid-cols-3">
            <article class="access-stat"><div><span>همه کاربران</span><strong>{{ users.total }}</strong></div><UsersRound :size="22" /></article>
            <article class="access-stat"><div><span>مربیان فعال</span><strong>{{ users.data.filter(u => u.role === 'coach' && u.is_active).length }}</strong></div><ShieldCheck :size="22" /></article>
            <article class="access-stat"><div><span>نمایش در این صفحه</span><strong>{{ users.data.length }}</strong></div><CircleUserRound :size="22" /></article>
        </section>

        <section class="panel-flat">
            <header class="users-toolbar">
                <form class="flex flex-1 flex-col gap-2 md:flex-row" @submit.prevent="applyFilters">
                    <div class="relative min-w-0 flex-1"><Search class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8a959b]" :size="16" /><input v-model="search" class="table-filter pr-10" placeholder="جست‌وجوی نام، ایمیل یا شماره تماس"></div>
                    <div class="relative"><Filter class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8a959b]" :size="15" /><select v-model="roleFilter" class="table-filter min-w-44 pr-9" @change="applyFilters"><option value="">همه نقش‌ها</option><option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option></select></div>
                    <button class="secondary-button justify-center">اعمال فیلتر</button>
                </form>
                <button class="primary-sport-button" @click="showCreate = true"><Plus :size="17" /> حساب جدید</button>
            </header>

            <div class="overflow-x-auto">
                <table class="sport-table users-table">
                    <thead><tr><th>کاربر</th><th>نقش</th><th>تماس</th><th>تیم‌های مربی</th><th>وضعیت</th><th /></tr></thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td><div class="flex items-center gap-3"><div class="user-initial">{{ user.name.slice(0, 1) }}</div><div><strong class="block">{{ user.name }}</strong><span class="mt-1 block text-[9px] text-[#8a959b]" dir="ltr">{{ user.email }}</span></div></div></td>
                            <td><span class="role-chip" :class="roleClass(user.role)">{{ user.role_label }}</span></td>
                            <td dir="ltr" class="text-right">{{ user.phone || '—' }}</td>
                            <td><div v-if="user.teams.length" class="flex flex-wrap gap-1"><span v-for="team in user.teams" :key="team" class="team-chip">{{ team }}</span></div><span v-else class="text-[#a0a8ad]">—</span></td>
                            <td><span :class="user.is_active ? 'tag-ok' : 'tag-danger'">{{ user.is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                            <td><button class="account-toggle" :class="{ 'cursor-not-allowed opacity-30': !user.can_toggle }" :disabled="!user.can_toggle" :title="!user.can_toggle ? 'حساب جاری' : (user.is_active ? 'غیرفعال کردن' : 'فعال کردن')" @click="toggle(user)"><ToggleRight v-if="user.is_active" :size="24" class="text-[#247c62]" /><ToggleLeft v-else :size="24" class="text-[#9aa3a8]" /></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer class="flex flex-col gap-3 border-t border-[#e2e5e6] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[9px] text-[#7d8990]">نمایش {{ users.from || 0 }} تا {{ users.to || 0 }} از {{ users.total }} حساب</p>
                <div class="flex flex-wrap gap-1"><Link v-for="link in users.links" :key="link.label" :href="link.url || '#'" class="pagination-link" :class="{ 'pagination-active': link.active, 'pointer-events-none opacity-40': !link.url }" v-html="paginationLabel(link.label)" /></div>
            </footer>
        </section>

        <div v-if="showCreate" class="fixed inset-0 z-[70] flex items-end justify-center bg-[#07192b]/50 p-0 sm:items-center sm:p-5" @click.self="showCreate = false">
            <section class="max-h-[92vh] w-full overflow-y-auto bg-[#faf9f6] sm:max-w-xl">
                <header class="flex items-center justify-between border-b border-[#dfe3e6] px-5 py-4"><div><p class="section-kicker">مدیریت دسترسی</p><h2 class="mt-1 text-base font-black">ایجاد حساب کاربری</h2></div><button class="p-2 text-[#74818a]" @click="showCreate = false"><X :size="20" /></button></header>
                <form class="space-y-5 p-5" autocomplete="off" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="form-label">نام و نام خانوادگی<input v-model="form.name" class="form-input" name="new-user-name" autocomplete="off" placeholder="مثلاً علی محمدی"><small v-if="form.errors.name">{{ form.errors.name }}</small></label>
                        <label class="form-label">شماره تماس<input v-model="form.phone" class="form-input" name="new-user-phone" autocomplete="off" dir="ltr" placeholder="09120000000"><small v-if="form.errors.phone">{{ form.errors.phone }}</small></label>
                    </div>
                    <label class="form-label">ایمیل<input v-model="form.email" type="email" class="form-input" name="new-user-email" autocomplete="off" dir="ltr" placeholder="name@volleyball.ir"><small v-if="form.errors.email">{{ form.errors.email }}</small></label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="form-label">نقش<select v-model="form.role" class="form-input"><option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option></select><small v-if="form.errors.role">{{ form.errors.role }}</small></label>
                        <label class="form-label">رمز عبور اولیه<input v-model="form.password" type="password" class="form-input" name="new-user-password" autocomplete="new-password" dir="ltr" placeholder="حداقل ۸ کاراکتر"><small v-if="form.errors.password">{{ form.errors.password }}</small></label>
                    </div>
                    <div v-if="form.role === 'coach'" class="border border-[#dfe3e6] bg-white p-4"><p class="mb-3 text-[10px] font-black text-[#344957]">تیم‌های قابل مشاهده توسط مربی</p><div class="grid gap-2 sm:grid-cols-2"><label v-for="team in teams" :key="team.id" class="flex items-center gap-2 border border-[#e2e5e6] px-3 py-2 text-[10px] font-bold"><input v-model="form.team_ids" type="checkbox" :value="team.id" class="accent-[#e87532]">{{ team.name }}</label></div></div>
                    <div class="flex justify-end gap-2 border-t border-[#e2e5e6] pt-4"><button type="button" class="secondary-button" @click="showCreate = false">انصراف</button><button class="primary-sport-button" :disabled="form.processing">{{ form.processing ? 'در حال ثبت...' : 'ایجاد حساب' }}</button></div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
