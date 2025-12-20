import React from 'react';
import { BrowserRouter } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { ReactLenis } from 'lenis/react';
import AppRoutes from './components/AppRoutes';

export function App() {
    return (
        <ReactLenis root>
            <BrowserRouter>
                <Toaster position="bottom-right" />
                <AppRoutes />
            </BrowserRouter>
        </ReactLenis>
    );
}

export default App;
