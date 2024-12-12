import type { Config } from "tailwindcss";
import defaultTheme  from "tailwindcss/defaultTheme";
import flowbitePlugin from "flowbite/plugin";
export default {
  content: ["./src/**/*.{html,js,svelte,ts}","'node_modules/flowbite-svelte/**/*.{html,js,svelte,ts}'"],
  darkMode: "selector",
  theme: {
    extend: {
      fontFamily: {
        sans: ["Roboto", ...defaultTheme.fontFamily.sans]
      },

      colors: {
        primary: '#3498db',
        secondary: '#f1c40f',
        background: '#2c3e50',
        text: '#ecf0f1',
      },
    }
  },
  plugins: [flowbitePlugin],
} as Config;
