import React from 'react';
import { Toaster } from 'react-hot-toast';

export function AppToaster() {
    return (
        <Toaster 
            position="bottom-right"
            containerClassName="responsive-toaster"
            toastOptions={{
                className: 'app-toast',
                style: {
                    background: 'var(--bg-card)',
                    color: 'var(--text-primary)',
                    border: '1px solid var(--border-color)',
                    padding: '8px 12px',
                    fontSize: '0.8rem',
                    borderRadius: '10px',
                    boxShadow: 'var(--shadow)',
                    gap: '6px',
                },
                success: {
                    iconTheme: {
                        primary: '#16a34a',
                        secondary: 'var(--bg-card)',
                    },
                },
                error: {
                    iconTheme: {
                        primary: '#dc2626',
                        secondary: 'var(--bg-card)',
                    },
                },
            }}
        />
    );
}

export default AppToaster;
