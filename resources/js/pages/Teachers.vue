<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { PencilSquareIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'
import { Checkbox } from '@/components/ui/checkbox'
import type { BreadcrumbItem } from '@/types'

interface Teacher {
    id: number
    full_name: string
    email: string
    phone: string
    subject: string
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: "O‘qituvchilar", href: '/teachers' },
]

// Teachers data
const { props } = usePage<{ teachers?: Teacher[] }>()
const teachers = ref<Teacher[]>(props.teachers || [
    { id: 1, full_name: 'Azizbek Jo‘rayev', email: 'azizbek@example.com', phone: '+998901234567', subject: 'Matematika' },
    { id: 2, full_name: 'Dilnoza Murodova', email: 'dilnoza@example.com', phone: '+998998765432', subject: 'Ingliz tili' },
    { id: 3, full_name: 'Alisher Karimov', email: 'alisher@example.com', phone: '+998935551122', subject: 'Fizika' },
    { id: 4, full_name: 'Farrux Xudoyberdiyev', email: 'farrux@example.com', phone: '+998971234567', subject: 'Kimyo' },
    { id: 5, full_name: 'Zuhra Karimova', email: 'zuhra@example.com', phone: '+998931234567', subject: 'Biologiya' },
    { id: 6, full_name: 'Jasmina Xo‘jaeva', email: 'jasmina@example.com', phone: '+998931114445', subject: 'Tarix' },
])

// Search
const searchQuery = ref('')
const filteredTeachers = computed(() =>
    teachers.value.filter(t =>
        t.full_name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
)

// Checkbox selection
const selectedTeachers = ref<number[]>([])
const selectAll = ref(false)

const toggleSelection = (id: number) => {
    if (selectedTeachers.value.includes(id)) {
        selectedTeachers.value = selectedTeachers.value.filter(t => t !== id)
    } else {
        selectedTeachers.value = [...selectedTeachers.value, id]
    }
}

const isSelected = (id: number) => selectedTeachers.value.includes(id)

const toggleSelectAll = () => {
    selectAll.value = !selectAll.value
    if (selectAll.value) {
        selectedTeachers.value = filteredTeachers.value.map(t => t.id)
    } else {
        selectedTeachers.value = []
    }
}

// Sync selectAll with filteredTeachers
watch(filteredTeachers, () => {
    const filteredIds = filteredTeachers.value.map(t => t.id)
    selectAll.value = filteredIds.length > 0 && filteredIds.every(id => selectedTeachers.value.includes(id))
})

// Reset selections on search change
watch(searchQuery, () => {
    currentPage.value = 1
    selectedTeachers.value = []
    selectAll.value = false
})

// Pagination
const currentPage = ref(1)
const perPage = 5

const totalPages = computed(() => Math.ceil(filteredTeachers.value.length / perPage))

const paginatedTeachers = computed(() => {
    const start = (currentPage.value - 1) * perPage
    const end = start + perPage
    return filteredTeachers.value.slice(start, end)
})

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

// Delete function
const handleDelete = (id: number) => {
    if (confirm("O‘chirishni xohlaysizmi?")) {
        teachers.value = teachers.value.filter(t => t.id !== id)
        selectedTeachers.value = selectedTeachers.value.filter(t => t !== id)
        alert('O‘qituvchi muvaffaqiyatli o‘chirildi!')
    }
}
</script>

<template>
    <Head title="O‘qituvchilar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 space-y-6">
            <div class="p-4">
                <div class="bg-white dark:bg-neutral-950 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-md overflow-hidden">
                    <!-- Search -->
                    <div class="w-full flex flex-col md:flex-row justify-between gap-4 p-4">
                        <div class="w-full md:w-1/4">
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="O‘qituvchi qidirish..."
                                class="w-full rounded-lg px-4 py-3 text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600
                                       focus:outline-none transition-all"
                            />
                        </div>
                        <div class="w-full md:w-auto flex justify-start md:justify-end">
                            <Button
                                variant="secondary"
                                class="w-full md:w-auto flex items-center justify-center gap-2 px-5 py-3 font-semibold rounded-lg
                                       text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
                                       hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                            >
                                Yangi O‘qituvchi
                            </Button>
                        </div>
                    </div>

                    <!-- Table yoki Empty State -->
                    <div v-if="filteredTeachers.length > 0">
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow class="bg-gray-50 dark:bg-neutral-900 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <TableHead class="px-4 py-3 text-center w-10">
                                        <Checkbox
                                            :checked="selectAll"
                                            @update:checked="toggleSelectAll"
                                            class="w-5 h-5 rounded-md border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
                                               hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                        />
                                    </TableHead>
                                    <TableHead class="px-4 py-3">ID</TableHead>
                                    <TableHead class="px-4 py-3">Ism</TableHead>
                                    <TableHead class="px-4 py-3">Email</TableHead>
                                    <TableHead class="px-4 py-3">Telefon</TableHead>
                                    <TableHead class="px-4 py-3">Fan</TableHead>
                                    <TableHead class="px-4 py-3 text-center">Amallar</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="teacher in paginatedTeachers"
                                    :key="teacher.id"
                                    class="hover:bg-gray-100 dark:hover:bg-neutral-900 transition-colors"
                                >
                                    <TableCell class="px-4 py-3 text-center">
                                        <Checkbox
                                            :checked="isSelected(teacher.id)"
                                            @update:checked="() => toggleSelection(teacher.id)"
                                            class="w-5 h-5 rounded-md"
                                        />
                                    </TableCell>
                                    <TableCell class="px-4 py-3">{{ teacher.id }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ teacher.full_name }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ teacher.email }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ teacher.phone }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ teacher.subject }}</TableCell>
                                    <TableCell class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <Link :href="`/teachers/${teacher.id}`"><EyeIcon class="w-5 h-5" /></Link>
                                            <Link :href="`/teachers/${teacher.id}/edit`"><PencilSquareIcon class="w-5 h-5" /></Link>
                                            <button @click="handleDelete(teacher.id)"><TrashIcon class="w-5 h-5" /></button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex justify-end items-center gap-3 p-4">
                            <Button variant="secondary" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">
                                Orqaga
                            </Button>
                            <div class="font-semibold text-gray-700 dark:text-gray-300">
                                {{ currentPage }} / {{ totalPages }}
                            </div>
                            <Button variant="secondary" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">
                                Keyingi
                            </Button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center text-gray-500 py-10 text-lg font-semibold">
                        O‘qituvchilar topilmadi.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

