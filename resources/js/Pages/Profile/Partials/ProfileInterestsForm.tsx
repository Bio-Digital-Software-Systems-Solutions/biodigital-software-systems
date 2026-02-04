import { useState } from 'react';
import { HeartIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

interface Interest {
    id: number;
    uuid: string;
    name: string;
    icon: string | null;
}

interface Props {
    availableInterests: Interest[];
    userInterests: number[];
    className?: string;
}

export default function ProfileInterestsForm({
    availableInterests: initialAvailableInterests,
    userInterests: initialUserInterests,
    className = '',
}: Props) {
    const [availableInterests, setAvailableInterests] = useState<Interest[]>(initialAvailableInterests);
    const [selectedInterests, setSelectedInterests] = useState<number[]>(initialUserInterests);
    const [processing, setProcessing] = useState(false);
    const [newInterestName, setNewInterestName] = useState('');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [creatingInterest, setCreatingInterest] = useState(false);

    const toggleInterest = (interestId: number) => {
        setSelectedInterests((prev) =>
            prev.includes(interestId)
                ? prev.filter((id) => id !== interestId)
                : [...prev, interestId]
        );
    };

    const createInterest = async () => {
        if (!newInterestName.trim()) return;

        setCreatingInterest(true);
        try {
            const response = await axios.post('/api/profile/interests', {
                name: newInterestName.trim(),
            });

            const newInterest = response.data.interest;
            setAvailableInterests([...availableInterests, newInterest]);
            setSelectedInterests([...selectedInterests, newInterest.id]);
            setNewInterestName('');
            setIsDialogOpen(false);
            toast.success('Centre d\'intérêt créé avec succès');
        } catch (error: any) {
            toast.error('Erreur lors de la création', {
                description: error.response?.data?.message || 'Une erreur est survenue',
            });
        } finally {
            setCreatingInterest(false);
        }
    };

    const saveInterests = async () => {
        setProcessing(true);
        try {
            await axios.put('/api/profile/interests', {
                interests: selectedInterests,
            });
            toast.success('Centres d\'intérêt mis à jour avec succès');
        } catch (error: any) {
            toast.error('Erreur lors de la mise à jour', {
                description: error.response?.data?.message || 'Une erreur est survenue',
            });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <section className={className}>
            <header className="flex items-center justify-between">
                <div>
                    <h2 className="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <HeartIcon className="w-5 h-5" />
                        Centres d'intérêt
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Sélectionnez vos centres d'intérêt pour aider les autres membres à mieux vous connaître.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={() => setIsDialogOpen(true)}
                    className="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-md transition-colors"
                >
                    <PlusIcon className="w-4 h-4" />
                    Nouveau
                </button>

                <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Ajouter un centre d'intérêt</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 px-6 py-4">
                            <Input
                                placeholder="Nom du centre d'intérêt"
                                value={newInterestName}
                                onChange={(e) => setNewInterestName(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        createInterest();
                                    }
                                }}
                            />
                            <div className="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setIsDialogOpen(false)}
                                    className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                                >
                                    Annuler
                                </button>
                                <PrimaryButton
                                    onClick={createInterest}
                                    disabled={!newInterestName.trim() || creatingInterest}
                                >
                                    {creatingInterest ? 'Création...' : 'Créer'}
                                </PrimaryButton>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </header>

            <div className="mt-6 space-y-4">
                {/* Interest Selection Grid */}
                <div className="flex flex-wrap gap-2">
                    {availableInterests.map((interest) => {
                        const isSelected = selectedInterests.includes(interest.id);
                        return (
                            <button
                                key={interest.id}
                                type="button"
                                onClick={() => toggleInterest(interest.id)}
                                className={`
                                    inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium
                                    transition-all duration-200 ease-in-out
                                    ${
                                        isSelected
                                            ? 'bg-primary text-white shadow-md'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                                    }
                                `}
                            >
                                {interest.icon && <span>{interest.icon}</span>}
                                {interest.name}
                                {isSelected && <XMarkIcon className="w-3.5 h-3.5 ml-1" />}
                            </button>
                        );
                    })}
                </div>

                {/* Empty State */}
                {availableInterests.length === 0 && (
                    <p className="text-center text-gray-500 dark:text-gray-400 py-8">
                        Aucun centre d'intérêt disponible. Créez-en un nouveau !
                    </p>
                )}

                {/* Selected Count */}
                {selectedInterests.length > 0 && (
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        {selectedInterests.length} centre{selectedInterests.length > 1 ? 's' : ''} d'intérêt sélectionné{selectedInterests.length > 1 ? 's' : ''}
                    </p>
                )}

                {/* Save Button */}
                <div className="flex justify-end pt-4">
                    <PrimaryButton onClick={saveInterests} disabled={processing}>
                        {processing ? 'Enregistrement...' : 'Enregistrer les centres d\'intérêt'}
                    </PrimaryButton>
                </div>
            </div>
        </section>
    );
}
