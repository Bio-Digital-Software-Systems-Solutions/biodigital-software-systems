import React from 'react';

interface TrainingIntroProps {
  badge: string;
  heading: string;
  subtitle: string;
  imageSrc?: string;
}

const TrainingIntro: React.FC<TrainingIntroProps> = ({
  badge,
  heading,
  subtitle,
  imageSrc = '/training-1.jpg',
}) => {
  return (
    <div className="mb-11 grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
      <div className="max-w-[62ch]">
        <p className="mb-3.5 text-[12.5px] font-semibold uppercase tracking-[0.14em] text-bd-brand-deep">
          {badge}
        </p>
        <h2 className="font-display text-[clamp(1.7rem,3.4vw,2.5rem)] font-semibold tracking-tight text-bd-ink">
          {heading}
        </h2>
        <p className="mt-3.5 text-[1.05rem] text-bd-ink-2">{subtitle}</p>
      </div>
      <div className="relative">
        <img
          src={imageSrc}
          alt={heading}
          className="h-auto w-full rounded-2xl border border-bd-line object-cover shadow-[0_18px_40px_-28px_oklch(0.5_0.18_22_/_0.6)]"
        />
      </div>
    </div>
  );
};

export default TrainingIntro;
