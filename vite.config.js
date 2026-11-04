import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    server: {
        host: "0.0.0.0",
        port: 5174,
        strictPort: true,
        origin: "http://localhost:5174",

        // Sem isso o laravel-vite-plugin usa o `origin` acima como única origem liberada,
        // e a aplicação em localhost:8080 (nginx) não consegue carregar os assets.
        cors: {
            origin: /^https?:\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?$/,
        },

        hmr: {
            host: "localhost",
        },
    },

    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
