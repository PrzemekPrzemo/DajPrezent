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
                'dp-card': '0 16px 32px -16px rgba(79, 70, 229, 0.18)',
                'dp-card-lg': '0 24px 48px -16px rgba(79, 70, 229, 0.28)',
                'dp-focus': '0 0 0 4px rgba(79, 70, 229, 0.18)',
            },
            borderRadius: {
                'dp': '12px',
                'dp-lg': '20px',
            },
            transitionTimingFunction: {
                'dp': 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },
    plugins: [forms, typography],
};
