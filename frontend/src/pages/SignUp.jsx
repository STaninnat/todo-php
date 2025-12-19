import React from 'react';
import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import './Auth.css';
import { validateEmail, validatePassword, validateConfirmPassword } from '../utils/validation';
import { useAuth } from '../hooks/useAuth';
import Button from '../components/Button';

/**
 * Sign Up Page Component.
 * Handles existing user registration with validation for email and password complexity.
 * Redirects to home page upon successful registration (auto-login).
 */
export default function SignUp() {
    const navigate = useNavigate();
    const { register } = useAuth();

    // Initialize state
    const [formData, setFormData] = useState({
        username: '',
        email: '',
        password: '',
        confirmPassword: '',
    });
    const [error, setError] = useState('');

    // Handle input changes
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: value,
        }));
        setError('');
    };

    // Handle form submission
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateEmail(formData.email)) {
            setError('Please enter a valid email address.');
            return;
        }

        if (!validatePassword(formData.password)) {
            setError(
                'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.'
            );
            return;
        }

        if (!validateConfirmPassword(formData.password, formData.confirmPassword)) {
            setError('Passwords do not match.');
            return;
        }

        setError('');

        try {
            const data = await register({
                username: formData.username,
                email: formData.email,
                password: formData.password,
            });
            // Auto-login: Redirect directly to Home
            const user = data?.user || data?.data?.user;
            if (user?.username) {
                navigate(`/${user.username}`);
            } else {
                navigate('/');
            }
        } catch (err) {
            // Error Handling Logic:
            // 4xx (Client Error) -> Show specific message (e.g. "Email taken")
            // 5xx (Server Error) or Network Error -> Show generic message
            if (err.status && err.status >= 400 && err.status < 500) {
                setError(err.message);
            } else {
                setError('Something went wrong. Please try again later.');
            }
        }
    };

    return (
        <div className="auth-container">
            <h2>Sign Up</h2>
            <form onSubmit={handleSubmit}>
                <div>
                    <label htmlFor="username">Username:</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value={formData.username}
                        onChange={handleChange}
                        required
                    />
                </div>
                <div>
                    <label htmlFor="email">Email:</label>
                    <input
                        type="email"
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
                <div>
                    <label htmlFor="confirmPassword">Confirm Password:</label>
                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirmPassword"
                        value={formData.confirmPassword}
                        onChange={handleChange}
                        required
                    />
                </div>
                {error && <div className="auth-error">{error}</div>}

                <Button type="submit" className="btn-auth-submit">Sign Up</Button>
            </form>
            <p>
                Already have an account? <Link to="/signin">Sign In</Link>
            </p>
        </div>
    );
}
