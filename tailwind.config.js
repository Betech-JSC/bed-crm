import defaultTheme from 'tailwindcss/defaultTheme'

export default {
  content: ['./resources/**/*.{js,vue,blade.php}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#fef5f0',
          100: '#fde8dc',
          200: '#fbd0b8',
          300: '#f8b089',
          400: '#f48554',
          500: '#ef6820',
          600: '#e04f0f',
          700: '#ba3d0f',
          800: '#943214',
          900: '#782b13',
        },
        secondary: {
          DEFAULT: '#000000',
          50: '#f6f6f6',
          100: '#e7e7e7',
          200: '#d1d1d1',
          300: '#b0b0b0',
          400: '#888888',
          500: '#6d6d6d',
          600: '#5d5d5d',
          700: '#4f4f4f',
          800: '#454545',
          900: '#3d3d3d',
        },
        indigo: {
          100: '#e6e8ff',
          300: '#b2b7ff',
          400: '#7886d7',
          500: '#6574cd',
          600: '#5661b3',
          800: '#2f365f',
          900: '#191e38',
        },
      },
      fontFamily: {
        sans: ['"Cerebri Sans"', ...defaultTheme.fontFamily.sans],
      },
    },
  },
}
