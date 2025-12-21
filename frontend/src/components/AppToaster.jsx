import React from 'react';
import { Toaster } from 'react-hot-toast';

export function AppToaster() {
    return (
        <Toaster 
            position="bottom-right"
            toastOptions={{
                style: {
                    background: 'var(--bg-card)',
                    color: 'var(--text-primary)',
                    border: '1px solid var(--border-color)',
                    padding: '12px',
                    fontSize: '0.9rem',
                    borderRadius: '12px',
                    boxShadow: 'var(--shadow)',
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
