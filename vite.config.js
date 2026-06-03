import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from '@tailwindcss/vite'; // Importado correctamente

export default defineConfig({
    // base: '/', // Generalmente no es necesario definirlo en Laravel, el plugin lo maneja
    plugins: [
        tailwindcss(), // 🔥 DEBE IR AQUÍ, antes que laravel y vue
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            // Nota: Si usas Composition API (lo normal en Vue 3), 
            // no sueles necesitar el "vue.esm-bundler.js". 
            // Pero lo dejamos por si usas plantillas Blade con Vue.
            vue: "vue/dist/vue.esm-bundler.js",
            "@": "/resources/js",
        },
    },
    // Eliminé el bloque 'build' manual porque el plugin de Laravel 
    // ya configura 'public/build' por defecto de forma automática.
});