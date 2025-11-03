import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { toast } from 'sonner';
import { Upload } from 'lucide-react';

interface AddMaterialModalProps {
  isOpen: boolean;
  onClose: () => void;
  classUuid: string;
}

export default function AddMaterialModal({ isOpen, onClose, classUuid }: AddMaterialModalProps) {
  const [uploadMode, setUploadMode] = useState<'file' | 'url'>('file');
  const [processing, setProcessing] = useState(false);

  const [data, setDataState] = useState({
    title: '',
    type: 'pdf',
    file: null as File | null,
    url: '',
    duration: '',
    description: '',
    is_active: true,
  });

  const setData = (key: string, value: any) => {
    setDataState(prev => ({ ...prev, [key]: value }));
  };

  const reset = () => {
    setDataState({
      title: '',
      type: 'pdf',
      file: null,
      url: '',
      duration: '',
      description: '',
      is_active: true,
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const formData = new FormData();
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

    router.post(route('training-classes.materials.store', classUuid), formData as any, {
      forceFormData: true,
      onSuccess: () => {
        toast.success('Support de cours ajouté avec succès');
        reset();
        onClose();
        setProcessing(false);
      },
      onError: () => {
        toast.error('Erreur lors de l\'ajout du support de cours');
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
        <DialogHeader className="space-y-3">
          <DialogTitle>Ajouter un support de cours</DialogTitle>
          <DialogDescription>
            Ajoutez un document, vidéo, audio ou présentation pour vos étudiants.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-6 pt-2">
          <div className="space-y-3">
            <Label htmlFor="title">Titre *</Label>
            <Input
              id="title"
              value={data.title}
              onChange={(e) => setData('title', e.target.value)}
              placeholder="Ex: Guide de référence"
              required
            />
          </div>

          <div className="space-y-3">
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

          <div className="space-y-3">
            <Label>Source *</Label>
            <div className="flex gap-2">
              <Button
                type="button"
                variant={uploadMode === 'file' ? 'default' : 'outline'}
                onClick={() => setUploadMode('file')}
                className="flex-1"
              >
                Uploader un fichier
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

            {uploadMode === 'file' ? (
              <div className="border-2 border-dashed rounded-lg p-8 text-center mt-3">
                <Upload className="mx-auto h-12 w-12 text-gray-400 mb-4" />
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
                  <p className="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    {data.file.name} ({(data.file.size / 1024 / 1024).toFixed(2)} MB)
                  </p>
                )}
                <p className="mt-3 text-xs text-gray-500">
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
                className="mt-3"
              />
            )}
          </div>

          {(data.type === 'video' || data.type === 'audio') && (
            <div className="space-y-3">
              <Label htmlFor="duration">Durée</Label>
              <Input
                id="duration"
                value={data.duration}
                onChange={(e) => setData('duration', e.target.value)}
                placeholder="Ex: 15 min, 1h30"
              />
            </div>
          )}

          <div className="space-y-3">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={data.description}
              onChange={(e) => setData('description', e.target.value)}
              placeholder="Description du support de cours..."
              rows={3}
            />
          </div>

          <div className="flex items-center space-x-3 pt-2">
            <input
              type="checkbox"
              id="is_active"
              checked={data.is_active}
              onChange={(e) => setData('is_active', e.target.checked)}
              className="rounded border-gray-300 h-4 w-4"
            />
            <Label htmlFor="is_active" className="cursor-pointer">
              Visible pour les étudiants
            </Label>
          </div>

          <DialogFooter className="pt-4">
            <Button type="button" variant="outline" onClick={handleClose}>
              Annuler
            </Button>
            <Button type="submit" disabled={processing}>
              {processing ? 'Ajout en cours...' : 'Ajouter'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
