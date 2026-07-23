import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['selector', '[data-theme="dark"]'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', 'ui-serif', 'Georgia', 'serif'],
            },
            colors: {
                accent: {
                    DEFAULT: 'var(--accent)',
                    hover: 'var(--accent-hover)',
                    soft: 'var(--accent-soft)',
                    'soft-hover': 'var(--accent-soft-hover)',
                },
                ink: {
                    DEFAULT: 'var(--ink)',
                    soft: 'var(--ink-soft)',
                },
                paper: 'var(--paper)',
                surface: 'var(--surface)',
                line: {
                    DEFAULT: 'var(--line)',
                    soft: 'var(--line-soft)',
                },
                good: {
                    DEFAULT: 'var(--good)',
                    soft: 'var(--good-soft)',
                },
                warn: {
                    DEFAULT: 'var(--warn)',
                    soft: 'var(--warn-soft)',
                },
                critical: {
                    DEFAULT: 'var(--critical)',
                    soft: 'var(--critical-soft)',
                },
            },
        },
    },

    plugins: [forms],
};
