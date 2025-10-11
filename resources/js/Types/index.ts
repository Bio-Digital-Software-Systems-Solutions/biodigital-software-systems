import { Config } from 'ziggy-js';

export interface User {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    email_verified_at?: string;
    birth_date?: string;
    avatar?: string;
    roles: string[];
    permissions: string[];
    departments?: any[];
    full_name?: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
    };
    ziggy: Config & { location: string };
    flash?: {
        message?: string;
        error?: string;
    };
};

export * from './models';