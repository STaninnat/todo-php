import React from 'react';
import { useState } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import './Auth.css';
import { validateEmail } from '../utils/validation';
import { api } from '../services/api';

/**
 * Sign In Page Component.
 * Handles user authentication via email/password.
 * Redirects to home page upon successful login.
 */
export default function SignIn() {
    const navigate = useNavigate();
    const location = useLocation();
    
    // Check for flash message from registration
    const [successMessage, setSuccessMessage] = useState(location.state?.successMessage || '');

    const [formData, setFormData] = useState({
        email: '',
        password: '',
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

        if (!validateEmail(formData.email)) {
            setError('Please enter a valid email address.');
            return;
        }

        setError('');
        setSuccessMessage(''); // Clear success msg on submit attempt

        try {
            await api.login({
                email: formData.email,
                password: formData.password,
            });
            // Redirect to Home/Todo Page
            navigate('/');
        } catch (err) {
            // Safety Filter: 4xx vs 5xx
            if (err.status && err.status >= 400 && err.status < 500) {
                setError('Invalid email or password.'); // Generic 400 for login security
            } else {
                setError('Something went wrong. Please try again later.');
            }
        }
    };

    return (
        <div className="auth-container">
            <h2>Sign In</h2>
            {successMessage && <div className="auth-success">{successMessage}</div>}
            <form onSubmit={handleSubmit}>
                <div>
                    <label htmlFor="email">Email:</label>
                    <input
                        type="text"
                        id="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        required
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
                    />
                </div>
                {error && <div className="auth-error">{error}</div>}

                <button type="submit">Sign In</button>
            </form>
            <p>
                New here? <Link to="/signup">Sign Up</Link>
            </p>
        </div>
    );
}
