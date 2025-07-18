<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { ref, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import type { CustomPageProps } from '@/types/custom';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
} from '@/components/ui/alert-dialog';
import { debounce } from 'lodash';
import { useToast } from 'vue-toastification';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const props = page.props as CustomPageProps;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'O‘quvchilar', href: '/students' },
];

const students = ref<Student[]>(props.students || []);
const pagination = ref<Pagination>(props.pagination || {
    current_page: 1,
    total_pages: 1,
    total: 0,
    per_page: 5,
});
const searchQuery = ref(props.search || '');
const isSearching = ref(false);

// Reference to the search input element
const searchInput = ref<HTMLElement | null>(null);

// Client-side validation errors
const errors = ref<Record<string, string[]>>({});

// Use toast correctly
const toast = useToast();

// Handle flash messages
watch(
    () => props.flash,
    (flash) => {
        if (flash.success) {
            toast.success(flash.success, { timeout: 5000 });
        }
    },
    { deep: true }
);

// Watch for server-side errors
watch(
    () => props.errors,
    (serverErrors) => {
        if (Object.keys(serverErrors).length > 0) {
            errors.value = serverErrors;
            toast.error(serverErrors.error?.[0] || 'Xatolik yuz berdi.', { timeout: 5000 });
        } else {
            errors.value = {};
        }
    },
    { deep: true }
);

// Watch for prop changes
watch(
    () => props,
    (newProps) => {
        console.log('Props updated:', newProps);
        students.value = newProps.students || [];
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

// Debounced server-side search
const debouncedSearch = debounce(() => {
    isSearching.value = true;
    router.get(
        route('students.index'),
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
                toast.error('Qidiruvda xato yuz berdi.', { timeout: 5000 });
            },
        }
    );
}, 300);

// Update server data on search
watch(searchQuery, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        debouncedSearch();
    }
});

// Page navigation
const goToPage = (page: number) => {
    if (page >= 1 && page <= pagination.value.total_pages) {
        router.get(
            route('students.index'),
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

// Delete Modal
const isDeleteModalOpen = ref(false);
const deleteStudentId = ref<number | null>(null);

const openDeleteModal = (id: number) => {
    deleteStudentId.value = id;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteStudentId.value = null;
};

const handleDelete = () => {
    if (deleteStudentId.value !== null) {
        router.delete(route('students.destroy', deleteStudentId.value), {
            onSuccess: () => {
                closeDeleteModal();
                router.get(route('students.index'));
            },
            onError: () => {
                toast.error('O‘chirishda xato yuz berdi.', { timeout: 5000 });
            },
        });
    }
};

// Add Modal
const isAddModalOpen = ref(false);
const newStudent = ref<Partial<Student>>({
    full_name: '',
    phone: '',
    birth_date: null,
    balance: '0',
});

const openAddModal = () => {
    isAddModalOpen.value = true;
    errors.value = {};
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    newStudent.value = { full_name: '', phone: '', birth_date: null, balance: '0' };
    errors.value = {};
};

const validateForm = (student: Partial<Student>) => {
    errors.value = {};
    let isValid = true;

    if (!student.full_name) {
        errors.value.full_name = ['Ism va familiya kiritilishi shart.'];
        isValid = false;
    }
    if (!student.phone) {
        errors.value.phone = ['Telefon raqam kiritilishi shart.'];
        isValid = false;
    } else if (!/^\+?[1-9]\d{1,13}$/.test(student.phone)) {
        errors.value.phone = ['Telefon raqam formati noto‘g‘ri.'];
        isValid = false;
    }
    if (student.birth_date && !/^\d{4}-\d{2}-\d{2}$/.test(student.birth_date)) {
        errors.value.birth_date = ['Tug‘ilgan sana formati noto‘g‘ri (YYYY-MM-DD).'];
        isValid = false;
    }
    if (!student.balance || isNaN(Number(student.balance))) {
        errors.value.balance = ['Balans raqam bo‘lishi kerak.'];
        isValid = false;
    }

    if (!isValid) {
        toast.error('Iltimos, barcha maydonlarni to‘g‘ri to‘ldiring.', { timeout: 5000 });
    }

    return isValid;
};

const handleAdd = async () => {
    if (!validateForm(newStudent.value)) {
        return;
    }

    try {
        await router.post(route('students.store'), newStudent.value as Record<string, any>, {
            onSuccess: () => {
                closeAddModal();
                router.get(route('students.index'));
            },
            onError: (serverErrors) => {
                errors.value = serverErrors;
                toast.error('Server xatosi: Ma’lumotlarni qo‘shib bo‘lmadi.', { timeout: 5000 });
            },
        });
    } catch (error) {
        console.error('Xato:', error);
        toast.error('Tizim xatosi yuz berdi.', { timeout: 5000 });
    }
};

// Edit Modal
const isEditModalOpen = ref(false);
const editStudent = ref<Partial<Student>>({
    id: 0,
    full_name: '',
    phone: '',
    birth_date: null,
    balance: '0',
});

const openEditModal = (student: Student) => {
    editStudent.value = {
        id: student.id,
        full_name: student.full_name,
        phone: student.phone,
        birth_date: student.birth_date,
        balance: student.balance,
    };
    isEditModalOpen.value = true;
    errors.value = {};
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editStudent.value = { id: 0, full_name: '', phone: '', birth_date: null, balance: '0' };
    errors.value = {};
};

const handleEdit = async () => {
    if (!validateForm(editStudent.value)) {
        return;
    }

    try {
        await router.put(route('students.update', editStudent.value.id), editStudent.value as Record<string, any>, {
            onSuccess: () => {
                closeEditModal();
                router.get(route('students.index'));
            },
            onError: (serverErrors) => {
                errors.value = serverErrors;
                toast.error('Server xatosi: Ma’lumotlarni yangilab bo‘lmadi.', { timeout: 5000 });
            },
        });
    } catch (error) {
        console.error('Xato:', error);
        toast.error('Tizim xatosi yuz berdi.', { timeout: 5000 });
    }
};
</script>

<template>
    <Head title="O‘quvchilar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 space-y-6">
            <div class="p-4">
                <!-- Search -->
                <div class="w-full flex flex-col md:flex-row justify-between p-4 mx-auto px-4 md:px-6 lg:px-8">
                    <div class="w-full md:w-1/4 relative">
                        <Input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="search"
                            placeholder="O‘quvchi qidirish..."
                            class="w-full rounded-lg px-4 py-3 text-gray-900 dark:text-gray-100
                                   bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                   shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600
                                   focus:outline-none transition-all"
                        />
                        <div v-if="isSearching" class="absolute right-2 top-3 text-gray-500"></div>
                    </div>
                    <div class="w-full md:w-auto flex justify-start md:justify-end">
                        <Button
                            variant="secondary"
                            @click="openAddModal"
                            class="w-full md:w-auto flex items-center justify-center gap-2 px-5 py-3 font-semibold rounded-lg
                                   text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
                                   hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                        >
                            Yangi O‘quvchi
                        </Button>
                    </div>
                </div>

                <!-- Table or Empty State -->
                <div v-if="students.length > 0" class="mx-auto px-4 md:px-6 lg:px-8">
                    <div class="rounded-2xl overflow-hidden border border-gray-300 dark:border-gray-700">
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow class="bg-gray-50 dark:bg-neutral-900 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    <TableHead class="px-4 py-3">ID</TableHead>
                                    <TableHead class="px-4 py-3">Ism</TableHead>
                                    <TableHead class="px-4 py-3">Telefon</TableHead>
                                    <TableHead class="px-4 py-3">Tug‘ilgan sana</TableHead>
                                    <TableHead class="px-4 py-3">Balans</TableHead>
                                    <TableHead class="px-4 py-3">Yaratilgan sana</TableHead>
                                    <TableHead class="px-4 py-3 text-center">Amallar</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="student in students"
                                    :key="student.id"
                                    class="hover:bg-gray-100 dark:hover:bg-neutral-900 transition-colors"
                                >
                                    <TableCell class="px-4 py-3">{{ student.id }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ student.full_name }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ student.phone }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ student.birth_date || 'N/A' }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ student.balance }}</TableCell>
                                    <TableCell class="px-4 py-3">{{ student.created_at_formatted }}</TableCell>
                                    <TableCell class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <button @click="openEditModal(student)">
                                                <PencilSquareIcon class="w-5 h-5" />
                                            </button>
                                            <button @click="openDeleteModal(student.id)">
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
                    O‘quvchilar topilmadi.
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
                        Bu o‘quvchini o‘chirishni xohlaysizmi?
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

        <!-- Add Student AlertDialog -->
        <AlertDialog v-model:open="isAddModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Yangi O‘quvchi Qo‘shish
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Yangi o‘quvchi ma’lumotlarini kiriting.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newStudent.full_name"
                                placeholder="Ism va familiya"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.full_name?.length }"
                            />
                            <p v-if="errors.full_name?.length" class="text-red-500 text-sm mt-1">{{ errors.full_name[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="newStudent.phone"
                                placeholder="Telefon raqam"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.phone?.length }"
                            />
                            <p v-if="errors.phone?.length" class="text-red-500 text-sm mt-1">{{ errors.phone[0] }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newStudent.birth_date"
                                placeholder="Tug‘ilgan sana (YYYY-MM-DD)"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.birth_date?.length }"
                            />
                            <p v-if="errors.birth_date?.length" class="text-red-500 text-sm mt-1">{{ errors.birth_date[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="newStudent.balance"
                                placeholder="Balans"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.balance?.length }"
                            />
                            <p v-if="errors.balance?.length" class="text-red-500 text-sm mt-1">{{ errors.balance[0] }}</p>
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
                               text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 border border-blue-600 dark:border-blue-700 shadow-sm
                               focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all"
                    >
                        Qo‘shish
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Edit Student AlertDialog -->
        <AlertDialog v-model:open="isEditModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        O‘quvchi Ma’lumotlarini Tahrirlash
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        O‘quvchi ma’lumotlarini yangilang.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="editStudent.full_name"
                                placeholder="Ism va familiya"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.full_name?.length }"
                            />
                            <p v-if="errors.full_name?.length" class="text-red-500 text-sm mt-1">{{ errors.full_name[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="editStudent.phone"
                                placeholder="Telefon raqam"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.phone?.length }"
                            />
                            <p v-if="errors.phone?.length" class="text-red-500 text-sm mt-1">{{ errors.phone[0] }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="editStudent.birth_date"
                                placeholder="Tug‘ilgan sana (YYYY-MM-DD)"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.birth_date?.length }"
                            />
                            <p v-if="errors.birth_date?.length" class="text-red-500 text-sm mt-1">{{ errors.birth_date[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="editStudent.balance"
                                placeholder="Balans"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.balance?.length }"
                            />
                            <p v-if="errors.balance?.length" class="text-red-500 text-sm mt-1">{{ errors.balance[0] }}</p>
                        </div>
                    </div>
                </div>
                <AlertDialogFooter class="flex justify-end gap-4">
                    <button
                        type="button"
                        @click="closeEditModal"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
                               text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700 shadow-sm
                               hover:bg-gray-100 dark:hover:bg-neutral-900 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                    >
                        Bekor qilish
                    </button>
                    <button
                        type="button"
                        @click="handleEdit"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
                               text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 border border-blue-600 dark:border-blue-700 shadow-sm
                               focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all"
                    >
                        Yangilash
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
```
