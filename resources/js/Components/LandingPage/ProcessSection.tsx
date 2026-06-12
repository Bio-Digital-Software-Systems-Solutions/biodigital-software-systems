import { useTranslation } from 'react-i18next';

const STEPS = [
    { titleKey: 'home.process.step1.title', descKey: 'home.process.step1.desc' },
    { titleKey: 'home.process.step2.title', descKey: 'home.process.step2.desc' },
    { titleKey: 'home.process.step3.title', descKey: 'home.process.step3.desc' },
    { titleKey: 'home.process.step4.title', descKey: 'home.process.step4.desc' },
];

/**
 * "Ablauf / How it works" band: four numbered steps from intro call to handover,
 * matching the Bio-Digital landing proposal.
 */
export default function ProcessSection() {
    const { t } = useTranslation();

    return (
        <section id="process" className="border-t border-bd-line py-16 sm:py-20 lg:py-24">
            <div className="mx-auto max-w-[1140px] px-5 sm:px-8 lg:px-10">
                <div className="max-w-[62ch]">
                    <p className="mb-3.5 text-[12.5px] font-semibold uppercase tracking-[0.14em] text-bd-brand-deep">
                        {t('home.process.kicker')}
                    </p>
                    <h2 className="font-display text-[clamp(1.7rem,3.4vw,2.5rem)] font-semibold tracking-tight text-bd-ink">
                        {t('home.process.title')}
                    </h2>
                </div>

                <ol className="mt-11 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {STEPS.map((step, index) => (
                        <li key={step.titleKey} className="border-t-2 border-bd-line pt-[22px]">
                            <span className="font-display text-[13px] font-semibold text-bd-brand">
                                {String(index + 1).padStart(2, '0')}
                            </span>
                            <h4 className="mt-2.5 font-display text-[1.08rem] font-semibold text-bd-ink">
                                {t(step.titleKey)}
                            </h4>
                            <p className="mt-1.5 text-[0.9rem] text-bd-ink-3">{t(step.descKey)}</p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
