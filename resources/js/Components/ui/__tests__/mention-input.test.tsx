import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MentionInput, renderMentionedContent } from '../mention-input';
import axios from 'axios';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

const mockUsers = [
    { id: 1, first_name: 'John', last_name: 'Doe', full_name: 'John Doe', email: 'john@test.com', avatar: null },
    { id: 2, first_name: 'Jane', last_name: 'Smith', full_name: 'Jane Smith', email: 'jane@test.com', avatar: null },
    { id: 3, first_name: 'Bob', last_name: 'Johnson', full_name: 'Bob Johnson', email: 'bob@test.com', avatar: null },
];

describe('MentionInput Component', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockedAxios.get.mockResolvedValue({ data: mockUsers });
    });

    describe('Rendering', () => {
        it('renders textarea with placeholder', () => {
            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    placeholder="Add a comment..."
                />
            );
            expect(screen.getByPlaceholderText('Add a comment...')).toBeInTheDocument();
        });

        it('renders with initial value', () => {
            render(
                <MentionInput
                    value="Hello world"
                    onChange={() => {}}
                />
            );
            const textarea = screen.getByRole('textbox');
            expect(textarea).toHaveValue('Hello world');
        });

        it('respects rows prop', () => {
            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    rows={5}
                />
            );
            const textarea = screen.getByRole('textbox');
            expect(textarea).toHaveAttribute('rows', '5');
        });

        it('can be disabled', () => {
            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    disabled
                />
            );
            const textarea = screen.getByRole('textbox');
            expect(textarea).toBeDisabled();
        });
    });

    describe('User Interaction', () => {
        it('calls onChange when typing', async () => {
            const user = userEvent.setup();
            const handleChange = vi.fn();

            render(
                <MentionInput
                    value=""
                    onChange={handleChange}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, 'Hello');

            expect(handleChange).toHaveBeenCalled();
        });

        it('shows suggestions dropdown when typing @', async () => {
            const user = userEvent.setup();

            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    mentionableUsers={mockUsers}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
                expect(screen.getByText('Jane Smith')).toBeInTheDocument();
            });
        });

        it('shows all users when @ is typed', async () => {
            const user = userEvent.setup();

            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    mentionableUsers={mockUsers}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
                expect(screen.getByText('Jane Smith')).toBeInTheDocument();
                expect(screen.getByText('Bob Johnson')).toBeInTheDocument();
            });
        });

        it('inserts mention when clicking a suggestion', async () => {
            const user = userEvent.setup();
            const handleChange = vi.fn();

            render(
                <MentionInput
                    value=""
                    onChange={handleChange}
                    mentionableUsers={mockUsers}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            await user.click(screen.getByText('John Doe'));

            // Should have called onChange with the mention format
            expect(handleChange).toHaveBeenCalledWith(
                expect.stringContaining('@[John Doe](1)'),
                expect.arrayContaining([1])
            );
        });

        it('closes suggestions on Escape key', async () => {
            const user = userEvent.setup();

            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    mentionableUsers={mockUsers}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            await user.keyboard('{Escape}');

            await waitFor(() => {
                expect(screen.queryByText('John Doe')).not.toBeInTheDocument();
            });
        });

        it('navigates suggestions with arrow keys', async () => {
            const user = userEvent.setup();

            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    mentionableUsers={mockUsers}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            // Press arrow down to select second item
            await user.keyboard('{ArrowDown}');

            // The second item (Jane Smith) should now be highlighted
            const janeButton = screen.getByRole('button', { name: /Jane Smith/i });
            expect(janeButton).toHaveClass('bg-gray-100');
        });

        it('selects suggestion with Enter key', async () => {
            const user = userEvent.setup();
            const handleChange = vi.fn();

            render(
                <MentionInput
                    value=""
                    onChange={handleChange}
                    mentionableUsers={mockUsers}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });

            await user.keyboard('{Enter}');

            expect(handleChange).toHaveBeenCalledWith(
                expect.stringContaining('@[John Doe](1)'),
                expect.arrayContaining([1])
            );
        });
    });

    describe('API Integration', () => {
        it('fetches mentionable users from URL', async () => {
            const user = userEvent.setup();
            mockedAxios.get.mockResolvedValue({ data: mockUsers });

            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    mentionableUsersUrl="/api/tasks/123/mentionable-users"
                />
            );

            await waitFor(() => {
                expect(mockedAxios.get).toHaveBeenCalledWith('/api/tasks/123/mentionable-users');
            });

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                expect(screen.getByText('John Doe')).toBeInTheDocument();
            });
        });

        it('prefers provided mentionableUsers over URL', async () => {
            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    mentionableUsers={mockUsers}
                    mentionableUsersUrl="/api/tasks/123/mentionable-users"
                />
            );

            // Should not call API when users are provided
            expect(mockedAxios.get).not.toHaveBeenCalled();
        });
    });

    describe('Display with users', () => {
        it('displays user avatars with initials', async () => {
            const user = userEvent.setup();

            render(
                <MentionInput
                    value=""
                    onChange={() => {}}
                    mentionableUsers={mockUsers}
                />
            );

            const textarea = screen.getByRole('textbox');
            await user.type(textarea, '@');

            await waitFor(() => {
                // Check that initials are displayed (JD for John Doe)
                expect(screen.getByText('JD')).toBeInTheDocument();
                expect(screen.getByText('JS')).toBeInTheDocument();
                expect(screen.getByText('BJ')).toBeInTheDocument();
            });
        });
    });

    describe('Overlay display (hiding IDs in input)', () => {
        it('shows overlay with styled mention when value contains mention format', () => {
            const { container } = render(
                <MentionInput
                    value="Hello @[John Doe](123) how are you?"
                    onChange={() => {}}
                />
            );

            // The overlay should render the mention without the ID visible
            const overlay = container.querySelector('[aria-hidden="true"]');
            expect(overlay).toBeInTheDocument();
            expect(overlay?.textContent).toContain('@John Doe');
            expect(overlay?.textContent).not.toContain('(123)');
        });

        it('does not show overlay when no mentions in value', () => {
            const { container } = render(
                <MentionInput
                    value="Hello world, no mentions here"
                    onChange={() => {}}
                />
            );

            // No overlay should be rendered
            const overlay = container.querySelector('[aria-hidden="true"]');
            expect(overlay).not.toBeInTheDocument();
        });

        it('makes textarea text transparent when mentions are present', () => {
            const { container } = render(
                <MentionInput
                    value="Hello @[John Doe](123)"
                    onChange={() => {}}
                />
            );

            const textarea = container.querySelector('textarea');
            expect(textarea).toHaveClass('text-transparent');
        });

        it('keeps textarea text visible when no mentions', () => {
            const { container } = render(
                <MentionInput
                    value="Hello world"
                    onChange={() => {}}
                />
            );

            const textarea = container.querySelector('textarea');
            expect(textarea).not.toHaveClass('text-transparent');
        });
    });
});

describe('renderMentionedContent', () => {
    it('renders plain text without mentions unchanged', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hello world')}</div>
        );
        expect(container.textContent).toBe('Hello world');
        // Should not have any mention links
        expect(container.querySelector('a.text-primary')).not.toBeInTheDocument();
    });

    it('renders mention as clickable link', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hello @[John Doe](1), how are you?')}</div>
        );

        const mentionLink = container.querySelector('a.text-primary');
        expect(mentionLink).toBeInTheDocument();
        expect(mentionLink).toHaveTextContent('@John Doe');
    });

    it('hides user ID in mention display', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hello @[John Doe](123)')}</div>
        );

        // The ID (123) should not be visible in the rendered text
        expect(container.textContent).not.toContain('123');
        expect(container.textContent).not.toContain('(123)');
        expect(container.textContent).toContain('@John Doe');
    });

    it('links to user profile with correct href', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hello @[John Doe](42)')}</div>
        );

        const mentionLink = container.querySelector('a.text-primary');
        expect(mentionLink).toHaveAttribute('href', '/profile/42');
    });

    it('renders multiple mentions as clickable links', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hello @[John](1) and @[Jane](2)')}</div>
        );

        const mentionLinks = container.querySelectorAll('a.text-primary');
        expect(mentionLinks).toHaveLength(2);
        expect(mentionLinks[0]).toHaveTextContent('@John');
        expect(mentionLinks[0]).toHaveAttribute('href', '/profile/1');
        expect(mentionLinks[1]).toHaveTextContent('@Jane');
        expect(mentionLinks[1]).toHaveAttribute('href', '/profile/2');
    });

    it('preserves text before and after mentions', () => {
        const { container } = render(
            <div>{renderMentionedContent('Start @[User](1) middle @[Other](2) end')}</div>
        );

        expect(container.textContent).toContain('Start');
        expect(container.textContent).toContain('middle');
        expect(container.textContent).toContain('end');
    });

    it('mention links have hover styling class', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hello @[John Doe](1)')}</div>
        );

        const mentionLink = container.querySelector('a.text-primary');
        expect(mentionLink).toHaveClass('hover:underline');
        expect(mentionLink).toHaveClass('cursor-pointer');
    });
});

describe('renderMentionedContent - URL linkification', () => {
    it('renders a URL as a clickable link', () => {
        const { container } = render(
            <div>{renderMentionedContent('Check this https://example.com please')}</div>
        );

        const link = container.querySelector('a[href="https://example.com"]');
        expect(link).toBeInTheDocument();
        expect(link).toHaveTextContent('https://example.com');
        expect(link).toHaveAttribute('target', '_blank');
        expect(link).toHaveAttribute('rel', 'noopener noreferrer');
    });

    it('renders URL with path and query string', () => {
        const { container } = render(
            <div>{renderMentionedContent('See https://example.com/page?id=42&lang=fr for details')}</div>
        );

        const link = container.querySelector('a[href="https://example.com/page?id=42&lang=fr"]');
        expect(link).toBeInTheDocument();
        expect(link).toHaveTextContent('https://example.com/page?id=42&lang=fr');
    });

    it('renders http URL as a clickable link', () => {
        const { container } = render(
            <div>{renderMentionedContent('Visit http://example.com')}</div>
        );

        const link = container.querySelector('a[href="http://example.com"]');
        expect(link).toBeInTheDocument();
    });

    it('renders multiple URLs as clickable links', () => {
        const { container } = render(
            <div>{renderMentionedContent('Check https://one.com and https://two.com')}</div>
        );

        const links = container.querySelectorAll('a[target="_blank"]');
        expect(links).toHaveLength(2);
        expect(links[0]).toHaveAttribute('href', 'https://one.com');
        expect(links[1]).toHaveAttribute('href', 'https://two.com');
    });

    it('renders URL at the start of text', () => {
        const { container } = render(
            <div>{renderMentionedContent('https://example.com is great')}</div>
        );

        const link = container.querySelector('a[href="https://example.com"]');
        expect(link).toBeInTheDocument();
        expect(container.textContent).toContain('is great');
    });

    it('renders URL at the end of text', () => {
        const { container } = render(
            <div>{renderMentionedContent('Visit https://example.com')}</div>
        );

        const link = container.querySelector('a[href="https://example.com"]');
        expect(link).toBeInTheDocument();
        expect(container.textContent).toContain('Visit');
    });

    it('preserves text around URLs', () => {
        const { container } = render(
            <div>{renderMentionedContent('Before https://example.com after')}</div>
        );

        expect(container.textContent).toBe('Before https://example.com after');
    });

    it('does not linkify text without URLs', () => {
        const { container } = render(
            <div>{renderMentionedContent('Just plain text here')}</div>
        );

        expect(container.querySelector('a')).not.toBeInTheDocument();
        expect(container.textContent).toBe('Just plain text here');
    });

    it('renders URL with special characters in path', () => {
        const { container } = render(
            <div>{renderMentionedContent('See https://docs.google.com/spreadsheets/d/1WeT0kxNg9054eclo-VVk/edit?gid=300551873#gid=300551873')}</div>
        );

        const link = container.querySelector('a[target="_blank"]');
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', 'https://docs.google.com/spreadsheets/d/1WeT0kxNg9054eclo-VVk/edit?gid=300551873#gid=300551873');
    });

    it('URL link has correct styling classes', () => {
        const { container } = render(
            <div>{renderMentionedContent('Visit https://example.com')}</div>
        );

        const link = container.querySelector('a[target="_blank"]');
        expect(link).toHaveClass('text-blue-600');
        expect(link).toHaveClass('hover:underline');
    });

    it('renders mentions and URLs together correctly', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hey @[John Doe](1) check https://example.com please')}</div>
        );

        // Mention link
        const mentionLink = container.querySelector('a[href="/profile/1"]');
        expect(mentionLink).toBeInTheDocument();
        expect(mentionLink).toHaveTextContent('@John Doe');

        // URL link
        const urlLink = container.querySelector('a[href="https://example.com"]');
        expect(urlLink).toBeInTheDocument();
        expect(urlLink).toHaveAttribute('target', '_blank');
    });

    it('renders URL before mention correctly', () => {
        const { container } = render(
            <div>{renderMentionedContent('See https://example.com @[Jane](2) FYI')}</div>
        );

        const urlLink = container.querySelector('a[href="https://example.com"]');
        expect(urlLink).toBeInTheDocument();

        const mentionLink = container.querySelector('a[href="/profile/2"]');
        expect(mentionLink).toBeInTheDocument();
        expect(mentionLink).toHaveTextContent('@Jane');
    });

    it('renders multiple URLs and mentions mixed together', () => {
        const { container } = render(
            <div>{renderMentionedContent('Hey @[John](1) see https://one.com and @[Jane](2) check https://two.com')}</div>
        );

        const mentionLinks = container.querySelectorAll('a.text-primary');
        expect(mentionLinks).toHaveLength(2);

        const urlLinks = container.querySelectorAll('a[target="_blank"]');
        expect(urlLinks).toHaveLength(2);
    });
});
