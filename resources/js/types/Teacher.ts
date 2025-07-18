// types/custom.ts

import { PageProps } from '@inertiajs/core';

export interface Teacher {
    id: number;
    full_name: string;
    phone: string;
    password: string;
    created_at_formatted : string;

}

export interface Pagination {
    current_page: number;
    total_pages: number;
    total: number;
    per_page: number;
}

export interface CustomPageProps extends PageProps {
    teachers: Teacher[];
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
