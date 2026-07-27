import { Building2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import LegalPageShell from '@/Pages/Legal/LegalPageShell';
import { IMPRINT_CONTENT } from '@/Pages/Legal/imprintContent';
import { resolveLegalLanguage } from '@/Pages/Legal/legalShared';

export default function Imprint() {
    const { i18n } = useTranslation();
    const content = IMPRINT_CONTENT[resolveLegalLanguage(i18n.language)];

    return <LegalPageShell content={content} icon={<Building2 className="h-8 w-8 text-primary" />} />;
}
