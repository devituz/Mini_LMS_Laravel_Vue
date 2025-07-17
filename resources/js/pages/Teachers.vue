<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'O‘qituvchilar', href: '/teachers' },
];

// Use props for dynamic data from Laravel, fallback to static data
const { props } = usePage();
const teachers = ref(props.teachers || [
    {
        id: 1,
        full_name: 'Azizbek Jo‘rayev',
        email: 'azizbek@example.com',
        phone: '+998901234567',
        subject: 'Matematika',
    },
    {
        id: 2,
        full_name: 'Dilnoza Murodova',
        email: 'dilnoza@example.com',
        phone: '+998998765432',
        subject: 'Ingliz tili',
    },
    {
        id: 3,
        full_name: 'Alisher Karimov',
        email: 'alisher@example.com',
        phone: '+998935551122',
        subject: 'Fizika',
    },
]);
</script>

<template>
    <Head title="O‘qituvchilar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">O‘qituvchilar</h1>

            <!-- Teachers Data Table -->
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>ID</TableHead>
                        <TableHead>Ism</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Telefon</TableHead>
                        <TableHead>Fan</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="teacher in teachers" :key="teacher.id">
                        <TableCell>{{ teacher.id }}</TableCell>
                        <TableCell>{{ teacher.full_name }}</TableCell>
                        <TableCell>{{ teacher.email }}</TableCell>
                        <TableCell>{{ teacher.phone }}</TableCell>
                        <TableCell>{{ teacher.subject }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AppLayout>
</template>
