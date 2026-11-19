import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../api.js';
import CrosswordGrid from '../components/CrosswordGrid.jsx';
import Celebration from '../components/Celebration.jsx';
import LeaderboardPanel from '../components/LeaderboardPanel.jsx';
import { colorFor, iconFor } from '../utils/brandColor.js';

export default function PuzzlePlay() {
  const { code } = useParams();
  const [puzzle, setPuzzle] = useState(null);
  const [unavailable, setUnavailable] = useState(false);
  const [session, setSession] = useState(null);
  const [name, setName] = useState('');
  const [joinCode, setJoinCode] = useState('');
  const [setupMode, setSetupMode] = useState(null); // 'individual' | 'group-host' | 'group-join'
  const [error, setError] = useState(null);
  const [toast, setToast] = useState(null);
  const [showBoard, setShowBoard] = useState(false);
  const pollRef = useRef(null);
  const toastTimer = useRef(null);

  useEffect(() => {
    api
      .get(`/puzzles/${code}`)
      .then((res) => setPuzzle(res.data))
      .catch(() => setUnavailable(true));
  }, [code]);

  // Group sessions poll so everyone sees the shared grid and score update.
  // Sending our participant id also tells the host's dashboard we're still here.
  useEffect(() => {
    if (!session || session.mode !== 'group') return;
    pollRef.current = setInterval(() => {
      api
        .get(`/sessions/${session.id}`, { params: { participant: session.you } })
        .then((res) => setSession((prev) => ({ ...res.data, you: prev.you })));
    }, 1800);
    return () => clearInterval(pollRef.current);
  }, [session?.id, session?.mode]);

  const startIndividual = async () => {
    const res = await api.post(`/puzzles/${code}/sessions`, { mode: 'individual', name });
    setSession(res.data);
  };

  const startGroup = async () => {
    const res = await api.post(`/puzzles/${code}/sessions`, { mode: 'group', name });
    setSession(res.data);
  };

  const joinGroup = async () => {
    try {
      const res = await api.post('/sessions/join', {
        join_code: joinCode.trim(),
        name,
        puzzle_code: code,
      });
      setSession(res.data);
    } catch {
      setError('Could not find a group with that PIN for this puzzle.');
    }
  };

  const handleCellChange = async (row, col, letter) => {
    setSession((prev) => ({
      ...prev,
      grid_state: { ...prev.grid_state, [`${row}-${col}`]: letter || undefined },
    }));
    await api.patch(`/sessions/${session.id}/cell`, { row, col, letter, participant_id: session.you });
  };

  const flashToast = (t) => {
    setToast(t);
    clearTimeout(toastTimer.current);
    toastTimer.current = setTimeout(() => setToast(null), 3500);
  };

  const handleCheck = async () => {
    const res = await api.post(`/sessions/${session.id}/check`);
    const { points_awarded, newly_solved, completion_bonus } = res.data;
    setSession((prev) => ({ ...res.data, you: prev.you }));
    flashToast({ points: points_awarded, words: newly_solved || [], bonus: completion_bonus || 0 });
  };

  if (unavailable) {
    return (
      <div className="landing">
        <div className="landing-icon">🔒</div>
        <h1>This puzzle isn’t available</h1>
        <p>The link may be wrong, or your host hasn’t opened it yet. Ask your host for the QR code.</p>
      </div>
    );
  }

  if (!puzzle) return <p>Loading puzzle…</p>;

  if (!session) {
    const topic = puzzle.category || 'Uncategorized';
    const color = colorFor(topic);
    return (
      <div className="setup-screen">
        <div className="topic-hero" style={{ background: color.bg, color: color.on }}>
          <span className="topic-icon">{iconFor(topic)}</span>
          <p className="topic-label">{puzzle.category || 'Puzzle'}</p>
          <h1>{puzzle.title}</h1>
          <p className="topic-sub">{setupMode ? 'Almost there!' : 'How do you want to play?'}</p>
        </div>

        {!setupMode && (
          <div className="mode-choices">
            <button className="mode-card solo" onClick={() => setSetupMode('individual')}>
              <span className="mode-icon">🙋</span>Play solo
              <small>Just you</small>
            </button>
            <button className="mode-card host" onClick={() => setSetupMode('group-host')}>
              <span className="mode-icon">🎉</span>Host a group
              <small>Get a PIN to share with your team</small>
            </button>
            <button className="mode-card join" onClick={() => setSetupMode('group-join')}>
              <span className="mode-icon">🔑</span>Join a group
              <small>Enter the PIN from your host</small>
            </button>
          </div>
        )}

        {setupMode && (
          <>
            <button
              type="button"
              className="back-link"
              onClick={() => {
                setSetupMode(null);
                setError(null);
              }}
            >
              ← Back
            </button>
            <div className="setup-form">
              <input placeholder="Your name" value={name} onChange={(e) => setName(e.target.value)} />
              {setupMode === 'group-join' && (
                <input
                  placeholder="Group PIN"
                  value={joinCode}
                  onChange={(e) => setJoinCode(e.target.value.toUpperCase())}
                />
              )}
              {error && <p className="error">{error}</p>}
              <button
                className="btn-pill"
                disabled={!name || (setupMode === 'group-join' && !joinCode)}
                onClick={
                  setupMode === 'individual' ? startIndividual :
                  setupMode === 'group-host' ? startGroup :
                  joinGroup
                }
              >
                {setupMode === 'individual' ? 'Start puzzle' : setupMode === 'group-host' ? 'Create group' : 'Join group'}
              </button>
            </div>
          </>
        )}
      </div>
    );
  }

  const completed = session.status === 'completed';

  return (
    <div className="play-screen">
      <Celebration trigger={completed} />

      <div className="play-header">
        <div className="play-title-row">
          <h1>{puzzle.title}</h1>
          <span className="score-chip" title={`${puzzle.points_per_word} points for every word you get right`}>
            ⭐ {session.score ?? 0} pts
          </span>
        </div>
        {session.mode === 'group' && (
          <div className="group-info">
            <span className="pin-chip">PIN {session.join_code}</span>
            <div className="participant-chips">
              {session.participants.map((p) => {
                const color = colorFor(p.name);
                return (
                  <span key={p.id} className="participant-chip">
                    <span className="avatar-dot" style={{ background: color.bg, color: color.on }}>
                      {p.name.slice(0, 1).toUpperCase()}
                    </span>
                    {p.name}
                  </span>
                );
              })}
            </div>
          </div>
        )}
      </div>

      {completed && (
        <div className="celebration-banner">🎉 Solved it, nice work! You scored {session.score} points.</div>
      )}

      <div className="play-body">
        <CrosswordGrid
          grid={puzzle.grid}
          values={session.grid_state}
          correctness={session.correct_cells}
          onCellChange={handleCellChange}
        />
        <div className="clue-lists">
          <div className="clue-column across">
            <h3>Across</h3>
            <ul className="clue-list">
              {puzzle.clues.across.map((c) => (
                <li key={c.number}><strong>{c.number})</strong> {c.text}</li>
              ))}
            </ul>
          </div>
          <div className="clue-column down">
            <h3>Down</h3>
            <ul className="clue-list">
              {puzzle.clues.down.map((c) => (
                <li key={c.number}><strong>{c.number})</strong> {c.text}</li>
              ))}
            </ul>
          </div>
        </div>
      </div>

      <div className="play-actions">
        <button className="btn-pill green check-button" onClick={handleCheck}>Check answers</button>
        <button className="btn-pill blue check-button" onClick={() => setShowBoard((v) => !v)}>
          🏆 {showBoard || completed ? 'Hide' : 'Show'} leaderboard
        </button>
      </div>

      {(showBoard || completed) && (
        <section className="play-leaderboard">
          <h2>🏆 Leaderboard</h2>
          <LeaderboardPanel code={code} highlightSessionId={session.id} intervalMs={3000} />
        </section>
      )}

      {toast && (
        <div className={`points-toast ${toast.points > 0 ? '' : 'none'}`}>
          {toast.points > 0 ? (
            <>
              <strong>+{toast.points} pts</strong>
              <span>
                {toast.words.map((w) => `${w.direction === 'across' ? 'Across' : 'Down'} ${w.number}`).join(', ')}
                {toast.words.length > 0 ? ' solved' : ''}
                {toast.bonus > 0 ? ` · puzzle complete bonus +${toast.bonus}` : ''}
              </span>
            </>
          ) : (
            <strong>No new words yet. Keep going!</strong>
          )}
        </div>
      )}
    </div>
  );
}
