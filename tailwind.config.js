import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Every colour is a CSS variable (RGB channels) defined in resources/css/app.css,
// so themes swap by changing variables only.
const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './config/navigation.php',
    ],

    darkMode: ['selector', '[data-theme="dark"]'],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            fontSize: {
                // 12 / 13 / 14 / 16 / 20 / 24 / 30
                xs: ['0.75rem', { lineHeight: '1rem' }],
                '13': ['0.8125rem', { lineHeight: '1.125rem' }],
                sm: ['0.875rem', { lineHeight: '1.25rem' }],
                base: ['1rem', { lineHeight: '1.5rem' }],
                lg: ['1.25rem', { lineHeight: '1.75rem', letterSpacing: '-0.01em' }],
                xl: ['1.5rem', { lineHeight: '2rem', letterSpacing: '-0.015em' }],
                '2xl': ['1.875rem', { lineHeight: '2.25rem', letterSpacing: '-0.02em' }],
            },
            colors: {
                canvas: token('canvas'),
                surface: token('surface'),
                sunken: token('sunken'),
                rail: token('rail'),
                line: { DEFAULT: token('line'), strong: token('line-strong') },
                ink: { DEFAULT: token('ink'), 2: token('ink-2'), 3: token('ink-3') },
                primary: { DEFAULT: token('primary'), hover: token('primary-hover'), soft: token('primary-soft'), ink: token('primary-ink') },
                success: { DEFAULT: token('success'), soft: token('success-soft') },
                warn: { DEFAULT: token('warn'), soft: token('warn-soft') },
                danger: { DEFAULT: token('danger'), hover: token('danger-hover'), soft: token('danger-soft') },
                info: { DEFAULT: token('info'), soft: token('info-soft') },
            },
            borderRadius: {
                control: '6px',
                card: '10px',
            },
            boxShadow: {
                hairline: '0 1px 2px rgb(0 0 0 / 0.05)',
                pop: '0 1px 2px rgb(0 0 0 / 0.06), 0 12px 32px -8px rgb(16 24 32 / 0.18)',
            },
            ringColor: {
                DEFAULT: token('primary'),
            },
            transitionDuration: {
                DEFAULT: '150ms',
            },
        },
    },

    plugins: [forms],
};
