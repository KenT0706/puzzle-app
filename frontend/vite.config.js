import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Point VITE_API_URL at your Laravel app, e.g. http://localhost:8000/api
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
  },
});
