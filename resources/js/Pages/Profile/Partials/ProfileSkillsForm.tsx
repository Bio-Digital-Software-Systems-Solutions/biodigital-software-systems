import { useState } from 'react';
import { SparklesIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

interface ProfileSkill {
    id: number;
    uuid: string;
    name: string;
    category: 'soft' | 'hard' | 'technical';
}

interface UserSkill {
    id: number;
    level: 'beginner' | 'intermediate' | 'advanced' | 'expert' | null;
}

interface Props {
    availableSkills: ProfileSkill[];
    userSkills: UserSkill[];
    className?: string;
    hideHeader?: boolean;
}

const categoryLabels: Record<string, string> = {
    soft: 'Soft Skills',
    hard: 'Hard Skills',
    technical: 'Compétences Techniques',
};

const categoryDescriptions: Record<string, string> = {
    soft: 'Communication, leadership, travail d\'équipe...',
    hard: 'Gestion de projet, analyse de données...',
    technical: 'Langages de programmation, outils...',
};

const categoryColors: Record<string, string> = {
    soft: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    hard: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    technical: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
};

const levelLabels: Record<string, string> = {
    beginner: 'Débutant',
    intermediate: 'Intermédiaire',
    advanced: 'Avancé',
    expert: 'Expert',
};

export default function ProfileSkillsForm({
    availableSkills: initialAvailableSkills,
    userSkills: initialUserSkills,
    className = '',
    hideHeader = false,
}: Props) {
    const [availableSkills, setAvailableSkills] = useState<ProfileSkill[]>(initialAvailableSkills);
    const [userSkills, setUserSkills] = useState<UserSkill[]>(initialUserSkills);
    const [processing, setProcessing] = useState(false);
    const [newSkillName, setNewSkillName] = useState('');
    const [newSkillCategory, setNewSkillCategory] = useState<'soft' | 'hard' | 'technical'>('technical');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [creatingSkill, setCreatingSkill] = useState(false);
    const [activeTab, setActiveTab] = useState<string>('soft');

    const getSkillById = (id: number) => availableSkills.find((s) => s.id === id);

    const getUserSkill = (skillId: number) => userSkills.find((s) => s.id === skillId);

    const isSkillSelected = (skillId: number) => userSkills.some((s) => s.id === skillId);

    const skillsByCategory = availableSkills.reduce(
        (acc, skill) => {
            if (!acc[skill.category]) acc[skill.category] = [];
            acc[skill.category].push(skill);
            return acc;
        },
        {} as Record<string, ProfileSkill[]>
    );

    const toggleSkill = (skillId: number) => {
        if (isSkillSelected(skillId)) {
            setUserSkills(userSkills.filter((s) => s.id !== skillId));
        } else {
            setUserSkills([...userSkills, { id: skillId, level: 'intermediate' }]);
        }
    };

    const updateSkillLevel = (skillId: number, level: string) => {
        setUserSkills(
            userSkills.map((s) =>
                s.id === skillId ? { ...s, level: level as UserSkill['level'] } : s
            )
        );
    };

    const createSkill = async () => {
        if (!newSkillName.trim()) return;

        setCreatingSkill(true);
        try {
            const response = await axios.post('/api/profile/skills', {
                name: newSkillName.trim(),
                category: newSkillCategory,
            });

            const newSkill = response.data.skill;
            setAvailableSkills([...availableSkills, newSkill]);
            setUserSkills([...userSkills, { id: newSkill.id, level: 'intermediate' }]);
            setNewSkillName('');
            setIsDialogOpen(false);
            setActiveTab(newSkillCategory);
            toast.success('Compétence créée avec succès');
        } catch (error: any) {
            toast.error('Erreur lors de la création', {
                description: error.response?.data?.message || 'Une erreur est survenue',
            });
        } finally {
            setCreatingSkill(false);
        }
    };

    const saveSkills = async () => {
        setProcessing(true);
        try {
            await axios.put('/api/profile/skills', {
                skills: userSkills,
            });
            toast.success('Compétences mises à jour avec succès');
        } catch (error: any) {
            toast.error('Erreur lors de la mise à jour', {
                description: error.response?.data?.message || 'Une erreur est survenue',
            });
        } finally {
            setProcessing(false);
        }
    };

    const selectedSkillsCount = userSkills.length;

    return (
        <section className={className}>
            {!hideHeader ? (
                <header className="flex items-center justify-between">
                    <div>
                        <h2 className="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            <SparklesIcon className="w-5 h-5" />
                            Compétences
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Ajoutez vos compétences professionnelles et personnelles.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={() => setIsDialogOpen(true)}
                        className="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-md transition-colors"
                    >
                        <PlusIcon className="w-4 h-4" />
                        Nouvelle
                    </button>
                </header>
            ) : (
                <div className="flex justify-end mb-4">
                    <button
                        type="button"
                        onClick={() => setIsDialogOpen(true)}
                        className="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-md transition-colors"
                    >
                        <PlusIcon className="w-4 h-4" />
                        Nouvelle
                    </button>
                </div>
            )}

            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajouter une compétence</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 px-6 py-4">
                        <Input
                            placeholder="Nom de la compétence"
                            value={newSkillName}
                            onChange={(e) => setNewSkillName(e.target.value)}
                        />
                        <Select
                            value={newSkillCategory}
                            onValueChange={(value) =>
                                setNewSkillCategory(value as 'soft' | 'hard' | 'technical')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Catégorie" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="soft">Soft Skills</SelectItem>
                                <SelectItem value="hard">Hard Skills</SelectItem>
                                <SelectItem value="technical">Compétences Techniques</SelectItem>
                            </SelectContent>
                        </Select>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setIsDialogOpen(false)}
                                className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                            >
                                Annuler
                            </button>
                            <PrimaryButton
                                onClick={createSkill}
                                disabled={!newSkillName.trim() || creatingSkill}
                            >
                                {creatingSkill ? 'Création...' : 'Créer'}
                            </PrimaryButton>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <div className={`${hideHeader ? '' : 'mt-6'} space-y-4`}>
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="soft">Soft Skills</TabsTrigger>
                        <TabsTrigger value="hard">Hard Skills</TabsTrigger>
                        <TabsTrigger value="technical">Techniques</TabsTrigger>
                    </TabsList>

                    {(['soft', 'hard', 'technical'] as const).map((category) => (
                        <TabsContent key={category} value={category} className="mt-4">
                            <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                {categoryDescriptions[category]}
                            </p>

                            <div className="space-y-2">
                                {(skillsByCategory[category] || []).map((skill) => {
                                    const selected = isSkillSelected(skill.id);
                                    const userSkill = getUserSkill(skill.id);

                                    return (
                                        <div
                                            key={skill.id}
                                            className={`
                                                flex items-center justify-between p-3 rounded-lg border transition-all
                                                ${
                                                    selected
                                                        ? 'border-primary bg-primary/5 dark:bg-primary/10'
                                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                                }
                                            `}
                                        >
                                            <div className="flex items-center gap-3">
                                                <input
                                                    type="checkbox"
                                                    checked={selected}
                                                    onChange={() => toggleSkill(skill.id)}
                                                    className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                />
                                                <span
                                                    className={`font-medium ${
                                                        selected
                                                            ? 'text-gray-900 dark:text-white'
                                                            : 'text-gray-600 dark:text-gray-400'
                                                    }`}
                                                >
                                                    {skill.name}
                                                </span>
                                            </div>

                                            {selected && (
                                                <Select
                                                    value={userSkill?.level || 'intermediate'}
                                                    onValueChange={(value) =>
                                                        updateSkillLevel(skill.id, value)
                                                    }
                                                >
                                                    <SelectTrigger className="w-[140px]">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="beginner">Débutant</SelectItem>
                                                        <SelectItem value="intermediate">
                                                            Intermédiaire
                                                        </SelectItem>
                                                        <SelectItem value="advanced">Avancé</SelectItem>
                                                        <SelectItem value="expert">Expert</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        </div>
                                    );
                                })}

                                {(!skillsByCategory[category] || skillsByCategory[category].length === 0) && (
                                    <p className="text-center text-gray-500 dark:text-gray-400 py-8">
                                        Aucune compétence dans cette catégorie. Créez-en une nouvelle !
                                    </p>
                                )}
                            </div>
                        </TabsContent>
                    ))}
                </Tabs>

                {/* Selected Count */}
                {selectedSkillsCount > 0 && (
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        {selectedSkillsCount} compétence{selectedSkillsCount > 1 ? 's' : ''} sélectionnée{selectedSkillsCount > 1 ? 's' : ''}
                    </p>
                )}

                {/* Save Button */}
                <div className="flex justify-end pt-4">
                    <PrimaryButton onClick={saveSkills} disabled={processing}>
                        {processing ? 'Enregistrement...' : 'Enregistrer les compétences'}
                    </PrimaryButton>
                </div>
            </div>
        </section>
    );
}
