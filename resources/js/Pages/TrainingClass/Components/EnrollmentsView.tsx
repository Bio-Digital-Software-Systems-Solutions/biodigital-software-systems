import { useState, useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import { CheckCircle, XCircle, Clock, Mail, User, Calendar, AlertCircle } from 'lucide-react';
import { Training } from '../types';
import axios from 'axios';
import { toast } from 'sonner';
import { formatNumber } from '@/lib/utils';
import { apiLogger } from '@/utils/logger';

interface EnrollmentRequest {
    id: number;
    user_id: number;
    training_id: number;
    training_name: string;
    user_name: string;
    user_email: string;
    status: 'pending' | 'approved' | 'rejected';
    motivation?: string;
    payment_method?: string;
    enrolled_at: string;
    created_at: string;
    progress: number;
    grade?: number;
    attendance_rate: number;
}

interface Props {
    trainings: Training[];
}

export default function EnrollmentsView({ trainings }: Props) {
    const [enrollments, setEnrollments] = useState<EnrollmentRequest[]>([]);
    const [loading, setLoading] = useState(true);
    const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'approved' | 'rejected'>('pending');
    const [filterTraining, setFilterTraining] = useState<string>('');
    const [processingIds, setProcessingIds] = useState<number[]>([]);
    const [showRejectDialog, setShowRejectDialog] = useState(false);
    const [selectedEnrollmentId, setSelectedEnrollmentId] = useState<number | null>(null);
    const [rejectionReason, setRejectionReason] = useState('');

    useEffect(() => {
        fetchEnrollments();
    }, []);

    const fetchEnrollments = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('training-enrollments.index'));
            setEnrollments(response.data);
        } catch (error) {
            apiLogger.error('Error fetching enrollments:', error);
            toast.error('Erreur lors du chargement des inscriptions');
        } finally {
            setLoading(false);
        }
    };

    const handleApprove = async (enrollmentId: number) => {
        setProcessingIds(prev => [...prev, enrollmentId]);
        try {
            await axios.post(route('training-enrollments.approve', enrollmentId));
            toast.success('Inscription approuvée', {
                description: 'L\'étudiant a été inscrit à la formation.',
            });
            fetchEnrollments();
        } catch (error) {
            apiLogger.error('Error approving enrollment:', error);
            toast.error('Erreur lors de l\'approbation');
        } finally {
            setProcessingIds(prev => prev.filter(id => id !== enrollmentId));
        }
    };

    const handleRejectClick = (enrollmentId: number) => {
        setSelectedEnrollmentId(enrollmentId);
        setShowRejectDialog(true);
    };

    const handleReject = async () => {
        if (!selectedEnrollmentId || !rejectionReason.trim()) {
            toast.error('Veuillez fournir une raison de rejet');
            return;
        }

        setProcessingIds(prev => [...prev, selectedEnrollmentId]);
        try {
            await axios.post(route('training-enrollments.reject', selectedEnrollmentId), {
                rejection_reason: rejectionReason
            });
            toast.success('Inscription rejetée', {
                description: 'La demande d\'inscription a été rejetée et l\'utilisateur a été notifié.',
            });
            setShowRejectDialog(false);
            setRejectionReason('');
            setSelectedEnrollmentId(null);
            fetchEnrollments();
        } catch (error) {
            apiLogger.error('Error rejecting enrollment:', error);
            toast.error('Erreur lors du rejet');
        } finally {
            setProcessingIds(prev => prev.filter(id => id !== selectedEnrollmentId));
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    <Clock className="h-3 w-3 mr-1" />
                    En attente
                </Badge>;
            case 'approved':
                return <Badge className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Approuvée
                </Badge>;
            case 'rejected':
                return <Badge className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    <XCircle className="h-3 w-3 mr-1" />
                    Rejetée
                </Badge>;
            default:
                return null;
        }
    };

    const filteredEnrollments = enrollments.filter(enrollment => {
        const statusMatch = filterStatus === 'all' || enrollment.status === filterStatus;
        const trainingMatch = !filterTraining || enrollment.training_name === filterTraining;
        return statusMatch && trainingMatch;
    });

    const uniqueTrainings = Array.from(new Set(enrollments.map(e => e.training_name))).sort();

    if (loading) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                    Demandes d'inscription
                </h2>
            </div>

            {/* Filters */}
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 border dark:border-gray-700">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Status Filter */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Statut
                        </label>
                        <select
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value as any)}
                            className="w-full h-10 px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                        >
                            <option value="all">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="approved">Approuvées</option>
                            <option value="rejected">Rejetées</option>
                        </select>
                    </div>

                    {/* Training Filter */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Formation
                        </label>
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
                </div>

                {/* Results count */}
                <div className="mt-3">
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                        {filteredEnrollments.length} demande{filteredEnrollments.length > 1 ? 's' : ''} trouvée{filteredEnrollments.length > 1 ? 's' : ''}
                    </span>
                </div>
            </div>

            {/* Enrollments List */}
            {filteredEnrollments.length === 0 ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-12 text-center">
                    <p className="text-gray-600 dark:text-gray-400">
                        Aucune demande d'inscription ne correspond à vos critères.
                    </p>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6">
                    {filteredEnrollments.map((enrollment) => (
                        <Card key={enrollment.id} className="dark:bg-gray-800 dark:border-gray-700">
                            <CardHeader>
                                <div className="flex justify-between items-start">
                                    <div>
                                        <CardTitle className="text-lg text-violet-600 dark:text-violet-400">
                                            {enrollment.training_name}
                                        </CardTitle>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <Calendar className="h-4 w-4 inline mr-1" />
                                            Demande reçue le {new Date(enrollment.created_at).toLocaleDateString('fr-FR')}
                                        </p>
                                    </div>
                                    {getStatusBadge(enrollment.status)}
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Left Column - User Info */}
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-2">
                                            <User className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {enrollment.user_name}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Mail className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                            <a
                                                href={`mailto:${enrollment.user_email}`}
                                                className="text-sm text-primary hover:text-primary dark:text-blue-400"
                                            >
                                                {enrollment.user_email}
                                            </a>
                                        </div>
                                        {enrollment.payment_method && (
                                            <div className="flex items-center gap-2">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                    Paiement:
                                                </p>
                                                <p className="text-sm text-gray-700 dark:text-gray-300 font-medium">
                                                    {enrollment.payment_method}
                                                </p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Right Column - Motivation & Progress */}
                                    <div className="space-y-3">
                                        {enrollment.motivation && (
                                            <div>
                                                <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Motivation :
                                                </p>
                                                <p className="text-sm text-gray-600 dark:text-gray-400 italic">
                                                    "{enrollment.motivation}"
                                                </p>
                                            </div>
                                        )}
                                        {enrollment.status === 'approved' && (
                                            <div className="grid grid-cols-2 gap-3 pt-2">
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">Progression</p>
                                                    <p className="text-lg font-semibold text-violet-600 dark:text-violet-400">
                                                        {formatNumber(enrollment.progress)}%
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">Présence</p>
                                                    <p className="text-lg font-semibold text-green-600 dark:text-green-400">
                                                        {formatNumber(enrollment.attendance_rate)}%
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                {enrollment.status === 'pending' && (
                                    <div className="flex gap-3 mt-6 pt-6 border-t dark:border-gray-700">
                                        <Button
                                            onClick={() => handleApprove(enrollment.id)}
                                            disabled={processingIds.includes(enrollment.id)}
                                            className="bg-green-600 hover:bg-green-700 flex-1"
                                        >
                                            <CheckCircle className="h-4 w-4 mr-2" />
                                            {processingIds.includes(enrollment.id) ? 'Traitement...' : 'Approuver'}
                                        </Button>
                                        <Button
                                            onClick={() => handleRejectClick(enrollment.id)}
                                            disabled={processingIds.includes(enrollment.id)}
                                            variant="destructive"
                                            className="flex-1"
                                        >
                                            <XCircle className="h-4 w-4 mr-2" />
                                            {processingIds.includes(enrollment.id) ? 'Traitement...' : 'Rejeter'}
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            {/* Reject Dialog */}
            <Dialog open={showRejectDialog} onOpenChange={setShowRejectDialog}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader className="space-y-4">
                        <div className="flex items-center gap-3">
                            <div className="p-3 rounded-full bg-red-100 dark:bg-red-900">
                                <AlertCircle className="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <DialogTitle className="text-xl">Rejeter l'inscription</DialogTitle>
                        </div>
                        <DialogDescription className="text-base leading-relaxed">
                            Veuillez fournir une raison pour le rejet de cette demande d'inscription. L'utilisateur recevra un email avec cette explication.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-6 px-1">
                        <Textarea
                            value={rejectionReason}
                            onChange={(e) => setRejectionReason(e.target.value)}
                            placeholder="Ex: Le profil ne correspond pas aux prérequis de la formation..."
                            className="min-h-[140px] text-base"
                        />
                        {rejectionReason.length > 0 && (
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-3">
                                {rejectionReason.length} caractères
                            </p>
                        )}
                    </div>
                    <DialogFooter className="gap-3 sm:gap-3">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowRejectDialog(false);
                                setRejectionReason('');
                                setSelectedEnrollmentId(null);
                            }}
                            className="px-6"
                        >
                            Annuler
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleReject}
                            disabled={!rejectionReason.trim() || processingIds.includes(selectedEnrollmentId || 0)}
                            className="px-6"
                        >
                            {processingIds.includes(selectedEnrollmentId || 0) ? 'Traitement...' : 'Confirmer le rejet'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
