import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Plus, GripVertical } from 'lucide-react';
import MaterialCard from './MaterialCard';
import AddMaterialModal from './AddMaterialModal';
import EditMaterialModal from './EditMaterialModal';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';
import axios from 'axios';

interface Material {
  id: number;
  uuid: string;
  training_material_id?: number;
  training_material_uuid?: string;
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

interface TrainingClass {
  id: number;
  uuid: string;
  name: string;
  training?: {
    id: number;
    title: string;
  };
}

interface ClassMaterialsListProps {
  trainingClass: TrainingClass;
}

export default function ClassMaterialsList({ trainingClass }: ClassMaterialsListProps) {
  const [materials, setMaterials] = useState<Material[]>([]);
  const [loading, setLoading] = useState(true);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [selectedMaterial, setSelectedMaterial] = useState<Material | null>(null);
  const [materialToDelete, setMaterialToDelete] = useState<Material | null>(null);

  useEffect(() => {
    fetchMaterials();
  }, [trainingClass.uuid]);

  const fetchMaterials = async () => {
    try {
      setLoading(true);
      const response = await axios.get(
        route('training-classes.materials.index', trainingClass.uuid)
      );
      setMaterials(response.data.materials);
    } catch (error) {
      console.error('Error fetching materials:', error);
      toast.error('Erreur lors du chargement des supports de cours');
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (material: Material) => {
    setSelectedMaterial(material);
    setIsEditModalOpen(true);
  };

  const handleDelete = (material: Material) => {
    setMaterialToDelete(material);
  };

  const confirmDelete = () => {
    if (!materialToDelete) return;

    router.delete(
      route('training-classes.materials.destroy', [trainingClass.uuid, materialToDelete.uuid]),
      {
        onSuccess: () => {
          toast.success('Support de cours supprimé avec succès');
          fetchMaterials();
          setMaterialToDelete(null);
        },
        onError: (errors) => {
          toast.error('Erreur lors de la suppression');
          console.error(errors);
        },
      }
    );
  };

  const handleDownload = (material: Material) => {
    window.open(route('training-class-materials.download', material.uuid), '_blank');
  };

  const handleToggleActive = (material: Material) => {
    router.patch(
      route('training-classes.materials.toggle-active', [trainingClass.uuid, material.uuid]),
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success(material.is_active ? 'Support masqué pour cette classe' : 'Support visible pour cette classe');
          fetchMaterials();
        },
        onError: () => toast.error('Erreur lors du changement de visibilité'),
      }
    );
  };

  const handleModalClose = () => {
    setIsAddModalOpen(false);
    setIsEditModalOpen(false);
    setSelectedMaterial(null);
    fetchMaterials();
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Supports de cours</CardTitle>
              <CardDescription>
                {trainingClass.training?.title} - {trainingClass.name}
              </CardDescription>
            </div>
            <Button onClick={() => setIsAddModalOpen(true)}>
              <Plus className="w-4 h-4 mr-2" />
              Ajouter un support
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {materials.length === 0 ? (
            <div className="text-center py-12">
              <div className="mx-auto h-12 w-12 text-gray-400 mb-4">
                <svg
                  className="h-full w-full"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
              </div>
              <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">
                Aucun support de cours
              </h3>
              <p className="text-gray-600 dark:text-gray-400 mb-4">
                Commencez par ajouter votre premier support de cours pour cette classe.
              </p>
              <Button onClick={() => setIsAddModalOpen(true)}>
                <Plus className="w-4 h-4 mr-2" />
                Ajouter un support
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {materials.map((material) => (
                <MaterialCard
                  key={material.id}
                  material={material}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                  onDownload={handleDownload}
                  onToggleActive={handleToggleActive}
                  isTeacher={true}
                />
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <AddMaterialModal
        isOpen={isAddModalOpen}
        onClose={handleModalClose}
        classUuid={trainingClass.uuid}
      />

      {selectedMaterial && (
        <EditMaterialModal
          isOpen={isEditModalOpen}
          onClose={handleModalClose}
          material={selectedMaterial}
          classUuid={trainingClass.uuid}
        />
      )}

      <DeleteConfirmationDialog
        open={!!materialToDelete}
        onOpenChange={(open) => !open && setMaterialToDelete(null)}
        onConfirm={confirmDelete}
        title="Supprimer le support de cours"
        description={`Êtes-vous sûr de vouloir supprimer "${materialToDelete?.title}" ? Cette action est irréversible.`}
      />
    </div>
  );
}
