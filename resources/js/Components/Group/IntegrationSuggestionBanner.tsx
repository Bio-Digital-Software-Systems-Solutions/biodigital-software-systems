import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { StarIcon, CheckIcon, XMarkIcon, ClockIcon } from '@heroicons/react/24/outline';
import type { IntegrationSuggestion } from '@/Types/visitor';

interface Props {
    groupUuid: string;
    count: number;
    onResponded: () => void;
}

export default function IntegrationSuggestionBanner({ groupUuid, count, onResponded }: Props) {
    const [suggestions, setSuggestions] = useState<IntegrationSuggestion[]>([]);
    const [loading, setLoading] = useState(true);
    const [responding, setResponding] = useState<string | null>(null);

    useEffect(() => {
        const fetchSuggestions = async () => {
            try {
                const response = await axios.get('/integration-suggestions');
                setSuggestions(response.data.suggestions);
            } catch {
                // Silent fail
            } finally {
                setLoading(false);
            }
        };
        fetchSuggestions();
    }, []);

    const handleRespond = async (uuid: string, status: 'accepted' | 'rejected' | 'deferred') => {
        setResponding(uuid);
        try {
            await axios.post(`/integration-suggestions/${uuid}/respond`, { status });
            const messages: Record<string, string> = {
                accepted: 'Visiteur intégré avec succès.',
                rejected: 'Suggestion refusée.',
                deferred: 'Suggestion reportée.',
            };
            toast.success(messages[status]);
            setSuggestions((prev) => prev.filter((s) => s.uuid !== uuid));
            onResponded();
        } catch {
            toast.error('Erreur lors de la réponse.');
        } finally {
            setResponding(null);
        }
    };

    if (loading || suggestions.length === 0) return null;

    return (
        <Card className="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950">
            <CardContent className="p-4">
                <div className="flex items-start gap-3">
                    <StarIcon className="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div className="flex-1 space-y-3">
                        <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                            {suggestions.length} visiteur{suggestions.length > 1 ? 's' : ''} prêt{suggestions.length > 1 ? 's' : ''} pour l'intégration
                        </p>
                        {suggestions.map((suggestion) => (
                            <div
                                key={suggestion.uuid}
                                className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-2 bg-white dark:bg-gray-900 rounded-lg"
                            >
                                <div>
                                    <span className="text-sm font-medium text-gray-900 dark:text-white">
                                        {suggestion.visitor_name}
                                    </span>
                                    <span className="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                        Score: {Math.round(suggestion.score)}%
                                    </span>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="text-green-600 border-green-300 hover:bg-green-50 dark:hover:bg-green-950"
                                        disabled={responding === suggestion.uuid}
                                        onClick={() => handleRespond(suggestion.uuid, 'accepted')}
                                    >
                                        <CheckIcon className="h-3 w-3 mr-1" />
                                        Intégrer
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={responding === suggestion.uuid}
                                        onClick={() => handleRespond(suggestion.uuid, 'deferred')}
                                    >
                                        <ClockIcon className="h-3 w-3 mr-1" />
                                        Reporter
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-950"
                                        disabled={responding === suggestion.uuid}
                                        onClick={() => handleRespond(suggestion.uuid, 'rejected')}
                                    >
                                        <XMarkIcon className="h-3 w-3 mr-1" />
                                        Refuser
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
