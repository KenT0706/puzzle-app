import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api.js';
import { colorFor, iconFor } from '../utils/brandColor.js';
import ShareModal from '../components/ShareModal.jsx';

// Host-only home screen: every puzzle, with QR/link, leaderboard, open/close, edit, delete.
export default function PuzzleList() {
  const navigate = useNavigate();
  const [puzzles, setPuzzles] = useState(null);
  const [error, setError] = useState(null);
  const [sharing, setSharing] = useState(null);

  const load = () => {
    api
      .get('/puzzles')
      .then((res) => setPuzzles(res.data))
      .catch(() => setError('Could not load puzzles. Is the backend running?'));
  };

  useEffect(load, []);

  const handleDelete = async (id, title) => {
    if (!window.confirm(`Delete "${title}"? This can't be undone.`)) return;
    await api.delete(`/puzzles/${id}`);
    load();
  };

  const toggleOpen = async (p) => {
    await api.patch(`/puzzles/${p.id}/publish`, { is_published: !p.is_published });
    load();
  };

  if (error) return <p className="error">{error}</p>;
  if (!puzzles) return <p>Loading…</p>;
  if (puzzles.length === 0) {
    return (
      <div className="empty-state">
        <p>No puzzles yet. Time to build the first one.</p>
        <Link to="/create">Create the first one</Link>
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
                <div
                  key={p.id}
                  className="puzzle-card"
                  style={{ background: color.bg, opacity: p.is_published ? 1 : 0.7 }}
                >
                  <div
                    className="tile-body"
                    style={{ color: color.on }}
                    onClick={() => navigate(`/p/${p.share_code}`)}
                  >
                    <span className="tile-icon">{iconFor(category)}</span>
                    <h3 className="tile-title">{p.title}</h3>
                    <p className="tile-meta">
                      {p.rows} × {p.cols} grid · {p.is_published ? 'Open' : 'Closed'}
                    </p>
                  </div>
                  <div className="admin-card-actions">
                    <button className="link-button" onClick={() => setSharing(p)}>QR / Link</button>
                    <Link to={`/p/${p.share_code}/leaderboard`} target="_blank">Leaderboard</Link>
                    <button className="link-button" onClick={() => toggleOpen(p)}>
                      {p.is_published ? 'Close' : 'Open'}
                    </button>
                    <Link to={`/edit/${p.id}`}>Edit</Link>
                    <button className="link-button" onClick={() => handleDelete(p.id, p.title)}>Delete</button>
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      ))}
      {sharing && <ShareModal puzzle={sharing} onClose={() => setSharing(null)} />}
    </div>
  );
}
