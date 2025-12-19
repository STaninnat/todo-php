import React from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { CheckSquare, LogOut, LogIn } from 'lucide-react';
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

    if (isAuthPage) return null;

    return (
        <header className="header">
            <Link to={user ? `/${user.username}` : '/'} className="header-logo">
                <CheckSquare size={24} strokeWidth={2.5} />
                Todo App
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
