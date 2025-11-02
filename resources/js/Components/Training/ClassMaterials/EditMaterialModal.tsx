import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { toast } from 'sonner';
import { Upload } from 'lucide-react';

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
}

interface EditMaterialModalProps {
  isOpen: boolean;
  onClose: () => void;
  material: Material;
  classUuid: string;
}

export default function EditMaterialModal({ isOpen, onClose, material, classUuid }: EditMaterialModalProps) {
  const [uploadMode, setUploadMode] = useState<'file' | 'url' | 'keep'>('keep');
  const [processing, setProcessing] = useState(false);

  const [data, setDataState] = useState({
    title: material.title,
    type: material.type,
    file: null as File | null,
    url: material.url || '',
    duration: material.duration || '',
    description: material.description || '',
    is_active: material.is_active,
  });

  const setData = (key: string, value: any) => {
    setDataState(prev => ({ ...prev, [key]: value }));
  };

  const reset = () => {
    setDataState({
      title: material.title,
      type: material.type,
      file: null,
      url: material.url || '',
      duration: material.duration || '',
      description: material.description || '',
      is_active: material.is_active,
    });
  };

  useEffect(() => {
    setDataState({
      title: material.title,
      type: material.type,
      file: null,
      url: material.url || '',
      duration: material.duration || '',
      description: material.description || '',
      is_active: material.is_active,
    });
    setUploadMode(material.file_url ? 'keep' : 'url');
  }, [material]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('title', data.title);
    formData.append('type', data.type);

    if (uploadMode === 'file' && data.file) {
      formData.append('file', data.file);
    } else if (uploadMode === 'url' && data.url) {
      formData.append('url', data.url);
    }

    if (data.duration) formData.append('duration', data.duration);
    if (data.description) formData.append('description', data.description);
    formData.append('is_active', data.is_active ? '1' : '0');

    setProcessing(true);

    router.post(route('training-classes.materials.update', [classUuid, material.uuid]), formData as any, {
      forceFormData: true,
      onSuccess: () => {
        toast.success('Support de cours mis à jour avec succès');
        onClose();
        setProcessing(false);
      },
      onError: () => {
        toast.error('Erreur lors de la mise à jour du support de cours');
        setProcessing(false);
      },
    });
  };

  const handleClose = () => {
    reset();
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Modifier le support de cours</DialogTitle>
          <DialogDescription>
            Modifiez les informations du support de cours.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="title">Titre *</Label>
            <Input
              id="title"
              value={data.title}
              onChange={(e) => setData('title', e.target.value)}
              placeholder="Ex: Guide de référence"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="type">Type *</Label>
            <Select value={data.type} onValueChange={(value) => setData('type', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Sélectionner un type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="pdf">PDF</SelectItem>
                <SelectItem value="video">Vidéo</SelectItem>
                <SelectItem value="audio">Audio</SelectItem>
                <SelectItem value="powerpoint">PowerPoint</SelectItem>
                <SelectItem value="document">Document</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label>Source</Label>
            <div className="flex gap-2 mb-2">
              <Button
                type="button"
                variant={uploadMode === 'keep' ? 'default' : 'outline'}
                onClick={() => setUploadMode('keep')}
                className="flex-1"
                disabled={!material.file_url && !material.url}
              >
                Garder actuel
              </Button>
              <Button
                type="button"
                variant={uploadMode === 'file' ? 'default' : 'outline'}
                onClick={() => setUploadMode('file')}
                className="flex-1"
              >
                Nouveau fichier
              </Button>
              <Button
                type="button"
                variant={uploadMode === 'url' ? 'default' : 'outline'}
                onClick={() => setUploadMode('url')}
                className="flex-1"
              >
                URL externe
              </Button>
            </div>

            {uploadMode === 'keep' ? (
              <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Fichier actuel: {material.file_url ? 'Fichier uploadé' : material.url}
                </p>
              </div>
            ) : uploadMode === 'file' ? (
              <div className="border-2 border-dashed rounded-lg p-6 text-center">
                <Upload className="mx-auto h-12 w-12 text-gray-400" />
                <div className="mt-4">
                  <Label
                    htmlFor="file-upload"
                    className="cursor-pointer inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90"
                  >
                    Choisir un fichier
                  </Label>
                  <Input
                    id="file-upload"
                    type="file"
                    className="sr-only"
                    accept=".pdf,.mp4,.mp3,.wav,.ppt,.pptx,.doc,.docx"
                    onChange={(e) => setData('file', e.target.files?.[0] || null)}
                  />
                  {data.file && (
                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                      {data.file.name} ({(data.file.size / 1024 / 1024).toFixed(2)} MB)
                    </p>
                  )}
                </div>
                <p className="mt-2 text-xs text-gray-500">
                  PDF, MP4, MP3, PPT, PPTX, DOC, DOCX jusqu'à 100MB
                </p>
              </div>
            ) : (
              <Input
                id="url"
                type="url"
                value={data.url}
                onChange={(e) => setData('url', e.target.value)}
                placeholder="https://example.com/video.mp4"
              />
            )}
          </div>

          {(data.type === 'video' || data.type === 'audio') && (
            <div className="space-y-2">
              <Label htmlFor="duration">Durée</Label>
              <Input
                id="duration"
                value={data.duration}
                onChange={(e) => setData('duration', e.target.value)}
                placeholder="Ex: 15 min, 1h30"
              />
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={data.description}
              onChange={(e) => setData('description', e.target.value)}
              placeholder="Description du support de cours..."
              rows={3}
            />
          </div>

          <div className="flex items-center space-x-2">
            <input
              type="checkbox"
              id="is_active"
              checked={data.is_active}
              onChange={(e) => setData('is_active', e.target.checked)}
              className="rounded border-gray-300"
            />
            <Label htmlFor="is_active" className="cursor-pointer">
              Visible pour les étudiants
            </Label>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={handleClose}>
              Annuler
            </Button>
            <Button type="submit" disabled={processing}>
              {processing ? 'Mise à jour...' : 'Mettre à jour'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
