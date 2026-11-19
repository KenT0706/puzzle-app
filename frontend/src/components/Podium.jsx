// Leaderboard laid out as a pyramid: rank 1 on top, then 2-3, 4-6, 7-10,
// with everyone from rank 11 down listed underneath. Shows names and scores only.
const MEDAL = { 1: '🥇', 2: '🥈', 3: '🥉' };

function Cell({ entry, mine, move }) {
  const cls = [
    'podium-cell',
    entry.rank <= 3 ? `rank-${entry.rank}` : 'rank-other',
    mine ? 'mine' : '',
  ].join(' ');

  return (
    <div className={cls}>
      <span className="podium-rank">{entry.rank === 1 ? '👑' : MEDAL[entry.rank] || `#${entry.rank}`}</span>
      <span className="podium-name" title={entry.name}>{entry.name}</span>
      <span className="podium-score">{entry.score.toLocaleString()} pts</span>
      {entry.completed && <span className="podium-done">✅ finished</span>}
      {move > 0 && <span className="podium-move up">▲{move}</span>}
      {move < 0 && <span className="podium-move down">▼{-move}</span>}
    </div>
  );
}

export default function Podium({ entries, highlightSessionId, moves = {}, big = false }) {
  if (!entries || entries.length === 0) {
    return <p className="podium-empty">No scores yet. Solve a word to get on the board!</p>;
  }

  const top = entries.slice(0, 10);
  const rest = entries.slice(10);

  const rows = [];
  let i = 0;
  for (let size = 1; size <= 4 && i < top.length; size++) {
    rows.push(top.slice(i, i + size));
    i += size;
  }

  return (
    <div className={`podium ${big ? 'podium-big' : ''}`}>
      <div className="pyramid">
        {rows.map((row, ri) => (
          <div className="pyramid-row" key={ri}>
            {row.map((e) => (
              <Cell
                key={e.session_id}
                entry={e}
                mine={e.session_id === highlightSessionId}
                move={moves[e.session_id] || 0}
              />
            ))}
          </div>
        ))}
      </div>

      {rest.length > 0 && (
        <div className="podium-rest">
          {rest.map((e) => (
            <div
              key={e.session_id}
              className={`rest-row ${e.session_id === highlightSessionId ? 'mine' : ''}`}
            >
              <span className="rest-rank">{e.rank}</span>
              <span className="rest-name">{e.name}</span>
              <span className="rest-score">{e.score.toLocaleString()} pts</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
