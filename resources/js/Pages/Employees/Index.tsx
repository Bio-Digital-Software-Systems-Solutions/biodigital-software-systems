import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    UserGroupIcon,
    UserIcon,
    ClockIcon,
    SparklesIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';

interface Employee {
    id: number;
    uuid: string;
    employee_number: string;
    full_name: string;
    position: string | null;
    job_title: string | null;
    status: {
        value: string;
        label: string;
        color: string;
    };
    employment_type: {
        value: string;
        label: string;
        color: string;
    };
    department: {
        id: number;
        uuid: string;
        name: string;
    } | null;
    hire_date: string | null;
    years_of_service: number | null;
    user: {
        id: number;
        uuid: string;
        name: string;
        email: string;
        avatar: string | null;
    } | null;
    avatar: string | null;
}

interface Status {
    value: string;
    label: string;
    color: string;
}

interface EmploymentType {
    value: string;
    label: string;
    color: string;
}

interface Department {
    id: number;
    uuid: string;
    name: string;
}

interface Props {
    employees: {
        data: Employee[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        status?: string;
        employment_type?: string;
        department?: string;
    };
    statuses: Status[];
    employmentTypes: EmploymentType[];
    departments: Department[];
    stats: {
        total: number;
        active: number;
        on_leave: number;
        new_this_month: number;
    };
}

export default function EmployeesIndex({
    employees,
    filters,
    statuses,
    employmentTypes,
    departments,
    stats,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/employees', { ...filters, search }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/employees', { ...filters, [key]: value || undefined }, { preserveState: true });
    };

    const getStatusBadgeClass = (color: string) => {
        const colors: Record<string, string> = {
            green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        };
        return colors[color] || colors.gray;
    };

    const getTypeColor = (color: string) => {
        const colors: Record<string, string> = {
            blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
            cyan: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
            green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        };
        return colors[color] || colors.blue;
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <DashboardLayout>
            <Head title="Employés" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Employés
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">
                            Gérez les informations et dossiers de vos employés
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/employees/create">
                            <PlusIcon className="h-4 w-4 mr-2" />
                            Nouvel Employé
                        </Link>
                    </Button>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">Total</p>
                                    <p className="text-2xl font-bold">{stats.total}</p>
                                </div>
                                <UserGroupIcon className="h-8 w-8 text-gray-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">Actifs</p>
                                    <p className="text-2xl font-bold text-green-600">{stats.active}</p>
                                </div>
                                <UserIcon className="h-8 w-8 text-green-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">En congé</p>
                                    <p className="text-2xl font-bold text-yellow-600">{stats.on_leave}</p>
                                </div>
                                <ClockIcon className="h-8 w-8 text-yellow-400" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500">Nouveaux ce mois</p>
                                    <p className="text-2xl font-bold text-blue-600">{stats.new_this_month}</p>
                                </div>
                                <SparklesIcon className="h-8 w-8 text-blue-400" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="py-4">
                        <div className="flex flex-wrap gap-4 items-end">
                            <form onSubmit={handleSearch} className="flex-1 min-w-[200px]">
                                <div className="relative">
                                    <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                                    <Input
                                        type="text"
                                        placeholder="Rechercher par nom, email, position..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </form>

                            <Select
                                value={filters.status || ''}
                                onValueChange={(value) => handleFilterChange('status', value)}
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue placeholder="Statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Tous les statuts</SelectItem>
                                    {statuses.map((status) => (
                                        <SelectItem key={status.value} value={status.value}>
                                            {status.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.employment_type || ''}
                                onValueChange={(value) => handleFilterChange('employment_type', value)}
                            >
                                <SelectTrigger className="w-44">
                                    <SelectValue placeholder="Type de contrat" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Tous les types</SelectItem>
                                    {employmentTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.department || ''}
                                onValueChange={(value) => handleFilterChange('department', value)}
                            >
                                <SelectTrigger className="w-44">
                                    <SelectValue placeholder="Département" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Tous les départements</SelectItem>
                                    {departments.map((dept) => (
                                        <SelectItem key={dept.id} value={dept.id.toString()}>
                                            {dept.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Employees List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Liste des employés ({employees.total})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b dark:border-gray-700">
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Employé</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Position</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Département</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Type</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Statut</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Ancienneté</th>
                                        <th className="text-right py-3 px-4 text-sm font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {employees.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="text-center py-8 text-gray-500">
                                                Aucun employé trouvé
                                            </td>
                                        </tr>
                                    ) : (
                                        employees.data.map((employee) => (
                                            <tr
                                                key={employee.uuid}
                                                className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                            >
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <Avatar>
                                                            {employee.avatar || employee.user?.avatar ? (
                                                                <AvatarImage src={employee.avatar || employee.user?.avatar || ''} />
                                                            ) : null}
                                                            <AvatarFallback className="bg-blue-100 text-blue-600">
                                                                {getInitials(employee.full_name)}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <Link
                                                                href={`/employees/${employee.uuid}`}
                                                                className="font-medium hover:text-blue-600"
                                                            >
                                                                {employee.full_name}
                                                            </Link>
                                                            <p className="text-xs text-gray-500">
                                                                {employee.employee_number}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div>
                                                        <p className="font-medium">{employee.position || '-'}</p>
                                                        {employee.job_title && (
                                                            <p className="text-xs text-gray-500">{employee.job_title}</p>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    {employee.department ? (
                                                        <Link
                                                            href={`/departments/${employee.department.uuid}`}
                                                            className="text-blue-600 hover:underline"
                                                        >
                                                            {employee.department.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-gray-400">-</span>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Badge className={getTypeColor(employee.employment_type.color)}>
                                                        {employee.employment_type.label}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Badge className={getStatusBadgeClass(employee.status.color)}>
                                                        {employee.status.label}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-4">
                                                    {employee.years_of_service !== null ? (
                                                        <span>{employee.years_of_service} ans</span>
                                                    ) : (
                                                        <span className="text-gray-400">-</span>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/employees/${employee.uuid}`}>
                                                            Voir
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {employees.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4 pt-4 border-t dark:border-gray-700">
                                <p className="text-sm text-gray-500">
                                    Affichage de {(employees.current_page - 1) * employees.per_page + 1} à{' '}
                                    {Math.min(employees.current_page * employees.per_page, employees.total)} sur{' '}
                                    {employees.total} résultats
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={employees.current_page === 1}
                                        onClick={() =>
                                            router.get('/employees', {
                                                ...filters,
                                                page: employees.current_page - 1,
                                            })
                                        }
                                    >
                                        <ChevronLeftIcon className="h-4 w-4" />
                                    </Button>
                                    <span className="text-sm">
                                        Page {employees.current_page} / {employees.last_page}
                                    </span>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={employees.current_page === employees.last_page}
                                        onClick={() =>
                                            router.get('/employees', {
                                                ...filters,
                                                page: employees.current_page + 1,
                                            })
                                        }
                                    >
                                        <ChevronRightIcon className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}
