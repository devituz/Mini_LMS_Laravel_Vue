```vue
<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { ref, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import type { GroupStudentPageProps, GroupStudent, Student, Group } from '@/types/group-student';
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
} from '@/components/ui/select';
import { debounce } from 'lodash';
import { useToast } from 'vue-toastification';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const props = page.props as GroupStudentPageProps;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Group-Student Relations', href: '/group-students' },
];

const relations = ref<GroupStudent[]>(props.relations || []);
const pagination = ref(props.pagination || {
    current_page: 1,
    total_pages: 1,
    total: 0,
    per_page: 5,
});
const searchQuery = ref(props.search || '');
const isSearching = ref(false);
const students = ref<Student[]>(props.students || []);
const groups = ref<Group[]>(props.groups || []);

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
        relations.value = newProps.relations || [];
        pagination.value = newProps.pagination || {
            current_page: 1,
            total_pages: 1,
            total: 0,
            per_page: 5,
        };
        searchQuery.value = newProps.search || '';
        students.value = newProps.students || [];
        groups.value = newProps.groups || [];
    },
    { deep: true }
);

// Debounced server-side search
const debouncedSearch = debounce(() => {
    isSearching.value = true;
    router.get(
        route('group-students.index'),
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
            route('group-students.index'),
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
const deleteRelationId = ref<number | null>(null);

const openDeleteModal = (id: number) => {
    deleteRelationId.value = id;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteRelationId.value = null;
};

const handleDelete = () => {
    if (deleteRelationId.value !== null) {
        router.delete(route('group-students.destroy', deleteRelationId.value), {
            onSuccess: () => {
                closeDeleteModal();
                router.get(route('group-students.index'));
            },
            onError: () => {
                toast.error('An error occurred while deleting.', { timeout: 5000 });
            },
        });
    }
};

// Add Modal
const isAddModalOpen = ref(false);
const newRelation = ref<Partial<GroupStudent>>({
    student_id: 0,
    group_id: 0,
});

const openAddModal = () => {
    isAddModalOpen.value = true;
    errors.value = {};
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    newRelation.value = { student_id: 0, group_id: 0 };
    errors.value = {};
};

const validateForm = (relation: Partial<GroupStudent>) => {
    errors.value = {};
    let isValid = true;

    if (!relation.student_id) {
        errors.value.student_id = ['Student must be selected.'];
        isValid = false;
    }
    if (!relation.group_id) {
        errors.value.group_id = ['Group must be selected.'];
        isValid = false;
    }

    if (!isValid) {
        toast.error('Please fill all fields correctly.', { timeout: 5000 });
    }

    return isValid;
};

const handleAdd = async () => {
    if (!validateForm(newRelation.value)) {
        return;
    }

    try {
        await router.post(route('group-students.store'), newRelation.value as Record<string, any>, {
            onSuccess: () => {
                closeAddModal();
                router.get(route('group-students.index'));
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
const editRelation = ref<Partial<GroupStudent>>({
    id: 0,
    student_id: 0,
    group_id: 0,
});

const openEditModal = (relation: GroupStudent) => {
    editRelation.value = {
        id: relation.id,
        student_id: relation.student.id,
        group_id: relation.group.id,
    };
    isEditModalOpen.value = true;
    errors.value = {};
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editRelation.value = { id: 0, student_id: 0, group_id: 0 };
    errors.value = {};
};

const handleEdit = async () => {
    if (!validateForm(editRelation.value)) {
        return;
    }

    try {
        await router.put(route('group-students.update', editRelation.value.id), editRelation.value as Record<string, any>, {
            onSuccess: () => {
                closeEditModal();
                router.get(route('group-students.index'));
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
    <Head title="Group-Student Relations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="pt-7 space-y-6">
            <!-- Search -->
            <div class="w-full flex flex-col md:flex-row justify-between p-4 mx-auto px-4 md:px-6 lg:px-8">
                <div class="w-full md:w-1/4 relative">
                    <Input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search students or groups..."
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
                        New Relation
                    </Button>
                </div>
            </div>

            <!-- Table or Empty State -->
            <div v-if="relations.length > 0" class="mx-auto px-4 md:px-6 lg:px-8">
                <div class="rounded-2xl overflow-hidden border border-gray-300 dark:border-gray-700">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow class="bg-gray-50 dark:bg-neutral-900 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <TableHead class="px-4 py-3">ID</TableHead>
                                <TableHead class="px-4 py-3">Student</TableHead>
                                <TableHead class="px-4 py-3">Group</TableHead>
                                <TableHead class="px-4 py-3">Created At</TableHead>
                                <TableHead class="px-4 py-3 text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="relation in relations"
                                :key="relation.id"
                                class="hover:bg-gray-100 dark:hover:bg-neutral-900 transition-colors"
                            >
                                <TableCell class="px-4 py-3">{{ relation.id }}</TableCell>
                                <TableCell class="px-4 py-3">{{ relation.student.full_name }}</TableCell>
                                <TableCell class="px-4 py-3">{{ relation.group.name }}</TableCell>
                                <TableCell class="px-4 py-3">{{ relation.created_at }}</TableCell>
                                <TableCell class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button @click="openEditModal(relation)">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="openDeleteModal(relation.id)">
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
                No group-student relations found.
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
                        Are you sure you want to delete this group-student relation?
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

        <!-- Add Relation AlertDialog -->
        <AlertDialog v-model:open="isAddModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Add New Group-Student Relation
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Select a student and group.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Select v-model.number="newRelation.student_id">
                                <SelectTrigger
                                    class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                           bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                           shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                    :class="{ 'border-red-500': errors.student_id?.length }"
                                >
                                    <SelectValue placeholder="Select a student" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Students</SelectLabel>
                                        <SelectItem
                                            v-for="student in students"
                                            :key="student.id"
                                            :value="student.id"
                                        >
                                            {{ student.full_name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <p v-if="errors.student_id?.length" class="text-red-500 text-sm mt-1">{{ errors.student_id[0] }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Select v-model.number="newRelation.group_id">
                                <SelectTrigger
                                    class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                           bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                           shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                    :class="{ 'border-red-500': errors.group_id?.length }"
                                >
                                    <SelectValue placeholder="Select a group" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Groups</SelectLabel>
                                        <SelectItem
                                            v-for="group in groups"
                                            :key="group.id"
                                            :value="group.id"
                                        >
                                            {{ group.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <p v-if="errors.group_id?.length" class="text-red-500 text-sm mt-1">{{ errors.group_id[0] }}</p>
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

        <!-- Edit Relation AlertDialog -->
        <AlertDialog v-model:open="isEditModalOpen">
            <AlertDialogContent class="w-full max-w-2xl p-8 space-y-6">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Edit Group-Student Relation
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-gray-600 dark:text-gray-300">
                        Update the group-student relation.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <Select v-model.number="editRelation.student_id">
                                <SelectTrigger
                                    class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                           bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                           shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                    :class="{ 'border-red-500': errors.student_id?.length }"
                                >
                                    <SelectValue placeholder="Select a student" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Students</SelectLabel>
                                        <SelectItem
                                            v-for="student in students"
                                            :key="student.id"
                                            :value="student.id"
                                        >
                                            {{ student.full_name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <p v-if="errors.student_id?.length" class="text-red-500 text-sm mt-1">{{ errors.student_id[0] }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <Select v-model.number="editRelation.group_id">
                                <SelectTrigger
                                    class="w-full rounded-lg px-5 py-4 text-lg text-gray-900 dark:text-gray-100
                                           bg-white dark:bg-neutral-950 border border-gray-300 dark:border-gray-700
                                           shadow-sm focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:outline-none transition-all"
                                    :class="{ 'border-red-500': errors.group_id?.length }"
                                >
                                    <SelectValue placeholder="Select a group" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Groups</SelectLabel>
                                        <SelectItem
                                            v-for="group in groups"
                                            :key="group.id"
                                            :value="group.id"
                                        >
                                            {{ group.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <p v-if="errors.group_id?.length" class="text-red-500 text-sm mt-1">{{ errors.group_id[0] }}</p>
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
