<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { ref, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import type { DebtPageProps, Debt } from '@/types/debt';
import { debounce } from 'lodash';
import { useToast } from 'vue-toastification';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const props = page.props as DebtPageProps;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Debts', href: '/debts' },
];

const debts = ref<Debt[]>(props.debts || []);
const pagination = ref(props.pagination || {
    current_page: 1,
    total_pages: 1,
    total: 0,
    per_page: 5,
});
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
    { deep: true }
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
    { deep: true }
);

watch(
    () => props,
    (newProps) => {
        debts.value = newProps.debts || [];
        pagination.value = newProps.pagination || {
            current_page: 1,
            total_pages: 1,
            total: 0,
            per_page: 5,
        };
        searchQuery.value = newProps.search || '';
    },
    { deep: true }
);

const debouncedSearch = debounce(() => {
    isSearching.value = true;
    router.get(
        route('debts.index'),
        { search: searchQuery.value, page: 1 },
        {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                isSearching.value = false;
                nextTick(() => {
                    if (searchInput.value) {
                        searchInput.value.focus();
                    }
                });
            },
            onError: () => {
                isSearching.value = false;
                toast.error('Search failed.', { timeout: 5000 });
            },
        }
    );
}, 300);

watch(searchQuery, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        debouncedSearch();
    }
});

const goToPage = (page: number) => {
    if (page >= 1 && page <= pagination.value.total_pages) {
        router.get(
            route('debts.index'),
            { search: searchQuery.value, page },
            {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    nextTick(() => {
                        if (searchInput.value) {
                            searchInput.value.focus();
                        }
                    });
                },
            }
        );
    }
};
</script>

<template>
    <Head title="Debts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="pt-7 space-y-6">
            <!-- Search -->
            <div class="w-full flex flex-col md:flex-row justify-between p-4 mx-auto px-4 md:px-6 lg:px-8">
                <div class="w-full md:w-1/4 relative">
                    <Input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search student or group..."
                        class="w-full rounded-lg px-4 py-3 text-gray-900 dark:text-gray-100
                               bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                               shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600
                               focus:outline-none transition-all"
                    />
                    <div v-if="isSearching" class="absolute right-2 top-3 text-gray-500"></div>
                </div>
            </div>

            <!-- Table -->
            <div v-if="debts.length > 0" class="mx-auto px-4 md:px-6 lg:px-8">
                <div class="rounded-2xl overflow-hidden border border-gray-300 dark:border-gray-700">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow class="bg-gray-50 dark:bg-neutral-900 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <TableHead class="px-4 py-3">ID</TableHead>
                                <TableHead class="px-4 py-3">Student</TableHead>
                                <TableHead class="px-4 py-3">Group</TableHead>
                                <TableHead class="px-4 py-3">Amount</TableHead>
                                <TableHead class="px-4 py-3">Month</TableHead>
                                <TableHead class="px-4 py-3">Paid Amount</TableHead>
                                <TableHead class="px-4 py-3">Status</TableHead>
                                <TableHead class="px-4 py-3">Created At</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="debt in debts"
                                :key="debt.id"
                                class="hover:bg-gray-100 dark:hover:bg-neutral-900 transition-colors"
                            >
                                <TableCell class="px-4 py-3">{{ debt.id }}</TableCell>
                                <TableCell class="px-4 py-3">{{ debt.student.full_name }}</TableCell>
                                <TableCell class="px-4 py-3">{{ debt.group.name }}</TableCell>
                                <TableCell class="px-4 py-3">{{ debt.amount }}</TableCell>
                                <TableCell class="px-4 py-3">{{ debt.month }}</TableCell>
                                <TableCell class="px-4 py-3">{{ debt.paid_amount }}</TableCell>
                                <TableCell class="px-4 py-3">
    <span
        :class="debt.is_paid
            ? 'text-green-600 font-semibold'
            : 'text-red-600 font-semibold'"
    >
        {{ debt.is_paid ? 'Paid' : 'Unpaid' }}
    </span>
                                </TableCell>

                                <TableCell class="px-4 py-3">{{ debt.created_at }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.total > pagination.per_page" class="flex justify-end items-center gap-3 p-4">
                    <Button
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-5 py-3 font-semibold rounded-lg
                               text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
                               hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                        variant="secondary"
                        @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                    >
                        Previous
                    </Button>
                    <div class="font-semibold text-gray-700 dark:text-gray-300">
                        {{ pagination.current_page }} / {{ pagination.total_pages }}
                    </div>
                    <Button
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-5 py-3 font-semibold rounded-lg
                               text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
                               hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                        variant="secondary"
                        @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.total_pages"
                    >
                        Next
                    </Button>
                </div>
            </div>

            <div v-else class="text-center text-gray-500 py-10 text-lg font-semibold">
                No debts found.
            </div>
        </div>
    </AppLayout>
</template>
