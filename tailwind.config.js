import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                // From plansze brandingowe: Poppins for display, Inter for body.
                display: ['Poppins', 'Montserrat', ...defaultTheme.fontFamily.sans],
                sans: ['Inter', 'Open Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                dp: {
                    purple: {
                        50:  '#F5F3FF',
                        100: '#EDE9FE',
                        200: '#DDD6FE',
                        300: '#C4B5FD',
                        400: '#A78BFA',
                        500: '#7C6CF7',
                        600: '#4F46E5',
                        700: '#4338CA',
                        800: '#3730A3',
                        900: '#312E81',
                    },
                    blue: {
                        500: '#3B82F6',
                        600: '#2563EB',
                    },
                    navy: '#1E293B',
                    muted: '#64746B',
                    green: '#10B981',
                    pink: '#EC4899',
                    bg: '#FFFFFF',
                    'card-bg': '#FFFFFF',
                },
            },
            backgroundImage: {
                'dp-gradient': 'linear-gradient(135deg, #4F46E5 0%, #3B82F6 100%)',
                'dp-gradient-soft': 'linear-gradient(135deg, #F5F3FF 0%, #EFF6FF 100%)',
            },
            boxShadow: {
                // From dokument UX/UI: soft, low-opacity slate shadow
                // (`0 4px 20px -2px rgba(30, 41, 59, 0.05)`) for everyday
                // surfaces; deeper brand-tinted lift for hero cards.
                'dp-soft':    '0 4px 20px -2px rgba(30, 41, 59, 0.05)',
                'dp-card':    '0 4px 20px -2px rgba(30, 41, 59, 0.06)',
                'dp-card-lg': '0 24px 48px -16px rgba(79, 70, 229, 0.22)',
                'dp-focus':   '0 0 0 4px rgba(79, 70, 229, 0.12)',
            },
            borderRadius: {
                // Dokument: 12px (small/buttons/inputs) + 16px (large cards/modals).
                'dp':    '12px',
                'dp-lg': '16px',
                'dp-xl': '20px',
            },
            transitionTimingFunction: {
                'dp': 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },
    plugins: [forms, typography],
};
