import React, { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';

interface MentionableUser {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    avatar?: string | null;
}

interface MentionInputProps {
    value: string;
    onChange: (value: string, mentions: number[]) => void;
    placeholder?: string;
    rows?: number;
    className?: string;
    disabled?: boolean;
    autoFocus?: boolean;
    mentionableUsersUrl?: string;
    mentionableUsers?: MentionableUser[];
}

// Helper function to render content for the overlay (hides IDs, shows styled mentions)
function renderOverlayContent(content: string): React.ReactNode {
    if (!content) return null;

    // Pattern to match @[Name](id) format
    const mentionPattern = /@\[([^\]]+)\]\((\d+)\)/g;
    const parts: React.ReactNode[] = [];
    let lastIndex = 0;
    let match;

    while ((match = mentionPattern.exec(content)) !== null) {
        // Add text before the mention
        if (match.index > lastIndex) {
            parts.push(<span key={`text-${lastIndex}`}>{content.slice(lastIndex, match.index)}</span>);
        }

        // Add the mention styled (hiding the ID)
        const [fullMatch, name] = match;
        parts.push(
            <span
                key={`mention-${match.index}`}
                className="text-primary font-medium bg-primary/10 rounded px-0.5"
            >
                @{name}
            </span>
        );

        lastIndex = match.index + fullMatch.length;
    }

    // Add remaining text
    if (lastIndex < content.length) {
        parts.push(<span key={`text-${lastIndex}`}>{content.slice(lastIndex)}</span>);
    }

    return parts.length > 0 ? parts : content;
}

export function MentionInput({
    value,
    onChange,
    placeholder = 'Ajouter un commentaire...',
    rows = 3,
    className = '',
    disabled = false,
    autoFocus = false,
    mentionableUsersUrl,
    mentionableUsers: propUsers,
}: MentionInputProps) {
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [suggestions, setSuggestions] = useState<MentionableUser[]>([]);
    const [filteredSuggestions, setFilteredSuggestions] = useState<MentionableUser[]>([]);
    const [mentionSearch, setMentionSearch] = useState('');
    const [mentionStartIndex, setMentionStartIndex] = useState<number | null>(null);
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [mentions, setMentions] = useState<number[]>([]);
    const [loading, setLoading] = useState(false);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const overlayRef = useRef<HTMLDivElement>(null);
    const suggestionsRef = useRef<HTMLDivElement>(null);

    // Load mentionable users
    useEffect(() => {
        if (propUsers) {
            setSuggestions(propUsers);
            return;
        }

        if (mentionableUsersUrl) {
            setLoading(true);
            axios.get(mentionableUsersUrl)
                .then(response => {
                    setSuggestions(response.data);
                })
                .catch(error => {
                    console.error('Failed to load mentionable users:', error);
                })
                .finally(() => {
                    setLoading(false);
                });
        }
    }, [mentionableUsersUrl, propUsers]);

    // Filter suggestions based on search
    useEffect(() => {
        if (mentionSearch) {
            const search = mentionSearch.toLowerCase();
            const filtered = suggestions.filter(user =>
                user.first_name.toLowerCase().includes(search) ||
                user.last_name.toLowerCase().includes(search) ||
                user.full_name.toLowerCase().includes(search) ||
                user.email.toLowerCase().includes(search)
            );
            setFilteredSuggestions(filtered);
            setSelectedIndex(0);
        } else {
            setFilteredSuggestions(suggestions);
            setSelectedIndex(0);
        }
    }, [mentionSearch, suggestions]);

    // Handle clicks outside to close suggestions
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (
                suggestionsRef.current &&
                !suggestionsRef.current.contains(event.target as Node) &&
                textareaRef.current &&
                !textareaRef.current.contains(event.target as Node)
            ) {
                setShowSuggestions(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleInputChange = useCallback((e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const newValue = e.target.value;
        const cursorPosition = e.target.selectionStart;

        // Check if we're in a mention context
        const textBeforeCursor = newValue.slice(0, cursorPosition);
        const lastAtIndex = textBeforeCursor.lastIndexOf('@');

        if (lastAtIndex !== -1) {
            // Check if there's a space or newline between @ and cursor
            const textAfterAt = textBeforeCursor.slice(lastAtIndex + 1);
            const hasSpaceOrNewline = /[\s\n]/.test(textAfterAt);

            if (!hasSpaceOrNewline && (lastAtIndex === 0 || /[\s\n]/.test(textBeforeCursor[lastAtIndex - 1]))) {
                setMentionStartIndex(lastAtIndex);
                setMentionSearch(textAfterAt);
                setShowSuggestions(true);
            } else {
                setShowSuggestions(false);
                setMentionStartIndex(null);
                setMentionSearch('');
            }
        } else {
            setShowSuggestions(false);
            setMentionStartIndex(null);
            setMentionSearch('');
        }

        onChange(newValue, mentions);
    }, [mentions, onChange]);

    const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (!showSuggestions || filteredSuggestions.length === 0) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setSelectedIndex(prev =>
                    prev < filteredSuggestions.length - 1 ? prev + 1 : prev
                );
                break;
            case 'ArrowUp':
                e.preventDefault();
                setSelectedIndex(prev => prev > 0 ? prev - 1 : 0);
                break;
            case 'Enter':
            case 'Tab':
                if (filteredSuggestions[selectedIndex]) {
                    e.preventDefault();
                    insertMention(filteredSuggestions[selectedIndex]);
                }
                break;
            case 'Escape':
                e.preventDefault();
                setShowSuggestions(false);
                break;
        }
    }, [showSuggestions, filteredSuggestions, selectedIndex]);

    const insertMention = useCallback((user: MentionableUser) => {
        if (mentionStartIndex === null || !textareaRef.current) return;

        const cursorPosition = textareaRef.current.selectionStart;
        const beforeMention = value.slice(0, mentionStartIndex);
        const afterMention = value.slice(cursorPosition);

        // Insert mention in format @[Full Name](user_id)
        const mentionText = `@[${user.full_name}](${user.id}) `;
        const newValue = beforeMention + mentionText + afterMention;

        // Add user to mentions list if not already there
        const newMentions = mentions.includes(user.id)
            ? mentions
            : [...mentions, user.id];

        setMentions(newMentions);
        onChange(newValue, newMentions);
        setShowSuggestions(false);
        setMentionStartIndex(null);
        setMentionSearch('');

        // Set cursor position after mention
        setTimeout(() => {
            if (textareaRef.current) {
                const newCursorPosition = beforeMention.length + mentionText.length;
                textareaRef.current.setSelectionRange(newCursorPosition, newCursorPosition);
                textareaRef.current.focus();
            }
        }, 0);
    }, [mentionStartIndex, value, mentions, onChange]);

    const handleSuggestionClick = useCallback((user: MentionableUser) => {
        insertMention(user);
    }, [insertMention]);

    const getInitials = (firstName: string, lastName: string) => {
        return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase();
    };

    // Sync scroll between textarea and overlay
    const handleScroll = useCallback(() => {
        if (textareaRef.current && overlayRef.current) {
            overlayRef.current.scrollTop = textareaRef.current.scrollTop;
            overlayRef.current.scrollLeft = textareaRef.current.scrollLeft;
        }
    }, []);

    // Check if content has any mentions (to determine if we need the overlay)
    const hasMentions = /@\[[^\]]+\]\(\d+\)/.test(value);

    return (
        <div className="relative">
            {/* Overlay that shows styled mentions (hidden IDs) */}
            {hasMentions && (
                <div
                    ref={overlayRef}
                    className={`absolute top-0 left-0 w-full px-3 py-2 border border-transparent rounded pointer-events-none overflow-hidden whitespace-pre-wrap break-words dark:text-white ${className}`}
                    style={{
                        height: `${(rows || 3) * 1.5}rem`,
                        lineHeight: '1.5rem',
                        fontFamily: 'inherit',
                        fontSize: 'inherit',
                    }}
                    aria-hidden="true"
                >
                    {renderOverlayContent(value)}
                </div>
            )}
            <textarea
                ref={textareaRef}
                value={value}
                onChange={handleInputChange}
                onKeyDown={handleKeyDown}
                onScroll={handleScroll}
                placeholder={placeholder}
                rows={rows}
                disabled={disabled}
                autoFocus={autoFocus}
                className={`w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 resize-none ${hasMentions ? 'text-transparent caret-gray-900 dark:caret-white' : 'dark:text-white'} ${className}`}
                style={hasMentions ? { background: 'transparent' } : undefined}
            />

            {/* Mention Suggestions Dropdown */}
            {showSuggestions && filteredSuggestions.length > 0 && (
                <div
                    ref={suggestionsRef}
                    className="absolute z-50 w-full max-h-48 overflow-y-auto bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg mt-1"
                >
                    {loading ? (
                        <div className="p-3 text-center text-gray-500 dark:text-gray-400">
                            Chargement...
                        </div>
                    ) : (
                        filteredSuggestions.map((user, index) => (
                            <button
                                key={user.id}
                                type="button"
                                className={`w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ${
                                    index === selectedIndex ? 'bg-gray-100 dark:bg-gray-700' : ''
                                }`}
                                onClick={() => handleSuggestionClick(user)}
                                onMouseEnter={() => setSelectedIndex(index)}
                            >
                                {user.avatar ? (
                                    <img
                                        src={user.avatar}
                                        alt={user.full_name}
                                        className="w-8 h-8 rounded-full object-cover"
                                    />
                                ) : (
                                    <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white text-sm font-semibold">
                                        {getInitials(user.first_name, user.last_name)}
                                    </div>
                                )}
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {user.full_name}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {user.email}
                                    </p>
                                </div>
                            </button>
                        ))
                    )}
                </div>
            )}

            {/* Help text */}
            {showSuggestions && filteredSuggestions.length === 0 && mentionSearch && (
                <div
                    ref={suggestionsRef}
                    className="absolute z-50 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg mt-1 p-3"
                >
                    <p className="text-sm text-gray-500 dark:text-gray-400 text-center">
                        Aucun utilisateur trouvé pour "{mentionSearch}"
                    </p>
                </div>
            )}
        </div>
    );
}

// Helper function to render content with highlighted mentions as clickable links
export function renderMentionedContent(content: string): React.ReactNode {
    // Pattern to match @[Name](id) format
    const mentionPattern = /@\[([^\]]+)\]\((\d+)\)/g;
    const parts: React.ReactNode[] = [];
    let lastIndex = 0;
    let match;

    while ((match = mentionPattern.exec(content)) !== null) {
        // Add text before the mention
        if (match.index > lastIndex) {
            parts.push(content.slice(lastIndex, match.index));
        }

        // Add the mention as a clickable link to user profile
        const [fullMatch, name, userId] = match;
        parts.push(
            <a
                key={`${userId}-${match.index}`}
                href={`/profile/${userId}`}
                className="text-primary font-medium bg-primary/10 rounded px-1 hover:underline cursor-pointer"
                onClick={(e) => {
                    e.stopPropagation();
                }}
            >
                @{name}
            </a>
        );

        lastIndex = match.index + fullMatch.length;
    }

    // Add remaining text
    if (lastIndex < content.length) {
        parts.push(content.slice(lastIndex));
    }

    return parts.length > 0 ? parts : content;
}

export default MentionInput;
