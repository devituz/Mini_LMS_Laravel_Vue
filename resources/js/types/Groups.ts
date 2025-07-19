import { PageProps } from '@inertiajs/core';

export interface Teacher {
    id: number;
    full_name: string;
    phone: string;
}

export interface Group {
    id: number;
    name: string;
    teacher_id: number;
    teacher: Teacher;
    monthly_fee: string;
    start_date: string | null;
    time: string;
    created_at_formatted: string;
}

export interface Pagination {
    current_page: number;
    total_pages: number;
    total: number;
    per_page: number;
}

export interface CustomPageProps extends PageProps {
    groups: Group[];
    teachers: Teacher[]; // O'qituvchilar ro'yxati qo'shildi
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
