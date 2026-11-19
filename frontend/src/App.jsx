import { Routes, Route, Link, useNavigate } from 'react-router-dom';
import PuzzleList from './pages/PuzzleList.jsx';
import PuzzleCreate from './pages/PuzzleCreate.jsx';
import PuzzlePlay from './pages/PuzzlePlay.jsx';
import Login from './pages/Login.jsx';
import Landing from './pages/Landing.jsx';
import Dashboard from './pages/Dashboard.jsx';
import Leaderboard from './pages/Leaderboard.jsx';
import RequireAdmin from './auth/RequireAdmin.jsx';
import { useAuth } from './auth/AuthContext.jsx';
import './extras.css';

export default function App() {
  const { user, isAdmin, ready, logout } = useAuth();
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
          {isAdmin && (
            <>
              <Link to="/dashboard">Dashboard</Link>
              <Link to="/create">+ New Puzzle</Link>
              <span className="admin-badge">Host: {user.name}</span>
              <button className="link-button" onClick={handleLogout}>Log out</button>
            </>
          )}
        </nav>
      </header>
      <main>
        <Routes>
          <Route
            path="/"
            element={!ready ? <p>Loading…</p> : isAdmin ? <PuzzleList /> : <Landing />}
          />
          <Route path="/login" element={<Login />} />
          <Route path="/p/:code" element={<PuzzlePlay />} />
          <Route path="/p/:code/leaderboard" element={<Leaderboard />} />
          <Route path="/dashboard" element={<RequireAdmin><Dashboard /></RequireAdmin>} />
          <Route path="/create" element={<RequireAdmin><PuzzleCreate /></RequireAdmin>} />
          <Route path="/edit/:puzzleId" element={<RequireAdmin><PuzzleCreate /></RequireAdmin>} />
        </Routes>
      </main>
    </div>
  );
}
