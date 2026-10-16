import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import { VitePWA } from "vite-plugin-pwa";
import path from "path";
// import path from "path";

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    // Offline app shell. Registration is manual (see src/lib/pwa.ts) so an update can
    // be held back while a POS session is open; the plugin still injects the manifest
    // and generates the service worker at build time.
    VitePWA({
      registerType: "prompt",
      injectRegister: null,
      includeAssets: ["afroduuka.png"],
      manifest: {
        name: "AfroDuuka",
        short_name: "AfroDuuka",
        description:
          "Inventory management for African businesses — sales, stock, suppliers and branches in one view.",
        lang: "en",
        theme_color: "#2f7d4f",
        background_color: "#0b0b0f",
        display: "standalone",
        start_url: "/",
        scope: "/",
        icons: [
          {
            src: "/afroduuka.png",
            sizes: "256x256",
            type: "image/png",
            purpose: "any",
          },
          {
            src: "/afroduuka.png",
            sizes: "256x256",
            type: "image/png",
            purpose: "maskable",
          },
        ],
      },
      workbox: {
        // Precache the built shell and static assets only. API traffic is never
        // matched here — authenticated responses and mutation requests must not be
        // cached by the service worker (offline.md §2).
        globPatterns: ["**/*.{js,css,html,png,svg,ico,woff,woff2}"],
        navigateFallback: "/index.html",
        navigateFallbackDenylist: [/^\/api\//],
        runtimeCaching: [],
        cleanupOutdatedCaches: true,
      },
      devOptions: { enabled: false },
    }),
  ],
  server: {
    host: "0.0.0.0",
    port: 5173,
    // Only localhost by default (allowedHosts: true lets ANY host reach the dev
    // server, which enables DNS-rebinding attacks). Add extra dev hosts — e.g. a
    // LAN IP or tunnel domain — via VITE_ALLOWED_HOSTS=192.168.1.14,.trycloudflare.com
    allowedHosts: [
      "localhost",
      "127.0.0.1",
      ...(process.env.VITE_ALLOWED_HOSTS ?? "")
        .split(",")
        .map((host) => host.trim())
        .filter(Boolean),
    ],
    hmr: {
      // This forces Vite's WebSocket to connect through the Nginx proxy
      clientPort: 80,
    },
    watch: {
      // Required for file changes to be detected inside Docker volumes on some OS types
      usePolling: true,
    },
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
});
