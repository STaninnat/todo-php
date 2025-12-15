import { serve } from 'bun';
import index from './index.html';

const server = serve({
    routes: {
        // Proxy API requests to backend
        '/v1/*': async (req) => {
            const url = new URL(req.url);
            const targetUrl = `http://localhost:8085${url.pathname}${url.search}`;

            return fetch(targetUrl, {
                method: req.method,
                headers: req.headers,
                body: req.body,
            });
        },

        // Serve index.html for all unmatched routes.
        '/*': index,
    },

    development: process.env.NODE_ENV !== 'production' && {
        // Enable browser hot reloading in development
        hmr: true,

        // Echo console logs from the browser to the server
        console: true,
    },
});

console.log(`🚀 Server running at ${server.url}`);
