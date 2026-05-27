/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/js/**/*.{vue,js,ts}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        dark: {
          900: '#0f1117',
          800: '#1a1d27',
          700: '#252a38',
          600: '#2f3549',
        },
        accent: {
          gold: '#f59e0b',
          emerald: '#10b981',
          ruby: '#ef4444',
        }
      }
    },
  },
  plugins: [],
}
