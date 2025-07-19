import { PageProps } from '@inertiajs/core';

export interface Student {
    id: number;
    full_name: string;
    phone: string;
}

export interface Group {
    id: number;
    name: string;
}

export interface Debt {
    id: number;
    student: Student;
    group: Group;
    amount: number;
    month: string;
    paid_amount: number;
    is_paid: boolean;
    status: string;
    created_at: string;
}

export interface Pagination {
    current_page: number;
    total_pages: number;
    total: number;
    per_page: number;
}

export interface DebtPageProps extends PageProps {
    debts: Debt[];
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
