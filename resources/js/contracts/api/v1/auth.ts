export interface AuthUserWireDto {
    id: number;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    roles: string[];
    phone: string | null;
    is_email_verified: boolean;
}

export interface AuthTokenResultWireDto {
    token: string;
    user: AuthUserWireDto;
}
