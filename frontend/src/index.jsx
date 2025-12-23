import { serve, file } from 'bun';
import { join } from 'path';
import index from './index.html';

const server = serve({
    routes: {
        // Proxy API requests to backend
        '/v1/*': async (req) => {
            const url = new URL(req.url);
            const apiUrl = process.env.API_URL || 'http://localhost:8085';
            const targetUrl = `${apiUrl}${url.pathname}${url.search}`;

            return fetch(targetUrl, {
                method: req.method,
                headers: req.headers,
                body: req.body,
            });
        },

        // Serve favicon
        '/favicon.svg': async () => {
            return new Response(file(join(import.meta.dir, '../public/favicon.svg')));
        },
        '/logo-light.svg': async () => {
            return new Response(file(join(import.meta.dir, '../public/logo-light.svg')));
        },
        '/logo-dark.svg': async () => {
            return new Response(file(join(import.meta.dir, '../public/logo-dark.svg')));
        },

        // Serve static files if they exist, otherwise fallback to index.html
        '/*': async (req) => {
            const url = new URL(req.url);
            const filePath = join(import.meta.dir, url.pathname);
            const f = file(filePath);
            
            if (await f.exists()) {
                return new Response(f);
            }
            
            return new Response(index, { headers: { "Content-Type": "text/html" } });
        },
    },

    development: process.env.NODE_ENV !== 'production' && {
        // Enable browser hot reloading in development
        hmr: true,

        // Echo console logs from the browser to the server
        console: true,
    },
});

console.log(`🚀 Server running at ${server.url}`);
