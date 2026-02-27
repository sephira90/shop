export interface AuthUser {
    id: number;
    first_name?: string | null;
    last_name?: string | null;
    name: string;
    email: string;
    roles: string[];
    phone?: string | null;
    is_email_verified?: boolean;
}

export interface AuthTokenResult {
    token: string;
    user: AuthUser;
}

export interface AuthLoginPayload {
    email: string;
    password: string;
    guest_token?: string;
}

export interface AuthRegisterPayload {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export interface AuthUpdateProfilePayload {
    first_name: string;
    last_name: string;
    phone: string | null;
}
