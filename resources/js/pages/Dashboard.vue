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
        title: 'Jami o‘quvchilar',
        value: props.studentCount,
        icon: '🎓',
    },
    {
        title: 'Jami o‘qituvchilar',
        value: props.teacherCount,
        icon: '🧑‍🏫',
    },
    {
        title: 'Jami guruhlar',
        value: props.groupCount,
        icon: '👥',
    },
    {
        title: 'Bugungi tushum',
        value: `${props.todaysRevenue.toLocaleString()} so‘m`,
        icon: '💰',
    },
    {
        title: 'Umumiy qarzdorlik',
        value: `${props.totalDebt.toLocaleString()} so‘m`,
        icon: '📉',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 ">
            <!-- Statistics Cards -->
            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 ">
                <Card v-for="(item, index) in stats" :key="index" class="border-gray-300 dark:border-gray-700
                        shadow-sm focus:ring-2">
                    <CardHeader class="flex flex-row items-center gap-4 ">
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

            <Card class="min-h-[300px] flex items-center justify-center border border-gray-300 dark:border-gray-700
                        shadow-sm focus:ring-2" >
                <CardContent class="text-muted-foreground text-sm text-shadow-2xs font-bold font-mono">
                    📊 Grafiklar, jadval yoki so‘nggi faoliyat shu yerda chiqadi
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>




