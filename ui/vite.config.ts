import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import path from "path";
// import path from "path";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
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
