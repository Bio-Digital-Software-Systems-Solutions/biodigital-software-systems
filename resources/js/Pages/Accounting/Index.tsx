import React, { useState, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Badge } from '@/Components/ui/badge';
import {
    BookOpenIcon,
    CogIcon,
    DocumentChartBarIcon,
    MagnifyingGlassIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/react/24/outline';
import type {
    AccountingSystem,
    OhadaAccountClass,
    OhadaFinancialStatement,
    PcgAccountClass,
    IfrsAccountClass,
} from '@/Types/accounting';
import AccountTreeView from './Components/AccountTreeView';

interface Props {
    ohadaClasses: OhadaAccountClass[];
    pcgClasses: PcgAccountClass[];
    ifrsClasses: IfrsAccountClass[];
    accountingSystems: AccountingSystem[];
    financialStatements: OhadaFinancialStatement[];
    stats: {
        totalOhadaAccounts: number;
        totalPcgAccounts: number;
        totalIfrsAccounts: number;
        totalSystems: number;
    };
}

export default function AccountingIndex({
    ohadaClasses,
    pcgClasses,
    ifrsClasses,
    accountingSystems,
    financialStatements,
    stats,
}: Props) {
    const [activeTab, setActiveTab] = useState('plans-comptables');
    const [activePlanTab, setActivePlanTab] = useState('ohada');
    const [searchTerm, setSearchTerm] = useState('');

    const statCards = [
        {
            label: 'Comptes OHADA',
            value: stats.totalOhadaAccounts,
            icon: BookOpenIcon,
            color: 'text-blue-600 dark:text-blue-400',
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
        },
        {
            label: 'Comptes PCG',
            value: stats.totalPcgAccounts,
            icon: ClipboardDocumentListIcon,
            color: 'text-purple-600 dark:text-purple-400',
            bgColor: 'bg-purple-50 dark:bg-purple-900/20',
        },
        {
            label: 'Comptes IFRS',
            value: stats.totalIfrsAccounts,
            icon: DocumentChartBarIcon,
            color: 'text-emerald-600 dark:text-emerald-400',
            bgColor: 'bg-emerald-50 dark:bg-emerald-900/20',
        },
        {
            label: 'Systèmes Comptables',
            value: stats.totalSystems,
            icon: CogIcon,
            color: 'text-orange-600 dark:text-orange-400',
            bgColor: 'bg-orange-50 dark:bg-orange-900/20',
        },
    ];

    interface FilterableAccount {
        id: number;
        account_number: string;
        name: string;
        normal_balance: 'debit' | 'credit';
        children: FilterableAccount[];
    }

    const filterAccounts = <T extends FilterableAccount>(accounts: T[]): T[] => {
        if (!searchTerm) {
            return accounts;
        }
        const term = searchTerm.toLowerCase();
        const matchesSearch = (account: T): boolean => {
            if (account.account_number.toLowerCase().includes(term) || account.name.toLowerCase().includes(term)) {
                return true;
            }
            return account.children?.some((c) => matchesSearch(c as T)) ?? false;
        };
        return accounts.filter(matchesSearch);
    };

    const filteredOhadaClasses = useMemo(() => {
        if (!searchTerm) {
            return ohadaClasses;
        }
        return ohadaClasses
            .map((cls) => ({
                ...cls,
                accounts: filterAccounts(cls.accounts),
            }))
            .filter((cls) => cls.accounts.length > 0);
    }, [ohadaClasses, searchTerm]);

    const filteredPcgClasses = useMemo(() => {
        if (!searchTerm) {
            return pcgClasses;
        }
        return pcgClasses
            .map((cls) => ({
                ...cls,
                accounts: filterAccounts(cls.accounts),
            }))
            .filter((cls) => cls.accounts.length > 0);
    }, [pcgClasses, searchTerm]);

    const filteredIfrsClasses = useMemo(() => {
        if (!searchTerm) {
            return ifrsClasses;
        }
        return ifrsClasses
            .map((cls) => ({
                ...cls,
                accounts: filterAccounts(cls.accounts),
            }))
            .filter((cls) => cls.accounts.length > 0);
    }, [ifrsClasses, searchTerm]);

    return (
        <DashboardLayout title="Tableau de Bord Comptabilité" description="Plans comptables, systèmes et états financiers">
            <Head title="Comptabilité" />

            {/* Stat Cards */}
            <div className="grid gap-3 sm:gap-4 grid-cols-2 lg:grid-cols-4 mb-6">
                {statCards.map((stat) => (
                    <Card key={stat.label} className="relative overflow-hidden">
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-gray-500 dark:text-gray-400">{stat.label}</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">{stat.value}</p>
                                </div>
                                <div className={`p-2 rounded-lg ${stat.bgColor}`}>
                                    <stat.icon className={`h-5 w-5 ${stat.color}`} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Main Tabs */}
            <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
                <div className="overflow-x-auto -mx-3 sm:mx-0 px-3 sm:px-0">
                    <TabsList className="flex w-max sm:grid sm:w-full sm:grid-cols-3 gap-1 p-1">
                        <TabsTrigger value="plans-comptables" className="flex items-center gap-2 px-3 whitespace-nowrap">
                            <BookOpenIcon className="h-4 w-4" />
                            <span>Plans Comptables</span>
                        </TabsTrigger>
                        <TabsTrigger value="systemes-comptables" className="flex items-center gap-2 px-3 whitespace-nowrap">
                            <CogIcon className="h-4 w-4" />
                            <span>Systèmes Comptables</span>
                        </TabsTrigger>
                        <TabsTrigger value="etats-financiers" className="flex items-center gap-2 px-3 whitespace-nowrap">
                            <DocumentChartBarIcon className="h-4 w-4" />
                            <span>États Financiers OHADA</span>
                        </TabsTrigger>
                    </TabsList>
                </div>

                {/* Tab 1: Plans Comptables */}
                <TabsContent value="plans-comptables">
                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <CardTitle>Plans Comptables</CardTitle>
                                    <CardDescription>Nomenclature des comptes par référentiel</CardDescription>
                                </div>
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Rechercher un compte..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-9 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full sm:w-64"
                                    />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {/* Sub-tabs for plan type */}
                            <Tabs value={activePlanTab} onValueChange={setActivePlanTab}>
                                <TabsList className="mb-4">
                                    <TabsTrigger value="ohada">
                                        OHADA
                                        <Badge variant="secondary" className="ml-2">{stats.totalOhadaAccounts}</Badge>
                                    </TabsTrigger>
                                    <TabsTrigger value="pcg">
                                        PCG
                                        <Badge variant="secondary" className="ml-2">{stats.totalPcgAccounts}</Badge>
                                    </TabsTrigger>
                                    <TabsTrigger value="ifrs">
                                        IFRS
                                        <Badge variant="secondary" className="ml-2">{stats.totalIfrsAccounts}</Badge>
                                    </TabsTrigger>
                                </TabsList>

                                {/* OHADA */}
                                <TabsContent value="ohada">
                                    <div className="space-y-3">
                                        {filteredOhadaClasses.map((cls) => (
                                            <ClassAccordion
                                                key={cls.id}
                                                classNumber={cls.class_number}
                                                name={cls.name}
                                                description={cls.description}
                                                category={cls.category}
                                                accountCount={cls.accounts.length}
                                            >
                                                <AccountTreeView accounts={cls.accounts} searchTerm={searchTerm} />
                                            </ClassAccordion>
                                        ))}
                                        {filteredOhadaClasses.length === 0 && (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                Aucun résultat pour "{searchTerm}"
                                            </div>
                                        )}
                                    </div>
                                </TabsContent>

                                {/* PCG */}
                                <TabsContent value="pcg">
                                    <div className="space-y-3">
                                        {filteredPcgClasses.map((cls) => (
                                            <ClassAccordion
                                                key={cls.id}
                                                classNumber={cls.class_number}
                                                name={cls.name}
                                                description={cls.description}
                                                category={cls.category}
                                                accountCount={cls.accounts.length}
                                            >
                                                <AccountTreeView accounts={cls.accounts} searchTerm={searchTerm} />
                                            </ClassAccordion>
                                        ))}
                                        {filteredPcgClasses.length === 0 && (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                Aucun résultat pour "{searchTerm}"
                                            </div>
                                        )}
                                    </div>
                                </TabsContent>

                                {/* IFRS */}
                                <TabsContent value="ifrs">
                                    <div className="space-y-3">
                                        {filteredIfrsClasses.map((cls) => (
                                            <ClassAccordion
                                                key={cls.id}
                                                classNumber={cls.class_number}
                                                name={cls.name}
                                                description={cls.description}
                                                category={cls.category}
                                                accountCount={cls.accounts.length}
                                            >
                                                <AccountTreeView accounts={cls.accounts} searchTerm={searchTerm} />
                                            </ClassAccordion>
                                        ))}
                                        {filteredIfrsClasses.length === 0 && (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                Aucun résultat pour "{searchTerm}"
                                            </div>
                                        )}
                                    </div>
                                </TabsContent>
                            </Tabs>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* Tab 2: Systèmes Comptables */}
                <TabsContent value="systemes-comptables">
                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Systèmes Comptables OHADA</CardTitle>
                                <CardDescription>
                                    Les trois systèmes comptables définis par le SYSCOHADA selon la taille de l'entreprise
                                </CardDescription>
                            </CardHeader>
                        </Card>
                        <div className="grid gap-4 md:grid-cols-3">
                            {accountingSystems.map((system) => (
                                <Card key={system.id} className="flex flex-col">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-center justify-between">
                                            <Badge className={getSystemBadgeColor(system.code)}>
                                                {system.code}
                                            </Badge>
                                            {system.is_active && (
                                                <Badge variant="outline" className="text-green-600 dark:text-green-400 border-green-300">
                                                    Actif
                                                </Badge>
                                            )}
                                        </div>
                                        <CardTitle className="text-lg mt-2">{system.name}</CardTitle>
                                    </CardHeader>
                                    <CardContent className="flex-1 space-y-4">
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {system.description}
                                        </p>

                                        {system.revenue_threshold && (
                                            <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Seuil de CA</p>
                                                <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {system.revenue_threshold}
                                                </p>
                                            </div>
                                        )}

                                        {system.applicable_entities && system.applicable_entities.length > 0 && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Entités concernées</p>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {system.applicable_entities.map((entity: string) => (
                                                        <Badge key={entity} variant="secondary" className="text-xs">
                                                            {entity}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {system.financial_statements && system.financial_statements.length > 0 && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">États financiers requis</p>
                                                <ul className="space-y-1">
                                                    {system.financial_statements.map((fs) => (
                                                        <li key={fs.id} className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                            <CheckCircleIcon className="h-4 w-4 text-green-500 flex-shrink-0" />
                                                            {fs.name}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}

                                        {system.required_statements && system.required_statements.length > 0 && (!system.financial_statements || system.financial_statements.length === 0) && (
                                            <div>
                                                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">États financiers requis</p>
                                                <ul className="space-y-1">
                                                    {system.required_statements.map((stmt: string) => (
                                                        <li key={stmt} className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                            <CheckCircleIcon className="h-4 w-4 text-green-500 flex-shrink-0" />
                                                            {stmt}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                        {accountingSystems.length === 0 && (
                            <Card>
                                <CardContent className="py-8 text-center text-gray-500 dark:text-gray-400">
                                    Aucun système comptable configuré
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </TabsContent>

                {/* Tab 3: États Financiers OHADA */}
                <TabsContent value="etats-financiers">
                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>États Financiers OHADA</CardTitle>
                                <CardDescription>
                                    Les documents de synthèse requis par le Système Comptable OHADA (SYSCOHADA)
                                </CardDescription>
                            </CardHeader>
                        </Card>
                        <div className="grid gap-4 md:grid-cols-2">
                            {financialStatements.map((statement) => (
                                <Card key={statement.id} className="flex flex-col">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                                <DocumentChartBarIcon className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div className="flex-1">
                                                <CardTitle className="text-lg">{statement.name}</CardTitle>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <Badge variant="outline" className="text-xs">{statement.code}</Badge>
                                                    {statement.is_required && (
                                                        <Badge className="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-0">
                                                            Obligatoire
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="flex-1 space-y-4">
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {statement.description}
                                        </p>

                                        {statement.accounting_system && (
                                            <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <CogIcon className="h-4 w-4" />
                                                <span>Système : {statement.accounting_system.name}</span>
                                            </div>
                                        )}

                                        {statement.structure && (
                                            <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Structure</p>
                                                {renderStructure(statement.structure)}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                        {financialStatements.length === 0 && (
                            <Card>
                                <CardContent className="py-8 text-center text-gray-500 dark:text-gray-400">
                                    Aucun état financier configuré
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </TabsContent>
            </Tabs>
        </DashboardLayout>
    );
}

// Class accordion component for plan comptable
function ClassAccordion({
    classNumber,
    name,
    description,
    category,
    accountCount,
    children,
}: {
    classNumber: number;
    name: string;
    description: string | null;
    category: string;
    accountCount: number;
    children: React.ReactNode;
}) {
    const [expanded, setExpanded] = useState(false);

    const categoryColors: Record<string, string> = {
        bilan: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        gestion: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        hors_bilan: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        assets: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        liabilities: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        equity: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        revenue: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        expenses: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };

    const categoryLabels: Record<string, string> = {
        bilan: 'Bilan',
        gestion: 'Gestion',
        hors_bilan: 'Hors bilan',
        assets: 'Actifs',
        liabilities: 'Passifs',
        equity: 'Capitaux',
        revenue: 'Produits',
        expenses: 'Charges',
    };

    return (
        <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <button
                onClick={() => setExpanded(!expanded)}
                className="w-full flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-left"
            >
                <ChevronRightIcon
                    className={`h-5 w-5 text-gray-400 transition-transform duration-200 flex-shrink-0 ${expanded ? 'rotate-90' : ''}`}
                />
                <span className="font-mono text-lg font-bold text-blue-600 dark:text-blue-400 flex-shrink-0">
                    {classNumber}
                </span>
                <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">{name}</p>
                    {description && (
                        <p className="text-xs text-gray-500 dark:text-gray-400 truncate">{description}</p>
                    )}
                </div>
                <Badge className={`text-xs border-0 flex-shrink-0 ${categoryColors[category] || 'bg-gray-100 text-gray-700'}`}>
                    {categoryLabels[category] || category}
                </Badge>
                <Badge variant="secondary" className="flex-shrink-0">
                    {accountCount}
                </Badge>
            </button>
            {expanded && (
                <div className="p-2 bg-white dark:bg-gray-900">
                    {children}
                </div>
            )}
        </div>
    );
}

function getSystemBadgeColor(code: string): string {
    switch (code) {
        case 'SN':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-0';
        case 'SA':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border-0';
        case 'SMT':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-0';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400 border-0';
    }
}

function renderStructure(structure: Record<string, unknown>): React.ReactNode {
    return (
        <ul className="space-y-1">
            {Object.entries(structure).map(([key, value]) => (
                <li key={key}>
                    <span className="text-xs font-medium text-gray-700 dark:text-gray-300 capitalize">
                        {key.replace(/_/g, ' ')} :
                    </span>
                    {Array.isArray(value) && (
                        <ul className="ml-4 mt-1 space-y-0.5">
                            {value.map((item, idx) => (
                                <li key={idx} className="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                    <span className="w-1 h-1 bg-gray-400 rounded-full flex-shrink-0" />
                                    {String(item)}
                                </li>
                            ))}
                        </ul>
                    )}
                </li>
            ))}
        </ul>
    );
}
