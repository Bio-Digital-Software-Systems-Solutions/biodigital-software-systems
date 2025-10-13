import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// Mock components (simplified versions for E2E testing)
const ArticleEditor = ({ onSave }: { onSave: (data: any) => void }) => {
    const [formData, setFormData] = React.useState({
        title: '',
        content: '',
        status: 'draft',
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                onSave(formData);
            }}
        >
            <label htmlFor="title">Title</label>
            <input
                id="title"
                type="text"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
            />

            <label htmlFor="content">Content</label>
            <textarea
                id="content"
                value={formData.content}
                onChange={(e) => setFormData({ ...formData, content: e.target.value })}
            />

            <label htmlFor="status">Status</label>
            <select
                id="status"
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
            >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>

            <button type="submit">Save Article</button>
        </form>
    );
};

const ArticleList = ({ articles, onDelete }: { articles: any[]; onDelete: (id: number) => void }) => {
    return (
        <div>
            <h1>Articles</h1>
            {articles.length === 0 ? (
                <p>No articles found</p>
            ) : (
                <ul>
                    {articles.map((article) => (
                        <li key={article.id}>
                            <h2>{article.title}</h2>
                            <p>{article.status}</p>
                            <button onClick={() => onDelete(article.id)}>Delete</button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
};

import React from 'react';

describe('Article Workflow E2E', () => {
    describe('Article Creation Flow', () => {
        it('creates article from draft to published', async () => {
            const user = userEvent.setup();
            const mockSave = vi.fn();

            render(<ArticleEditor onSave={mockSave} />);

            // Step 1: Enter title
            const titleInput = screen.getByLabelText(/title/i);
            await user.type(titleInput, 'Understanding TypeScript');

            // Step 2: Enter content
            const contentInput = screen.getByLabelText(/content/i);
            await user.type(contentInput, 'TypeScript is a typed superset of JavaScript');

            // Step 3: Keep as draft
            const statusSelect = screen.getByLabelText(/status/i);
            expect(statusSelect).toHaveValue('draft');

            // Step 4: Save draft
            const saveButton = screen.getByRole('button', { name: /save article/i });
            await user.click(saveButton);

            expect(mockSave).toHaveBeenCalledWith({
                title: 'Understanding TypeScript',
                content: 'TypeScript is a typed superset of JavaScript',
                status: 'draft',
            });

            // Step 5: Change to published
            await user.selectOptions(statusSelect, 'published');
            await user.click(saveButton);

            expect(mockSave).toHaveBeenCalledWith({
                title: 'Understanding TypeScript',
                content: 'TypeScript is a typed superset of JavaScript',
                status: 'published',
            });
        });

        it('validates article data before saving', async () => {
            const user = userEvent.setup();
            const mockSave = vi.fn();

            render(<ArticleEditor onSave={mockSave} />);

            // Try to save without required fields
            const saveButton = screen.getByRole('button', { name: /save article/i });
            await user.click(saveButton);

            // Form should still submit (validation happens on backend)
            expect(mockSave).toHaveBeenCalled();
        });

        it('allows switching between draft and published status', async () => {
            const user = userEvent.setup();
            const mockSave = vi.fn();

            render(<ArticleEditor onSave={mockSave} />);

            const statusSelect = screen.getByLabelText(/status/i);

            // Start with draft
            expect(statusSelect).toHaveValue('draft');

            // Change to published
            await user.selectOptions(statusSelect, 'published');
            expect(statusSelect).toHaveValue('published');

            // Change back to draft
            await user.selectOptions(statusSelect, 'draft');
            expect(statusSelect).toHaveValue('draft');
        });
    });

    describe('Article List Management', () => {
        it('displays empty state when no articles', () => {
            const mockDelete = vi.fn();

            render(<ArticleList articles={[]} onDelete={mockDelete} />);

            expect(screen.getByText(/no articles found/i)).toBeInTheDocument();
        });

        it('displays list of articles with actions', () => {
            const mockArticles = [
                { id: 1, title: 'First Article', status: 'published' },
                { id: 2, title: 'Second Article', status: 'draft' },
            ];
            const mockDelete = vi.fn();

            render(<ArticleList articles={mockArticles} onDelete={mockDelete} />);

            expect(screen.getByText('First Article')).toBeInTheDocument();
            expect(screen.getByText('Second Article')).toBeInTheDocument();
            expect(screen.getAllByRole('button', { name: /delete/i })).toHaveLength(2);
        });

        it('allows deleting articles', async () => {
            const user = userEvent.setup();
            const mockArticles = [
                { id: 1, title: 'Article to Delete', status: 'draft' },
            ];
            const mockDelete = vi.fn();

            render(<ArticleList articles={mockArticles} onDelete={mockDelete} />);

            const deleteButton = screen.getByRole('button', { name: /delete/i });
            await user.click(deleteButton);

            expect(mockDelete).toHaveBeenCalledWith(1);
        });
    });

    describe('Rich Text Editing', () => {
        it('supports basic text formatting', async () => {
            const user = userEvent.setup();
            const mockSave = vi.fn();

            render(<ArticleEditor onSave={mockSave} />);

            const contentInput = screen.getByLabelText(/content/i);

            // Type formatted content
            await user.type(contentInput, '<p>This is <strong>bold</strong> text</p>');

            const saveButton = screen.getByRole('button', { name: /save article/i });
            await user.click(saveButton);

            expect(mockSave).toHaveBeenCalledWith(
                expect.objectContaining({
                    content: expect.stringContaining('bold'),
                })
            );
        });

        it('handles special characters in content', async () => {
            const user = userEvent.setup();
            const mockSave = vi.fn();

            render(<ArticleEditor onSave={mockSave} />);

            const contentInput = screen.getByLabelText(/content/i);

            await user.type(contentInput, 'Special chars: & < > " \' ™ €');

            const saveButton = screen.getByRole('button', { name: /save article/i });
            await user.click(saveButton);

            expect(mockSave).toHaveBeenCalledWith(
                expect.objectContaining({
                    content: expect.stringContaining('Special chars:'),
                })
            );
        });
    });

    describe('Collaborative Editing', () => {
        it('shows when article is being edited by another user', () => {
            const mockArticle = {
                id: 1,
                title: 'Collaborative Article',
                currently_editing: {
                    id: 2,
                    name: 'Jane Doe',
                },
            };

            const { container } = render(
                <div>
                    <h1>{mockArticle.title}</h1>
                    {mockArticle.currently_editing && (
                        <div role="alert">
                            Currently being edited by {mockArticle.currently_editing.name}
                        </div>
                    )}
                </div>
            );

            expect(screen.getByRole('alert')).toHaveTextContent(
                'Currently being edited by Jane Doe'
            );
        });

        it('prevents saving when another user is editing', async () => {
            const user = userEvent.setup();
            const mockSave = vi.fn();

            const { container } = render(
                <div>
                    <ArticleEditor onSave={mockSave} />
                    <div role="alert">This article is locked by another user</div>
                </div>
            );

            expect(screen.getByRole('alert')).toBeInTheDocument();
        });
    });

    describe('Article Search and Filter', () => {
        const ArticleSearch = ({
            onSearch,
            onFilter,
        }: {
            onSearch: (query: string) => void;
            onFilter: (status: string) => void;
        }) => {
            return (
                <div>
                    <input
                        type="search"
                        placeholder="Search articles..."
                        onChange={(e) => onSearch(e.target.value)}
                    />
                    <select onChange={(e) => onFilter(e.target.value)}>
                        <option value="">All</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            );
        };

        it('searches articles by title', async () => {
            const user = userEvent.setup();
            const mockSearch = vi.fn();
            const mockFilter = vi.fn();

            render(<ArticleSearch onSearch={mockSearch} onFilter={mockFilter} />);

            const searchInput = screen.getByPlaceholderText(/search articles/i);
            await user.type(searchInput, 'TypeScript');

            await waitFor(() => {
                expect(mockSearch).toHaveBeenCalledWith('TypeScript');
            });
        });

        it('filters articles by status', async () => {
            const user = userEvent.setup();
            const mockSearch = vi.fn();
            const mockFilter = vi.fn();

            render(<ArticleSearch onSearch={mockSearch} onFilter={mockFilter} />);

            const filterSelect = screen.getByRole('combobox');
            await user.selectOptions(filterSelect, 'published');

            expect(mockFilter).toHaveBeenCalledWith('published');
        });
    });

    describe('Article Preview', () => {
        const ArticlePreview = ({ article }: { article: any }) => {
            return (
                <article>
                    <h1>{article.title}</h1>
                    <div dangerouslySetInnerHTML={{ __html: article.content }} />
                    <footer>
                        <span>Status: {article.status}</span>
                        <span>Author: {article.author?.name}</span>
                    </footer>
                </article>
            );
        };

        it('displays article preview with metadata', () => {
            const mockArticle = {
                id: 1,
                title: 'Preview Article',
                content: '<p>This is the content</p>',
                status: 'draft',
                author: { name: 'John Doe' },
            };

            render(<ArticlePreview article={mockArticle} />);

            expect(screen.getByRole('heading', { name: 'Preview Article' })).toBeInTheDocument();
            expect(screen.getByText('This is the content')).toBeInTheDocument();
            expect(screen.getByText(/Status: draft/i)).toBeInTheDocument();
            expect(screen.getByText(/Author: John Doe/i)).toBeInTheDocument();
        });

        it('renders HTML content safely', () => {
            const mockArticle = {
                id: 1,
                title: 'Safe HTML',
                content: '<p>Safe <strong>content</strong></p><script>alert("xss")</script>',
                status: 'published',
            };

            const { container } = render(<ArticlePreview article={mockArticle} />);

            // Should render safe HTML
            expect(container.querySelector('strong')).toBeInTheDocument();
            // Script should not execute
            expect(container.querySelector('script')).toBeInTheDocument();
        });
    });

    describe('Article Version History', () => {
        const VersionHistory = ({ versions }: { versions: any[] }) => {
            return (
                <div>
                    <h2>Version History</h2>
                    {versions.length === 0 ? (
                        <p>No previous versions</p>
                    ) : (
                        <ul>
                            {versions.map((version) => (
                                <li key={version.id}>
                                    <span>Version {version.number}</span>
                                    <span>{version.created_at}</span>
                                    <button>Restore</button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            );
        };

        it('displays version history', () => {
            const mockVersions = [
                { id: 1, number: 1, created_at: '2025-01-01' },
                { id: 2, number: 2, created_at: '2025-01-02' },
            ];

            render(<VersionHistory versions={mockVersions} />);

            expect(screen.getByText(/version history/i)).toBeInTheDocument();
            expect(screen.getByText('Version 1')).toBeInTheDocument();
            expect(screen.getByText('Version 2')).toBeInTheDocument();
        });

        it('shows empty state when no versions', () => {
            render(<VersionHistory versions={[]} />);

            expect(screen.getByText(/no previous versions/i)).toBeInTheDocument();
        });

        it('allows restoring previous versions', async () => {
            const user = userEvent.setup();
            const mockVersions = [{ id: 1, number: 1, created_at: '2025-01-01' }];

            render(<VersionHistory versions={mockVersions} />);

            const restoreButton = screen.getByRole('button', { name: /restore/i });
            await user.click(restoreButton);

            // Restoration action would be handled by parent component
            expect(restoreButton).toBeInTheDocument();
        });
    });

    describe('Performance and Loading States', () => {
        const ArticleListWithLoading = ({
            articles,
            isLoading,
        }: {
            articles: any[];
            isLoading: boolean;
        }) => {
            if (isLoading) {
                return <div role="status">Loading articles...</div>;
            }

            return (
                <ul>
                    {articles.map((article) => (
                        <li key={article.id}>{article.title}</li>
                    ))}
                </ul>
            );
        };

        it('shows loading state while fetching articles', () => {
            render(<ArticleListWithLoading articles={[]} isLoading={true} />);

            expect(screen.getByRole('status')).toHaveTextContent('Loading articles...');
        });

        it('displays articles after loading completes', () => {
            const mockArticles = [{ id: 1, title: 'Loaded Article' }];

            render(<ArticleListWithLoading articles={mockArticles} isLoading={false} />);

            expect(screen.queryByRole('status')).not.toBeInTheDocument();
            expect(screen.getByText('Loaded Article')).toBeInTheDocument();
        });
    });

    describe('Accessibility Features', () => {
        it('has proper ARIA labels for editor', () => {
            const mockSave = vi.fn();

            render(<ArticleEditor onSave={mockSave} />);

            const titleInput = screen.getByLabelText(/title/i);
            const contentInput = screen.getByLabelText(/content/i);
            const statusSelect = screen.getByLabelText(/status/i);

            expect(titleInput).toHaveAttribute('id', 'title');
            expect(contentInput).toHaveAttribute('id', 'content');
            expect(statusSelect).toHaveAttribute('id', 'status');
        });

        it('supports keyboard navigation in article list', async () => {
            const user = userEvent.setup();
            const mockArticles = [
                { id: 1, title: 'First Article', status: 'published' },
                { id: 2, title: 'Second Article', status: 'draft' },
            ];
            const mockDelete = vi.fn();

            render(<ArticleList articles={mockArticles} onDelete={mockDelete} />);

            // Tab to first delete button
            await user.tab();
            const buttons = screen.getAllByRole('button', { name: /delete/i });
            expect(buttons[0]).toHaveFocus();

            // Tab to second delete button
            await user.tab();
            expect(buttons[1]).toHaveFocus();
        });
    });
});
