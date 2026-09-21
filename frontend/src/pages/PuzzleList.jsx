import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api.js';
import { useAuth } from '../auth/AuthContext.jsx';
import { colorFor, iconFor } from '../utils/brandColor.js';

export default function PuzzleList() {
  const { isAdmin } = useAuth();
  const navigate = useNavigate();
  const [puzzles, setPuzzles] = useState(null);
  const [error, setError] = useState(null);

  const load = () => {
    api.get('/puzzles')
      .then((res) => setPuzzles(res.data))
      .catch(() => setError('Could not load puzzles. Is the backend running?'));
  };

  useEffect(load, []);

  const handleDelete = async (id, title) => {
    if (!window.confirm(`Delete "${title}"? This can't be undone.`)) return;
    await api.delete(`/puzzles/${id}`);
    load();
  };

  if (error) return <p className="error">{error}</p>;
  if (!puzzles) return <p>Loading…</p>;
  if (puzzles.length === 0) {
    return (
      <div className="empty-state">
        <p>No puzzles yet — time to build the first one.</p>
        {isAdmin && <Link to="/create">Create the first one</Link>}
      </div>
    );
  }

  const byCategory = puzzles.reduce((acc, p) => {
    const cat = p.category || 'Uncategorized';
    (acc[cat] ||= []).push(p);
    return acc;
  }, {});

  return (
    <div className="puzzle-list">
      {Object.entries(byCategory).map(([category, items]) => (
        <section key={category}>
          <h2>{category}</h2>
          <div className="puzzle-cards">
            {items.map((p) => {
              const color = colorFor(category);
              return (
                <div key={p.id} className="puzzle-card" style={{ background: color.bg }}>
                  <div className="tile-body" style={{ color: color.on }} onClick={() => navigate(`/play/${p.id}`)}>
                    <span className="tile-icon">{iconFor(category)}</span>
                    <h3 className="tile-title">{p.title}</h3>
                    <p className="tile-meta">{p.rows} × {p.cols} grid</p>
                  </div>
                  {isAdmin && (
                    <div className="admin-card-actions">
                      <Link to={`/edit/${p.id}`}>Edit</Link>
                      <button className="link-button" onClick={() => handleDelete(p.id, p.title)}>Delete</button>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </section>
      ))}
    </div>
  );
}
