import { Routes, Route, Link, useNavigate } from 'react-router-dom';
import PuzzleList from './pages/PuzzleList.jsx';
import PuzzleCreate from './pages/PuzzleCreate.jsx';
import PuzzlePlay from './pages/PuzzlePlay.jsx';
import Login from './pages/Login.jsx';
import RequireAdmin from './auth/RequireAdmin.jsx';
import { useAuth } from './auth/AuthContext.jsx';

export default function App() {
  const { user, isAdmin, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/');
  };

  return (
    <div className="app-shell">
      <header className="app-header">
        <Link to="/" className="brand">🧩 Payroll Puzzle</Link>
        <nav className="header-nav">
          {isAdmin && <Link to="/create">+ New Puzzle</Link>}
          {isAdmin ? (
            <>
              <span className="admin-badge">Admin: {user.name}</span>
              <button className="link-button" onClick={handleLogout}>Log out</button>
            </>
          ) : (
            <Link to="/login">Admin login</Link>
          )}
        </nav>
      </header>
      <main>
        <Routes>
          <Route path="/" element={<PuzzleList />} />
          <Route path="/login" element={<Login />} />
          <Route path="/play/:puzzleId" element={<PuzzlePlay />} />
          <Route
            path="/create"
            element={<RequireAdmin><PuzzleCreate /></RequireAdmin>}
          />
          <Route
            path="/edit/:puzzleId"
            element={<RequireAdmin><PuzzleCreate /></RequireAdmin>}
          />
        </Routes>
      </main>
    </div>
  );
}
