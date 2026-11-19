import { useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../api.js';
import LeaderboardPanel from '../components/LeaderboardPanel.jsx';
import { useAuth } from '../auth/AuthContext.jsx';

// Open this page on the projector: it refreshes by itself as teams score.
// Players can open the same link to see where they stand.
export default function Leaderboard() {
  const { code } = useParams();
  const { isAdmin } = useAuth();
  const [resetKey, setResetKey] = useState(0);

  const newRound = async () => {
    if (!window.confirm('Clear the leaderboard and start a new round? Players already in the game stop appearing on it.')) return;
    await api.patch(`/puzzles/${code}/new-round`);
    setResetKey((k) => k + 1); // remount so the board reloads immediately
  };

  return (
    <div className="leaderboard-page">
      <LeaderboardPanel key={resetKey} code={code} big showTitle intervalMs={2500} />
      {isAdmin && (
        <div className="leaderboard-admin">
          <button className="btn-pill red" onClick={newRound}>Start a new round</button>
        </div>
      )}
    </div>
  );
}
