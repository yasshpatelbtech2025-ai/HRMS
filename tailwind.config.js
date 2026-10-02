import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Funnel Display', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f6f3ff',
                    100: '#ede8ff',
                    200: '#ddd4ff',
                    300: '#c3b4ff',
                    400: '#a68cff',
                    500: '#8a66f7',
                    600: '#7448ea',
                    700: '#6038cb',
                },
            },
        },
    },

    plugins: [forms],
};
