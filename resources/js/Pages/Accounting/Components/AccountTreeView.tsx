import React, { useState } from 'react';
import { ChevronRightIcon } from '@heroicons/react/24/outline';
import { Badge } from '@/Components/ui/badge';

interface AccountNode {
    id: number;
    account_number: string;
    name: string;
    normal_balance: 'debit' | 'credit';
    children: AccountNode[];
}

interface AccountTreeNodeProps {
    account: AccountNode;
    depth?: number;
    searchTerm?: string;
}

function highlightMatch(text: string, search: string): React.ReactNode {
    if (!search) {
        return text;
    }
    const index = text.toLowerCase().indexOf(search.toLowerCase());
    if (index === -1) {
        return text;
    }
    return (
        <>
            {text.slice(0, index)}
            <mark className="bg-yellow-200 dark:bg-yellow-800 rounded px-0.5">{text.slice(index, index + search.length)}</mark>
            {text.slice(index + search.length)}
        </>
    );
}

function AccountTreeNode({ account, depth = 0, searchTerm = '' }: AccountTreeNodeProps) {
    const [expanded, setExpanded] = useState(depth < 1);
    const hasChildren = account.children && account.children.length > 0;

    return (
        <div>
            <div
                className={`flex items-center gap-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-2 cursor-pointer transition-colors`}
                style={{ paddingLeft: `${depth * 20 + 8}px` }}
                onClick={() => hasChildren && setExpanded(!expanded)}
            >
                {hasChildren ? (
                    <ChevronRightIcon
                        className={`h-4 w-4 flex-shrink-0 text-gray-400 transition-transform duration-200 ${expanded ? 'rotate-90' : ''}`}
                    />
                ) : (
                    <span className="w-4 flex-shrink-0" />
                )}
                <span className="font-mono text-sm font-medium text-blue-600 dark:text-blue-400 flex-shrink-0">
                    {highlightMatch(account.account_number, searchTerm)}
                </span>
                <span className="text-sm text-gray-900 dark:text-gray-100 truncate">
                    {highlightMatch(account.name, searchTerm)}
                </span>
                <Badge
                    variant="outline"
                    className={`text-xs flex-shrink-0 ml-auto ${
                        account.normal_balance === 'credit'
                            ? 'text-green-600 dark:text-green-400 border-green-300 dark:border-green-700'
                            : 'text-orange-600 dark:text-orange-400 border-orange-300 dark:border-orange-700'
                    }`}
                >
                    {account.normal_balance === 'credit' ? 'Cr' : 'Db'}
                </Badge>
            </div>
            {expanded && hasChildren && (
                <div>
                    {account.children.map((child) => (
                        <AccountTreeNode
                            key={child.id}
                            account={child}
                            depth={depth + 1}
                            searchTerm={searchTerm}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

interface AccountTreeViewProps {
    accounts: AccountNode[];
    searchTerm?: string;
}

export default function AccountTreeView({ accounts, searchTerm = '' }: AccountTreeViewProps) {
    if (accounts.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                Aucun compte trouvé
            </div>
        );
    }

    return (
        <div className="divide-y divide-gray-100 dark:divide-gray-800">
            {accounts.map((account) => (
                <AccountTreeNode
                    key={account.id}
                    account={account}
                    searchTerm={searchTerm}
                />
            ))}
        </div>
    );
}
