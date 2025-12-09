import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { api } from '../services/api';
import { CheckSquare, LogOut, LogIn } from 'lucide-react';
import './Header.css';

export default function Header() {
    const [user, setUser] = useState(null);
    const navigate = useNavigate();
    const location = useLocation();

    // Check auth status on mount and when location changes (e.g. after login redirect)
    useEffect(() => {
        const checkAuth = async () => {
            try {
                const userData = await api.me();
                if (userData && userData.user) {
                    setUser(userData.user);
                }
            } catch (err) {
                if (process.env.NODE_ENV !== 'production') {
                    console.error('Failed to check auth status', err);
                }
                setUser(null);
            }
        };
        checkAuth();
    }, [location.pathname]); // Re-check when route changes

    const handleLogout = async () => {
        try {
            await api.logout();
            setUser(null);
            navigate('/signin'); // Or stay on home? User said "disappear when machine shut down"
            window.location.reload(); // Hard reload to clear any local state/cache just in case
        } catch (err) {
            if (process.env.NODE_ENV !== 'production') {
                console.error('Logout failed', err);
            }
        }
    };

    // Don't show header on auth pages
    const isAuthPage = ['/signin', '/signup'].includes(location.pathname);

    if (isAuthPage) return null;

    return (
        <header className="header">
            <Link to="/" className="header-logo">
                <CheckSquare size={24} strokeWidth={2.5} />
                Todo App
            </Link>

            <div className="header-actions">
                    {user ? (
                        <>
                            <span className="user-welcome">Hi, {user.username}</span>
                            <button onClick={handleLogout} className="btn-auth btn-logout">
                                <LogOut size={16} style={{ marginRight: '6px' }} />
                                Log Out
                            </button>
                        </>
                    ) : (
                        <>
                            {location.pathname !== '/signin' && (
                                <Link to="/signin" className="btn-auth btn-signin">
                                    <LogIn size={16} style={{ marginRight: '6px' }} />
                                    Sign In
                                </Link>
                            )}
                        </>
                    )}
            </div>
        </header>
    );
}
