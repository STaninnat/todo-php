import React from 'react';
import { BrowserRouter } from 'react-router-dom';
import { ReactLenis } from 'lenis/react';
import AppRoutes from './components/AppRoutes';
import AppToaster from './components/AppToaster';

export function App() {
    return (
        <ReactLenis root>
            <BrowserRouter>
                <AppToaster />
                <AppRoutes />
            </BrowserRouter>
        </ReactLenis>
    );
}

export default App;
