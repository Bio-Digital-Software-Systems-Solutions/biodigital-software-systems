import { Config } from 'ziggy-js';
import { User } from './models';

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    app: {
        name: string;
    };
    auth: {
        user: User | null;
    };
    ziggy: Config & { location: string };
    flash?: {
        message?: string;
        error?: string;
        success?: string;
    };
};

export * from './models';

export interface PaginatedData<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
        path: string;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
}