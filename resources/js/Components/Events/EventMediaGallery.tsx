import React, { useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import {
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    PlayIcon,
    PhotoIcon,
    VideoCameraIcon,
    StarIcon,
    TrashIcon,
    FlagIcon,
    Squares2X2Icon,
    RectangleStackIcon,
} from '@heroicons/react/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/react/24/solid';
import { EventMedia } from '@/Types/event.d';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import VideoPreview from './VideoPreview';
import MediaCarousel from './MediaCarousel';

interface EventMediaGalleryProps {
    media: EventMedia[];
    eventUuid: string;
    canEdit?: boolean;
    onMediaDeleted?: (mediaId: number) => void;
    onMediaUpdated?: (media: EventMedia) => void;
    className?: string;
}

export default function EventMediaGallery({
    media,
    eventUuid,
    canEdit = false,
    onMediaDeleted,
    onMediaUpdated,
    className = '',
}: EventMediaGalleryProps) {
    const [selectedIndex, setSelectedIndex] = useState<number | null>(null);
    const [activeTab, setActiveTab] = useState<'all' | 'images' | 'videos'>('all');
    const [deleteMedia, setDeleteMedia] = useState<EventMedia | null>(null);
    const [viewMode, setViewMode] = useState<'carousel' | 'grid'>('carousel');

    const filteredMedia = media.filter((item) => {
        if (activeTab === 'images') return item.media_type === 'image';
        if (activeTab === 'videos') return item.media_type === 'video';
        return true;
    });

    const images = media.filter((item) => item.media_type === 'image');
    const videos = media.filter((item) => item.media_type === 'video');

    const selectedMedia = selectedIndex !== null ? filteredMedia[selectedIndex] : null;

    const openLightbox = (index: number) => {
        setSelectedIndex(index);
    };

    const closeLightbox = () => {
        setSelectedIndex(null);
    };

    const goToPrevious = () => {
        if (selectedIndex !== null && selectedIndex > 0) {
            setSelectedIndex(selectedIndex - 1);
        }
    };

    const goToNext = () => {
        if (selectedIndex !== null && selectedIndex < filteredMedia.length - 1) {
            setSelectedIndex(selectedIndex + 1);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'ArrowLeft') goToPrevious();
        if (e.key === 'ArrowRight') goToNext();
        if (e.key === 'Escape') closeLightbox();
    };

    const handleSetFeatured = async (media: EventMedia) => {
        try {
            const response = await fetch(route('events.media.set-featured', [eventUuid, media.uuid]), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) throw new Error('Erreur lors de la mise à jour');

            const data = await response.json();
            toast.success('Média mis en avant');
            onMediaUpdated?.(data.media);
        } catch (error) {
            toast.error('Erreur lors de la mise à jour');
        }
    };

    const handleSetBanner = async (media: EventMedia) => {
        try {
            const response = await fetch(route('events.media.set-banner', [eventUuid, media.uuid]), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) throw new Error('Erreur lors de la mise à jour');

            const data = await response.json();
            toast.success('Banner défini');
            onMediaUpdated?.(data.media);
        } catch (error) {
            toast.error('Erreur lors de la mise à jour');
        }
    };

    const handleDelete = async () => {
        if (!deleteMedia) return;

        try {
            const response = await fetch(route('events.media.destroy', [eventUuid, deleteMedia.uuid]), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) throw new Error('Erreur lors de la suppression');

            toast.success('Média supprimé');
            onMediaDeleted?.(deleteMedia.id);
            closeLightbox();
        } catch (error) {
            toast.error('Erreur lors de la suppression');
        } finally {
            setDeleteMedia(null);
        }
    };

    const getMediaUrl = (item: EventMedia) => {
        return item.file_url || `/storage/${item.file_path}`;
    };

    const getThumbnailUrl = (item: EventMedia) => {
        if (item.thumbnail_url) return item.thumbnail_url;
        if (item.thumbnail_path) return `/storage/${item.thumbnail_path}`;
        if (item.media_type === 'image') return getMediaUrl(item);
        return null;
    };

    if (media.length === 0) {
        return (
            <div className={`text-center py-12 ${className}`}>
                <PhotoIcon className="mx-auto h-12 w-12 text-gray-400" />
                <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Aucun média pour le moment
                </p>
            </div>
        );
    }

    return (
        <div className={className}>
            {/* Tabs and View Toggle */}
            <div className="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-gray-700">
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() => setActiveTab('all')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'all'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        }`}
                    >
                        Tout ({media.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('images')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1 ${
                            activeTab === 'images'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        }`}
                    >
                        <PhotoIcon className="h-4 w-4" />
                        Images ({images.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('videos')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1 ${
                            activeTab === 'videos'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        }`}
                    >
                        <VideoCameraIcon className="h-4 w-4" />
                        Vidéos ({videos.length})
                    </button>
                </div>

                {/* View Mode Toggle */}
                <div className="flex items-center gap-1 mr-2" data-testid="view-mode-toggle">
                    <button
                        type="button"
                        onClick={() => setViewMode('carousel')}
                        className={`p-2 rounded transition-colors ${
                            viewMode === 'carousel'
                                ? 'bg-primary text-white'
                                : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                        }`}
                        title="Vue carrousel"
                        aria-label="Vue carrousel"
                        data-testid="carousel-view-btn"
                    >
                        <RectangleStackIcon className="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        onClick={() => setViewMode('grid')}
                        className={`p-2 rounded transition-colors ${
                            viewMode === 'grid'
                                ? 'bg-primary text-white'
                                : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                        }`}
                        title="Vue grille"
                        aria-label="Vue grille"
                        data-testid="grid-view-btn"
                    >
                        <Squares2X2Icon className="h-5 w-5" />
                    </button>
                </div>
            </div>

            {/* Carousel View */}
            {viewMode === 'carousel' && (
                <MediaCarousel
                    media={filteredMedia}
                    autoPlay={true}
                    autoPlayInterval={5000}
                    className="rounded-lg"
                    onMediaClick={(item, index) => openLightbox(index)}
                    onSlideChange={(index) => {
                        // Optional: track current slide for other purposes
                    }}
                />
            )}

            {/* Gallery Grid View */}
            {viewMode === 'grid' && (
                <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2" data-testid="gallery-grid">
                    {filteredMedia.map((item, index) => (
                        <div
                            key={item.id}
                            className="relative aspect-[4/3] group cursor-pointer rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800"
                            onClick={() => openLightbox(index)}
                        >
                            {item.media_type === 'image' ? (
                                <img
                                    src={getMediaUrl(item)}
                                    alt={item.title || item.file_name}
                                    className="w-full h-full object-contain transition-transform group-hover:scale-105"
                                    loading="lazy"
                                />
                            ) : (
                                <VideoPreview
                                    media={item}
                                    className="w-full h-full object-contain"
                                />
                            )}

                            {/* Featured Badge */}
                            {item.is_featured && (
                                <div className="absolute top-2 left-2">
                                    <StarIconSolid className="h-5 w-5 text-yellow-400" />
                                </div>
                            )}

                            {/* Banner Badge */}
                            {item.collection === 'banner' && (
                                <div className="absolute top-2 right-2 bg-primary text-white text-xs px-2 py-0.5 rounded">
                                    Banner
                                </div>
                            )}

                            {/* Hover Overlay */}
                            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors" />
                        </div>
                    ))}
                </div>
            )}

            {/* Lightbox */}
            <Transition show={selectedIndex !== null} as={React.Fragment}>
                <Dialog
                    as="div"
                    className="relative z-50"
                    onClose={closeLightbox}
                    onKeyDown={handleKeyDown}
                >
                    <Transition.Child
                        as={React.Fragment}
                        enter="ease-out duration-300"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="ease-in duration-200"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="fixed inset-0 bg-black/90" />
                    </Transition.Child>

                    <div className="fixed inset-0 overflow-y-auto">
                        <div className="flex min-h-full items-center justify-center p-4">
                            <Transition.Child
                                as={React.Fragment}
                                enter="ease-out duration-300"
                                enterFrom="opacity-0 scale-95"
                                enterTo="opacity-100 scale-100"
                                leave="ease-in duration-200"
                                leaveFrom="opacity-100 scale-100"
                                leaveTo="opacity-0 scale-95"
                            >
                                <Dialog.Panel className="relative w-full max-w-5xl">
                                    {/* Close Button */}
                                    <button
                                        type="button"
                                        onClick={closeLightbox}
                                        className="absolute top-4 right-4 z-10 p-2 rounded-full bg-black/50 text-white hover:bg-black/70"
                                    >
                                        <XMarkIcon className="h-6 w-6" />
                                    </button>

                                    {/* Navigation Buttons */}
                                    {selectedIndex !== null && selectedIndex > 0 && (
                                        <button
                                            type="button"
                                            onClick={goToPrevious}
                                            className="absolute left-4 top-1/2 -translate-y-1/2 z-10 p-2 rounded-full bg-black/50 text-white hover:bg-black/70"
                                        >
                                            <ChevronLeftIcon className="h-6 w-6" />
                                        </button>
                                    )}
                                    {selectedIndex !== null && selectedIndex < filteredMedia.length - 1 && (
                                        <button
                                            type="button"
                                            onClick={goToNext}
                                            className="absolute right-4 top-1/2 -translate-y-1/2 z-10 p-2 rounded-full bg-black/50 text-white hover:bg-black/70"
                                        >
                                            <ChevronRightIcon className="h-6 w-6" />
                                        </button>
                                    )}

                                    {/* Media Content */}
                                    {selectedMedia && (
                                        <div className="flex flex-col items-center">
                                            {selectedMedia.media_type === 'image' ? (
                                                <img
                                                    src={getMediaUrl(selectedMedia)}
                                                    alt={selectedMedia.title || selectedMedia.file_name}
                                                    className="max-h-[80vh] w-auto object-contain rounded-lg"
                                                />
                                            ) : (
                                                <video
                                                    src={getMediaUrl(selectedMedia)}
                                                    controls
                                                    autoPlay
                                                    className="max-h-[80vh] w-auto rounded-lg"
                                                />
                                            )}

                                            {/* Media Info and Actions */}
                                            <div className="mt-4 flex items-center justify-between w-full max-w-xl px-4">
                                                <div className="text-white">
                                                    <p className="font-medium">
                                                        {selectedMedia.title || selectedMedia.file_name}
                                                    </p>
                                                    {selectedMedia.description && (
                                                        <p className="text-sm text-gray-300">
                                                            {selectedMedia.description}
                                                        </p>
                                                    )}
                                                    <p className="text-xs text-gray-400 mt-1">
                                                        {selectedMedia.file_size_for_humans || `${Math.round(selectedMedia.file_size / 1024)} KB`}
                                                        {selectedMedia.dimensions && ` • ${selectedMedia.dimensions}`}
                                                        {selectedMedia.duration_for_humans && ` • ${selectedMedia.duration_for_humans}`}
                                                    </p>
                                                </div>

                                                {canEdit && (
                                                    <div className="flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleSetFeatured(selectedMedia)}
                                                            className={`p-2 rounded-full ${
                                                                selectedMedia.is_featured
                                                                    ? 'bg-yellow-500 text-white'
                                                                    : 'bg-white/10 text-white hover:bg-white/20'
                                                            }`}
                                                            title="Mettre en avant"
                                                        >
                                                            <StarIcon className="h-5 w-5" />
                                                        </button>
                                                        {selectedMedia.media_type === 'image' && (
                                                            <button
                                                                type="button"
                                                                onClick={() => handleSetBanner(selectedMedia)}
                                                                className={`p-2 rounded-full ${
                                                                    selectedMedia.collection === 'banner'
                                                                        ? 'bg-primary text-white'
                                                                        : 'bg-white/10 text-white hover:bg-white/20'
                                                                }`}
                                                                title="Définir comme banner"
                                                            >
                                                                <FlagIcon className="h-5 w-5" />
                                                            </button>
                                                        )}
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteMedia(selectedMedia)}
                                                            className="p-2 rounded-full bg-white/10 text-white hover:bg-red-500"
                                                            title="Supprimer"
                                                        >
                                                            <TrashIcon className="h-5 w-5" />
                                                        </button>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Counter */}
                                            <p className="mt-2 text-sm text-gray-400">
                                                {selectedIndex !== null ? selectedIndex + 1 : 0} / {filteredMedia.length}
                                            </p>
                                        </div>
                                    )}
                                </Dialog.Panel>
                            </Transition.Child>
                        </div>
                    </div>
                </Dialog>
            </Transition>

            {/* Delete Confirmation */}
            <DeleteConfirmationDialog
                open={deleteMedia !== null}
                onOpenChange={(open) => !open && setDeleteMedia(null)}
                onConfirm={handleDelete}
                title="Supprimer le média"
                description="Êtes-vous sûr de vouloir supprimer ce média ? Cette action est irréversible."
            />
        </div>
    );
}
