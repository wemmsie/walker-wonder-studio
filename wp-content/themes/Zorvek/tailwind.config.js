/** @type {import('tailwindcss').Config} */

const fs = require('fs');
const path = require('path');

let colors = {};

try {
  colors = JSON.parse(fs.readFileSync(path.resolve(__dirname, 'assets/tailwind-colors.json')));
} catch (err) {
  console.log("Error reading tailwind-colors.json:", err);
}
module.exports = {
  content: [
    './src/**/*.{js}', // Add paths to your content files
    './public/index.html',
    './**/*.php', // Include all PHP files
  ],
  safelist: [
    ...Object.keys(colors).map((k) => `fill-${k}`),
    {
      pattern: /^bg-/,
    },
    {
      pattern: /^text-/,
    },
  ],
  purge: {
    content: ['./**/*.php', './src/**/*.{html,js,jsx,ts,tsx}'],
  },
  theme: {
    extend: {
      boxShadow: {
        1: 'rgba(0, 0, 0, 0.07) 0px 3px 8px;',
        2: 'rgba(0, 0, 0, 0.08) 0px 5px 12px;',
      },
      fill: {
        ...colors, // gives fill-primary, fill-secondary, etc.
      },
      spacing: {
        post: '25px',
        sub: '10px',
        '1/10': '10%',
        '1/8': '12.5%',
        '1/4': '25%',
        '1/3': '33%',
        '2/3': '66%',
        '4/5': '80%',
        '3/1': '300%',
        '2/1': '200%',
        '3/2': '150%',
      },

      maxWidth: {
        100: '100px',
        160: '160px',
        180: '180px',
        200: '200px',
        300: '300px',
        screen: '1400px',
        subscreen: '1200px',
        resource: '900px',
      },
      maxHeight: {
        100: '100px',
        160: '160px',
        180: '180px',
        200: '200px',
        300: '300px',
      },

      borderWidth: {
        tip: '10px', // Custom border width
      },

      borderRadius: {
        xxl: '1em', // Custom border width
      },

      colors: {
        ...colors,
        gray: {
          1: '#E8E5EB',
        },
      },

      fontSize: {
        8: '0.8rem',
        9: '0.9rem',
        10: '1rem',
        12: '1.2rem',
        13: '1.3rem',
        14: '1.4rem',
        15: '1.5rem',
        16: '1.563rem',
        17: '1.7rem',
        18: '1.8rem',
        20: '2.0rem',
        21: '2.1rem',
        22: '2.2rem',
        23: '2.3rem',
        24: '2.4rem',
        25: '2.5rem',
        27: '2.7rem',
        30: '3rem',
        36: '3.6rem',
        40: '4rem',
        44: '4.4rem',
        45: '4.5rem',
        50: '5rem',
        51: '5.1rem',
        60: '6rem',
        70: '7rem',
        95: '9.5rem',
      },
    },
    plugins: [require('@tailwindcss/aspect-ratio')],
    screens: {
      xs: '350px',
      ss: '420px',
      sm: '600px',
      mm: '800px',
      md: '1200px',
      lg: '1400px',
      tbt: '1250px',
      xl: '1440px',
      xxl: '1920px',
    },

    fontFamily: {
      poppins: ['poppins', 'sans-serif'],
      futura: ['futura', 'sans-serif'],
      aktiv: ['aktiv-grotesk-extended', 'sans-serif'],
      awesome: ['Font Awesome 5 Free'],
    },
  },
  plugins: [],
};
