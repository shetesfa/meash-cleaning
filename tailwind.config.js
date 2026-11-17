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
          DEFAULT: '#3E8ECC',
          dark: '#2F6FA8',
          light: '#E8F2FA',
          50: '#EFF7FD',
          100: '#DCEFFA',
          200: '#BCE0F5',
          300: '#8CC8EC',
          400: '#5FA9DD',
          500: '#3E8ECC',
          600: '#2F6FA8',
          700: '#285A87',
          800: '#1F4568',
          900: '#16324B',
          950: '#0D2133',
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
