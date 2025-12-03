import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import SignUp from './pages/SignUp';

export function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/signup" element={<SignUp />} />
        <Route path="/" element={<h1>Todo App Main Page</h1>} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
