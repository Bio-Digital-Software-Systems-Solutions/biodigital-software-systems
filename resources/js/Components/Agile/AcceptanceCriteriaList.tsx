import React from 'react';
import { AcceptanceCriterion, UserStory } from '@/Types/Agile';

interface Props {
    story: UserStory;
    criteria: AcceptanceCriterion[];
}

// Placeholder — full implementation in F4.
export const AcceptanceCriteriaList: React.FC<Props> = ({ criteria }) => {
    if (criteria.length === 0) {
        return (
            <p className="text-center text-gray-500 py-10">
                Aucun critère d'acceptation.
            </p>
        );
    }

    return (
        <ul className="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            {criteria.map((ac) => (
                <li key={ac.id} className="p-4">
                    <p className="font-medium">{ac.position}. {ac.title}</p>
                    <p className="text-sm text-gray-600 dark:text-gray-400">{ac.status_label}</p>
                </li>
            ))}
        </ul>
    );
};
