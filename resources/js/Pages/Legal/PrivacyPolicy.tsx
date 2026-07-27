import { Shield } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import LegalPageShell from '@/Pages/Legal/LegalPageShell';
import { PRIVACY_CONTENT } from '@/Pages/Legal/privacyContent';
import { resolveLegalLanguage } from '@/Pages/Legal/legalShared';

export default function PrivacyPolicy() {
    const { i18n } = useTranslation();
    const content = PRIVACY_CONTENT[resolveLegalLanguage(i18n.language)];

    return <LegalPageShell content={content} icon={<Shield className="h-8 w-8 text-primary" />} />;
}
