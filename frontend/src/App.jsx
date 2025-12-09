import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import SignUp from './pages/SignUp';
import SignIn from './pages/SignIn';
import TodoPage from './pages/TodoPage';
import Header from './components/Header';

export function App() {
    return (
        <BrowserRouter>
            <Header />
            <Routes>
                <Route path="/signup" element={<SignUp />} />
                <Route path="/signin" element={<SignIn />} />
                <Route path="/" element={<TodoPage />} />
            </Routes>
        </BrowserRouter>
    );
}

export default App;
