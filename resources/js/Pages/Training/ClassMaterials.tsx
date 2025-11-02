import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import ClassMaterialsList from '@/Components/Training/ClassMaterials/ClassMaterialsList';

interface TrainingClass {
  id: number;
  uuid: string;
  name: string;
  training?: {
    id: number;
    title: string;
  };
}

interface ClassMaterialsProps {
  trainingClass: TrainingClass;
}

export default function ClassMaterials({ trainingClass }: ClassMaterialsProps) {
  return (
    <DashboardLayout>
      <Head title={`Supports de cours - ${trainingClass.name}`} />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <ClassMaterialsList trainingClass={trainingClass} />
        </div>
      </div>
    </DashboardLayout>
  );
}
