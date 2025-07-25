```vue
<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import { PageProps } from '@inertiajs/core';
import AppLayout from '@/layouts/AppLayout.vue';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { ref, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import type { CustomPageProps, Teacher } from '@/types/Teacher';
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
    { title: 'Teachers', href: '/teachers' },
];

const teachers = ref<Teacher[]>(props.teachers || []);
const pagination = ref(props.pagination || {
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

// Use toast
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
            toast.error(serverErrors.error?.[0] || 'An error occurred.', { timeout: 5000 });
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
        teachers.value = newProps.teachers || [];
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
        route('teachers.index'),
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
                toast.error('An error occurred during search.', { timeout: 5000 });
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
            route('teachers.index'),
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
const deleteTeacherId = ref<number | null>(null);

const openDeleteModal = (id: number) => {
    deleteTeacherId.value = id;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteTeacherId.value = null;
};

const handleDelete = () => {
    if (deleteTeacherId.value !== null) {
        router.delete(route('teachers.destroy', deleteTeacherId.value), {
            onSuccess: () => {
                closeDeleteModal();
                router.get(route('teachers.index'));
            },
            onError: () => {
                toast.error('An error occurred while deleting.', { timeout: 5000 });
            },
        });
    }
};

// Add Modal
const isAddModalOpen = ref(false);
const newTeacher = ref<Partial<Teacher>>({
    full_name: '',
    phone: '',
    password: '',
});

const openAddModal = () => {
    isAddModalOpen.value = true;
    errors.value = {};
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    newTeacher.value = { full_name: '', phone: '', password: '' };
    errors.value = {};
};

const validateForm = (teacher: Partial<Teacher>) => {
    errors.value = {};
    let isValid = true;

    if (!teacher.full_name) {
        errors.value.full_name = ['Full name is required.'];
        isValid = false;
    }
    if (!teacher.phone) {
        errors.value.phone = ['Phone number is required.'];
        isValid = false;
    } else if (!/^\+?[1-9]\d{1,13}$/.test(teacher.phone)) {
        errors.value.phone = ['Invalid phone number format.'];
        isValid = false;
    }
    if (teacher.password && teacher.password.length < 6) {
        errors.value.password = ['Password must be at least 6 characters long.'];
        isValid = false;
    }

    if (!isValid) {
        toast.error('Please fill all fields correctly.', { timeout: 5000 });
    }

    return isValid;
};

const handleAdd = async () => {
    if (!validateForm(newTeacher.value)) {
        return;
    }

    try {
        await router.post(route('teachers.store'), newTeacher.value as Record<string, any>, {
            onSuccess: () => {
                closeAddModal();
                router.get(route('teachers.index'));
            },
            onError: (serverErrors) => {
                errors.value = serverErrors;
                toast.error('Server error: Could not add data.', { timeout: 5000 });
            },
        });
    } catch (error) {
        console.error('Error:', error);
        toast.error('A system error occurred.', { timeout: 5000 });
    }
};

// Edit Modal
const isEditModalOpen = ref(false);
const editTeacher = ref<Partial<Teacher>>({
    id: 0,
    full_name: '',
    phone: '',
    password: '',
});

const openEditModal = (teacher: Teacher) => {
    editTeacher.value = {
        id: teacher.id,
        full_name: teacher.full_name,
        phone: teacher.phone,
        password: '',
    };
    isEditModalOpen.value = true;
    errors.value = {};
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editTeacher.value = { id: 0, full_name: '', phone: '', password: '' };
    errors.value = {};
};

const handleEdit = async () => {
    if (!validateForm(editTeacher.value)) {
        return;
    }

    try {
        await router.put(route('teachers.update', editTeacher.value.id), editTeacher.value as Record<string, any>, {
            onSuccess: () => {
                closeEditModal();
                router.get(route('teachers.index'));
            },
            onError: (serverErrors) => {
                errors.value = serverErrors;
                toast.error('Server error: Could not update data.', { timeout: 5000 });
            },
        });
    } catch (error) {
        console.error('Error:', error);
        toast.error('A system error occurred.', { timeout: 5000 });
    }
};
</script>

<template>
    <Head title="Teachers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="pt-7 space-y-6">
            <!-- Search -->
            <div class="w-full flex flex-col md:flex-row justify-between p-4 mx-auto px-4 md:px-6 lg:px-8">
                <div class="w-full md:w-1/4 relative">
                    <Input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search teachers..."
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
                        New Teacher
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
                                <TableHead class="px-4 py-3">Name</TableHead>
                                <TableHead class="px-4 py-3">Phone</TableHead>
                                <TableHead class="px-4 py-3">Created At</TableHead>
                                <TableHead class="px-4 py-3 text-center">Actions</TableHead>
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
                                <TableCell class="px-4 py-3">{{ teacher.created_at_formatted }}</TableCell>
                                <TableCell class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button @click="openEditModal(teacher)">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
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

            <!-- Empty State -->
            <div v-else class="text-center text-gray-500 py-10 text-lg font-semibold">
                No teachers found.
            </div>
        </div>

        <!-- Delete Confirmation AlertDialog -->
        <AlertDialog v-model:open="isDeleteModalOpen">
            <AlertDialogContent class="w-96 p-6 space-y-4">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Confirm Deletion
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Are you sure you want to delete this teacher?
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
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="handleDelete"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
                               text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 border border-red-600 dark:border-red-700 shadow-sm
                               focus:ring-2 focus:ring-red-500 focus:outline-none transition-all"
                    >
                        Delete
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Add Teacher AlertDialog -->
        <AlertDialog v-model:open="isAddModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Add New Teacher
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Enter the details for the new teacher.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newTeacher.full_name"
                                placeholder="Full Name"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.full_name?.length }"
                            />
                            <p v-if="errors.full_name?.length" class="text-red-500 text-sm mt-1">{{ errors.full_name[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="newTeacher.password"
                                type="password"
                                placeholder="Password"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.password?.length }"
                            />
                            <p v-if="errors.password?.length" class="text-red-500 text-sm mt-1">{{ errors.password[0] }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="newTeacher.phone"
                                placeholder="Phone Number"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.phone?.length }"
                            />
                            <p v-if="errors.phone?.length" class="text-red-500 text-sm mt-1">{{ errors.phone[0] }}</p>
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
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="handleAdd"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
                               text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 border border-blue-600 dark:border-blue-700 shadow-sm
                               focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all"
                    >
                        Add
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Edit Teacher AlertDialog -->
        <AlertDialog v-model:open="isEditModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Edit Teacher Details
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Update the teacher's information.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="editTeacher.full_name"
                                placeholder="Full Name"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.full_name?.length }"
                            />
                            <p v-if="errors.full_name?.length" class="text-red-500 text-sm mt-1">{{ errors.full_name[0] }}</p>
                        </div>
                        <div>
                            <Input
                                v-model="editTeacher.password"
                                type="password"
                                placeholder="New Password (if changing)"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.password?.length }"
                            />
                            <p v-if="errors.password?.length" class="text-red-500 text-sm mt-1">{{ errors.password[0] }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Input
                                v-model="editTeacher.phone"
                                placeholder="Phone Number"
                                class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                       bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                       shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                :class="{ 'border-red-500': errors.phone?.length }"
                            />
                            <p v-if="errors.phone?.length" class="text-red-500 text-sm mt-1">{{ errors.phone[0] }}</p>
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
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="handleEdit"
                        class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg
                               text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 border border-blue-600 dark:border-blue-700 shadow-sm
                               focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all"
                    >
                        Update
                    </button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
```
