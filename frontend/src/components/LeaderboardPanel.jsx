import { useEffect, useRef, useState } from 'react';
import api from '../api.js';
import Podium from './Podium.jsx';

// Polls the leaderboard for one puzzle and shows it as a podium.
// Used on the players' screens and on the host's big-screen page.
export default function LeaderboardPanel({
  code,
  highlightSessionId = null,
  intervalMs = 3000,
  big = false,
  showTitle = false,
}) {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [moves, setMoves] = useState({});
  const prevRanks = useRef({});

  useEffect(() => {
    let alive = true;

    const load = () =>
      api
        .get(`/puzzles/${code}/leaderboard`)
        .then((res) => {
          if (!alive) return;
          const entries = res.data.entries;
          // Who climbed (or dropped) since the last refresh.
          const moved = {};
          entries.forEach((e) => {
            const before = prevRanks.current[e.session_id];
            if (before !== undefined && before !== e.rank) moved[e.session_id] = before - e.rank;
          });
          prevRanks.current = Object.fromEntries(entries.map((e) => [e.session_id, e.rank]));
          setMoves(moved);
          setData(res.data);
          setError(null);
        })
        .catch(() => alive && setError('Could not load the leaderboard.'));

    load();
    const t = setInterval(load, intervalMs);
    return () => {
      alive = false;
      clearInterval(t);
    };
  }, [code, intervalMs]);

  if (error && !data) return <p className="error">{error}</p>;
  if (!data) return <p>Loading leaderboard…</p>;

  return (
    <div className="leaderboard-panel">
      {showTitle && (
        <div className="leaderboard-head">
          <p className="topic-label">{data.puzzle.category || 'Puzzle'}</p>
          <h1>{data.puzzle.title}</h1>
          <p className="leaderboard-sub">
            {data.points_per_word} points for every word · {data.total_words} words to find
          </p>
        </div>
      )}
      <Podium
        entries={data.entries}
        highlightSessionId={highlightSessionId}
        moves={moves}
        big={big}
      />
    </div>
  );
}
