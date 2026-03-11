export interface AccountingSystem {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description: string | null;
    applicable_entities: string[] | null;
    required_statements: string[] | null;
    revenue_threshold: string | null;
    is_active: boolean;
    sort_order: number;
    financial_statements: OhadaFinancialStatement[];
    created_at: string;
    updated_at: string;
}

export interface OhadaAccountClass {
    id: number;
    uuid: string;
    class_number: number;
    name: string;
    description: string | null;
    category: 'bilan' | 'gestion' | 'hors_bilan';
    accounts: OhadaAccount[];
    accounts_count?: number;
    sort_order: number;
}

export interface OhadaAccount {
    id: number;
    uuid: string;
    account_number: string;
    name: string;
    description: string | null;
    class_id: number;
    parent_id: number | null;
    level: number;
    normal_balance: 'debit' | 'credit';
    is_active: boolean;
    children: OhadaAccount[];
    sort_order: number;
}

export interface OhadaFinancialStatement {
    id: number;
    uuid: string;
    name: string;
    code: string;
    description: string | null;
    accounting_system_id: number;
    accounting_system?: AccountingSystem;
    structure: Record<string, unknown> | null;
    is_required: boolean;
    sort_order: number;
}

export interface PcgAccountClass {
    id: number;
    uuid: string;
    class_number: number;
    name: string;
    description: string | null;
    category: string;
    accounts: PcgAccount[];
    accounts_count?: number;
    sort_order: number;
}

export interface PcgAccount {
    id: number;
    uuid: string;
    account_number: string;
    name: string;
    description: string | null;
    class_id: number;
    parent_id: number | null;
    level: number;
    normal_balance: 'debit' | 'credit';
    is_active: boolean;
    children: PcgAccount[];
    sort_order: number;
}

export interface IfrsAccountClass {
    id: number;
    uuid: string;
    class_number: number;
    name: string;
    description: string | null;
    category: string;
    accounts: IfrsAccount[];
    accounts_count?: number;
    sort_order: number;
}

export interface IfrsAccount {
    id: number;
    uuid: string;
    account_number: string;
    name: string;
    description: string | null;
    class_id: number;
    parent_id: number | null;
    level: number;
    normal_balance: 'debit' | 'credit';
    is_active: boolean;
    children: IfrsAccount[];
    sort_order: number;
}
