export interface User {
    id: number;
    name: string;
    username: string;
    email?: string;
    role: 'OWNER' | 'ADMIN' | 'PARTNER' | 'CLIENT';
    status: string;
    email_verified_at?: string;
}

export interface ActiveProject {
    id: number;
    name: string;
    status: string;
    key_prefix?: string;
}

export interface ProjectSummary {
    id: number;
    name: string;
    status: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: { success?: string; project_key?: string };
    applicationContext: {
        active?: ActiveProject;
        projects: ProjectSummary[];
    };
};