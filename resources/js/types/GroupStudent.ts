import { PageProps } from '@inertiajs/core';

export interface Student {
    id: number;
    full_name: string;
}

export interface Group {
    id: number;
    name: string;
}

export interface GroupStudent {
    id: number;
    student: Student;
    group: Group;
    created_at: string;
}

export interface Pagination {
    current_page: number;
    total_pages: number;
    total: number;
    per_page: number;
}

export interface GroupStudentPageProps extends PageProps {
    relations: GroupStudent[];
    pagination: Pagination;
    search: string;
    flash: { success?: string };
    errors: Record<string, string[]>;
    name?: string;
    quote?: string;
    auth?: any;
    ziggy?: any;
    sidebarOpen?: boolean;
}
