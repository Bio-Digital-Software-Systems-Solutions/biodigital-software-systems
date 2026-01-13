import React, { useState, useCallback, useMemo, useEffect } from 'react';
import {
    DocumentIcon,
    DocumentTextIcon,
    PhotoIcon,
    TableCellsIcon,
    PresentationChartBarIcon,
    ArchiveBoxIcon,
    FolderIcon,
    FolderOpenIcon,
    ChevronRightIcon,
    ChevronDownIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    PencilIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    CloudArrowUpIcon,
    EyeIcon,
    SpeakerWaveIcon,
    FilmIcon,
} from '@heroicons/react/24/outline';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/Components/ui/context-menu';
import { toast } from 'sonner';
import axios from 'axios';
import { useDropzone } from 'react-dropzone';
import DocumentPreviewDialog from './DocumentPreviewDialog';

interface DocumentData {
    uuid: string;
    title: string;
    original_name: string;
    file_name: string;
    file_url: string;
    preview_url: string;
    file_size: number;
    formatted_file_size: string;
    mime_type: string;
    extension: string;
    file_type: string;
    can_preview: boolean;
    preview_type: string;
    description: string | null;
    category: string | null;
    created_at: string;
    uploader: {
        id: number;
        name: string;
    } | null;
}

interface CategoryData {
    uuid?: string;
    name: string;
    key: string;
    is_system?: boolean;
    documents: DocumentData[];
}

interface MonthData {
    month: number;
    month_name: string;
    categories: CategoryData[];
    document_count: number;
}

interface YearData {
    year: number;
    months: MonthData[];
    document_count: number;
}

interface DepartmentDocumentsProps {
    departmentUuid: string;
    initialTree?: YearData[];
    canManage?: boolean;
}

const fileTypeIcons: Record<string, React.ComponentType<{ className?: string }>> = {
    pdf: DocumentTextIcon,
    word: DocumentTextIcon,
    excel: TableCellsIcon,
    powerpoint: PresentationChartBarIcon,
    image: PhotoIcon,
    video: FilmIcon,
    audio: SpeakerWaveIcon,
    archive: ArchiveBoxIcon,
    text: DocumentTextIcon,
    other: DocumentIcon,
};

const fileTypeColors: Record<string, string> = {
    pdf: 'text-red-500',
    word: 'text-blue-500',
    excel: 'text-green-500',
    powerpoint: 'text-orange-500',
    image: 'text-purple-500',
    video: 'text-pink-500',
    audio: 'text-cyan-500',
    archive: 'text-yellow-500',
    text: 'text-gray-500',
    other: 'text-gray-400',
};

export default function DepartmentDocuments({
    departmentUuid,
    initialTree = [],
    canManage = false,
}: DepartmentDocumentsProps) {
    const [tree, setTree] = useState<YearData[]>(initialTree);
    const [expandedYears, setExpandedYears] = useState<Set<number>>(new Set());
    const [expandedMonths, setExpandedMonths] = useState<Set<string>>(new Set());
    const [expandedCategories, setExpandedCategories] = useState<Set<string>>(new Set());
    const [isLoading, setIsLoading] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [isUploadDialogOpen, setIsUploadDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingDocument, setEditingDocument] = useState<DocumentData | null>(null);
    const [deleteDocument, setDeleteDocument] = useState<DocumentData | null>(null);
    const [previewDocument, setPreviewDocument] = useState<DocumentData | null>(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
    const [isCreateFolderDialogOpen, setIsCreateFolderDialogOpen] = useState(false);
    const [createFolderContext, setCreateFolderContext] = useState<{ year: number; month: number } | null>(null);
    const [newFolderName, setNewFolderName] = useState('');
    const [isCreatingFolder, setIsCreatingFolder] = useState(false);
    // Rename folder states
    const [isRenameFolderDialogOpen, setIsRenameFolderDialogOpen] = useState(false);
    const [renameFolderContext, setRenameFolderContext] = useState<CategoryData | null>(null);
    const [renameFolderName, setRenameFolderName] = useState('');
    const [isRenamingFolder, setIsRenamingFolder] = useState(false);
    // Delete folder states
    const [deleteFolderContext, setDeleteFolderContext] = useState<CategoryData | null>(null);
    const [isDeletingFolder, setIsDeletingFolder] = useState(false);
    const [uploadForm, setUploadForm] = useState({
        title: '',
        description: '',
        category: '',
    });
    const [editForm, setEditForm] = useState({
        title: '',
        description: '',
        category: '',
    });
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
    const [availableCategories, setAvailableCategories] = useState<Array<{ slug: string; name: string; is_system: boolean }>>([]);
    const [isLoadingCategories, setIsLoadingCategories] = useState(false);
    const [showNewCategoryInput, setShowNewCategoryInput] = useState(false);
    const [newCategoryName, setNewCategoryName] = useState('');

    // Debounce search term
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearchTerm(searchTerm);
        }, 300);
        return () => clearTimeout(timer);
    }, [searchTerm]);

    // Check if a document matches the search term
    const documentMatchesSearch = useCallback((doc: DocumentData, term: string): boolean => {
        if (!term) return true;
        const lowerTerm = term.toLowerCase();
        return (
            doc.title.toLowerCase().includes(lowerTerm) ||
            doc.original_name.toLowerCase().includes(lowerTerm) ||
            (doc.description?.toLowerCase().includes(lowerTerm) ?? false) ||
            (doc.category?.toLowerCase().includes(lowerTerm) ?? false)
        );
    }, []);

    // Filter tree based on search term - returns filtered tree and matching document count
    const filteredTree = useMemo(() => {
        if (!debouncedSearchTerm) return tree;

        return tree
            .map(yearData => {
                const filteredMonths = yearData.months
                    .map(monthData => {
                        const filteredCategories = monthData.categories
                            .map(category => ({
                                ...category,
                                documents: category.documents.filter((doc: DocumentData) =>
                                    documentMatchesSearch(doc, debouncedSearchTerm)
                                ),
                            }))
                            .filter(category => category.documents.length > 0);

                        const docCount = filteredCategories.reduce((acc, cat) => acc + cat.documents.length, 0);

                        return {
                            ...monthData,
                            categories: filteredCategories,
                            document_count: docCount,
                        };
                    })
                    .filter(monthData => monthData.categories.length > 0);

                return {
                    ...yearData,
                    months: filteredMonths,
                    document_count: filteredMonths.reduce((acc, m) => acc + m.document_count, 0),
                };
            })
            .filter(yearData => yearData.months.length > 0);
    }, [tree, debouncedSearchTerm, documentMatchesSearch]);

    // Auto-expand all folders when searching
    useEffect(() => {
        if (debouncedSearchTerm) {
            // Expand all years, months, and categories that have matching documents
            const yearsToExpand = new Set<number>();
            const monthsToExpand = new Set<string>();
            const categoriesToExpand = new Set<string>();

            filteredTree.forEach(yearData => {
                yearsToExpand.add(yearData.year);
                yearData.months.forEach(monthData => {
                    monthsToExpand.add(`${yearData.year}-${monthData.month}`);
                    monthData.categories.forEach(category => {
                        categoriesToExpand.add(`${yearData.year}-${monthData.month}-${category.key}`);
                    });
                });
            });

            setExpandedYears(yearsToExpand);
            setExpandedMonths(monthsToExpand);
            setExpandedCategories(categoriesToExpand);
        }
    }, [debouncedSearchTerm, filteredTree]);

    // Highlight matching text in document title/name
    const highlightText = useCallback((text: string, searchTerm: string): React.ReactNode => {
        if (!searchTerm) return text;

        const lowerText = text.toLowerCase();
        const lowerSearch = searchTerm.toLowerCase();
        const index = lowerText.indexOf(lowerSearch);

        if (index === -1) return text;

        return (
            <>
                {text.substring(0, index)}
                <mark className="bg-yellow-200 dark:bg-yellow-800 rounded px-0.5">
                    {text.substring(index, index + searchTerm.length)}
                </mark>
                {text.substring(index + searchTerm.length)}
            </>
        );
    }, []);

    // Load documents tree
    const loadDocuments = async () => {
        setIsLoading(true);
        try {
            const response = await axios.get(`/api/departments/${departmentUuid}/documents`);
            if (response.data.success) {
                setTree(response.data.data);
            }
        } catch (error) {
            toast.error('Erreur lors du chargement des documents');
        } finally {
            setIsLoading(false);
        }
    };

    // Load available categories for current month
    const loadAvailableCategories = async () => {
        setIsLoadingCategories(true);
        try {
            const response = await axios.get(`/api/departments/${departmentUuid}/document-categories`);
            if (response.data.success) {
                setAvailableCategories(response.data.data.map((cat: { slug: string; name: string; is_system: boolean }) => ({
                    slug: cat.slug,
                    name: cat.name,
                    is_system: cat.is_system,
                })));
            }
        } catch (error) {
            // Silently fail - user can still type a new category
            setAvailableCategories([]);
        } finally {
            setIsLoadingCategories(false);
        }
    };

    // Load categories when upload dialog opens
    useEffect(() => {
        if (isUploadDialogOpen) {
            loadAvailableCategories();
            setShowNewCategoryInput(false);
            setNewCategoryName('');
        }
    }, [isUploadDialogOpen]);

    // Toggle year expansion
    const toggleYear = (year: number) => {
        setExpandedYears(prev => {
            const newSet = new Set(prev);
            if (newSet.has(year)) {
                newSet.delete(year);
            } else {
                newSet.add(year);
            }
            return newSet;
        });
    };

    // Toggle month expansion
    const toggleMonth = (year: number, month: number) => {
        const key = `${year}-${month}`;
        setExpandedMonths(prev => {
            const newSet = new Set(prev);
            if (newSet.has(key)) {
                newSet.delete(key);
            } else {
                newSet.add(key);
            }
            return newSet;
        });
    };

    // Toggle category expansion
    const toggleCategory = (year: number, month: number, categoryKey: string) => {
        const key = `${year}-${month}-${categoryKey}`;
        setExpandedCategories(prev => {
            const newSet = new Set(prev);
            if (newSet.has(key)) {
                newSet.delete(key);
            } else {
                newSet.add(key);
            }
            return newSet;
        });
    };

    // Handle file drop
    const onDrop = useCallback((acceptedFiles: File[]) => {
        setSelectedFiles(prev => [...prev, ...acceptedFiles]);
    }, []);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: {
            'application/pdf': ['.pdf'],
            'application/msword': ['.doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx'],
            'application/vnd.ms-excel': ['.xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['.xlsx'],
            'application/vnd.ms-powerpoint': ['.ppt'],
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': ['.pptx'],
            'image/*': ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'],
            'text/plain': ['.txt'],
            'text/csv': ['.csv'],
            'application/zip': ['.zip'],
            'application/x-rar-compressed': ['.rar'],
        },
        maxSize: 52428800, // 50MB
    });

    // Remove file from selection
    const removeFile = (index: number) => {
        setSelectedFiles(prev => prev.filter((_, i) => i !== index));
    };

    // Upload files
    const handleUpload = async () => {
        if (selectedFiles.length === 0) {
            toast.error('Veuillez sélectionner au moins un fichier');
            return;
        }

        setIsUploading(true);
        let successCount = 0;
        let errorCount = 0;

        for (const file of selectedFiles) {
            const formData = new FormData();
            formData.append('file', file);
            if (uploadForm.title) formData.append('title', uploadForm.title);
            if (uploadForm.description) formData.append('description', uploadForm.description);
            if (uploadForm.category) formData.append('category', uploadForm.category);

            try {
                await axios.post(`/api/departments/${departmentUuid}/documents`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                successCount++;
            } catch (error) {
                errorCount++;
            }
        }

        setIsUploading(false);

        if (successCount > 0) {
            toast.success(`${successCount} document(s) téléchargé(s) avec succès`);
            setIsUploadDialogOpen(false);
            setSelectedFiles([]);
            setUploadForm({ title: '', description: '', category: '' });
            loadDocuments();
        }
        if (errorCount > 0) {
            toast.error(`${errorCount} document(s) n'ont pas pu être téléchargés`);
        }
    };

    // Edit document
    const handleEdit = (doc: DocumentData) => {
        setEditingDocument(doc);
        setEditForm({
            title: doc.title || '',
            description: doc.description || '',
            category: doc.category || '',
        });
        setIsEditDialogOpen(true);
    };

    // Save edit
    const handleSaveEdit = async () => {
        if (!editingDocument) return;

        try {
            await axios.patch(`/api/departments/${departmentUuid}/documents/${editingDocument.uuid}`, editForm);
            toast.success('Document mis à jour');
            setIsEditDialogOpen(false);
            setEditingDocument(null);
            loadDocuments();
        } catch (error) {
            toast.error('Erreur lors de la mise à jour');
        }
    };

    // Delete document
    const handleDelete = async () => {
        if (!deleteDocument) return;

        try {
            await axios.delete(`/api/departments/${departmentUuid}/documents/${deleteDocument.uuid}`);
            toast.success('Document supprimé');
            setDeleteDocument(null);
            loadDocuments();
        } catch (error) {
            toast.error('Erreur lors de la suppression');
        }
    };

    // Download document
    const handleDownload = (doc: DocumentData) => {
        window.open(`/api/departments/${departmentUuid}/documents/${doc.uuid}/download`, '_blank');
    };

    // Open create folder dialog
    const openCreateFolderDialog = (year: number, month: number) => {
        setCreateFolderContext({ year, month });
        setNewFolderName('');
        setIsCreateFolderDialogOpen(true);
    };

    // Create new folder
    const handleCreateFolder = async () => {
        if (!createFolderContext || !newFolderName.trim()) {
            toast.error('Veuillez entrer un nom de dossier');
            return;
        }

        setIsCreatingFolder(true);
        try {
            await axios.post(`/api/departments/${departmentUuid}/document-categories`, {
                name: newFolderName.trim(),
                year: createFolderContext.year,
                month: createFolderContext.month,
            });
            toast.success('Sous-dossier créé avec succès');
            setIsCreateFolderDialogOpen(false);
            setNewFolderName('');
            setCreateFolderContext(null);
            loadDocuments();
        } catch (error: any) {
            const message = error.response?.data?.message || 'Erreur lors de la création du sous-dossier';
            toast.error(message);
        } finally {
            setIsCreatingFolder(false);
        }
    };

    // Open rename folder dialog
    const openRenameFolderDialog = (category: CategoryData) => {
        setRenameFolderContext(category);
        setRenameFolderName(category.name);
        setIsRenameFolderDialogOpen(true);
    };

    // Rename folder
    const handleRenameFolder = async () => {
        if (!renameFolderContext?.uuid || !renameFolderName.trim()) {
            toast.error('Veuillez entrer un nom de dossier');
            return;
        }

        setIsRenamingFolder(true);
        try {
            await axios.patch(`/api/departments/${departmentUuid}/document-categories/${renameFolderContext.uuid}`, {
                name: renameFolderName.trim(),
            });
            toast.success('Sous-dossier renommé avec succès');
            setIsRenameFolderDialogOpen(false);
            setRenameFolderName('');
            setRenameFolderContext(null);
            loadDocuments();
        } catch (error: any) {
            const message = error.response?.data?.message || 'Erreur lors du renommage du sous-dossier';
            toast.error(message);
        } finally {
            setIsRenamingFolder(false);
        }
    };

    // Delete folder
    const handleDeleteFolder = async () => {
        if (!deleteFolderContext?.uuid) return;

        setIsDeletingFolder(true);
        try {
            await axios.delete(`/api/departments/${departmentUuid}/document-categories/${deleteFolderContext.uuid}`);
            toast.success('Sous-dossier supprimé avec succès');
            setDeleteFolderContext(null);
            loadDocuments();
        } catch (error: any) {
            const message = error.response?.data?.message || 'Erreur lors de la suppression du sous-dossier';
            toast.error(message);
        } finally {
            setIsDeletingFolder(false);
        }
    };

    // Get file icon component
    const getFileIcon = (fileType: string) => {
        const IconComponent = fileTypeIcons[fileType] || fileTypeIcons.other;
        const colorClass = fileTypeColors[fileType] || fileTypeColors.other;
        return <IconComponent className={`h-5 w-5 ${colorClass}`} />;
    };

    // Calculate total documents
    const totalDocuments = tree.reduce((acc, year) => acc + year.document_count, 0);
    const filteredDocumentCount = filteredTree.reduce((acc, year) => acc + year.document_count, 0);
    const isSearching = debouncedSearchTerm.length > 0;

    return (
        <Card className="border-primary/20">
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle className="flex items-center gap-2">
                        <FolderIcon className="h-5 w-5" />
                        Documents du Département
                    </CardTitle>
                    <p className="text-sm text-muted-foreground mt-1">
                        {isSearching ? (
                            <>
                                {filteredDocumentCount} résultat{filteredDocumentCount > 1 ? 's' : ''} sur {totalDocuments} document{totalDocuments > 1 ? 's' : ''}
                            </>
                        ) : (
                            <>
                                {totalDocuments} document{totalDocuments > 1 ? 's' : ''} classé{totalDocuments > 1 ? 's' : ''} par année et mois
                            </>
                        )}
                    </p>
                </div>
                {canManage && (
                    <Button onClick={() => setIsUploadDialogOpen(true)}>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Ajouter un document
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                {/* Search */}
                <div className="mb-4">
                    <div className="relative">
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Rechercher un document..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="pl-9 pr-9"
                        />
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={() => setSearchTerm('')}
                                className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground hover:text-foreground transition-colors"
                                title="Effacer la recherche"
                                aria-label="Effacer la recherche"
                            >
                                <XMarkIcon className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                </div>

                {/* Document Tree */}
                {isLoading ? (
                    <div className="text-center py-8 text-muted-foreground">
                        Chargement...
                    </div>
                ) : tree.length === 0 ? (
                    <div className="text-center py-8">
                        <FolderIcon className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                        <p className="text-muted-foreground">Aucun document dans ce département</p>
                        {canManage && (
                            <Button
                                variant="outline"
                                className="mt-4"
                                onClick={() => setIsUploadDialogOpen(true)}
                            >
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Ajouter le premier document
                            </Button>
                        )}
                    </div>
                ) : isSearching && filteredTree.length === 0 ? (
                    <div className="text-center py-8">
                        <MagnifyingGlassIcon className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                        <p className="text-muted-foreground">
                            Aucun document trouvé pour "{debouncedSearchTerm}"
                        </p>
                        <Button
                            variant="outline"
                            className="mt-4"
                            onClick={() => setSearchTerm('')}
                        >
                            <XMarkIcon className="h-4 w-4 mr-2" />
                            Effacer la recherche
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {filteredTree.map((yearData) => (
                            <div key={yearData.year} className="border rounded-lg">
                                {/* Year folder */}
                                <button
                                    onClick={() => toggleYear(yearData.year)}
                                    className="w-full flex items-center gap-2 p-3 hover:bg-muted/50 transition-colors"
                                >
                                    {expandedYears.has(yearData.year) ? (
                                        <ChevronDownIcon className="h-4 w-4" />
                                    ) : (
                                        <ChevronRightIcon className="h-4 w-4" />
                                    )}
                                    {expandedYears.has(yearData.year) ? (
                                        <FolderOpenIcon className="h-5 w-5 text-yellow-500" />
                                    ) : (
                                        <FolderIcon className="h-5 w-5 text-yellow-500" />
                                    )}
                                    <span className="font-medium">{yearData.year}</span>
                                    <span className="text-sm text-muted-foreground">
                                        ({yearData.document_count} document{yearData.document_count > 1 ? 's' : ''})
                                    </span>
                                </button>

                                {/* Months */}
                                {expandedYears.has(yearData.year) && (
                                    <div className="pl-6 pb-2">
                                        {yearData.months.map((monthData) => {
                                            const monthKey = `${yearData.year}-${monthData.month}`;

                                            return (
                                                <div key={monthKey} className="border-l pl-4 mt-2">
                                                    {/* Month folder */}
                                                    <div className="flex items-center justify-between group/month">
                                                        <button
                                                            onClick={() => toggleMonth(yearData.year, monthData.month)}
                                                            className="flex-1 flex items-center gap-2 p-2 hover:bg-muted/50 transition-colors rounded"
                                                        >
                                                            {expandedMonths.has(monthKey) ? (
                                                                <ChevronDownIcon className="h-4 w-4" />
                                                            ) : (
                                                                <ChevronRightIcon className="h-4 w-4" />
                                                            )}
                                                            {expandedMonths.has(monthKey) ? (
                                                                <FolderOpenIcon className="h-4 w-4 text-blue-500" />
                                                            ) : (
                                                                <FolderIcon className="h-4 w-4 text-blue-500" />
                                                            )}
                                                            <span>{monthData.month_name}</span>
                                                            <span className="text-sm text-muted-foreground">
                                                                ({monthData.document_count} document{monthData.document_count > 1 ? 's' : ''})
                                                            </span>
                                                        </button>
                                                        {canManage && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => openCreateFolderDialog(yearData.year, monthData.month)}
                                                                className="opacity-0 group-hover/month:opacity-100 transition-opacity mr-2"
                                                                title="Créer un sous-dossier"
                                                            >
                                                                <PlusIcon className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>

                                                    {/* Categories and uncategorized documents */}
                                                    {expandedMonths.has(monthKey) && (
                                                        <div className="pl-6 space-y-1 mt-1">
                                                            {/* First, show uncategorized documents directly (no subfolder) */}
                                                            {monthData.categories
                                                                .filter(cat => cat.key === '_uncategorized')
                                                                .map(category => category.documents.map((doc) => (
                                                                    <div
                                                                        key={doc.uuid}
                                                                        className="flex items-center justify-between p-2 hover:bg-muted/50 rounded group border-l pl-4"
                                                                    >
                                                                        <div className="flex items-center gap-2 flex-1 min-w-0">
                                                                            {getFileIcon(doc.file_type)}
                                                                            <div className="flex-1 min-w-0">
                                                                                <p className="text-sm font-medium truncate">
                                                                                    {highlightText(doc.title, debouncedSearchTerm)}
                                                                                </p>
                                                                                <p className="text-xs text-muted-foreground truncate">
                                                                                    {highlightText(doc.original_name, debouncedSearchTerm)} • {doc.formatted_file_size}
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                        <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                            {doc.can_preview && (
                                                                                <Button
                                                                                    variant="ghost"
                                                                                    size="sm"
                                                                                    onClick={() => setPreviewDocument(doc)}
                                                                                    title="Prévisualiser"
                                                                                >
                                                                                    <EyeIcon className="h-4 w-4" />
                                                                                </Button>
                                                                            )}
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => handleDownload(doc)}
                                                                                title="Télécharger"
                                                                            >
                                                                                <ArrowDownTrayIcon className="h-4 w-4" />
                                                                            </Button>
                                                                            {canManage && (
                                                                                <>
                                                                                    <Button
                                                                                        variant="ghost"
                                                                                        size="sm"
                                                                                        onClick={() => handleEdit(doc)}
                                                                                        title="Modifier"
                                                                                    >
                                                                                        <PencilIcon className="h-4 w-4" />
                                                                                    </Button>
                                                                                    <Button
                                                                                        variant="ghost"
                                                                                        size="sm"
                                                                                        onClick={() => setDeleteDocument(doc)}
                                                                                        title="Supprimer"
                                                                                        className="text-destructive hover:text-destructive"
                                                                                    >
                                                                                        <TrashIcon className="h-4 w-4" />
                                                                                    </Button>
                                                                                </>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                )))}

                                                            {/* Then, show categorized documents in subfolders */}
                                                            {monthData.categories
                                                                .filter(cat => cat.key !== '_uncategorized')
                                                                .map((category) => {
                                                                const categoryKey = `${yearData.year}-${monthData.month}-${category.key}`;

                                                                return (
                                                                    <div key={categoryKey} className="border-l pl-4">
                                                                        {/* Category folder with context menu */}
                                                                        <ContextMenu>
                                                                            <ContextMenuTrigger asChild>
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={() => toggleCategory(yearData.year, monthData.month, category.key)}
                                                                                    className="w-full flex items-center gap-2 p-2 hover:bg-muted/50 transition-colors rounded"
                                                                                >
                                                                                    {expandedCategories.has(categoryKey) ? (
                                                                                        <ChevronDownIcon className="h-4 w-4" />
                                                                                    ) : (
                                                                                        <ChevronRightIcon className="h-4 w-4" />
                                                                                    )}
                                                                                    {expandedCategories.has(categoryKey) ? (
                                                                                        <FolderOpenIcon className={`h-4 w-4 ${category.key === 'rapports' ? 'text-indigo-500' : 'text-green-500'}`} />
                                                                                    ) : (
                                                                                        <FolderIcon className={`h-4 w-4 ${category.key === 'rapports' ? 'text-indigo-500' : 'text-green-500'}`} />
                                                                                    )}
                                                                                    <span>{category.name}</span>
                                                                                    <span className="text-sm text-muted-foreground">
                                                                                        ({category.documents.length} document{category.documents.length > 1 ? 's' : ''})
                                                                                    </span>
                                                                                </button>
                                                                            </ContextMenuTrigger>
                                                                            <ContextMenuContent>
                                                                                {canManage && !category.is_system && category.uuid && (
                                                                                    <>
                                                                                        <ContextMenuItem
                                                                                            onClick={() => openRenameFolderDialog(category)}
                                                                                            className="cursor-pointer"
                                                                                        >
                                                                                            <PencilIcon className="h-4 w-4 mr-2" />
                                                                                            Renommer
                                                                                        </ContextMenuItem>
                                                                                        <ContextMenuSeparator />
                                                                                        <ContextMenuItem
                                                                                            onClick={() => setDeleteFolderContext(category)}
                                                                                            className="cursor-pointer text-destructive focus:text-destructive"
                                                                                            disabled={category.documents.length > 0}
                                                                                        >
                                                                                            <TrashIcon className="h-4 w-4 mr-2" />
                                                                                            Supprimer
                                                                                        </ContextMenuItem>
                                                                                    </>
                                                                                )}
                                                                                {(category.is_system || !canManage) && (
                                                                                    <ContextMenuItem disabled className="text-muted-foreground">
                                                                                        {category.is_system ? 'Dossier système (non modifiable)' : 'Aucune action disponible'}
                                                                                    </ContextMenuItem>
                                                                                )}
                                                                            </ContextMenuContent>
                                                                        </ContextMenu>

                                                                        {/* Documents */}
                                                                        {expandedCategories.has(categoryKey) && (
                                                                            <div className="pl-6 space-y-1 mt-1">
                                                                                {category.documents.map((doc) => (
                                                                                    <div
                                                                                        key={doc.uuid}
                                                                                        className="flex items-center justify-between p-2 hover:bg-muted/50 rounded group"
                                                                                    >
                                                                                        <div className="flex items-center gap-2 flex-1 min-w-0">
                                                                                            {getFileIcon(doc.file_type)}
                                                                                            <div className="flex-1 min-w-0">
                                                                                                <p className="text-sm font-medium truncate">
                                                                                                    {highlightText(doc.title, debouncedSearchTerm)}
                                                                                                </p>
                                                                                                <p className="text-xs text-muted-foreground truncate">
                                                                                                    {highlightText(doc.original_name, debouncedSearchTerm)} • {doc.formatted_file_size}
                                                                                                </p>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                                            {doc.can_preview && (
                                                                                                <Button
                                                                                                    variant="ghost"
                                                                                                    size="sm"
                                                                                                    onClick={() => setPreviewDocument(doc)}
                                                                                                    title="Prévisualiser"
                                                                                                >
                                                                                                    <EyeIcon className="h-4 w-4" />
                                                                                                </Button>
                                                                                            )}
                                                                                            <Button
                                                                                                variant="ghost"
                                                                                                size="sm"
                                                                                                onClick={() => handleDownload(doc)}
                                                                                                title="Télécharger"
                                                                                            >
                                                                                                <ArrowDownTrayIcon className="h-4 w-4" />
                                                                                            </Button>
                                                                                            {canManage && (
                                                                                                <>
                                                                                                    <Button
                                                                                                        variant="ghost"
                                                                                                        size="sm"
                                                                                                        onClick={() => handleEdit(doc)}
                                                                                                        title="Modifier"
                                                                                                    >
                                                                                                        <PencilIcon className="h-4 w-4" />
                                                                                                    </Button>
                                                                                                    <Button
                                                                                                        variant="ghost"
                                                                                                        size="sm"
                                                                                                        onClick={() => setDeleteDocument(doc)}
                                                                                                        title="Supprimer"
                                                                                                        className="text-destructive hover:text-destructive"
                                                                                                    >
                                                                                                        <TrashIcon className="h-4 w-4" />
                                                                                                    </Button>
                                                                                                </>
                                                                                            )}
                                                                                        </div>
                                                                                    </div>
                                                                                ))}
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>

            {/* Upload Dialog */}
            <Dialog open={isUploadDialogOpen} onOpenChange={setIsUploadDialogOpen}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Ajouter des documents</DialogTitle>
                        <DialogDescription>
                            Déposez vos fichiers ou cliquez pour sélectionner.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4 px-3">
                        {/* Dropzone */}
                        <div
                            {...getRootProps()}
                            className={`border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors ${
                                isDragActive ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-primary'
                            }`}
                        >
                            <input {...getInputProps()} />
                            <CloudArrowUpIcon className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                            {isDragActive ? (
                                <p>Déposez les fichiers ici...</p>
                            ) : (
                                <div>
                                    <p>Glissez-déposez des fichiers ici</p>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        ou cliquez pour sélectionner
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Selected files */}
                        {selectedFiles.length > 0 && (
                            <div className="space-y-2">
                                <Label>Fichiers sélectionnés ({selectedFiles.length})</Label>
                                <div className="max-h-40 overflow-y-auto space-y-1">
                                    {selectedFiles.map((file, index) => (
                                        <div key={index} className="flex items-center justify-between p-2 bg-muted rounded">
                                            <span className="text-sm truncate flex-1">{file.name}</span>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => removeFile(index)}
                                            >
                                                <XMarkIcon className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Optional metadata */}
                        <div className="space-y-3">
                            <div>
                                <Label htmlFor="upload-title">Titre (optionnel)</Label>
                                <Input
                                    id="upload-title"
                                    placeholder="Titre du document"
                                    value={uploadForm.title}
                                    onChange={(e) => setUploadForm({ ...uploadForm, title: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label htmlFor="upload-description">Description (optionnel)</Label>
                                <Textarea
                                    id="upload-description"
                                    placeholder="Description du document"
                                    value={uploadForm.description}
                                    onChange={(e) => setUploadForm({ ...uploadForm, description: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label htmlFor="upload-category">Sous-dossier</Label>
                                <p className="text-xs text-muted-foreground mb-2">
                                    Si non précisé, le document sera placé dans le dossier du mois en cours.
                                </p>
                                {isLoadingCategories ? (
                                    <div className="text-sm text-muted-foreground py-2">Chargement des sous-dossiers...</div>
                                ) : showNewCategoryInput ? (
                                    <div className="space-y-2">
                                        <div className="flex gap-2">
                                            <Input
                                                id="upload-new-category"
                                                placeholder="Nom du nouveau sous-dossier"
                                                value={newCategoryName}
                                                onChange={(e) => {
                                                    setNewCategoryName(e.target.value);
                                                    setUploadForm({ ...uploadForm, category: e.target.value });
                                                }}
                                                autoFocus
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    setShowNewCategoryInput(false);
                                                    setNewCategoryName('');
                                                    setUploadForm({ ...uploadForm, category: '' });
                                                }}
                                            >
                                                <XMarkIcon className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        <select
                                            id="upload-category"
                                            aria-label="Sélectionner un sous-dossier"
                                            value={uploadForm.category}
                                            onChange={(e) => setUploadForm({ ...uploadForm, category: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                                        >
                                            <option value="">-- Dossier du mois (par défaut) --</option>
                                            {availableCategories.map((cat) => (
                                                <option key={cat.slug} value={cat.slug}>
                                                    {cat.name} {cat.is_system ? '(système)' : ''}
                                                </option>
                                            ))}
                                        </select>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-primary"
                                            onClick={() => setShowNewCategoryInput(true)}
                                        >
                                            <PlusIcon className="h-4 w-4 mr-1" />
                                            Créer un nouveau sous-dossier
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsUploadDialogOpen(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleUpload} disabled={isUploading || selectedFiles.length === 0}>
                            {isUploading ? 'Téléchargement...' : 'Télécharger'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Edit Dialog */}
            <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                <DialogContent className="sm:max-w-[400px]">
                    <DialogHeader>
                        <DialogTitle>Modifier le document</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-4 px-3">
                        <div>
                            <Label htmlFor="edit-title">Titre</Label>
                            <Input
                                id="edit-title"
                                value={editForm.title}
                                onChange={(e) => setEditForm({ ...editForm, title: e.target.value })}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-description">Description</Label>
                            <Textarea
                                id="edit-description"
                                value={editForm.description}
                                onChange={(e) => setEditForm({ ...editForm, description: e.target.value })}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-category">Catégorie</Label>
                            <Input
                                id="edit-category"
                                value={editForm.category}
                                onChange={(e) => setEditForm({ ...editForm, category: e.target.value })}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsEditDialogOpen(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleSaveEdit}>
                            Enregistrer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation */}
            <DeleteConfirmationDialog
                open={!!deleteDocument}
                onOpenChange={(open) => !open && setDeleteDocument(null)}
                onConfirm={handleDelete}
                title="Supprimer le document"
                description={`Êtes-vous sûr de vouloir supprimer "${deleteDocument?.title || deleteDocument?.original_name}" ? Cette action est irréversible.`}
            />

            {/* Document Preview Dialog */}
            <DocumentPreviewDialog
                document={previewDocument}
                open={!!previewDocument}
                onOpenChange={(open) => !open && setPreviewDocument(null)}
                departmentUuid={departmentUuid}
            />

            {/* Create Folder Dialog */}
            <Dialog open={isCreateFolderDialogOpen} onOpenChange={setIsCreateFolderDialogOpen}>
                <DialogContent className="sm:max-w-[400px]">
                    <DialogHeader>
                        <DialogTitle>Créer un sous-dossier</DialogTitle>
                        <DialogDescription>
                            {createFolderContext && (
                                <>Créer un nouveau sous-dossier pour {
                                    ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                                     'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
                                    ][createFolderContext.month - 1]
                                } {createFolderContext.year}</>
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4 px-3">
                        <div>
                            <Label htmlFor="folder-name">Nom du sous-dossier</Label>
                            <Input
                                id="folder-name"
                                placeholder="Ex: Procès-verbaux, Factures, Contrats..."
                                value={newFolderName}
                                onChange={(e) => setNewFolderName(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && newFolderName.trim()) {
                                        handleCreateFolder();
                                    }
                                }}
                                autoFocus
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsCreateFolderDialogOpen(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleCreateFolder} disabled={isCreatingFolder || !newFolderName.trim()}>
                            {isCreatingFolder ? 'Création...' : 'Créer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Rename Folder Dialog */}
            <Dialog open={isRenameFolderDialogOpen} onOpenChange={setIsRenameFolderDialogOpen}>
                <DialogContent className="sm:max-w-[400px]">
                    <DialogHeader>
                        <DialogTitle>Renommer le sous-dossier</DialogTitle>
                        <DialogDescription>
                            Entrez un nouveau nom pour ce sous-dossier.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4 px-3">
                        <div>
                            <Label htmlFor="rename-folder-name">Nouveau nom</Label>
                            <Input
                                id="rename-folder-name"
                                placeholder="Nouveau nom du sous-dossier"
                                value={renameFolderName}
                                onChange={(e) => setRenameFolderName(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && renameFolderName.trim()) {
                                        handleRenameFolder();
                                    }
                                }}
                                autoFocus
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsRenameFolderDialogOpen(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleRenameFolder} disabled={isRenamingFolder || !renameFolderName.trim()}>
                            {isRenamingFolder ? 'Renommage...' : 'Renommer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Folder Confirmation */}
            <DeleteConfirmationDialog
                open={!!deleteFolderContext}
                onOpenChange={(open) => !open && setDeleteFolderContext(null)}
                onConfirm={handleDeleteFolder}
                title="Supprimer le sous-dossier"
                description={`Êtes-vous sûr de vouloir supprimer le sous-dossier "${deleteFolderContext?.name}" ? Cette action est irréversible.`}
                confirmText={isDeletingFolder ? 'Suppression...' : 'Supprimer'}
            />
        </Card>
    );
}
