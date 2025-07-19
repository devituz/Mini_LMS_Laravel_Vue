```vue
<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { ref, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import type { CustomPageProps, Group } from '@/types/custom';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
} from '@/components/ui/alert-dialog';

import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectGroup,
    SelectLabel,
    SelectItem,
} from '@/components/ui/select'

import { debounce } from 'lodash';
import { useToast } from 'vue-toastification';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const props = page.props as CustomPageProps;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Guruhlar', href: '/groups' },
];

const groups = ref<Group[]>(props.groups || []);
const pagination = ref<Pagination>(props.pagination || {
    current_page: 1,
    total_pages: 1,
    total: 0,
    per_page: 5,
});
const searchQuery = ref(props.search || '');
const isSearching = ref(false);
const teachers = ref<Teacher[]>(props.teachers || []);

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
        groups.value = newProps.groups || [];
        pagination.value = newProps.pagination || {
            current_page: 1,
            total_pages: 1,
            total: 0,
            per_page: 5,
        };
        searchQuery.value = newProps.search || '';
        teachers.value = newProps.teachers || [];
    },
    { deep: true }
);

// Debounced server-side search
const debouncedSearch = debounce(() => {
    isSearching.value = true;
    router.get(
        route('groups.index'),
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
            route('groups.index'),
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
const deleteGroupId = ref<number | null>(null);

const openDeleteModal = (id: number) => {
    deleteGroupId.value = id;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteGroupId.value = null;
};

const handleDelete = () => {
    if (deleteGroupId.value !== null) {
        router.delete(route('groups.destroy', deleteGroupId.value), {
            onSuccess: () => {
                closeDeleteModal();
                router.get(route('groups.index'));
            },
            onError: () => {
                toast.error('O‘chirishda xato yuz berdi.', { timeout: 5000 });
            },
        });
    }
};

// Add Modal
const isAddModalOpen = ref(false);
const newGroup = ref<Partial<Group>>({
    name: '',
    teacher_id: 0,
    monthly_fee: '0',
    start_date: null,
    time: '',
});

const openAddModal = () => {
    isAddModalOpen.value = true;
    errors.value = {};
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    newGroup.value = { name: '', teacher_id: 0, monthly_fee: '0', start_date: null, time: '' };
    errors.value = {};
};

const validateForm = (group: Partial<Group>) => {
    errors.value = {};
    let isValid = true;

    if (!group.name) {
        errors.value.name = ['Guruh nomi kiritilishi shart.'];
        isValid = false;
    }
    if (!group.teacher_id) {
        errors.value.teacher_id = ['O‘qituvchi tanlanishi shart.'];
        isValid = false;
    }
    if (!group.monthly_fee || isNaN(Number(group.monthly_fee)) || Number(group.monthly_fee) < 0) {
        errors.value.monthly_fee = ['Oylik to‘lov raqam bo‘lishi va 0 dan katta yoki teng bo‘lishi kerak.'];
        isValid = false;
    }
    if (!group.start_date || !/^\d{4}-\d{2}-\d{2}$/.test(group.start_date)) {
        errors.value.start_date = ['Boshlanish sanasi formati noto‘g‘ri (YYYY-MM-DD).'];
        isValid = false;
    }
    if (!group.time) {
        errors.value.time = ['Dars vaqti kiritilishi shart.'];
        isValid = false;
    }

    if (!isValid) {
        toast.error('Iltimos, barcha maydonlarni to‘g‘ri to‘ldiring.', { timeout: 5000 });
    }

    return isValid;
};

const handleAdd = async () => {
    if (!validateForm(newGroup.value)) {
        return;
    }

    try {
        await router.post(route('groups.store'), newGroup.value as Record<string, any>, {
            onSuccess: () => {
                closeAddModal();
                router.get(route('groups.index'));
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
const editGroup = ref<Partial<Group>>({
    id: 0,
    name: '',
    teacher_id: 0,
    monthly_fee: '0',
    start_date: null,
    time: '',
});

const openEditModal = (group: Group) => {
    editGroup.value = {
        id: group.id,
        name: group.name,
        teacher_id: group.teacher_id,
        monthly_fee: group.monthly_fee,
        start_date: group.start_date,
        time: group.time,
    };
    isEditModalOpen.value = true;
    errors.value = {};
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editGroup.value = { id: 0, name: '', teacher_id: 0, monthly_fee: '0', start_date: null, time: '' };
    errors.value = {};
};

const handleEdit = async () => {
    if (!validateForm(editGroup.value)) {
        return;
    }

    try {
        await router.put(route('groups.update', editGroup.value.id), editGroup.value as Record<string, any>, {
            onSuccess: () => {
                closeEditModal();
                router.get(route('groups.index'));
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
    <Head title="Guruhlar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="pt-7 space-y-6">
            <!-- Search -->
            <div class="w-full flex flex-col md:flex-row justify-between p-4 mx-auto px-4 md:px-6 lg:px-8">
                <div class="w-full md:w-1/4 relative">
                    <Input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Guruh qidirish..."
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
                        Yangi Guruh
                    </Button>
                </div>
            </div>

            <!-- Table or Empty State -->
            <div v-if="groups.length > 0" class="mx-auto px-4 md:px-6 lg:px-8">
                <div class="rounded-2xl overflow-hidden border border-gray-300 dark:border-gray-700">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow class="bg-gray-50 dark:bg-neutral-900 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <TableHead class="px-4 py-3">ID</TableHead>
                                <TableHead class="px-4 py-3">Nomi</TableHead>
                                <TableHead class="px-4 py-3">O‘qituvchi</TableHead>
                                <TableHead class="px-4 py-3">Oylik to‘lov</TableHead>
                                <TableHead class="px-4 py-3">Boshlanish sanasi</TableHead>
                                <TableHead class="px-4 py-3">Dars vaqti</TableHead>
                                <TableHead class="px-4 py-3">Yaratilgan sana</TableHead>
                                <TableHead class="px-4 py-3 text-center">Amallar</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="group in groups"
                                :key="group.id"
                                class="hover:bg-gray-100 dark:hover:bg-neutral-900 transition-colors"
                            >
                                <TableCell class="px-4 py-3">{{ group.id }}</TableCell>
                                <TableCell class="px-4 py-3">{{ group.name }}</TableCell>
                                <TableCell class="px-4 py-3">{{ group.teacher?.full_name || 'N/A' }}</TableCell>
                                <TableCell class="px-4 py-3">{{ group.monthly_fee }}</TableCell>
                                <TableCell class="px-4 py-3">{{ group.start_date || 'N/A' }}</TableCell>
                                <TableCell class="px-4 py-3">{{ group.time }}</TableCell>
                                <TableCell class="px-4 py-3">{{ group.created_at_formatted }}</TableCell>
                                <TableCell class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button @click="openEditModal(group)">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="openDeleteModal(group.id)">
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
                Guruhlar topilmadi.
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
                        Bu guruhni o‘chirishni xohlaysizmi?
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

        <!-- Add Group AlertDialog -->
        <AlertDialog v-model:open="isAddModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Yangi Guruh Qo‘shish
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Yangi guruh ma’lumotlarini kiriting.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newGroup.name"
                                placeholder="Guruh nomi"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.name?.length }"
                            />
                            <p v-if="errors.name?.length" class="text-red-500 text-sm mt-1">{{ errors.name[0] }}</p>
                        </div>
                        <div>
                            <Select
                                v-model.number="newGroup.teacher_id"
                                class="w-full"
                            >
                                <SelectTrigger
                                    class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                   bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                   shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                    :class="{ 'border-red-500': errors.teacher_id?.length }"
                                >
                                    <SelectValue placeholder="Tanlang" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="teacher in teachers"
                                        :key="teacher.id"
                                        :value="teacher.id"
                                    >
                                        {{ teacher.full_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="errors.teacher_id?.length" class="text-red-500 text-sm mt-1">{{ errors.teacher_id[0] }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newGroup.monthly_fee"
                                placeholder="Oylik to‘lov"
                                type="number"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.monthly_fee?.length }"
                            />
                            <p v-if="errors.monthly_fee?.length" class="text-red-500 text-sm mt-1">{{ errors.monthly_fee[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="newGroup.start_date"
                                placeholder="Boshlanish sanasi (YYYY-MM-DD)"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.start_date?.length }"
                            />
                            <p v-if="errors.start_date?.length" class="text-red-500 text-sm mt-1">{{ errors.start_date[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="newGroup.time"
                                placeholder="Dars vaqti"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.time?.length }"
                            />
                            <p v-if="errors.time?.length" class="text-red-500 text-sm mt-1">{{ errors.time[0] }}</p>
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

        <!-- Edit Group AlertDialog -->
        <AlertDialog v-model:open="isEditModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Guruh Ma’lumotlarini Tahrirlash
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Guruh ma’lumotlarini yangilang.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="editGroup.name"
                                placeholder="Guruh nomi"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.name?.length }"
                            />
                            <p v-if="errors.name?.length" class="text-red-500 text-sm mt-1">{{ errors.name[0] }}</p>
                        </div>
                        <div>
                            <Select v-model="editGroup.teacher_id">
                                <SelectTrigger
                                    id="teacher"
                                    class="w-full rounded-lg px-4 py-2 text-base text-gray-900 dark:text-gray-100
               bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
               shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none
               transition-all"
                                    :class="{ 'border-red-500': errors.teacher_id?.length }"
                                >
                                    <SelectValue :placeholder="selectedTeacherName" />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>O'qituvchilar</SelectLabel>
                                        <SelectItem
                                            v-for="teacher in teachers"
                                            :key="teacher.id"
                                            :value="teacher.id"
                                            class="text-gray-900 dark:text-gray-100 bg-white dark:bg-neutral-900"
                                        >
                                            {{ teacher.full_name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <p v-if="errors.teacher_id?.length" class="text-red-500 text-sm mt-1">
                                {{ errors.teacher_id[0] }}
                            </p>
                        </div>

                    </div>
                    <div class="space-y-6">
                        <div>
                            <Input
                                :model-value="editGroup.monthly_fee"
                                @update:model-value="val => editGroup.monthly_fee = val"
                                placeholder="Oylik to‘lov"
                                type="number"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
         bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
         shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.monthly_fee?.length }"
                            />

                            <p v-if="errors.monthly_fee?.length" class="text-red-500 text-sm mt-1">{{ errors.monthly_fee[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="editGroup.start_date"
                                placeholder="Boshlanish sanasi (YYYY-MM-DD)"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.start_date?.length }"
                            />
                            <p v-if="errors.start_date?.length" class="text-red-500 text-sm mt-1">{{ errors.start_date[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="editGroup.time"
                                placeholder="Dars vaqti"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.time?.length }"
                            />
                            <p v-if="errors.time?.length" class="text-red-500 text-sm mt-1">{{ errors.time[0] }}</p>
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
