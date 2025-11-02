import { useState, useMemo } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Eye, Edit, Trash2, Grid3x3, List, Table, Search, X, FileText } from 'lucide-react';
import { TrainingClass, Training, Teacher } from '../types';
import EditClassModal from './EditClassModal';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { router, Link } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'sonner';
import { apiLogger } from '@/utils/logger';

interface Props {
    classes: TrainingClass[];
    trainings: Training[];
    teachers: Teacher[];
    onClassUpdated: (updatedClass: TrainingClass) => void;
    onClassDeleted: (classUuid: string) => void;
}

type ViewMode = 'grid' | 'list' | 'table';

export default function ClassesView({ classes, trainings, teachers, onClassUpdated, onClassDeleted }: Props) {
    const [editingClass, setEditingClass] = useState<TrainingClass | null>(null);
    const [showEditModal, setShowEditModal] = useState(false);
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [classToDelete, setClassToDelete] = useState<string | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [filterTeacher, setFilterTeacher] = useState('');
    const [filterTraining, setFilterTraining] = useState('');
    const [filterStatus, setFilterStatus] = useState('');

    // Format schedule display
    const formatScheduleDisplay = (trainingClass: TrainingClass): string => {
        if (!trainingClass.schedules || trainingClass.schedules.length === 0) {
            // Fallback to old format if no schedules
            return `${trainingClass.start_time} - ${trainingClass.end_time}`;
        }

        // Check if all schedules have the same time
        const firstSchedule = trainingClass.schedules[0];
        const allSameTime = trainingClass.schedules.every(
            s => s.start_time === firstSchedule.start_time && s.end_time === firstSchedule.end_time
        );

        if (allSameTime) {
            // Single time for all days
            const days = trainingClass.schedules.map(s => {
                const dayMap: Record<string, string> = {
                    'Lundi': 'Lun',
                    'Mardi': 'Mar',
                    'Mercredi': 'Mer',
                    'Jeudi': 'Jeu',
                    'Vendredi': 'Ven',
                    'Samedi': 'Sam',
                    'Dimanche': 'Dim'
                };
                return dayMap[s.day_of_week] || s.day_of_week;
            }).join(', ');
            return `${days}: ${firstSchedule.start_time} - ${firstSchedule.end_time}`;
        } else {
            // Different times per day
            return trainingClass.schedules.map(s => {
                const dayMap: Record<string, string> = {
                    'Lundi': 'Lun',
                    'Mardi': 'Mar',
                    'Mercredi': 'Mer',
                    'Jeudi': 'Jeu',
                    'Vendredi': 'Ven',
                    'Samedi': 'Sam',
                    'Dimanche': 'Dim'
                };
                const day = dayMap[s.day_of_week] || s.day_of_week;
                return `${day}: ${s.start_time}-${s.end_time}`;
            }).join(' | ');
        }
    };

    const handleEdit = (trainingClass: TrainingClass) => {
        setEditingClass(trainingClass);
        setShowEditModal(true);
    };

    const handleDeleteClick = (classUuid: string) => {
        setClassToDelete(classUuid);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = async () => {
        if (!classToDelete) return;

        setIsDeleting(true);
        try {
            await axios.delete(route('training-classes.destroy', classToDelete));
            onClassDeleted(classToDelete);
            setDeleteDialogOpen(false);
            setClassToDelete(null);
            toast.success('Classe supprimée avec succès', {
                description: 'La classe et toutes ses données ont été supprimées.',
            });
        } catch (error) {
            apiLogger.error('Error deleting class:', error);
            toast.error('Erreur lors de la suppression', {
                description: 'Une erreur est survenue lors de la suppression de la classe.',
            });
        } finally {
            setIsDeleting(false);
        }
    };

    const getStatusColor = (status: string) => {
        return status === 'À venir'
            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    };

    // Filter and search classes
    const filteredClasses = useMemo(() => {
        return classes.filter((cls) => {
            // Search term filter
            const matchesSearch = searchTerm === '' ||
                cls.training_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                cls.teacher_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                cls.room?.toLowerCase().includes(searchTerm.toLowerCase());

            // Teacher filter
            const matchesTeacher = filterTeacher === '' || cls.teacher_name === filterTeacher;

            // Training filter
            const matchesTraining = filterTraining === '' || cls.training_name === filterTraining;

            // Status filter
            const matchesStatus = filterStatus === '' || cls.status === filterStatus;

            return matchesSearch && matchesTeacher && matchesTraining && matchesStatus;
        });
    }, [classes, searchTerm, filterTeacher, filterTraining, filterStatus]);

    // Get unique values for filters
    const uniqueTeachers = useMemo(() =>
        Array.from(new Set(classes.map(c => c.teacher_name))).sort(),
        [classes]
    );

    const uniqueTrainings = useMemo(() =>
        Array.from(new Set(classes.map(c => c.training_name))).sort(),
        [classes]
    );

    const uniqueStatuses = useMemo(() =>
        Array.from(new Set(classes.map(c => c.status))).sort(),
        [classes]
    );

    const clearFilters = () => {
        setSearchTerm('');
        setFilterTeacher('');
        setFilterTraining('');
        setFilterStatus('');
    };

    const hasActiveFilters = searchTerm || filterTeacher || filterTraining || filterStatus;

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                    Gestion des Classes
                </h2>

                {/* View Mode Switcher */}
                <div className="flex items-center gap-2 border border-gray-300 dark:border-gray-600 rounded-lg p-1">
                    <Button
                        variant={viewMode === 'grid' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setViewMode('grid')}
                        className="h-8 px-3"
                        title="Vue grille"
                    >
                        <Grid3x3 className="h-4 w-4" />
                    </Button>
                    <Button
                        variant={viewMode === 'list' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setViewMode('list')}
                        className="h-8 px-3"
                        title="Vue liste"
                    >
                        <List className="h-4 w-4" />
                    </Button>
                    <Button
                        variant={viewMode === 'table' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setViewMode('table')}
                        className="h-8 px-3"
                        title="Vue tableau"
                    >
                        <Table className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            {/* Search and Filters */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 border dark:border-gray-700">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    {/* Search */}
                    <div className="lg:col-span-2">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                type="text"
                                placeholder="Rechercher par formation, enseignant, salle..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </div>

                    {/* Filter by Training */}
                    <div>
                        <select
                            value={filterTraining}
                            onChange={(e) => setFilterTraining(e.target.value)}
                            className="w-full h-10 px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="">Toutes les formations</option>
                            {uniqueTrainings.map((training) => (
                                <option key={training} value={training}>
                                    {training}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Filter by Teacher */}
                    <div>
                        <select
                            value={filterTeacher}
                            onChange={(e) => setFilterTeacher(e.target.value)}
                            className="w-full h-10 px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="">Tous les enseignants</option>
                            {uniqueTeachers.map((teacher) => (
                                <option key={teacher} value={teacher}>
                                    {teacher}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Filter by Status */}
                    <div>
                        <select
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                            className="w-full h-10 px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="">Tous les statuts</option>
                            {uniqueStatuses.map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Clear Filters Button */}
                {hasActiveFilters && (
                    <div className="mt-3 flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={clearFilters}
                            className="text-xs"
                        >
                            <X className="h-3 w-3 mr-1" />
                            Réinitialiser les filtres
                        </Button>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                            {filteredClasses.length} classe{filteredClasses.length > 1 ? 's' : ''} trouvée{filteredClasses.length > 1 ? 's' : ''}
                        </span>
                    </div>
                )}
            </div>

            {classes.length === 0 ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">
                        Aucune classe disponible. Créez votre première classe!
                    </p>
                </div>
            ) : filteredClasses.length === 0 ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">
                        Aucune classe ne correspond à vos critères de recherche.
                    </p>
                </div>
            ) : (
                <>
                    {/* Grid View */}
                    {viewMode === 'grid' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {filteredClasses.map((trainingClass) => (
                                <div
                                    key={trainingClass.id}
                                    className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border dark:border-gray-700 hover:shadow-lg transition-shadow"
                                >
                                    <div className="flex justify-between items-start mb-4">
                                        <Link
                                            href={route('training-classes.show', { trainingClass: trainingClass.uuid })}
                                            className="text-lg font-semibold text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300"
                                        >
                                            {trainingClass.training_name}
                                        </Link>
                                        <span
                                            className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(
                                                trainingClass.status
                                            )}`}
                                        >
                                            {trainingClass.status}
                                        </span>
                                    </div>

                                    <div className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <p>
                                            <strong className="text-gray-900 dark:text-white">Enseignant:</strong>{' '}
                                            {trainingClass.teacher_name}
                                        </p>
                                        <p>
                                            <strong className="text-gray-900 dark:text-white">Étudiants:</strong>{' '}
                                            {trainingClass.students_count}
                                            {trainingClass.max_students && ` / ${trainingClass.max_students}`}
                                        </p>
                                        <p>
                                            <strong className="text-gray-900 dark:text-white">Date:</strong>{' '}
                                            {new Date(trainingClass.date).toLocaleDateString('fr-FR')}
                                        </p>
                                        <p>
                                            <strong className="text-gray-900 dark:text-white">Horaire:</strong>{' '}
                                            {formatScheduleDisplay(trainingClass)}
                                        </p>
                                        {trainingClass.room && (
                                            <p>
                                                <strong className="text-gray-900 dark:text-white">Salle:</strong>{' '}
                                                {trainingClass.room}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex gap-2 mt-4">
                                        <Link
                                            href={route('training-classes.materials.index', trainingClass.uuid)}
                                            className="flex-1"
                                        >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="w-full"
                                            >
                                                <FileText size={16} className="mr-1" />
                                                Supports
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleEdit(trainingClass)}
                                        >
                                            <Edit size={16} />
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => handleDeleteClick(trainingClass.uuid)}
                                        >
                                            <Trash2 size={16} />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* List View */}
                    {viewMode === 'list' && (
                        <div className="space-y-4">
                            {filteredClasses.map((trainingClass) => (
                                <div
                                    key={trainingClass.id}
                                    className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 border dark:border-gray-700 hover:shadow-lg transition-shadow"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                <Link
                                                    href={route('training-classes.show', { trainingClass: trainingClass.uuid })}
                                                    className="text-lg font-semibold text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300"
                                                >
                                                    {trainingClass.training_name}
                                                </Link>
                                                <span
                                                    className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(
                                                        trainingClass.status
                                                    )}`}
                                                >
                                                    {trainingClass.status}
                                                </span>
                                            </div>
                                            <div className="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                <span>
                                                    <strong className="text-gray-900 dark:text-white">Enseignant:</strong>{' '}
                                                    {trainingClass.teacher_name}
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    <strong className="text-gray-900 dark:text-white">Étudiants:</strong>{' '}
                                                    {trainingClass.students_count}
                                                    {trainingClass.max_students && ` / ${trainingClass.max_students}`}
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    <strong className="text-gray-900 dark:text-white">Date:</strong>{' '}
                                                    {new Date(trainingClass.date).toLocaleDateString('fr-FR')}
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    <strong className="text-gray-900 dark:text-white">Horaire:</strong>{' '}
                                                    {formatScheduleDisplay(trainingClass)}
                                                </span>
                                                {trainingClass.room && (
                                                    <>
                                                        <span>•</span>
                                                        <span>
                                                            <strong className="text-gray-900 dark:text-white">Salle:</strong>{' '}
                                                            {trainingClass.room}
                                                        </span>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex gap-2 ml-4">
                                            <Link href={route('training-classes.materials.index', trainingClass.uuid)}>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <FileText size={16} className="mr-1" />
                                                    Supports
                                                </Button>
                                            </Link>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleEdit(trainingClass)}
                                            >
                                                <Edit size={16} className="mr-1" />
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => handleDeleteClick(trainingClass.uuid)}
                                            >
                                                <Trash2 size={16} />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Table View */}
                    {viewMode === 'table' && (
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Formation
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Enseignant
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Date
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Horaire
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Salle
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Étudiants
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {filteredClasses.map((trainingClass) => (
                                            <tr key={trainingClass.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <Link
                                                        href={route('training-classes.show', { trainingClass: trainingClass.uuid })}
                                                        className="text-sm font-medium text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300"
                                                    >
                                                        {trainingClass.training_name}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {trainingClass.teacher_name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {new Date(trainingClass.date).toLocaleDateString('fr-FR')}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                    {formatScheduleDisplay(trainingClass)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {trainingClass.room || '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {trainingClass.students_count}
                                                    {trainingClass.max_students && ` / ${trainingClass.max_students}`}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(
                                                            trainingClass.status
                                                        )}`}
                                                    >
                                                        {trainingClass.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={route('training-classes.materials.index', trainingClass.uuid)}>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                title="Gérer les supports de cours"
                                                            >
                                                                <FileText size={16} />
                                                            </Button>
                                                        </Link>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleEdit(trainingClass)}
                                                            title="Modifier la classe"
                                                        >
                                                            <Edit size={16} />
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() => handleDeleteClick(trainingClass.uuid)}
                                                            title="Supprimer la classe"
                                                        >
                                                            <Trash2 size={16} />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </>
            )}

            {showEditModal && editingClass && (
                <EditClassModal
                    trainingClass={editingClass}
                    trainings={trainings}
                    teachers={teachers}
                    onClose={() => {
                        setShowEditModal(false);
                        setEditingClass(null);
                    }}
                    onClassUpdated={onClassUpdated}
                />
            )}

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={handleDeleteConfirm}
                title="Êtes-vous sûr de vouloir supprimer cette classe ?"
                description="Cette action est irréversible. La classe et toutes ses données de présence seront définitivement supprimées."
                isDeleting={isDeleting}
            />
        </div>
    );
}
