import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from './AuthContext.jsx';

// Wrap any admin-only route element with this. Redirects to /login if not
// an admin, preserving where the user was headed so login can send them back.
export default function RequireAdmin({ children }) {
  const { user, isAdmin, ready } = useAuth();
  const location = useLocation();

  if (!ready) return <p>Loading…</p>;
  if (!user || !isAdmin) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }
  return children;
}
