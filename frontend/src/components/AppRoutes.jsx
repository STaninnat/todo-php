import React from 'react';
import { Routes, Route, Navigate, useParams } from 'react-router-dom';
import SignUp from '../pages/SignUp';
import SignIn from '../pages/SignIn';
import TodoPage from '../pages/TodoPage';
import Header from './Header'; // Restored
import { useAuth } from '../hooks/useAuth'; // Restored
import LoadingSpinner from './LoadingSpinner';

/**
 * Helper component to ensure the logged-in user matches the URL username parameter.
 * Redirects to the correct username URL if there is a mismatch.
 */
const AuthenticatedTodoPage = () => {
    const { user } = useAuth();
    const { username } = useParams();

    if (!user) {
        return <Navigate to="/" replace />;
    }

    if (user.username !== username) {
        return <Navigate to={`/${user.username}`} replace />;
    }

    return <TodoPage />;
};

/**
 * Main application routing component.
 * Handles:
 * - Public routes (SignUp, SignIn, Guest TodoPage)
 * - Private routes (Authenticated TodoPage)
 * - Redirections based on authentication state
 */
export default function AppRoutes() {
    const { user, isLoading } = useAuth();

    if (isLoading) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
                <LoadingSpinner size="large" />
            </div>
        );
    }

    return (
        <>
            <Header />
            <Routes>
                <Route
                    path="/signup"
                    element={user ? <Navigate to={`/${user.username}`} replace /> : <SignUp />}
                />
                <Route
                    path="/signin"
                    element={user ? <Navigate to={`/${user.username}`} replace /> : <SignIn />}
                />
                <Route
                    path="/"
                    element={user ? <Navigate to={`/${user.username}`} replace /> : <TodoPage />}
                />
                <Route
                    path="/:username"
                    element={user ? <AuthenticatedTodoPage /> : <Navigate to="/" replace />}
                />
            </Routes>
        </>
    );
}
