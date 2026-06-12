import React from 'react';
import { useTranslation } from 'react-i18next';
import { Badge } from '../ui/badge';
import { resolveDesign, type DesignSettings } from '@/lib/sectionDesign';

export interface AboutContent {
  badge?: string;
  heading?: string;
  paragraphs?: string[];
  image_url?: string;
  mission_blocks?: Array<{ title: string; body: string; color?: string }>;
  stats?: Array<{ value: string; label: string; color?: string }>;
  affiliations?: Array<{ label: string; color?: string }>;
}

interface GlobalStats {
  total_churches: number;
  total_countries: number;
  total_members: number;
  europe: number;
  africa: number;
  americas: number;
  asia: number;
  oceania: number;
}

interface AboutSectionProps {
  globalStats?: GlobalStats;
  content?: AboutContent;
  design?: DesignSettings | null;
}

const TECH_STACK = ['Laravel', 'React', 'TypeScript', 'GraphQL', 'Java', 'Python', 'Cloud-Infrastruktur'];

const AboutSection: React.FC<AboutSectionProps> = ({ content, design }) => {
  const { t } = useTranslation();
  const resolved = resolveDesign(design);
  const badge = content?.badge ?? t('home.about.badge');
  const heading = content?.heading ?? t('home.about.heading');
  const paragraphs = content?.paragraphs ?? [
    t('home.about.p1'),
    t('home.about.p2'),
  ];
  const imageUrl = content?.image_url ?? '/ecosystem.png';

  return (
    <div
      id="about"
      className={`bg-gray-50 dark:bg-gray-900 px-6 md:px-12 ${resolved.sectionClass} ${resolved.hasPadding ? '' : 'py-16'}`}
      style={resolved.sectionStyle}
    >
      <div className={`space-y-4 mb-16 ${resolved.alignmentClass || 'text-center'}`}>
        <Badge variant="secondary" className="mb-2">
          {badge}
        </Badge>
      </div>
      <div className="max-w-7xl mx-auto">

        {/* Section 1: Geschäftsidee */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-24">
          {/* Left side - Text content */}
          <div className="space-y-6">
            <h1 className={`font-bold text-gray-900 dark:text-white leading-tight ${resolved.headingClass || 'text-5xl lg:text-6xl'}`}>
              {heading}
            </h1>
            {paragraphs.map((p, i) => (
              <p key={i} className={`text-gray-500 dark:text-gray-400 leading-relaxed ${resolved.paragraphClass || 'text-lg'}`}>
                {p}
              </p>
            ))}
          </div>

          {/* Right side - Tech image */}
          <div className="flex items-center justify-center">
            <img
              src={imageUrl}
              alt={heading}
              className="w-full h-auto max-h-[32rem] object-contain rounded-lg"
            />
          </div>
        </div>

        {/* Section 2: Kernkompetenzen */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-16 mb-24">
          {/* Left side - Competencies (2/3 width) */}
          <div className="lg:col-span-2 space-y-8">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white">
              {t('home.about.competencies.heading')}
            </h2>
            <div className="space-y-6 text-gray-500 dark:text-gray-400 text-lg leading-relaxed">
              <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-primary">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.competencies.bioinfo.title')}</h3>
                <p>{t('home.about.competencies.bioinfo.body')}</p>
              </div>
              <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-green-500">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.competencies.ai.title')}</h3>
                <p>{t('home.about.competencies.ai.body')}</p>
              </div>
              <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-purple-500">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.competencies.fullstack.title')}</h3>
                <p>{t('home.about.competencies.fullstack.body')}</p>
              </div>
              <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-blue-500">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.competencies.industry.title')}</h3>
                <p>{t('home.about.competencies.industry.body')}</p>
              </div>
            </div>
          </div>

          {/* Right side - Key facts (1/3 width) */}
          <div className="space-y-12 text-center">
            <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
              <div className="text-4xl font-bold text-primary dark:text-blue-400 mb-2">3x</div>
              <div className="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">{t('home.about.facts.faster')}</div>
            </div>
            <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
              <div className="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">GxP</div>
              <div className="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">{t('home.about.facts.gxp')}</div>
            </div>
            <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
              <div className="text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">NGS</div>
              <div className="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">{t('home.about.facts.ngs')}</div>
            </div>
          </div>
        </div>

        {/* Section 3: Leistungsangebot */}
        <div className="mb-24">
          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-12 text-center">
            {t('home.about.services.heading')}
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {/* Bio-MVP Turbo */}
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="w-16 h-16 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center mb-6">
                <svg className="w-8 h-8 text-primary dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.services.mvp.title')}</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                {t('home.about.services.mvp.body')}
              </p>
            </div>

            {/* Middleware-Lösungen */}
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="w-16 h-16 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mb-6">
                <svg className="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.services.middleware.title')}</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                {t('home.about.services.middleware.body')}
              </p>
            </div>

            {/* SaaS-Plattform-Entwicklungen */}
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="w-16 h-16 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center mb-6">
                <svg className="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 1.096A4.002 4.002 0 003 15z" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.services.saas.title')}</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                {t('home.about.services.saas.body')}
              </p>
            </div>

            {/* Software im Kundenauftrag */}
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="w-16 h-16 bg-orange-100 dark:bg-orange-900/50 rounded-full flex items-center justify-center mb-6">
                <svg className="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">{t('home.about.services.custom.title')}</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                {t('home.about.services.custom.body')}
              </p>
            </div>
          </div>
        </div>

        {/* Section 4: Technologie-Stack */}
        <div className="mb-24">
          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6 text-center">
            {t('home.about.tech.heading')}
          </h2>
          <p className="text-gray-500 dark:text-gray-400 text-lg text-center mb-12 max-w-3xl mx-auto">
            {t('home.about.tech.subtitle')}
          </p>
          <div className="relative rounded-3xl overflow-hidden bg-white dark:bg-gray-800 shadow-2xl p-10">
            <div className="flex flex-wrap justify-center gap-4">
              {TECH_STACK.map((tech) => (
                <span
                  key={tech}
                  className="px-6 py-3 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-lg shadow-sm"
                >
                  {tech}
                </span>
              ))}
            </div>
          </div>
        </div>

        {/* Section 5: Zielgruppen & Compliance */}
        <div className="max-w-6xl mb-8">
          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">
            {t('home.about.audience.heading')}
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg">
              <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">{t('home.about.audience.bridge.title')}</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                {t('home.about.audience.bridge.body')}
              </p>
            </div>
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg">
              <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">{t('home.about.compliance.title')}</h3>
              <div className="space-y-3">
                <div className="flex items-center space-x-3">
                  <div className="w-3 h-3 bg-primary rounded-full"></div>
                  <span className="text-gray-600 dark:text-gray-400">{t('home.about.compliance.gmp')}</span>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                  <span className="text-gray-600 dark:text-gray-400">{t('home.about.compliance.glp')}</span>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="w-3 h-3 bg-purple-500 rounded-full"></div>
                  <span className="text-gray-600 dark:text-gray-400">{t('home.about.compliance.gcp')}</span>
                </div>
              </div>
            </div>
          </div>

          {/* So arbeiten wir */}
          <div className="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-8 rounded-2xl mb-8">
            <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">{t('home.about.how.heading')}</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="text-center">
                <div className="w-12 h-12 bg-primary dark:bg-primary rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <h4 className="font-bold text-gray-900 dark:text-white mb-2">{t('home.about.how.ttm.title')}</h4>
                <p className="text-gray-600 dark:text-gray-400 text-sm">{t('home.about.how.ttm.body')}</p>
              </div>
              <div className="text-center">
                <div className="w-12 h-12 bg-purple-500 dark:bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                  </svg>
                </div>
                <h4 className="font-bold text-gray-900 dark:text-white mb-2">{t('home.about.how.interop.title')}</h4>
                <p className="text-gray-600 dark:text-gray-400 text-sm">{t('home.about.how.interop.body')}</p>
              </div>
              <div className="text-center">
                <div className="w-12 h-12 bg-green-500 dark:bg-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 1.096A4.002 4.002 0 003 15z" />
                  </svg>
                </div>
                <h4 className="font-bold text-gray-900 dark:text-white mb-2">{t('home.about.how.cloud.title')}</h4>
                <p className="text-gray-600 dark:text-gray-400 text-sm">{t('home.about.how.cloud.body')}</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
};

export default AboutSection;
