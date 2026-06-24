import React, { useMemo } from 'react';

interface KeywordBubblesProps {
  keywords: string[];
  /** Total number of bubbles rendered across the section. */
  count?: number;
}

interface BubbleStyle extends React.CSSProperties {
  '--bubble-drift': string;
}

interface Bubble {
  label?: string;
  size: number;
  fontSize: number;
  style: BubbleStyle;
}

/**
 * Deterministic pseudo-random in [0, 1) derived from a seed so the layout
 * stays stable between server and client renders (no hydration mismatch).
 */
const seededRandom = (seed: number): number => {
  const value = Math.sin(seed * 127.1 + 311.7) * 43758.5453;
  return value - Math.floor(value);
};

/**
 * Soap-bubble field: translucent, glossy bubbles tinted with the brand color
 * rise from the bottom and drift across the whole section. A subset carry a
 * keyword from the array; the rest are decorative to fill the surface.
 */
const KeywordBubbles: React.FC<KeywordBubblesProps> = ({ keywords, count = 26 }) => {
  const bubbles = useMemo<Bubble[]>(() => {
    if (keywords.length === 0) {
      return [];
    }

    return Array.from({ length: count }, (_, index) => {
      // Roughly every other bubble carries a keyword, cycling through them.
      const isLabeled = index % 2 === 0;
      const label = isLabeled ? keywords[(index / 2) % keywords.length | 0] : undefined;

      const size = label
        ? Math.min(168, Math.max(78, 46 + label.length * 5))
        : 34 + seededRandom(index + 3) * 96;

      const left = seededRandom(index + 1) * 92;
      const duration = 7 + seededRandom(index + 7) * 5;
      const delay = seededRandom(index + 13) * duration;
      const drift = (seededRandom(index + 19) - 0.5) * 90;

      const style: BubbleStyle = {
        left: `${left}%`,
        width: `${size}px`,
        height: `${size}px`,
        animationDuration: `${duration}s`,
        animationDelay: `-${delay}s`,
        '--bubble-drift': `${drift}px`,
      };

      return { label, size, fontSize: Math.max(11, size * 0.15), style };
    });
  }, [keywords, count]);

  return (
    <div
      className="pointer-events-none absolute inset-0 overflow-hidden"
      aria-hidden="true"
    >
      {bubbles.map((bubble, index) => (
        <span
          key={index}
          className="training-bubble"
          style={bubble.style}
        >
          {bubble.label && (
            <span
              className="training-bubble__label"
              style={{ fontSize: `${bubble.fontSize}px` }}
            >
              {bubble.label}
            </span>
          )}
        </span>
      ))}

      <ul className="sr-only">
        {keywords.map((keyword) => (
          <li key={keyword}>{keyword}</li>
        ))}
      </ul>

      <style>{`
        @keyframes training-bubble-rise {
          0% {
            bottom: -12%;
            transform: translateX(0) scale(0.82);
            opacity: 0;
          }
          12% {
            opacity: 1;
          }
          88% {
            opacity: 1;
          }
          100% {
            bottom: 112%;
            transform: translateX(var(--bubble-drift)) scale(1);
            opacity: 0;
          }
        }

        .training-bubble {
          position: absolute;
          bottom: -12%;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 9999px;
          border: 1px solid rgb(var(--bd-brand) / 0.28);
          background:
            radial-gradient(circle at 30% 26%, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0) 24%),
            radial-gradient(circle at 68% 74%, rgb(var(--bd-brand) / 0.05) 0%, rgba(255, 255, 255, 0) 36%),
            radial-gradient(circle at 50% 50%, rgb(var(--bd-brand) / 0.04) 30%, rgb(var(--bd-brand) / 0.20) 72%, rgb(var(--bd-brand) / 0.05) 100%);
          box-shadow:
            inset 6px 8px 16px rgba(255, 255, 255, 0.35),
            inset -4px -6px 14px rgb(var(--bd-brand) / 0.18),
            0 6px 22px rgb(var(--bd-brand) / 0.12);
          backdrop-filter: blur(1px);
          animation-name: training-bubble-rise;
          animation-timing-function: ease-in-out;
          animation-iteration-count: infinite;
          will-change: transform, bottom, opacity;
        }

        .training-bubble__label {
          padding: 0 0.6em;
          font-weight: 600;
          line-height: 1.1;
          text-align: center;
          color: rgb(var(--bd-brand-deep));
          text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
        }

        @media (prefers-reduced-motion: reduce) {
          .training-bubble {
            animation: none;
            opacity: 0.9;
          }
        }
      `}</style>
    </div>
  );
};

export default KeywordBubbles;
