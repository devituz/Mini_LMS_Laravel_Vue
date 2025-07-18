
<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { ref, watch, nextTick } from 'vue'
import { Button } from '@/components/ui/button'
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
} from '@/components/ui/alert-dialog'
import { debounce } from 'lodash'
import { useToast } from 'vue-toastification'
import type { BreadcrumbItem } from '@/types'

interface Teacher {
    id: number
    full_name: string
    phone: string
    password: string
}

interface Pagination {
    current_page: number
    total_pages: number
    total: number
    per_page: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'O‘qituvchilar', href: '/teachers' },
]

const { props } = usePage<{
    teachers: Teacher[]
    pagination: Pagination
    search: string
    flash: { success?: string }
    errors: Record<string, string>
}>()
const teachers = ref<Teacher[]>(props.teachers || [])
const pagination = ref<Pagination>(props.pagination || {
    current_page: 1,
    total_pages: 1,
    total: 0,
    per_page: 5,
})
const searchQuery = ref(props.search || '')

// Reference to the search input element
const searchInput = ref<HTMLElement | null>(null)

// Client-side validation errors
const errors = ref<Partial<Record<'full_name' | 'phone' | 'password', string>>>({})

// Handle flash messages and errors
const { toast } = useToast()
watch(
    () => props.flash,
    (flash) => {
        if (flash.success) {
            toast.success(flash.success, {
                timeout: 5000,
            })
        }
    },
    { deep: true }
)

// Watch for server-side errors
watch(
    () => props.errors,
    (serverErrors) => {
        if (Object.keys(serverErrors).length > 0) {
            errors.value = { ...serverErrors }
            toast.error(serverErrors.error || 'O‘qituvchi qo‘shilmadi.', {
                timeout: 5000,
            })
        } else {
            errors.value = {}
        }
    },
    { deep: true }
)

// Watch for prop changes
watch(
    () => props,
    (newProps) => {
        teachers.value = newProps.teachers || []
        pagination.value = newProps.pagination || {
            current_page: 1,
            total_pages: 1,
            total: 0,
            per_page: 5,
        }
        searchQuery.value = newProps.search || ''
    },
    { deep: true }
)

// Debounced server-side search
const debouncedSearch = debounce(() => {
    router.get(
        route('teachers.index'),
        { search: searchQuery.value, page: 1 },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                nextTick(() => {
                    if (searchInput.value) {
                        searchInput.value.focus()
                    }
                })
            },
        }
    )
}, 500)

// Update server data on search
watch(searchQuery, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        debouncedSearch()
    }
})

// Page navigation
const goToPage = (page: number) => {
    if (page >= 1 && page <= pagination.value.total_pages) {
        router.get(
            route('teachers.index'),
            { search: searchQuery.value, page },
            {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    nextTick(() => {
                        if (searchInput.value) {
                            searchInput.value.focus()
                        }
                    })
                },
            }
        )
    }
}

// Delete Modal
const isDeleteModalOpen = ref(false)
const deleteTeacherId = ref<number | null>(null)

const openDeleteModal = (id: number) => {
    deleteTeacherId.value = id
    isDeleteModalOpen.value = true
}

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false
    deleteTeacherId.value = null
}

const handleDelete = () => {
    if (deleteTeacherId.value !== null) {
        router.delete(route('teachers.destroy', deleteTeacherId.value), {
            onSuccess: () => {
                closeDeleteModal()
                router.get(route('teachers.index'))
            },
        })
    }
}

// Add Modal
const isAddModalOpen = ref(false)
const newTeacher = ref<Partial<Teacher>>({
    full_name: '',
    phone: '',
    password: '',
})

const openAddModal = () => {
    isAddModalOpen.value = true
    errors.value = {} // Clear errors when opening modal
}

const closeAddModal = () => {
    isAddModalOpen.value = false
    newTeacher.value = { full_name: '', phone: '', password: '' }
    errors.value = {} // Clear errors when closing modal
}

const validateForm = () => {
    errors.value = {}
    let isValid = true

    if (!newTeacher.value.full_name) {
        errors.value.full_name = 'Ism va familiya kiritilishi shart.'
        isValid = false
    }
    if (!newTeacher.value.phone) {
        errors.value.phone = 'Telefon raqam kiritilishi shart.'
        isValid = false
    } else if (!/^\+?[1-9]\d{1,14}$/.test(newTeacher.value.phone)) {
        errors.value.phone = 'Telefon raqam formati noto‘g‘ri.'
        isValid = false
    }
    if (!newTeacher.value.password) {
        errors.value.password = 'Parol kiritilishi shart.'
        isValid = false
    } else if (newTeacher.value.password.length < 6) {
        errors.value.password = 'Parol kamida 6 belgidan iborat bo‘lishi kerak.'
        isValid = false
    }

    if (!isValid) {
        toast.error('Iltimos, barcha maydonlarni to‘g‘ri to‘ldiring.', {
            timeout: 5000,
        })
    }

    return isValid
}

const handleAdd = async () => {
    if (!validateForm()) {
        return // Keep dialog open if validation fails
    }

    try {
        await router.post(route('teachers.store'), newTeacher.value, {
            onSuccess: () => {
                closeAddModal() // Close dialog only on success
                router.get(route('teachers.index'))
            },
            onError: (serverErrors) => {
                errors.value = { ...serverErrors } // Update with server-side errors
                toast.error('Server xatosi: Ma’lumotlarni qo‘shib bo‘lmadi.', {
                    timeout: 5000,
                })
            },
        })
    } catch (error) {
        console.error('Xato:', error)
        toast.error('Tizim xatosi yuz berdi.', {
            timeout: 5000,
        })
    }
}
</script>

<template>
    <Head title="O‘qituvchilar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 space-y-6">
            <div class="p-4">
                <div class="dark:bg-neutral-950 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-md overflow-hidden">
                    <!-- Search -->
                    <div class="w-full flex flex-col md:flex-row justify-between p-4 mx-auto px-4 md:px-6 lg:px-8">
                        <div class="w-full md:w-1/4">
                            <Input
                                ref="searchInput"
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
                                @click="openAddModal"
                                class="w-full md:w-auto flex items-center justify-center gap-2 px-5 py-3 font-semibold rounded-lg
                                       text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
                                       hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                            >
                                Yangi O‘qituvchi
                            </Button>
                        </div>
                    </div>

                    <!-- Table or Empty State -->
                    <div v-if="teachers.length > 0" class="mx-auto px-4 md:px-6 lg:px-8">
                        <div class="rounded-2xl overflow-hidden border border-gray-300 dark:border-gray-700">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow class="bg-gray-50 dark:bg-neutral-900 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        <TableHead class="px-4 py-3">ID</TableHead>
                                        <TableHead class="px-4 py-3">Ism</TableHead>
                                        <TableHead class="px-4 py-3">Telefon</TableHead>
                                        <TableHead class="px-4 py-3 text-center">Amallar</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="teacher in teachers"
                                        :key="teacher.id"
                                        class="hover:bg-gray-100 dark:hover:bg-neutral-900 transition-colors"
                                    >
                                        <TableCell class="px-4 py-3">{{ teacher.id }}</TableCell>
                                        <TableCell class="px-4 py-3">{{ teacher.full_name }}</TableCell>
                                        <TableCell class="px-4 py-3">{{ teacher.phone }}</TableCell>
                                        <TableCell class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <Link :href="route('teachers.edit', teacher.id)">
                                                    <PencilSquareIcon class="w-5 h-5" />
                                                </Link>
                                                <button @click="openDeleteModal(teacher.id)">
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </TableCell>
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
                                Orqaga
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

        <!-- Delete Confirmation AlertDialog -->
        <AlertDialog v-model:open="isDeleteModalOpen">
            <AlertDialogContent class="w-96 p-6 space-y-4">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        O‘chirishni tasdiqlang
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Bu o‘qituvchini o‘chirishni xohlaysizmi?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter class="flex justify-end gap-4">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
               text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
               hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                    >
                        Bekor qilish
                    </button>
                    <button
                        type="button"
                        @click="handleDelete"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
               text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 border border-red-600 dark:border-red-700 shadow-sm
               focus:ring-2 focus:ring-red-500 focus:outline-none transition-all"
                    >
                        O‘chirish
                    </button>
                </AlertDialogFooter>

            </AlertDialogContent>
        </AlertDialog>

        <!-- Add Teacher AlertDialog -->
        <AlertDialog v-model:open="isAddModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Yangi O‘qituvchi Qo‘shish
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Yangi o‘qituvchi ma’lumotlarini kiriting.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newTeacher.full_name"
                                placeholder="Ism va familiya"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.full_name }"
                            />
                            <p v-if="errors.full_name" class="text-red-500 text-sm mt-1">{{ errors.full_name }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="newTeacher.password"
                                type="password"
                                placeholder="Parol"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.password }"
                            />
                            <p v-if="errors.password" class="text-red-500 text-sm mt-1">{{ errors.password }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newTeacher.phone"
                                placeholder="Telefon raqam"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.phone }"
                            />
                            <p v-if="errors.phone" class="text-red-500 text-sm mt-1">{{ errors.phone }}</p>
                        </div>
                    </div>
                </div>
                <AlertDialogFooter class="flex justify-end gap-4">
                    <button
                        type="button"
                        @click="closeAddModal"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
               text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
               hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                    >
                        Bekor qilish
                    </button>
                    <button
                        type="button"
                        @click="handleAdd"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
               text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
               hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                    >
                        Qo‘shish
                    </button>
                </AlertDialogFooter>

            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
