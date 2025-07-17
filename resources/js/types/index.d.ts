import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

/**
 * Represents a teacher user object.
 */
export interface Teacher {
    id: number;
    full_name: string;
    phone: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

/**
 * Authenticated user data wrapper (for Ziggy/Inertia).
 */
export interface Auth {
    user: Teacher;
}

/**
 * Breadcrumb navigation item.
 */
export interface BreadcrumbItem {
    title: string;
    href: string;
}

/**
 * Navigation sidebar item.
 */
export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

/**
 * Main props passed to each Inertia page.
 */
export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: {
        message: string;
        author: string;
    };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

/**
 * Alias type for breadcrumbs if needed.
 */
export type BreadcrumbItemType = BreadcrumbItem;
