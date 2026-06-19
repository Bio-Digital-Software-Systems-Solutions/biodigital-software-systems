import { useTranslation } from 'react-i18next';
import { Dna, Sparkles, Layers, ShieldCheck, Rocket, Network, Cloud, type LucideIcon } from 'lucide-react';
import type { DesignSettings } from '@/lib/sectionDesign';

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

interface Competency {
  icon: LucideIcon;
  titleKey: string;
  bodyKey: string;
}

const COMPETENCIES: Competency[] = [
  { icon: Dna, titleKey: 'home.about.competencies.bioinfo.title', bodyKey: 'home.about.competencies.bioinfo.body' },
  { icon: Sparkles, titleKey: 'home.about.competencies.ai.title', bodyKey: 'home.about.competencies.ai.body' },
  { icon: Layers, titleKey: 'home.about.competencies.fullstack.title', bodyKey: 'home.about.competencies.fullstack.body' },
  { icon: ShieldCheck, titleKey: 'home.about.competencies.industry.title', bodyKey: 'home.about.competencies.industry.body' },
];

const FACTS = [
  { value: '3x', labelKey: 'home.about.facts.faster' },
  { value: 'GxP', labelKey: 'home.about.facts.gxp' },
  { value: 'NGS', labelKey: 'home.about.facts.ngs' },
];

const TECH_STACK = ['Laravel', 'React', 'TypeScript', 'GraphQL', 'Java', 'Python', 'Cloud-Infrastruktur'];

const HOW_STEPS: Competency[] = [
  { icon: Rocket, titleKey: 'home.about.how.ttm.title', bodyKey: 'home.about.how.ttm.body' },
  { icon: Network, titleKey: 'home.about.how.interop.title', bodyKey: 'home.about.how.interop.body' },
  { icon: Cloud, titleKey: 'home.about.how.cloud.title', bodyKey: 'home.about.how.cloud.body' },
];

const COMPLIANCE = [
  'home.about.compliance.gmp',
  'home.about.compliance.glp',
  'home.about.compliance.gcp',
];

/**
 * "À propos / About" band: company introduction, core competencies, key facts,
 * technology stack and the regulatory-compliance audience block. Themed with the
 * Bio-Digital landing tokens (bd-*) to match the public homepage.
 */
export default function AboutSection({ content }: AboutSectionProps) {
  const { t } = useTranslation();

  const badge = content?.badge ?? t('home.about.badge');
  const heading = content?.heading ?? t('home.about.heading');
  const paragraphs = content?.paragraphs ?? [t('home.about.p1'), t('home.about.p2')];
  const imageUrl = content?.image_url ?? '/3.png';

  return (
    <section id="about" className="border-t border-bd-line py-16 sm:py-20 lg:py-24">
      <div className="mx-auto max-w-none px-5 sm:px-8 lg:px-10">
        {/* Intro */}
        <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
          <div className="max-w-[62ch]">
            <p className="mb-3.5 text-[12.5px] font-semibold uppercase tracking-[0.14em] text-bd-brand-deep">
              {badge}
            </p>
            <h2 className="font-display text-[clamp(1.7rem,3.4vw,2.5rem)] font-semibold tracking-tight text-bd-ink">
              {heading}
            </h2>
            {paragraphs.map((paragraph, index) => (
              <p key={index} className="mt-3.5 text-[1.05rem] leading-relaxed text-bd-ink-2">
                {paragraph}
              </p>
            ))}
          </div>

          <div className="flex items-center justify-center">
            <img
              src={imageUrl}
              alt={heading}
              className="h-auto max-h-[28rem] w-full rounded-2xl border border-bd-line bg-bd-surface object-contain p-2"
            />
          </div>
        </div>

        {/* Core competencies + key facts */}
        <div className="mt-16 grid gap-10 lg:grid-cols-3 lg:gap-12">
          <div className="lg:col-span-2">
            <h3 className="font-display text-[clamp(1.35rem,2.4vw,1.75rem)] font-semibold tracking-tight text-bd-ink">
              {t('home.about.competencies.heading')}
            </h3>
            <div className="mt-7 grid gap-5 sm:grid-cols-2">
              {COMPETENCIES.map(({ icon: Icon, titleKey, bodyKey }) => (
                <article
                  key={titleKey}
                  className="rounded-2xl border border-bd-line bg-bd-surface p-7 transition-all duration-200 hover:-translate-y-0.5 hover:border-bd-brand hover:shadow-[0_18px_40px_-28px_oklch(0.5_0.18_22_/_0.6)]"
                >
                  <div className="mb-4 grid h-11 w-11 place-items-center rounded-xl bg-bd-brand-soft text-bd-brand-deep">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </div>
                  <h4 className="font-display text-[1.15rem] font-semibold text-bd-ink">{t(titleKey)}</h4>
                  <p className="mt-2 text-[0.97rem] text-bd-ink-2">{t(bodyKey)}</p>
                </article>
              ))}
            </div>
          </div>

          <div className="grid content-start gap-5">
            {FACTS.map((fact) => (
              <div
                key={fact.value}
                className="rounded-2xl border border-bd-line bg-bd-surface-2 p-6 text-center"
              >
                <div className="font-display text-4xl font-semibold text-bd-brand-deep">{fact.value}</div>
                <div className="mt-2 text-[0.8rem] uppercase tracking-[0.1em] text-bd-ink-3">
                  {t(fact.labelKey)}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Technology stack */}
        <div className="mt-16">
          <h3 className="font-display text-[clamp(1.35rem,2.4vw,1.75rem)] font-semibold tracking-tight text-bd-ink">
            {t('home.about.tech.heading')}
          </h3>
          <p className="mt-3 max-w-[62ch] text-[1.05rem] text-bd-ink-2">{t('home.about.tech.subtitle')}</p>
          <div className="mt-7 flex flex-wrap gap-3 rounded-2xl border border-bd-line bg-bd-surface p-7">
            {TECH_STACK.map((tech) => (
              <span
                key={tech}
                className="rounded-full bg-bd-surface-2 px-5 py-2.5 text-[0.95rem] font-semibold text-bd-ink"
              >
                {tech}
              </span>
            ))}
          </div>
        </div>

        {/* Audience & compliance */}
        <div className="mt-16">
          <h3 className="font-display text-[clamp(1.35rem,2.4vw,1.75rem)] font-semibold tracking-tight text-bd-ink">
            {t('home.about.audience.heading')}
          </h3>
          <div className="mt-7 grid gap-5 md:grid-cols-2">
            <div className="rounded-2xl border border-bd-line bg-bd-surface p-7">
              <h4 className="font-display text-[1.25rem] font-semibold text-bd-ink">
                {t('home.about.audience.bridge.title')}
              </h4>
              <p className="mt-2 text-[0.97rem] text-bd-ink-2">{t('home.about.audience.bridge.body')}</p>
            </div>
            <div className="rounded-2xl border border-bd-line bg-bd-surface p-7">
              <h4 className="font-display text-[1.25rem] font-semibold text-bd-ink">
                {t('home.about.compliance.title')}
              </h4>
              <ul className="mt-3 space-y-2.5">
                {COMPLIANCE.map((key) => (
                  <li key={key} className="flex items-center gap-3 text-[0.97rem] text-bd-ink-2">
                    <span className="h-2 w-2 shrink-0 rounded-full bg-bd-brand" aria-hidden="true" />
                    {t(key)}
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* How we work */}
          <div className="mt-5 rounded-2xl border border-bd-line bg-bd-surface-2 p-7 sm:p-9">
            <h4 className="text-center font-display text-[1.25rem] font-semibold text-bd-ink">
              {t('home.about.how.heading')}
            </h4>
            <div className="mt-7 grid gap-6 sm:grid-cols-3">
              {HOW_STEPS.map(({ icon: Icon, titleKey, bodyKey }) => (
                <div key={titleKey} className="text-center">
                  <div className="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-xl bg-bd-brand text-white">
                    <Icon className="h-5 w-5" aria-hidden="true" />
                  </div>
                  <h5 className="font-display text-[1.02rem] font-semibold text-bd-ink">{t(titleKey)}</h5>
                  <p className="mt-1.5 text-[0.9rem] text-bd-ink-3">{t(bodyKey)}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
