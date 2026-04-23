import React from 'react';
import {
    AcceptanceCriterionStatus,
    EpicStatus,
    TestScenarioExecutionStatus,
    UserStoryStatus,
    acceptanceCriterionStatusColor,
    epicStatusColor,
    testScenarioStatusColor,
    userStoryStatusColor,
} from '@/Types/Agile';

type AgileStatus = EpicStatus | UserStoryStatus | AcceptanceCriterionStatus | TestScenarioExecutionStatus;

interface Props {
    status: AgileStatus;
    label?: string;
    className?: string;
}

const colorFor = (status: AgileStatus): string => {
    if ((Object.values(EpicStatus) as string[]).includes(status)) {
        return epicStatusColor[status as EpicStatus];
    }
    if ((Object.values(UserStoryStatus) as string[]).includes(status)) {
        return userStoryStatusColor[status as UserStoryStatus];
    }
    if ((Object.values(AcceptanceCriterionStatus) as string[]).includes(status)) {
        return acceptanceCriterionStatusColor[status as AcceptanceCriterionStatus];
    }
    if ((Object.values(TestScenarioExecutionStatus) as string[]).includes(status)) {
        return testScenarioStatusColor[status as TestScenarioExecutionStatus];
    }
    return 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100';
};

export const StatusBadge: React.FC<Props> = ({ status, label, className = '' }) => (
    <span
        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorFor(status)} ${className}`}
    >
        {label ?? status}
    </span>
);
