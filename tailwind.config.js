import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';

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
    			blue: {
    				50: 'oklch(0.970 0.012 237 / <alpha-value>)',
    				100: 'oklch(0.930 0.028 237 / <alpha-value>)',
    				200: 'oklch(0.870 0.055 237 / <alpha-value>)',
    				300: 'oklch(0.780 0.085 237 / <alpha-value>)',
    				400: 'oklch(0.660 0.110 237 / <alpha-value>)',
    				500: 'oklch(0.580 0.122 237 / <alpha-value>)',
    				600: 'oklch(0.520 0.124 237 / <alpha-value>)',
    				700: 'oklch(0.450 0.110 237 / <alpha-value>)',
    				800: 'oklch(0.380 0.090 237 / <alpha-value>)',
    				900: 'oklch(0.320 0.072 237 / <alpha-value>)',
    				950: 'oklch(0.240 0.052 237 / <alpha-value>)',
    			},
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
    			sky: {
    				50: 'oklch(0.975 0.010 235 / <alpha-value>)',
    				100: 'oklch(0.940 0.025 235 / <alpha-value>)',
    				200: 'oklch(0.890 0.050 235 / <alpha-value>)',
    				300: 'oklch(0.810 0.080 235 / <alpha-value>)',
    				400: 'oklch(0.730 0.105 235 / <alpha-value>)',
    				500: 'oklch(0.640 0.110 235 / <alpha-value>)',
    				600: 'oklch(0.550 0.105 235 / <alpha-value>)',
    				700: 'oklch(0.470 0.090 235 / <alpha-value>)',
    				800: 'oklch(0.390 0.072 235 / <alpha-value>)',
    				900: 'oklch(0.330 0.058 235 / <alpha-value>)',
    				950: 'oklch(0.250 0.042 235 / <alpha-value>)',
    			},
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
    				blue: 'oklch(0.520 0.124 237)',
    				red: 'oklch(0.620 0.157 45)',
    				purple: 'oklch(0.730 0.105 235)',
    				yellow: '#eab308',
    				lime: '#84cc16'
    			},
    			// ============================================================
    			// Bio-Digital landing page tokens (proposal palette, teal brand)
    			// Light-only surface set used by the public homepage.
    			// ============================================================
    			bd: {
    				bg: 'oklch(0.99 0.004 195 / <alpha-value>)',
    				surface: 'oklch(1 0 0 / <alpha-value>)',
    				'surface-2': 'oklch(0.975 0.006 200 / <alpha-value>)',
    				line: 'oklch(0.91 0.008 200 / <alpha-value>)',
    				ink: 'oklch(0.22 0.02 215 / <alpha-value>)',
    				'ink-2': 'oklch(0.44 0.018 210 / <alpha-value>)',
    				'ink-3': 'oklch(0.6 0.014 205 / <alpha-value>)',
    				brand: 'oklch(0.62 0.12 200 / <alpha-value>)',
    				'brand-deep': 'oklch(0.52 0.11 205 / <alpha-value>)',
    				'brand-soft': 'oklch(0.95 0.03 200 / <alpha-value>)',
    				accent: 'oklch(0.7 0.13 155 / <alpha-value>)'
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
