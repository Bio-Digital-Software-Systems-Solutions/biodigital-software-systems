import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';

// ============================================================
// Cardinal-red brand ramp (CENTOGENE #D41F32 ≈ oklch 0.55 0.21 24).
// The default blue/indigo/sky/cyan scales are repointed to this ramp so
// every hard-coded `*-blue-*` / `*-indigo-*` / `*-sky-*` / `*-cyan-*`
// utility across the app renders in the brand red.
// ============================================================
const brandRed = {
    50: 'oklch(0.971 0.013 24 / <alpha-value>)',
    100: 'oklch(0.940 0.035 24 / <alpha-value>)',
    200: 'oklch(0.885 0.070 24 / <alpha-value>)',
    300: 'oklch(0.805 0.115 24 / <alpha-value>)',
    400: 'oklch(0.705 0.165 24 / <alpha-value>)',
    500: 'oklch(0.625 0.205 24 / <alpha-value>)',
    600: 'oklch(0.553 0.214 24 / <alpha-value>)',
    700: 'oklch(0.478 0.190 24 / <alpha-value>)',
    800: 'oklch(0.405 0.160 24 / <alpha-value>)',
    900: 'oklch(0.350 0.132 24 / <alpha-value>)',
    950: 'oklch(0.250 0.095 24 / <alpha-value>)',
};

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
    	container: {
    		center: true,
    		padding: '2rem',
    		screens: {
    			'2xl': '1400px'
    		}
    	},
    	extend: {
    		fontFamily: {
    			sans: [
    				'Figtree',
                    ...defaultTheme.fontFamily.sans
                ],
    			// Landing page typography (Bio-Digital proposal)
    			display: ['Sora', ...defaultTheme.fontFamily.sans],
    			body: ['Inter', ...defaultTheme.fontFamily.sans]
    		},
    		colors: {
    			// ============================================================
    			// REAGENT — palette de marque Bio-Digital Software Systems
    			// Gammes OKLCH 50–950 dérivées d'Okabe-Ito (sûres daltonismes)
    			// Ancres : blue-600 ≈ #0072B2 · vermillion-500 ≈ #D55E00
    			//          sky-400 ≈ #56B4E9 · graphite-900 ≈ #1C2B33
    			// `<alpha-value>` préserve le support des modificateurs d'opacité.
    			// ============================================================
    			graphite: {
    				50: 'oklch(0.975 0.003 230 / <alpha-value>)',
    				100: 'oklch(0.945 0.005 230 / <alpha-value>)',
    				200: 'oklch(0.890 0.008 230 / <alpha-value>)',
    				300: 'oklch(0.800 0.010 230 / <alpha-value>)',
    				400: 'oklch(0.680 0.013 230 / <alpha-value>)',
    				500: 'oklch(0.570 0.015 230 / <alpha-value>)',
    				600: 'oklch(0.480 0.017 230 / <alpha-value>)',
    				700: 'oklch(0.400 0.018 230 / <alpha-value>)',
    				800: 'oklch(0.330 0.019 230 / <alpha-value>)',
    				900: 'oklch(0.275 0.020 230 / <alpha-value>)',
    				950: 'oklch(0.210 0.018 230 / <alpha-value>)',
    			},
    			blue: brandRed,
    			indigo: brandRed,
    			cyan: brandRed,
    			vermillion: {
    				50: 'oklch(0.970 0.014 45 / <alpha-value>)',
    				100: 'oklch(0.940 0.035 45 / <alpha-value>)',
    				200: 'oklch(0.880 0.070 45 / <alpha-value>)',
    				300: 'oklch(0.800 0.110 45 / <alpha-value>)',
    				400: 'oklch(0.710 0.145 45 / <alpha-value>)',
    				500: 'oklch(0.620 0.157 45 / <alpha-value>)',
    				600: 'oklch(0.550 0.140 45 / <alpha-value>)',
    				700: 'oklch(0.470 0.118 45 / <alpha-value>)',
    				800: 'oklch(0.400 0.097 45 / <alpha-value>)',
    				900: 'oklch(0.340 0.078 45 / <alpha-value>)',
    				950: 'oklch(0.250 0.055 45 / <alpha-value>)',
    			},
    			sky: brandRed,
    			border: 'hsl(var(--border))',
    			input: 'hsl(var(--input))',
    			ring: 'hsl(var(--ring))',
    			background: 'hsl(var(--background))',
    			foreground: 'hsl(var(--foreground))',
    			primary: {
    				DEFAULT: 'hsl(var(--primary))',
    				foreground: 'hsl(var(--primary-foreground))'
    			},
    			secondary: {
    				DEFAULT: 'hsl(var(--secondary))',
    				foreground: 'hsl(var(--secondary-foreground))'
    			},
    			destructive: {
    				DEFAULT: 'hsl(var(--destructive))',
    				foreground: 'hsl(var(--destructive-foreground))'
    			},
    			muted: {
    				DEFAULT: 'hsl(var(--muted))',
    				foreground: 'hsl(var(--muted-foreground))'
    			},
    			accent: {
    				DEFAULT: 'hsl(var(--accent))',
    				foreground: 'hsl(var(--accent-foreground))'
    			},
    			popover: {
    				DEFAULT: 'hsl(var(--popover))',
    				foreground: 'hsl(var(--popover-foreground))'
    			},
    			card: {
    				DEFAULT: 'hsl(var(--card))',
    				foreground: 'hsl(var(--card-foreground))'
    			},
    			icc: {
    				blue: 'oklch(0.553 0.214 24)',
    				red: 'oklch(0.620 0.157 45)',
    				purple: 'oklch(0.700 0.165 24)',
    				yellow: '#eab308',
    				lime: '#84cc16'
    			},
    			// ============================================================
    			// Bio-Digital landing page tokens (CENTOGENE-inspired palette)
    			// Cardinal red (#D41F32) brand on clean neutral greys / white.
    			// Driven by CSS variables (see app.css) so they flip in dark mode.
    			// `bd-deep` is a fixed dark surface (footer / hero) that never flips.
    			// ============================================================
    			bd: {
    				bg: 'oklch(var(--bd-bg) / <alpha-value>)',
    				surface: 'oklch(var(--bd-surface) / <alpha-value>)',
    				'surface-2': 'oklch(var(--bd-surface-2) / <alpha-value>)',
    				line: 'oklch(var(--bd-line) / <alpha-value>)',
    				ink: 'oklch(var(--bd-ink) / <alpha-value>)',
    				'ink-2': 'oklch(var(--bd-ink-2) / <alpha-value>)',
    				'ink-3': 'oklch(var(--bd-ink-3) / <alpha-value>)',
    				deep: 'oklch(var(--bd-deep) / <alpha-value>)',
    				brand: 'rgb(var(--bd-brand) / <alpha-value>)',
    				'brand-deep': 'rgb(var(--bd-brand-deep) / <alpha-value>)',
    				'brand-soft': 'rgb(var(--bd-brand-soft) / <alpha-value>)',
    				accent: 'rgb(var(--bd-accent) / <alpha-value>)'
    			}
    		},
    		borderRadius: {
    			card: '0.75rem',
    			lg: 'var(--radius)',
    			md: 'calc(var(--radius) - 2px)',
    			sm: 'calc(var(--radius) - 4px)'
    		},
    		keyframes: {
    			'accordion-down': {
    				from: {
    					height: '0'
    				},
    				to: {
    					height: 'var(--radix-accordion-content-height)'
    				}
    			},
    			'accordion-up': {
    				from: {
    					height: 'var(--radix-accordion-content-height)'
    				},
    				to: {
    					height: '0'
    				}
    			}
    		},
    		animation: {
    			'accordion-down': 'accordion-down 0.2s ease-out',
    			'accordion-up': 'accordion-up 0.2s ease-out'
    		}
    	}
    },

    plugins: [forms, animate],
};
