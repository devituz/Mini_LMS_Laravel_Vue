<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Payment, PaymentPageProps } from '@/types/payment';
import { Head, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { nextTick, ref, watch } from 'vue';
import { useToast } from 'vue-toastification';

const page = usePage();
const props = page.props as PaymentPageProps;

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Payments', href: '/payments' }];

const payments = ref<Payment[]>(props.payments || []);
const pagination = ref(
    props.pagination || {
        current_page: 1,
        total_pages: 1,
        total: 0,
        per_page: 5,
    },
);
const searchQuery = ref(props.search || '');
const isSearching = ref(false);
const searchInput = ref<HTMLElement | null>(null);

const errors = ref<Record<string, string[]>>({});
const toast = useToast();

watch(
    () => props.flash,
    (flash) => {
        if (flash.success) {
            toast.success(flash.success, { timeout: 5000 });
        }
    },
    { deep: true },
);

watch(
    () => props.errors,
    (serverErrors) => {
        if (Object.keys(serverErrors).length > 0) {
            errors.value = serverErrors;
            toast.error(serverErrors.error?.[0] || 'An error occurred.', { timeout: 5000 });
        } else {
            errors.value = {};
        }
    },
    { deep: true },
);

watch(
    () => props,
    (newProps) => {
        payments.value = newProps.payments || [];
        pagination.value = newProps.pagination || {
            current_page: 1,
            total_pages: 1,
            total: 0,
            per_page: 5,
        };
        searchQuery.value = newProps.search || '';
    },
    { deep: true },
);

const debouncedSearch = debounce(() => {
    isSearching.value = true;
    router.get(
        route('payments.index'),
        { search: searchQuery.value, page: 1 },
        {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                isSearching.value = false;
                nextTick(() => searchInput.value?.focus());
            },
            onError: () => {
                isSearching.value = false;
                toast.error('Search failed.', { timeout: 5000 });
            },
        },
    );
}, 300);

watch(searchQuery, (newVal, oldVal) => {
    if (newVal !== oldVal) debouncedSearch();
});

const goToPage = (page: number) => {
    if (page >= 1 && page <= pagination.value.total_pages) {
        router.get(
            route('payments.index'),
            { search: searchQuery.value, page },
            {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    nextTick(() => searchInput.value?.focus());
                },
            },
        );
    }
};
</script>

<template>
    <Head title="Payments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 pt-7">
            <div class="mx-auto flex w-full flex-col justify-between p-4 px-4 md:flex-row md:px-6 lg:px-8">
                <div class="relative w-full md:w-1/4">
                    <Input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search student or note..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 shadow-sm transition-all focus:ring-2 focus:ring-gray-500 focus:outline-none dark:border-gray-700 dark:bg-neutral-950 dark:text-gray-100 dark:focus:ring-gray-600"
                    />
                </div>
            </div>

            <div v-if="payments.length > 0" class="mx-auto px-4 md:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl border border-gray-300 dark:border-gray-700">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow class="bg-gray-50 text-sm font-semibold text-gray-700 dark:bg-neutral-900 dark:text-gray-300">
                                <TableHead class="px-4 py-3">ID</TableHead>
                                <TableHead class="px-4 py-3">Student</TableHead>
                                <TableHead class="px-4 py-3">Amount</TableHead>
                                <TableHead class="px-4 py-3">Date</TableHead>
                                <TableHead class="px-4 py-3">Type</TableHead>
                                <TableHead class="px-4 py-3">Note</TableHead>
                                <TableHead class="px-4 py-3">Debt Month</TableHead>
                                <TableHead class="px-4 py-3">Debt Amount</TableHead>
                                <TableHead class="px-4 py-3">Created At</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="payment in payments"
                                :key="payment.id"
                                class="transition-colors hover:bg-gray-100 dark:hover:bg-neutral-800"
                            >
                                <TableCell class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ payment.id }} </TableCell>

                                <!-- Student -->
                                <TableCell class="max-w-[150px] truncate px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ payment.student?.full_name }}
                                </TableCell>

                                <!-- Amount -->
                                <TableCell class="px-4 py-3 font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ payment.amount }}
                                </TableCell>

                                <!-- Date -->
                                <TableCell class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ payment.date }}
                                </TableCell>

                                <TableCell class="px-4 py-3">
                                    <span
                                        :class="{
                                            'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100': payment.type === 'debt',
                                            'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100': payment.type === 'balance',
                                        }"
                                        class="inline-block rounded-full px-3 py-1 text-xs font-semibold tracking-wide uppercase"
                                    >
                                        {{ payment.type === 'debt' ? 'Debt' : 'Balance' }}
                                    </span>
                                </TableCell>

                                <TableCell class="max-w-[200px] truncate px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ payment.note || '—' }}
                                </TableCell>

                                <!-- Debt Month -->
                                <TableCell class="px-4 py-3">
                                    <template v-if="payment.debt">
                                        <span
                                            class="inline-block rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                                        >
                                            {{ payment.debt.month }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-400 dark:text-gray-600">—</span>
                                    </template>
                                </TableCell>

                                <!-- Debt Amount with is_paid -->
                                <TableCell class="px-4 py-3">
                                    <template v-if="payment.debt">
                                        <span
                                            :class="
                                                payment.debt.is_paid
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-800 dark:text-rose-100'
                                            "
                                            class="inline-block rounded-full px-2 py-1 text-xs font-medium"
                                        >
                                            {{ payment.debt.amount }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-400 dark:text-gray-600">—</span>
                                    </template>
                                </TableCell>

                                <!-- Created At -->
                                <TableCell class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ payment.created_at }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.total > pagination.per_page" class="flex items-center justify-end gap-3 p-4">
                    <Button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"> Previous </Button>
                    <div class="font-semibold text-gray-700 dark:text-gray-300">{{ pagination.current_page }} / {{ pagination.total_pages }}</div>
                    <Button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.total_pages">
                        Next
                    </Button>
                </div>
            </div>

            <div v-else class="py-10 text-center text-lg font-semibold text-gray-500">No payments found.</div>
        </div>
    </AppLayout>
</template>
