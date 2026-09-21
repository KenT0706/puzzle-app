import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../api.js';
import CrosswordGrid from '../components/CrosswordGrid.jsx';
import Celebration from '../components/Celebration.jsx';
import { colorFor } from '../utils/brandColor.js';

export default function PuzzlePlay() {
  const { puzzleId } = useParams();
  const [puzzle, setPuzzle] = useState(null);
  const [session, setSession] = useState(null);
  const [name, setName] = useState('');
  const [joinCode, setJoinCode] = useState('');
  const [setupMode, setSetupMode] = useState(null); // 'individual' | 'group-host' | 'group-join'
  const [error, setError] = useState(null);
  const pollRef = useRef(null);

  useEffect(() => {
    api.get(`/puzzles/${puzzleId}`).then((res) => setPuzzle(res.data));
  }, [puzzleId]);

  useEffect(() => {
    if (!session || session.mode !== 'group') return;
    pollRef.current = setInterval(() => {
      api.get(`/sessions/${session.id}`).then((res) => setSession((prev) => ({ ...res.data, you: prev.you })));
    }, 1800);
    return () => clearInterval(pollRef.current);
  }, [session?.id, session?.mode]);

  const startIndividual = async () => {
    const res = await api.post(`/puzzles/${puzzleId}/sessions`, { mode: 'individual', name });
    setSession(res.data);
  };

  const startGroup = async () => {
    const res = await api.post(`/puzzles/${puzzleId}/sessions`, { mode: 'group', name });
    setSession(res.data);
  };

  const joinGroup = async () => {
    try {
      const res = await api.post('/sessions/join', { join_code: joinCode.trim(), name });
      setSession(res.data);
    } catch {
      setError('Could not find a group with that code.');
    }
  };

  const handleCellChange = async (row, col, letter) => {
    setSession((prev) => ({
      ...prev,
      grid_state: { ...prev.grid_state, [`${row}-${col}`]: letter || undefined },
    }));
    await api.patch(`/sessions/${session.id}/cell`, { row, col, letter, participant_id: session.you });
  };

  const handleCheck = async () => {
    const res = await api.post(`/sessions/${session.id}/check`);
    setSession((prev) => ({ ...res.data, you: prev.you }));
  };

  if (!puzzle) return <p>Loading puzzle…</p>;

  if (!session) {
    return (
      <div className="setup-screen">
        <h1>{puzzle.title}</h1>

        {!setupMode && (
          <div className="mode-choices">
            <button className="mode-card solo" onClick={() => setSetupMode('individual')}>
              <span className="mode-icon">🙋</span>Play solo
            </button>
            <button className="mode-card host" onClick={() => setSetupMode('group-host')}>
              <span className="mode-icon">🎉</span>Start a group
            </button>
            <button className="mode-card join" onClick={() => setSetupMode('group-join')}>
              <span className="mode-icon">🔑</span>Join a group
            </button>
          </div>
        )}

        {setupMode && (
          <div className="setup-form">
            <input placeholder="Your name" value={name} onChange={(e) => setName(e.target.value)} />
            {setupMode === 'group-join' && (
              <input
                placeholder="Join code"
                value={joinCode}
                onChange={(e) => setJoinCode(e.target.value)}
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
              Let's go
            </button>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className="play-screen">
      <Celebration trigger={session.status === 'completed'} />

      <div className="play-header">
        <h1>{puzzle.title}</h1>
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

      {session.status === 'completed' && (
        <div className="celebration-banner">🎉 Solved it — nice work!</div>
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

      <button className="btn-pill green check-button" onClick={handleCheck}>Check answers</button>
    </div>
  );
}
