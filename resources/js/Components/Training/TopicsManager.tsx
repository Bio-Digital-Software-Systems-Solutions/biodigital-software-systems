import React, { useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
import { Plus, Trash2, ChevronUp, ChevronDown, GripVertical } from 'lucide-react';

export interface Topic {
    id?: number;
    name: string;
    description: string;
    order: number;
    _destroy?: boolean;
}

interface TopicsManagerProps {
    topics: Topic[];
    onChange: (topics: Topic[]) => void;
    error?: string;
}

export default function TopicsManager({ topics, onChange, error }: TopicsManagerProps) {
    const [newTopicName, setNewTopicName] = useState('');

    const addTopic = () => {
        if (!newTopicName.trim()) return;

        const newTopic: Topic = {
            name: newTopicName.trim(),
            description: '',
            order: topics.length,
        };

        onChange([...topics, newTopic]);
        setNewTopicName('');
    };

    const updateTopic = (index: number, field: keyof Topic, value: string) => {
        const updatedTopics = [...topics];
        updatedTopics[index] = {
            ...updatedTopics[index],
            [field]: value,
        };
        onChange(updatedTopics);
    };

    const removeTopic = (index: number) => {
        const updatedTopics = [...topics];

        // If topic has an id (exists in database), mark for deletion
        if (updatedTopics[index].id) {
            updatedTopics[index]._destroy = true;
        } else {
            // If it's a new topic, just remove it from array
            updatedTopics.splice(index, 1);
        }

        // Reorder remaining topics
        const reorderedTopics = updatedTopics
            .filter(t => !t._destroy)
            .map((t, i) => ({ ...t, order: i }));

        onChange([...reorderedTopics, ...updatedTopics.filter(t => t._destroy)]);
    };

    const moveTopicUp = (index: number) => {
        if (index === 0) return;

        const updatedTopics = [...topics];
        [updatedTopics[index - 1], updatedTopics[index]] = [updatedTopics[index], updatedTopics[index - 1]];

        // Update order
        updatedTopics.forEach((topic, i) => {
            topic.order = i;
        });

        onChange(updatedTopics);
    };

    const moveTopicDown = (index: number) => {
        if (index === topics.length - 1) return;

        const updatedTopics = [...topics];
        [updatedTopics[index], updatedTopics[index + 1]] = [updatedTopics[index + 1], updatedTopics[index]];

        // Update order
        updatedTopics.forEach((topic, i) => {
            topic.order = i;
        });

        onChange(updatedTopics);
    };

    const visibleTopics = topics.filter(t => !t._destroy);

    return (
        <div className="space-y-4">
            <div>
                <Label htmlFor="topics" className="text-base font-semibold">
                    Thèmes abordés *
                </Label>
                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Ajoutez les thèmes principaux qui seront abordés dans cette formation
                </p>
            </div>

            {/* Add new topic */}
            <div className="flex gap-2">
                <Input
                    id="new-topic"
                    placeholder="Ex: Principes du design"
                    value={newTopicName}
                    onChange={(e) => setNewTopicName(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            addTopic();
                        }
                    }}
                    className="flex-1"
                />
                <Button
                    type="button"
                    onClick={addTopic}
                    disabled={!newTopicName.trim()}
                    className="bg-primary hover:bg-primary/90"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Ajouter
                </Button>
            </div>

            {/* Topics list */}
            {visibleTopics.length > 0 && (
                <div className="space-y-3">
                    {visibleTopics.map((topic, index) => (
                        <Card key={topic.id || `new-${index}`} className="border-l-4 border-l-primary">
                            <CardContent className="p-4">
                                <div className="flex items-start gap-3">
                                    {/* Drag handle */}
                                    <div className="flex flex-col gap-1 pt-2">
                                        <GripVertical className="h-5 w-5 text-gray-400" />
                                    </div>

                                    {/* Content */}
                                    <div className="flex-1 space-y-3">
                                        <div>
                                            <Label htmlFor={`topic-name-${index}`} className="text-sm">
                                                Nom du thème *
                                            </Label>
                                            <Input
                                                id={`topic-name-${index}`}
                                                value={topic.name}
                                                onChange={(e) => updateTopic(index, 'name', e.target.value)}
                                                placeholder="Nom du thème"
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor={`topic-description-${index}`} className="text-sm">
                                                Description (optionnelle)
                                            </Label>
                                            <Textarea
                                                id={`topic-description-${index}`}
                                                value={topic.description}
                                                onChange={(e) => updateTopic(index, 'description', e.target.value)}
                                                placeholder="Description du thème..."
                                                rows={2}
                                                className="mt-1"
                                            />
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="flex flex-col gap-1">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => moveTopicUp(index)}
                                            disabled={index === 0}
                                            className="h-8 w-8 p-0"
                                        >
                                            <ChevronUp className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => moveTopicDown(index)}
                                            disabled={index === visibleTopics.length - 1}
                                            className="h-8 w-8 p-0"
                                        >
                                            <ChevronDown className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => removeTopic(index)}
                                            className="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            {/* Empty state */}
            {visibleTopics.length === 0 && (
                <Card className="border-dashed">
                    <CardContent className="p-8 text-center">
                        <p className="text-gray-500 dark:text-gray-400">
                            Aucun thème ajouté. Ajoutez au moins un thème pour cette formation.
                        </p>
                    </CardContent>
                </Card>
            )}

            {/* Error message */}
            {error && (
                <p className="text-sm text-red-600 dark:text-red-400">{error}</p>
            )}

            {/* Topics count */}
            {visibleTopics.length > 0 && (
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    {visibleTopics.length} thème{visibleTopics.length > 1 ? 's' : ''} ajouté{visibleTopics.length > 1 ? 's' : ''}
                </p>
            )}
        </div>
    );
}
