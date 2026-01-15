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
    StarIcon,
    SparklesIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/react/24/solid';

interface Star {
    id: number;
    uuid: string;
    star_number: string;
    full_name: string;
    title: string | null;
    status: {
        value: string;
        label: string;
        color: string;
    };
    type: {
        value: string;
        label: string;
        color: string;
    };
    category: {
        value: string;
        label: string;
        color: string;
    } | null;
    department: {
        id: number;
        uuid: string;
        name: string;
    } | null;
    level: number;
    level_title: string;
    points: number;
    total_hours_served: number;
    is_featured: boolean;
    is_public_profile: boolean;
    recognition_date: string | null;
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

interface Type {
    value: string;
    label: string;
    color: string;
}

interface Category {
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
    stars: {
        data: Star[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        status?: string;
        type?: string;
        category?: string;
        department?: string;
        level?: string;
        featured?: boolean;
    };
    statuses: Status[];
    types: Type[];
    categories: Category[];
    departments: Department[];
    stats: {
        total: number;
        active: number;
        featured: number;
        new_this_month: number;
    };
}

export default function StarsIndex({
    stars,
    filters,
    statuses,
    types,
    categories,
    departments,
    stats,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/stars', { ...filters, search }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/stars', { ...filters, [key]: value || undefined }, { preserveState: true });
    };

    const getStatusBadgeClass = (color: string) => {
        const colors: Record<string, string> = {
            green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
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
            pink: 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
            indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        };
        return colors[color] || colors.blue;
    };

    const getLevelColor = (level: number) => {
        const colors: Record<number, string> = {
            1: 'text-amber-600', // Bronze
            2: 'text-gray-400', // Argent
            3: 'text-yellow-500', // Or
            4: 'text-cyan-400', // Platine
            5: 'text-purple-400', // Diamant
        };
        return colors[level] || colors[1];
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
            <Head title="Stars" />

            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Stars
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">
                            Gérez les bénévoles et leurs reconnaissances
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/stars/create">
                            <PlusIcon className="h-4 w-4 mr-2" />
                            Nouvelle Star
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
                                    <p className="text-sm text-gray-500">En vedette</p>
                                    <p className="text-2xl font-bold text-yellow-600">{stats.featured}</p>
                                </div>
                                <StarIcon className="h-8 w-8 text-yellow-400" />
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
                                        placeholder="Rechercher par nom, email..."
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
                                <SelectTrigger className="w-36">
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
                                value={filters.type || ''}
                                onValueChange={(value) => handleFilterChange('type', value)}
                            >
                                <SelectTrigger className="w-36">
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Tous les types</SelectItem>
                                    {types.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.category || ''}
                                onValueChange={(value) => handleFilterChange('category', value)}
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue placeholder="Catégorie" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Toutes catégories</SelectItem>
                                    {categories.map((cat) => (
                                        <SelectItem key={cat.value} value={cat.value}>
                                            {cat.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters.department || ''}
                                onValueChange={(value) => handleFilterChange('department', value)}
                            >
                                <SelectTrigger className="w-40">
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

                {/* Stars List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Liste des stars ({stars.total})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b dark:border-gray-700">
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Star</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Type</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Catégorie</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Département</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Niveau</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Points</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Statut</th>
                                        <th className="text-right py-3 px-4 text-sm font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {stars.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="text-center py-8 text-gray-500">
                                                Aucune star trouvée
                                            </td>
                                        </tr>
                                    ) : (
                                        stars.data.map((star) => (
                                            <tr
                                                key={star.uuid}
                                                className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                            >
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="relative">
                                                            <Avatar>
                                                                {star.avatar || star.user?.avatar ? (
                                                                    <AvatarImage src={star.avatar || star.user?.avatar || ''} />
                                                                ) : null}
                                                                <AvatarFallback className="bg-yellow-100 text-yellow-600">
                                                                    {getInitials(star.full_name)}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            {star.is_featured && (
                                                                <StarIconSolid className="absolute -top-1 -right-1 h-4 w-4 text-yellow-400" />
                                                            )}
                                                        </div>
                                                        <div>
                                                            <Link
                                                                href={`/stars/${star.uuid}`}
                                                                className="font-medium hover:text-yellow-600"
                                                            >
                                                                {star.full_name}
                                                            </Link>
                                                            <p className="text-xs text-gray-500">
                                                                {star.star_number}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Badge className={getTypeColor(star.type.color)}>
                                                        {star.type.label}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-4">
                                                    {star.category ? (
                                                        <Badge variant="outline" className={getTypeColor(star.category.color)}>
                                                            {star.category.label}
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-gray-400">-</span>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4">
                                                    {star.department ? (
                                                        <Link
                                                            href={`/departments/${star.department.uuid}`}
                                                            className="text-blue-600 hover:underline"
                                                        >
                                                            {star.department.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-gray-400">-</span>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-1">
                                                        <StarIconSolid className={`h-4 w-4 ${getLevelColor(star.level)}`} />
                                                        <span className="text-sm font-medium">{star.level_title}</span>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <span className="font-medium">{star.points}</span>
                                                    <span className="text-xs text-gray-500 ml-1">pts</span>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Badge className={getStatusBadgeClass(star.status.color)}>
                                                        {star.status.label}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/stars/${star.uuid}`}>
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
                        {stars.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4 pt-4 border-t dark:border-gray-700">
                                <p className="text-sm text-gray-500">
                                    Affichage de {(stars.current_page - 1) * stars.per_page + 1} à{' '}
                                    {Math.min(stars.current_page * stars.per_page, stars.total)} sur{' '}
                                    {stars.total} résultats
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={stars.current_page === 1}
                                        onClick={() =>
                                            router.get('/stars', {
                                                ...filters,
                                                page: stars.current_page - 1,
                                            })
                                        }
                                    >
                                        <ChevronLeftIcon className="h-4 w-4" />
                                    </Button>
                                    <span className="text-sm">
                                        Page {stars.current_page} / {stars.last_page}
                                    </span>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={stars.current_page === stars.last_page}
                                        onClick={() =>
                                            router.get('/stars', {
                                                ...filters,
                                                page: stars.current_page + 1,
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
