import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            spacing: {
                4.5: '1.125rem',
            },
            fontFamily: {
                sans: ['Google Sans', 'DM Sans', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
