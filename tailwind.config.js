/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
    "./public/**/*.html",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        serif: ['Fraunces', 'Noto Sans Ethiopic', 'serif'],
        sans: ['Plus Jakarta Sans', 'Inter', 'Noto Sans Ethiopic', 'sans-serif'],
      },
      colors: {
        meash: {
          navy: '#0F172A',
          card: '#1E293B',
          cyan: '#06B6D4',
          blue: '#0284C7',
          emerald: '#10B981',
        },
        navy: {
          DEFAULT: '#0B1220',
          800: '#111B2E',
          700: '#1B2A45',
        },
        teal: {
          DEFAULT: '#0E7C7B',
          dark: '#0A5F5E',
          light: '#E3F3F2',
        },
        gold: {
          DEFAULT: '#B8912F',
          light: '#F6EFDD',
        },
        fog: '#F6F7F9',
      },
    },
  },
  plugins: [],
}
