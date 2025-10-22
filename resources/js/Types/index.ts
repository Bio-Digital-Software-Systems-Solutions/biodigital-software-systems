import { Config } from 'ziggy-js';
import { User } from './models';

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
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