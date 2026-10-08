import { useRef } from 'react';

// Clue numbers that start in a cell. Across and down numbers can differ
// (e.g. Across 1 and Down 3 can start in the same cell); identical numbers show once.
const distinctLabels = (labels = []) =>
  labels.filter((l, i, a) => a.findIndex((x) => x.number === l.number) === i);

/**
 * Renders the crossword grid and handles typing/navigation.
 * `grid` is the server's cell layout (blocked/labels, no letters).
 * `values` is a map "r-c" => letter currently filled in.
 * `correctness` (optional) is a map "r-c" => true|false from the last check.
 */
export default function CrosswordGrid({ grid, values, correctness, onCellChange, activeClue }) {
  const inputRefs = useRef({});

  if (!grid) return null;

  const focusCell = (r, c) => {
    const el = inputRefs.current[`${r}-${c}`];
    if (el) el.focus();
  };

  const handleKeyDown = (e, r, c) => {
    const moves = {
      ArrowRight: [0, 1],
      ArrowLeft: [0, -1],
      ArrowDown: [1, 0],
      ArrowUp: [-1, 0],
    };
    if (moves[e.key]) {
      e.preventDefault();
      const [dr, dc] = moves[e.key];
      focusCell(r + dr, c + dc);
    } else if (e.key === 'Backspace' && !values[`${r}-${c}`]) {
      // move back a cell when deleting an already-empty cell
      focusCell(r, c - 1);
    }
  };

  return (
    <div
      className="crossword-grid"
      style={{ gridTemplateColumns: `repeat(${grid[0].length}, 2.2rem)` }}
    >
      {grid.flatMap((row) =>
        row.map((cell) => {
          const key = `${cell.row}-${cell.col}`;
          if (cell.blocked) {
            return <div key={key} className="cell blocked" />;
          }
          const status =
            correctness && key in correctness ? (correctness[key] ? 'correct' : 'incorrect') : '';
          const isActive =
            activeClue &&
            activeClue.cells &&
            activeClue.cells.some((p) => p.row === cell.row && p.col === cell.col);
          const labels = distinctLabels(cell.labels);

          return (
            <div key={key} className={`cell ${status} ${isActive ? 'active-word' : ''}`}>
              {labels.length > 0 && (
                <span className="cell-number">
                  {labels.map((l) => (
                    <span
                      key={`${l.direction}-${l.number}`}
                      className={l.direction === 'down' ? 'down-num' : ''}
                    >
                      {l.number}
                    </span>
                  ))}
                </span>
              )}
              <input
                ref={(el) => (inputRefs.current[key] = el)}
                maxLength={1}
                value={values[key] || ''}
                onChange={(e) => {
                  const letter = e.target.value.replace(/[^a-zA-Z-]/, '').toUpperCase();
                  onCellChange(cell.row, cell.col, letter);
                  if (letter) focusCell(cell.row, cell.col + 1); // naive auto-advance
                }}
                onKeyDown={(e) => handleKeyDown(e, cell.row, cell.col)}
              />
            </div>
          );
        })
      )}
    </div>
  );
}