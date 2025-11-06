import React, { useEffect, useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { apiLogger } from '@/utils/logger';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    CalendarDaysIcon,
    UserIcon,
    TagIcon,
    ClockIcon,
    ShareIcon,
    BookmarkIcon,
    EyeIcon,
    HeartIcon
} from '@heroicons/react/24/outline';
import {
    BookmarkIcon as BookmarkSolidIcon,
    HeartIcon as HeartSolidIcon
} from '@heroicons/react/24/solid';
import { PageProps } from '@/Types';
import { sanitizeArticleContent } from '@/utils/sanitize';
import { userHasPermission } from '@/Enums/Permission';

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface Article {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    content: string;
    cover_image?: string;
    video_file?: string;
    published_at?: string;
    views_count?: number;
    category: {
        id: number;
        name: string;
    };
    user: {
        id: number;
        first_name: string;
        last_name: string;
    };
    tags: Tag[];
    created_at: string;
    updated_at: string;
}

interface Props extends PageProps {
    article: Article;
    relatedArticles: Article[];
    isLiked: boolean;
    isFavorited: boolean;
    likesCount: number;
}

export default function Show({ article, relatedArticles, auth, isLiked: initialIsLiked, isFavorited: initialIsFavorited, likesCount: initialLikesCount }: Props) {
    const canEditArticles = userHasPermission(auth.user, 'edit articles');

    const [isLiked, setIsLiked] = useState(initialIsLiked);
    const [isFavorited, setIsFavorited] = useState(initialIsFavorited);
    const [likesCount, setLikesCount] = useState(initialLikesCount);
    const [readingProgress, setReadingProgress] = useState(0);

    // Sanitize article content to prevent XSS
    const sanitizedContent = useMemo(() => {
        return sanitizeArticleContent(article.content);
    }, [article.content]);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    // Calculate reading time (average 200 words per minute)
    const calculateReadingTime = (content: string) => {
        const text = content.replace(/<[^>]*>/g, '');
        const wordCount = text.split(/\s+/).length;
        const minutes = Math.ceil(wordCount / 200);
        return minutes;
    };

    // Track reading progress
    useEffect(() => {
        const handleScroll = () => {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.scrollY;
            const progress = (scrollTop / (documentHeight - windowHeight)) * 100;
            setReadingProgress(Math.min(progress, 100));
        };

        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const readingTime = calculateReadingTime(article.content);

    // Handle like toggle
    const handleLike = async () => {
        try {
            const response = await fetch(route('articles.like', article.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setIsLiked(data.isLiked);
                setLikesCount(data.likesCount);
            }
        } catch (error) {
            apiLogger.error('Error toggling like:', error);
        }
    };

    // Handle favorite toggle
    const handleFavorite = async () => {
        try {
            const response = await fetch(route('articles.favorite', article.uuid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setIsFavorited(data.isBookmarked);
            }
        } catch (error) {
            apiLogger.error('Error toggling favorite:', error);
        }
    };

    // Handle share
    const handleShare = async () => {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: article.title,
                    text: `Découvrez cet article: ${article.title}`,
                    url: window.location.href,
                });
            } catch (error) {
                if ((error as Error).name !== 'AbortError') {
                    apiLogger.error('Error sharing:', error);
                }
            }
        } else {
            // Fallback: copy URL to clipboard
            try {
                await navigator.clipboard.writeText(window.location.href);
                alert('Lien copié dans le presse-papier!');
            } catch (error) {
                apiLogger.error('Error copying to clipboard:', error);
            }
        }
    };

    return (
        <DashboardLayout>
            <Head title={`${article.title} - Articles`} />

            {/* Reading Progress Bar */}
            <div
                className="fixed top-0 left-0 h-0.5 bg-gradient-to-r from-icc-blue via-purple-500 to-pink-500 z-50 transition-all duration-150"
                style={{ width: `${readingProgress}%` }}
            />

            <div className="p-4 bg-gray-50 dark:bg-gray-900 min-h-screen">
                {/* Breadcrumb */}
                <div className="mb-6">
                    <Link
                        href={route('articles.index')}
                        className="inline-flex items-center text-sm font-medium text-gray-600 hover:text-icc-blue dark:text-gray-400 dark:hover:text-icc-blue transition-colors"
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                        Retour aux articles
                    </Link>
                </div>

                {/* Hero Section with Cover Image */}
                <div className="relative mb-8">
                    {article.cover_image && (
                        <div className="relative h-[400px] rounded-2xl overflow-hidden shadow-2xl">
                            <div className="absolute inset-0 bg-gradient-to-b from-transparent via-black/30 to-black/80" />
                            <img
                                src={`/storage/${article.cover_image}`}
                                alt={article.title}
                                className="w-full h-full object-cover"
                            />

                            {/* Title Overlay */}
                            <div className="absolute bottom-0 left-0 right-0 p-8">
                                <div className="mb-4">
                                    <span className="inline-block px-3 py-1 bg-icc-blue text-white text-sm font-medium rounded-full">
                                        {article.category.name}
                                    </span>
                                </div>
                                <h1 className="text-4xl md:text-5xl font-bold text-white mb-4 leading-tight">
                                    {article.title}
                                </h1>
                            </div>
                        </div>
                    )}

                    {!article.cover_image && (
                        <div className="bg-gradient-to-r from-icc-blue to-blue-600 rounded-2xl p-12 shadow-2xl">
                            <div className="mb-4">
                                <span className="inline-block px-3 py-1 bg-white/20 text-white text-sm font-medium rounded-full">
                                    {article.category.name}
                                </span>
                            </div>
                            <h1 className="text-4xl md:text-5xl font-bold text-white leading-tight">
                                {article.title}
                            </h1>
                        </div>
                    )}
                </div>

                {/* Main Content Container */}
                <div className="mx-auto">
                    {/* Main Article Content */}
                    <article className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                            {/* Article Meta */}
                            <div className="border-b border-gray-200 dark:border-gray-700 px-8 py-6">
                                <div className="flex items-center justify-between flex-wrap gap-4">
                                    {/* Author Info */}
                                    <div className="flex items-center space-x-4">
                                        <div className="w-12 h-12 rounded-full bg-gradient-to-br from-icc-blue to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                                            {article.user.first_name[0]}{article.user.last_name[0]}
                                        </div>
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">
                                                {article.user.first_name} {article.user.last_name}
                                            </p>
                                            <div className="flex items-center space-x-3 text-sm text-gray-600 dark:text-gray-400">
                                                <span className="flex items-center">
                                                    <CalendarDaysIcon className="h-4 w-4 mr-1" />
                                                    {formatDate(article.published_at || article.created_at)}
                                                </span>
                                                <span>•</span>
                                                <span className="flex items-center">
                                                    <ClockIcon className="h-4 w-4 mr-1" />
                                                    {readingTime} min de lecture
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex items-center space-x-2">
                                        {/* Views count */}
                                        <div className="flex items-center gap-1 px-3 py-2">
                                            <EyeIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {article.views_count || 0}
                                            </span>
                                        </div>

                                        {/* Like button */}
                                        <button
                                            onClick={handleLike}
                                            className="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            title={isLiked ? "Retirer le like" : "Liker"}
                                        >
                                            {isLiked ? (
                                                <HeartSolidIcon className="h-5 w-5 text-red-500" />
                                            ) : (
                                                <HeartIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                            )}
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {likesCount}
                                            </span>
                                        </button>

                                        {/* Favorite button */}
                                        <button
                                            onClick={handleFavorite}
                                            className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            title={isFavorited ? "Retirer des favoris" : "Ajouter aux favoris"}
                                        >
                                            {isFavorited ? (
                                                <BookmarkSolidIcon className="h-5 w-5 text-icc-blue" />
                                            ) : (
                                                <BookmarkIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                            )}
                                        </button>

                                        {/* Share button */}
                                        <button
                                            onClick={handleShare}
                                            className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            title="Partager"
                                        >
                                            <ShareIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                        </button>

                                        {/* Edit/Delete for authorized users */}
                                        {(auth.user?.id === article.user.id || canEditArticles) && (
                                            <>
                                                <Link
                                                    href={route('articles.edit', article.slug)}
                                                    className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                    title="Modifier"
                                                >
                                                    <PencilIcon className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                                </Link>
                                                <Link
                                                    href={route('articles.destroy', article.slug)}
                                                    method="delete"
                                                    as="button"
                                                    className="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                                    data-confirm="Êtes-vous sûr de vouloir supprimer cet article ?"
                                                    title="Supprimer"
                                                >
                                                    <TrashIcon className="h-5 w-5 text-red-600 dark:text-red-400" />
                                                </Link>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {/* Tags */}
                                {article.tags && article.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-2 mt-4">
                                        {article.tags.map((tag) => (
                                            <span
                                                key={tag.id}
                                                className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border"
                                                style={{
                                                    backgroundColor: tag.color + '15',
                                                    color: tag.color,
                                                    borderColor: tag.color + '30'
                                                }}
                                            >
                                                {tag.name}
                                            </span>
                                        ))}
                                    </div>
                                )}

                                {/* Category */}
                                <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <Link
                                        href={`/articles?category=${article.category.id}`}
                                        className="inline-flex items-center gap-2 px-3 py-1.5 bg-icc-blue/10 text-icc-blue rounded-lg hover:bg-icc-blue/20 transition-colors text-sm font-medium"
                                    >
                                        <TagIcon className="h-4 w-4" />
                                        {article.category.name}
                                    </Link>
                                </div>
                            </div>

                            {/* Video Section */}
                            {article.video_file && (
                                <div className="px-8 pt-8">
                                    <div className="relative rounded-xl overflow-hidden shadow-lg" style={{ maxHeight: '500px' }}>
                                        <video
                                            src={`/storage/${article.video_file}`}
                                            controls
                                            controlsList="nodownload"
                                            className="w-full"
                                            style={{ maxHeight: '500px' }}
                                            onContextMenu={(e) => e.preventDefault()}
                                        >
                                            Votre navigateur ne supporte pas la lecture vidéo.
                                        </video>
                                    </div>
                                </div>
                            )}

                            {/* Article Content */}
                            <div className="px-8 py-8">
                                <div className="prose prose-lg dark:prose-invert prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white prose-p:text-gray-700 dark:prose-p:text-gray-300 prose-a:text-icc-blue prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl prose-img:shadow-lg max-w-none">
                                    <div dangerouslySetInnerHTML={{ __html: sanitizedContent }} />
                                </div>
                            </div>

                            {/* Article Footer */}
                            <div className="border-t border-gray-200 dark:border-gray-700 px-8 py-6 bg-gray-50 dark:bg-gray-900">
                                <div className="flex items-center justify-between">
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Dernière mise à jour le {formatDate(article.updated_at)}
                                    </p>
                                </div>
                            </div>
                        </article>
                </div>

                {/* Related Articles */}
                {relatedArticles && relatedArticles.length > 0 && (
                    <div className="mt-12">
                        <div className="flex items-center justify-between mb-8">
                            <h2 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Articles similaires
                            </h2>
                            <Link
                                href={route('articles.index')}
                                className="text-icc-blue hover:text-primary font-medium text-sm"
                            >
                                Voir tous les articles →
                            </Link>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {relatedArticles.map((relatedArticle) => (
                                <Link
                                    key={relatedArticle.id}
                                    href={route('articles.show', relatedArticle.uuid)}
                                    className="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1"
                                >
                                    {relatedArticle.cover_image ? (
                                        <div className="aspect-video bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                            <img
                                                src={`/storage/${relatedArticle.cover_image}`}
                                                alt={relatedArticle.title}
                                                className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                            />
                                        </div>
                                    ) : (
                                        <div className="aspect-video bg-gradient-to-br from-icc-blue/20 to-blue-600/20 flex items-center justify-center">
                                            <TagIcon className="h-16 w-16 text-icc-blue/40" />
                                        </div>
                                    )}

                                    <div className="p-6">
                                        <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-icc-blue transition-colors line-clamp-2">
                                            {relatedArticle.title}
                                        </h3>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                            par {relatedArticle.user?.first_name} {relatedArticle.user?.last_name}
                                        </p>
                                        <span className="inline-flex items-center text-icc-blue font-medium text-sm group-hover:translate-x-2 transition-transform">
                                            Lire l'article
                                            <ArrowLeftIcon className="h-4 w-4 ml-2 rotate-180" />
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}