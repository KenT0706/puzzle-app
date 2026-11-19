import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import api from '../api.js';
import { colorFor } from '../utils/brandColor.js';

const ago = (iso) => {
  if (!iso) return '—';
  const s = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
  if (s < 10) return 'just now';
  if (s < 60) return `${s}s ago`;
  if (s < 3600) return `${Math.floor(s / 60)}m ago`;
  return `${Math.floor(s / 3600)}h ago`;
};

// Host-only live view: who has joined, how far they are, whether they're still active.
export default function Dashboard() {
  const [params, setParams] = useSearchParams();
  const puzzleId = params.get('puzzle') || '';
  const [puzzles, setPuzzles] = useState([]);
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    api.get('/puzzles').then((r) => setPuzzles(r.data)).catch(() => {});
  }, []);

  useEffect(() => {
    let alive = true;
    const load = () =>
      api
        .get('/dashboard', { params: puzzleId ? { puzzle_id: puzzleId } : {} })
        .then((r) => {
          if (alive) {
            setData(r.data);
            setError(null);
          }
        })
        .catch(() => alive && setError('Could not load the dashboard.'));
    load();
    const t = setInterval(load, 3000);
    return () => {
      alive = false;
      clearInterval(t);
    };
  }, [puzzleId]);

  return (
    <div className="dashboard">
      <div className="dash-head">
        <h1>Live dashboard</h1>
        <select
          value={puzzleId}
          onChange={(e) => setParams(e.target.value ? { puzzle: e.target.value } : {})}
        >
          <option value="">All puzzles</option>
          {puzzles.map((p) => (
            <option key={p.id} value={p.id}>{p.title}</option>
          ))}
        </select>
      </div>

      {error && <p className="error">{error}</p>}
      {!data ? (
        <p>Loading…</p>
      ) : (
        <>
          <div className="dash-stats">
            <div className="stat"><strong>{data.online_now}</strong><span>online now</span></div>
            <div className="stat"><strong>{data.sessions.length}</strong><span>sessions (last 12h)</span></div>
            <div className="stat">
              <strong>{data.sessions.filter((s) => s.status === 'completed').length}</strong>
              <span>completed</span>
            </div>
          </div>

          {data.sessions.length === 0 && (
            <div className="empty-state">
              <p>No one has joined yet. Share a puzzle’s QR code to get started.</p>
            </div>
          )}

          <div className="dash-sessions">
            {data.sessions.map((s) => {
              const pct = s.total ? Math.round((s.filled / s.total) * 100) : 0;
              return (
                <div key={s.id} className="dash-card">
                  <div className="dash-card-top">
                    <div>
                      <h3>{s.puzzle_title}</h3>
                      <span className={`badge ${s.mode}`}>
                        {s.mode === 'group' ? `Group · PIN ${s.join_code}` : 'Solo'}
                      </span>
                    </div>
                    <span className={`status ${s.status}`}>
                      {s.status === 'completed' ? '✅ Completed' : 'In progress'}
                    </span>
                  </div>
                  <div className="progress"><div className="progress-bar" style={{ width: `${pct}%` }} /></div>
                  <p className="dash-meta">
                    ⭐ {s.score} pts · {s.filled}/{s.total} cells filled ({pct}%) · last activity {ago(s.updated_at)}
                  </p>
                  <div className="participant-chips">
                    {s.participants.map((p) => {
                      const c = colorFor(p.name);
                      return (
                        <span key={p.id} className={`participant-chip ${p.active ? '' : 'idle'}`}>
                          <span className="avatar-dot" style={{ background: c.bg, color: c.on }}>
                            {p.name.slice(0, 1).toUpperCase()}
                          </span>
                          {p.name}
                          <span className={`dot ${p.active ? 'on' : ''}`} />
                        </span>
                      );
                    })}
                  </div>
                </div>
              );
            })}
          </div>
        </>
      )}
    </div>
  );
}
