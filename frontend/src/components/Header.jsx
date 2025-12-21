import React from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { LogOut, LogIn } from 'lucide-react';
import { useLenis } from 'lenis/react';
import Button from './Button';
import './Header.css';

/**
 * Application header component.
 * Displays navigation links and authentication status.
 * Hides itself on authentication pages (Sign In/Sign Up).
 */
export default function Header() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const lenis = useLenis();

    const handleLogout = async () => {
        try {
            await logout();
            navigate('/signin'); 
        } catch (err) {
            if (process.env.NODE_ENV !== 'production') {
                console.error('Logout failed', err);
            }
        }
    };

    // Don't show header on auth pages
    const isAuthPage = ['/signin', '/signup'].includes(location.pathname);

    const handleLogoClick = (e) => {
        const targetPath = user ? `/${user.username}` : '/';
        if (location.pathname === targetPath) {
            e.preventDefault();
            if (lenis) {
                lenis.scrollTo(0);
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    };

    if (isAuthPage) return null;

    return (
        <header className="header">
            <Link 
                to={user ? `/${user.username}` : '/'} 
                className="header-logo"
                onClick={handleLogoClick}
            >
                <picture>
                    <source srcSet="/logo-dark.svg" media="(prefers-color-scheme: dark)" />
                    <img src="/logo-light.svg" alt="Todo Logo" className="logo-icon" />
                </picture>
            </Link>

            <div className="header-actions">
                    {user ? (
                        <>
                            <span className="user-welcome">Hi, {user.username}</span>
                            <Button onClick={handleLogout} variant="text" className="btn-logout">
                                <LogOut size={16} style={{ marginRight: '6px' }} />
                                Log Out
                            </Button>
                        </>
                    ) : (
                        <>
                            {location.pathname !== '/signin' && (
                                <Link to="/signin" className="btn-auth btn-signin">
                                    <LogIn size={16} />
                                    <span>Sign In</span>
                                </Link>
                            )}
                        </>
                    )}
            </div>
        </header>
    );
}
