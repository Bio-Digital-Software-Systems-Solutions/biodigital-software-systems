import React from 'react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { FileText, PlayCircle, File, Presentation, Pencil, Trash2, Download } from 'lucide-react';

interface Material {
  id: number;
  uuid: string;
  title: string;
  type: string;
  file_url?: string | null;
  url?: string | null;
  duration?: string | null;
  description?: string | null;
  order: number;
  is_active: boolean;
  uploaded_by?: {
    id: number;
    first_name: string;
    last_name: string;
  };
}

interface MaterialCardProps {
  material: Material;
  onEdit?: (material: Material) => void;
  onDelete?: (material: Material) => void;
  onDownload?: (material: Material) => void;
  isTeacher?: boolean;
}

export default function MaterialCard({ material, onEdit, onDelete, onDownload, isTeacher = false }: MaterialCardProps) {
  const getTypeIcon = (type: string) => {
    switch (type.toLowerCase()) {
      case 'pdf':
      case 'document':
        return <FileText className="w-5 h-5" />;
      case 'video':
      case 'audio':
        return <PlayCircle className="w-5 h-5" />;
      case 'powerpoint':
        return <Presentation className="w-5 h-5" />;
      default:
        return <File className="w-5 h-5" />;
    }
  };

  const getTypeColor = (type: string) => {
    switch (type.toLowerCase()) {
      case 'pdf':
        return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300';
      case 'video':
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300';
      case 'audio':
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300';
      case 'powerpoint':
        return 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300';
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
  };

  const handleOpen = () => {
    if (onDownload) {
      onDownload(material);
    }
  };

  return (
    <Card className="hover:shadow-md transition-shadow">
      <CardContent className="p-4">
        <div className="flex items-start justify-between gap-4">
          <div className="flex items-start gap-3 flex-1">
            <div
              className={`p-2 rounded-lg ${getTypeColor(material.type)} cursor-pointer hover:opacity-80 transition-opacity`}
              onClick={handleOpen}
              role="button"
              tabIndex={0}
              onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault();
                  handleOpen();
                }
              }}
            >
              {getTypeIcon(material.type)}
            </div>
            <div className="flex-1 min-w-0">
              <h4
                className="font-medium text-gray-900 dark:text-gray-100 truncate cursor-pointer hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                onClick={handleOpen}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handleOpen();
                  }
                }}
              >
                {material.title}
              </h4>
              <div className="flex items-center gap-2 mt-1 flex-wrap">
                <Badge variant="outline" className="text-xs">
                  {material.type.toUpperCase()}
                </Badge>
                {material.duration && (
                  <span className="text-xs text-gray-600 dark:text-gray-400">
                    {material.duration}
                  </span>
                )}
                {!material.is_active && isTeacher && (
                  <Badge variant="outline" className="text-xs border-yellow-500 text-yellow-700 dark:text-yellow-400">
                    Inactif
                  </Badge>
                )}
              </div>
              {material.description && (
                <p className="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                  {material.description}
                </p>
              )}
              {isTeacher && material.uploaded_by && (
                <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">
                  Ajouté par {material.uploaded_by.first_name} {material.uploaded_by.last_name}
                </p>
              )}
            </div>
          </div>

          <div className="flex items-center gap-2">
            {onDownload && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => onDownload(material)}
                className="shrink-0"
              >
                <Download className="w-4 h-4 mr-1" />
                {material.type === 'video' || material.type === 'audio' ? 'Lire' : 'Ouvrir'}
              </Button>
            )}
            {isTeacher && onEdit && (
              <Button
                size="sm"
                variant="ghost"
                onClick={() => onEdit(material)}
                className="shrink-0"
              >
                <Pencil className="w-4 h-4" />
              </Button>
            )}
            {isTeacher && onDelete && (
              <Button
                size="sm"
                variant="ghost"
                onClick={() => onDelete(material)}
                className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 shrink-0"
              >
                <Trash2 className="w-4 h-4" />
              </Button>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
