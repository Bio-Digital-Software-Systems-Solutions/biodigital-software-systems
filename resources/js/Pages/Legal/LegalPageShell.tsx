import { ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import {
    formatLegalDate,
    resolveLegalLanguage,
    type LegalBlock,
    type LegalPageContent,
} from '@/Pages/Legal/legalShared';

interface LegalPageShellProps {
    content: LegalPageContent;
    icon: ReactNode;
}

function LegalBlockView({ block }: { block: LegalBlock }) {
    return (
        <div className="space-y-2">
            {block.subtitle && (
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">{block.subtitle}</h3>
            )}
            {block.paragraphs?.map((paragraph) => (
                <p key={paragraph} className="text-gray-700 dark:text-gray-300 leading-relaxed">
                    {paragraph}
                </p>
            ))}
            {block.bullets && (
                <ul className="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                    {block.bullets.map((bullet) => (
                        <li key={bullet}>{bullet}</li>
                    ))}
                </ul>
            )}
            {block.links && (
                <ul className="space-y-1">
                    {block.links.map((link) => (
                        <li key={link.href}>
                            <a
                                href={link.href}
                                target={link.href.startsWith('mailto:') ? undefined : '_blank'}
                                rel="noopener noreferrer"
                                className="text-primary hover:text-primary/80 underline break-all"
                            >
                                {link.label}
                            </a>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

/**
 * Common frame for the public legal pages: back link, language switcher,
 * title, last-updated line and the generic section renderer.
 */
export default function LegalPageShell({ content, icon }: LegalPageShellProps) {
    const { i18n } = useTranslation();
    const language = resolveLegalLanguage(i18n.language);

    return (
        <>
            <Head title={content.metaTitle} />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
                <div className="max-w-4xl mx-auto">
                    <div className="mb-8">
                        <div className="flex items-center justify-between mb-4">
                            <Link
                                href="/"
                                className="inline-flex items-center text-primary hover:text-primary/80 font-medium"
                            >
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                {content.backToHome}
                            </Link>
                            <LanguageSwitcher />
                        </div>

                        <div className="flex items-center gap-3 mb-4">
                            {icon}
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{content.title}</h1>
                        </div>

                        <p className="text-gray-600 dark:text-gray-400">
                            {content.lastUpdatedLabel} : {formatLegalDate(language)}
                        </p>
                    </div>

                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 space-y-8">
                        {content.sections.map((section) => (
                            <section key={section.id} aria-labelledby={section.id}>
                                <h2
                                    id={section.id}
                                    className="text-2xl font-semibold text-gray-900 dark:text-white mb-4"
                                >
                                    {section.title}
                                </h2>
                                <div className="space-y-4">
                                    {section.blocks.map((block, index) => (
                                        <LegalBlockView key={block.subtitle ?? index} block={block} />
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                </div>
            </div>
        </>
    );
}
