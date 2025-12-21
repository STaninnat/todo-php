import React, { useState } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import './Auth.css';
import { useAuth } from '../hooks/useAuth';
import Button from '../components/Button';

/**
 * Sign In Page Component.
 * Handles user authentication via username/password.
 * Redirects to home page upon successful login.
 */
export default function SignIn() {
    const navigate = useNavigate();
    const location = useLocation();
    const { login } = useAuth();
    
    // Check for flash message from registration
    const [successMessage, setSuccessMessage] = useState(location.state?.successMessage || '');

    const [rememberMe, setRememberMe] = useState(() => {
        return !!localStorage.getItem('rememberedUsername');
    });

    const [formData, setFormData] = useState(() => {
        const remembered = localStorage.getItem('rememberedUsername');
        return {
            username: remembered || '',
            password: '',
        };
    });
    const [error, setError] = useState('');

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: value,
        }));
        setError('');
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!formData.username.trim()) {
            setError('Please enter your username.');
            return;
        }

        setError('');
        setSuccessMessage(''); // Clear success msg on submit attempt

        if (rememberMe) {
            localStorage.setItem('rememberedUsername', formData.username);
        } else {
            localStorage.removeItem('rememberedUsername');
        }

        try {
            const data = await login({
                username: formData.username,
                password: formData.password,
            });
            
            // Get username to redirect correctly
            const user = data?.user || data?.data?.user;
            if (user?.username) {
                navigate(`/${user.username}`);
            } else {
                navigate('/');
            }
        } catch (err) {
            // Safety Filter: 4xx vs 5xx
            if (err.status && err.status >= 400 && err.status < 500) {
                setError('Invalid username or password.'); // Generic 400 for login security
            } else {
                setError('Something went wrong. Please try again later.');
            }
        }
    };

    return (
        <div className="auth-container">
            <h2>Sign In</h2>
            {successMessage && <div className="auth-success">{successMessage}</div>}
            <form onSubmit={handleSubmit} autoComplete="off">
                <div>
                    <label htmlFor="username">Username:</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value={formData.username}
                        onChange={handleChange}
                        required
                        autoComplete="off"
                    />
                </div>
                <div>
                    <label htmlFor="password">Password:</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        value={formData.password}
                        onChange={handleChange}
                        required
                        autoComplete="new-password"
                    />
                </div>
                
                <div className="remember-me-container">
                    <label className="remember-me-label">
                        <input
                            type="checkbox"
                            checked={rememberMe}
                            onChange={(e) => setRememberMe(e.target.checked)}
                        />
                        Remember me
                    </label>
                </div>

                {error && <div className="auth-error">{error}</div>}

                <Button type="submit" className="btn-auth-submit">Sign In</Button>
            </form>
            <p>
                New here? <Link to="/signup">Sign Up</Link>
            </p>
        </div>
    );
}
