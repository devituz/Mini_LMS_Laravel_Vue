<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardContent, CardTitle, CardDescription } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

const { props } = usePage();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const stats = [
    {
        title: 'Total Students',
        value: props.studentCount,
        icon: '🎓',
    },
    {
        title: 'Total Teachers',
        value: props.teacherCount,
        icon: '🧑‍🏫',
    },
    {
        title: 'Total Groups',
        value: props.groupCount,
        icon: '👥',
    },
    {
        title: "Today's Revenue",
        value: `${props.todaysRevenue.toLocaleString()} UZS`,
        icon: '💰',
    },
    {
        title: 'Total Debt',
        value: `${props.totalDebt.toLocaleString()} UZS`,
        icon: '📉',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-8 space-y-6">
            <!-- Statistics Cards -->
            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-2 xl:grid-cols-5 ">
                <Card v-for="(item, index) in stats" :key="index" class="border-gray-300 dark:border-gray-700 shadow-sm focus:ring-2">
                    <CardHeader class="flex flex-row items-center gap-4">
                        <div class="text-3xl p-3 rounded-full bg-gray-100 dark:bg-gray-800">
                            {{ item.icon }}
                        </div>
                        <div>
                            <CardTitle class="text-sm font-medium">{{ item.title }}</CardTitle>
                            <CardDescription class="text-shadow-2xs font-bold font-mono">
                                {{ item.value }}
                            </CardDescription>
                        </div>
                    </CardHeader>
                </Card>
            </div>

            <Card class="min-h-[300px] flex items-center justify-center border border-gray-300 dark:border-gray-700 shadow-sm focus:ring-2">
                <CardContent class="text-muted-foreground text-sm text-shadow-2xs font-bold font-mono">
                    📊 Charts, tables, or recent activity will appear here.
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
