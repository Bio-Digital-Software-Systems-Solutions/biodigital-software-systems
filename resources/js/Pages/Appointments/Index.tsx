import React, { useState, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    CalendarDays,
    Clock,
    MapPin,
    Users,
    Plus,
    Search,
    Filter,
    Eye,
    Edit,
    Trash2,
    Calendar,
    MoreHorizontal,
    CheckCircle,
    XCircle,
    AlertCircle,
    User,
    Download,
    Upload,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { UserSelect } from '@/Components/ui/user-select';
import { PublicAgendaView } from '@/Components/ui/public-agenda-view';
import { toast } from 'sonner';
import { format, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';
import { Calendar as CalendarPicker } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';

import type { AppointmentPageProps, Appointment, AppointmentStatus, AppointmentType } from '@/Types/appointment';

interface User {
    id: number;
    uuid: string;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
}

export default function AppointmentIndex() {
    const { appointments, stats, filters, statuses, types } = usePage<AppointmentPageProps>().props;
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
    const [appointmentToDelete, setAppointmentToDelete] = useState<Appointment | null>(null);
    const [selectedUser, setSelectedUser] = useState<User | null>(null);
    const [showUserAgenda, setShowUserAgenda] = useState(false);
    const [datePickerOpen, setDatePickerOpen] = useState(false);
    const [importDialogOpen, setImportDialogOpen] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleExportBulk = () => {
        const params: Record<string, string> = {};
        if (filters.status) params.status = filters.status;
        if (filters.type) params.type = filters.type;
        window.open(route('appointments.export-bulk-ics', params));
    };

    const handleImport = () => {
        if (!importFile) return;

        const formData = new FormData();
        formData.append('file', importFile);

        setImporting(true);
        router.post(route('appointments.import-ics'), formData, {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Fichier importé avec succès');
                setImportDialogOpen(false);
                setImportFile(null);
            },
            onError: (errors) => {
                const message = Object.values(errors)[0] || 'Erreur lors de l\'import';
                toast.error(message as string);
            },
            onFinish: () => {
                setImporting(false);
            },
        });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('appointments.index'), {
            ...filters,
            search: searchTerm,
            page: 1,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (field: string, value: string) => {
        router.get(route('appointments.index'), {
            ...filters,
            [field]: value === 'all' ? undefined : value,
            page: 1,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (appointment: Appointment) => {
        setAppointmentToDelete(appointment);
        setDeleteConfirmOpen(true);
    };

    const confirmDelete = () => {
        if (appointmentToDelete) {
            router.delete(route('appointments.destroy', appointmentToDelete.uuid), {
                onSuccess: () => {
                    toast.success('Rendez-vous supprimé avec succès');
                },
                onError: () => {
                    toast.error('Erreur lors de la suppression');
                }
            });
        }
        setDeleteConfirmOpen(false);
        setAppointmentToDelete(null);
    };

    const handleStatusAction = (appointment: Appointment, action: 'confirm' | 'cancel') => {
        const routeName = action === 'confirm' ? 'appointments.confirm' : 'appointments.cancel';
        const message = action === 'confirm' ? 'Rendez-vous confirmé' : 'Rendez-vous annulé';

        router.patch(route(routeName, appointment.uuid), {}, {
            onSuccess: () => {
                toast.success(message);
            },
            onError: () => {
                toast.error('Erreur lors de la mise à jour');
            }
        });
    };

    const handleUserSelect = (user: User | null) => {
        setSelectedUser(user);
        setShowUserAgenda(!!user);
    };

    const handleCreateAppointmentFromAgenda = (user: User, date: string, startTime: string) => {
        router.get(route('appointments.create'), {
            date: date,
            time: startTime,
            participant_ids: [user.id],
            title: `Rendez-vous avec ${user.name}`,
        });
    };

    const getStatusIcon = (status: AppointmentStatus) => {
        switch (status) {
            case 'confirmed':
                return <CheckCircle className="h-4 w-4 text-green-600" />;
            case 'cancelled':
                return <XCircle className="h-4 w-4 text-red-600" />;
            case 'completed':
                return <CheckCircle className="h-4 w-4 text-blue-600" />;
            default:
                return <AlertCircle className="h-4 w-4 text-yellow-600" />;
        }
    };

    const getStatusVariant = (status: AppointmentStatus): "default" | "secondary" | "destructive" | "outline" => {
        switch (status) {
            case 'confirmed':
                return 'default';
            case 'cancelled':
                return 'destructive';
            case 'completed':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getTypeIcon = (type: AppointmentType) => {
        switch (type) {
            case 'group':
                return <Users className="h-4 w-4" />;
            case 'consultation':
                return <User className="h-4 w-4" />;
            case 'meeting':
                return <Users className="h-4 w-4" />;
            default:
                return <User className="h-4 w-4" />;
        }
    };

    const renderStatsCards = () => (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center space-x-2">
                        <CalendarDays className="h-5 w-5 text-blue-600" />
                        <div>
                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Total</p>
                            <p className="text-2xl font-bold">{stats.total}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center space-x-2">
                        <Clock className="h-5 w-5 text-green-600" />
                        <div>
                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400">À venir</p>
                            <p className="text-2xl font-bold">{stats.upcoming}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center space-x-2">
                        <CalendarDays className="h-5 w-5 text-purple-600" />
                        <div>
                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Aujourd'hui</p>
                            <p className="text-2xl font-bold">{stats.today}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center space-x-2">
                        <AlertCircle className="h-5 w-5 text-yellow-600" />
                        <div>
                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400">En attente</p>
                            <p className="text-2xl font-bold">{stats.pending}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center space-x-2">
                        <CheckCircle className="h-5 w-5 text-green-600" />
                        <div>
                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Confirmés</p>
                            <p className="text-2xl font-bold">{stats.confirmed}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );

    const renderFilters = () => (
        <Card className="mb-6">
            <CardContent className="p-4">
                <div className="flex flex-col lg:flex-row gap-4">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input
                                type="text"
                                placeholder="Rechercher un rendez-vous..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </form>

                    <div className="flex gap-2">
                        <Select
                            value={filters.status || 'all'}
                            onValueChange={(value) => handleFilterChange('status', value)}
                        >
                            <SelectTrigger className="w-[140px]">
                                <SelectValue placeholder="Statut" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les statuts</SelectItem>
                                {statuses.map((status) => (
                                    <SelectItem key={status} value={status}>
                                        {status === 'pending' && 'En attente'}
                                        {status === 'confirmed' && 'Confirmé'}
                                        {status === 'cancelled' && 'Annulé'}
                                        {status === 'completed' && 'Terminé'}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.type || 'all'}
                            onValueChange={(value) => handleFilterChange('type', value)}
                        >
                            <SelectTrigger className="w-[140px]">
                                <SelectValue placeholder="Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les types</SelectItem>
                                {types.map((type) => (
                                    <SelectItem key={type} value={type}>
                                        {type === 'individual' && 'Individuel'}
                                        {type === 'group' && 'Groupe'}
                                        {type === 'consultation' && 'Consultation'}
                                        {type === 'meeting' && 'Réunion'}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Popover open={datePickerOpen} onOpenChange={setDatePickerOpen}>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    className={cn(
                                        "w-[160px] justify-start text-left font-normal",
                                        !filters.date && "text-muted-foreground"
                                    )}
                                >
                                    <CalendarDays className="mr-2 h-4 w-4" />
                                    {filters.date
                                        ? format(new Date(filters.date), 'd MMM yyyy', { locale: fr })
                                        : 'Sélectionner'
                                    }
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0" align="end">
                                <CalendarPicker
                                    mode="single"
                                    selected={filters.date ? new Date(filters.date) : undefined}
                                    onSelect={(date) => {
                                        handleFilterChange('date', date ? format(date, 'yyyy-MM-dd') : '');
                                        setDatePickerOpen(false);
                                    }}
                                    locale={fr}
                                    initialFocus
                                />
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>
            </CardContent>
        </Card>
    );

    const renderAppointmentCard = (appointment: Appointment) => (
        <Card key={appointment.id} className="hover:shadow-md transition-shadow">
            <CardContent className="p-4">
                <div className="flex items-center justify-between mb-3">
                    <div className="flex items-center space-x-2">
                        {getTypeIcon(appointment.type)}
                        <Link
                            href={route('appointments.show', appointment.uuid)}
                            className="font-semibold text-lg hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                        >
                            {appointment.title}
                        </Link>
                        <Badge variant={getStatusVariant(appointment.status)}>
                            <div className="flex items-center space-x-1">
                                {getStatusIcon(appointment.status)}
                                <span>
                                    {appointment.status === 'pending' && 'En attente'}
                                    {appointment.status === 'confirmed' && 'Confirmé'}
                                    {appointment.status === 'cancelled' && 'Annulé'}
                                    {appointment.status === 'completed' && 'Terminé'}
                                </span>
                            </div>
                        </Badge>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm">
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem asChild>
                                <Link href={route('appointments.show', appointment.uuid)}>
                                    <Eye className="h-4 w-4 mr-2" />
                                    Voir
                                </Link>
                            </DropdownMenuItem>
                            {appointment.can_be_modified && (
                                <DropdownMenuItem asChild>
                                    <Link href={route('appointments.edit', appointment.uuid)}>
                                        <Edit className="h-4 w-4 mr-2" />
                                        Modifier
                                    </Link>
                                </DropdownMenuItem>
                            )}
                            {appointment.status === 'pending' && (
                                <DropdownMenuItem
                                    onClick={() => handleStatusAction(appointment, 'confirm')}
                                >
                                    <CheckCircle className="h-4 w-4 mr-2 text-green-600" />
                                    Confirmer
                                </DropdownMenuItem>
                            )}
                            {appointment.can_be_cancelled && (
                                <DropdownMenuItem
                                    onClick={() => handleStatusAction(appointment, 'cancel')}
                                >
                                    <XCircle className="h-4 w-4 mr-2 text-red-600" />
                                    Annuler
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={() => handleDelete(appointment)}
                                className="text-red-600"
                            >
                                <Trash2 className="h-4 w-4 mr-2" />
                                Supprimer
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                {appointment.description && (
                    <p className="text-gray-600 dark:text-gray-400 mb-3">
                        {appointment.description}
                    </p>
                )}

                <div className="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <div className="flex items-center space-x-1">
                        <CalendarDays className="h-4 w-4" />
                        <span>{format(parseISO(appointment.start_datetime), 'EEEE d MMMM yyyy', { locale: fr })}</span>
                    </div>
                    <div className="flex items-center space-x-1">
                        <Clock className="h-4 w-4" />
                        <span>{appointment.formatted_time_range}</span>
                    </div>
                    {appointment.location && (
                        <div className="flex items-center space-x-1">
                            <MapPin className="h-4 w-4" />
                            <span>{appointment.location}</span>
                        </div>
                    )}
                </div>

                <div className="flex items-center justify-between mt-3 pt-3 border-t">
                    <div className="flex items-center space-x-2">
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                            Organisé par {appointment.organizer?.name}
                        </span>
                    </div>
                    {appointment.participants_count > 0 && (
                        <div className="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-400">
                            <Users className="h-4 w-4" />
                            <span>{appointment.participants_count} participant(s)</span>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );

    return (
        <DashboardLayout>
            <Head title="Rendez-vous" />

            <div className="mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Rendez-vous</h1>
                        <p className="mt-2 text-gray-600 dark:text-gray-400">
                            Gérez vos rendez-vous et planifiez vos meetings
                        </p>
                    </div>
                    <div className="flex space-x-3">
                        <Button variant="outline" onClick={handleExportBulk}>
                            <Download className="h-4 w-4 mr-2" />
                            Exporter .ics
                        </Button>
                        <Button variant="outline" onClick={() => setImportDialogOpen(true)}>
                            <Upload className="h-4 w-4 mr-2" />
                            Importer .ics
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={route('appointments.calendar')}>
                                <Calendar className="h-4 w-4 mr-2" />
                                Vue calendrier
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('appointments.create')}>
                                <Plus className="h-4 w-4 mr-2" />
                                Nouveau rendez-vous
                            </Link>
                        </Button>
                    </div>
                </div>

                {renderStatsCards()}

                {/* User Selection for Public Agenda */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <User className="h-5 w-5" />
                            <span>Consulter l'agenda d'un utilisateur</span>
                        </CardTitle>
                        <CardDescription>
                            Sélectionnez un utilisateur pour voir son agenda public et prendre rendez-vous
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <UserSelect
                            onUserSelect={handleUserSelect}
                            selectedUser={selectedUser}
                            placeholder="Rechercher et sélectionner un utilisateur..."
                            className="max-w-md"
                        />
                    </CardContent>
                </Card>

                {/* Public Agenda View */}
                {showUserAgenda && selectedUser && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Agenda public de {selectedUser.name}</CardTitle>
                            <CardDescription>
                                Consultez les créneaux disponibles et prenez rendez-vous
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <PublicAgendaView
                                user={selectedUser}
                                onCreateAppointment={handleCreateAppointmentFromAgenda}
                            />
                        </CardContent>
                    </Card>
                )}

                {renderFilters()}

                <div className="space-y-4">
                    {appointments.data.length > 0 ? (
                        appointments.data.map(renderAppointmentCard)
                    ) : (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-12">
                                <CalendarDays className="h-12 w-12 text-gray-400 mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Aucun rendez-vous trouvé
                                </h3>
                                <p className="text-gray-600 dark:text-gray-400 text-center mb-4">
                                    {filters.search || filters.status || filters.type || filters.date
                                        ? 'Aucun rendez-vous ne correspond à vos critères de recherche.'
                                        : 'Vous n\'avez pas encore de rendez-vous. Créez votre premier rendez-vous !'}
                                </p>
                                <Button asChild>
                                    <Link href={route('appointments.create')}>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Créer un rendez-vous
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Pagination */}
                {appointments.links && appointments.meta && (
                    <div className="mt-8 flex justify-center">
                        {/* Pagination component would go here */}
                    </div>
                )}
            </div>

            <DeleteConfirmationDialog
                open={deleteConfirmOpen}
                onOpenChange={setDeleteConfirmOpen}
                onConfirm={confirmDelete}
                title="Supprimer le rendez-vous"
                description={`Êtes-vous sûr de vouloir supprimer le rendez-vous "${appointmentToDelete?.title}" ? Cette action est irréversible.`}
            />

            <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Importer un fichier iCalendar</DialogTitle>
                        <DialogDescription>
                            Sélectionnez un fichier .ics pour importer des rendez-vous.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="ics-file-index">Fichier iCalendar</Label>
                            <Input
                                id="ics-file-index"
                                ref={fileInputRef}
                                type="file"
                                accept=".ics,.ical"
                                onChange={(e) => setImportFile(e.target.files?.[0] || null)}
                                className="mt-2"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setImportDialogOpen(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleImport} disabled={!importFile || importing}>
                            {importing ? 'Import en cours...' : 'Importer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </DashboardLayout>
    );
}