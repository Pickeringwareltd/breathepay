/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue"
  ],
  theme: {
    extend: {
      fontSize: {
        xxs: '0.6rem'
      },
      colors: {
        ydrgreen: '#3c8b43',
        ydrblack: '#231e20',
        ydrgray: '#F7F7F7',
        ydrdarkgray: '#e4e4e4',
        lightgray: '#d3d3d3'
      },
      fontFamily: {
        ydr: ["rigid-square", 'sans-serif'],
      }
    },
  },
  plugins: [
    require("tailwindcss-inner-border"),
    require("@tailwindcss/forms")
  ],
}
