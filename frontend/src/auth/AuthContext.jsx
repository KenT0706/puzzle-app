import { createContext, useContext, useEffect, useState } from 'react';
import api from '../api.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);   // { id, name, email, is_admin }
  const [ready, setReady] = useState(false); // finished the initial "who am I" check

  useEffect(() => {
    const token = localStorage.getItem('puzzle_app_token');
    if (!token) {
      setReady(true);
      return;
    }
    api.get('/me')
      .then((res) => setUser(res.data))
      .catch(() => localStorage.removeItem('puzzle_app_token'))
      .finally(() => setReady(true));
  }, []);

  const login = async (email, password) => {
    const res = await api.post('/login', { email, password });
    localStorage.setItem('puzzle_app_token', res.data.token);
    setUser(res.data.user);
    return res.data.user;
  };

  const logout = async () => {
    try { await api.post('/logout'); } catch { /* token may already be gone */ }
    localStorage.removeItem('puzzle_app_token');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, isAdmin: !!user?.is_admin, ready, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
