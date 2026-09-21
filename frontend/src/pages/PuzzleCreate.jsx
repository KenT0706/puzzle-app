import { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../api.js';

const emptyClue = () => ({ direction: 'across', start_row: 0, start_col: 0, answer: '', clue_text: '' });
const BLANK = '__________';

// Handles both "+ New Puzzle" (no :puzzleId) and "Edit puzzle" (/edit/:puzzleId).
export default function PuzzleCreate() {
  const navigate = useNavigate();
  const { puzzleId } = useParams();
  const isEditing = !!puzzleId;

  const [title, setTitle] = useState('');
  const [category, setCategory] = useState('');
  const [rows, setRows] = useState(12);
  const [cols, setCols] = useState(12);
  const [clues, setClues] = useState([emptyClue()]);
  const [error, setError] = useState(null);
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(isEditing);
  const clueTextRefs = useRef({});

  useEffect(() => {
    if (!isEditing) return;
    api.get(`/puzzles/${puzzleId}/edit`).then((res) => {
      const p = res.data;
      setTitle(p.title);
      setCategory(p.category || '');
      setRows(p.rows);
      setCols(p.cols);
      setClues(p.clues.length ? p.clues : [emptyClue()]);
      setLoading(false);
    }).catch(() => {
      setError('Could not load this puzzle for editing.');
      setLoading(false);
    });
  }, [puzzleId, isEditing]);

  const updateClue = (i, field, value) => {
    setClues((prev) => prev.map((c, idx) => (idx === i ? { ...c, [field]: value } : c)));
  };

  const addClue = () => setClues((prev) => [...prev, emptyClue()]);
  const removeClue = (i) => setClues((prev) => prev.filter((_, idx) => idx !== i));
  // Inserts a blank at the cursor (or at the end, if the textarea isn't focused).
  const insertBlank = (i) => {
    const el = clueTextRefs.current[i];
    const current = clues[i].clue_text || '';
    const start = el ? el.selectionStart : current.length;
    const end = el ? el.selectionEnd : current.length;
    const next = current.slice(0, start) + BLANK + current.slice(end);
    updateClue(i, 'clue_text', next);
    setTimeout(() => {
      if (!el) return;
      const cursor = start + BLANK.length;
      el.focus();
      el.setSelectionRange(cursor, cursor);
    }, 0);
  };

  const submit = async (e) => {
    e.preventDefault();
    setError(null);
    setSaving(true);
    const payload = {
      title,
      category,
      rows: Number(rows),
      cols: Number(cols),
      clues: clues.map((c) => ({
        ...c,
        start_row: Number(c.start_row),
        start_col: Number(c.start_col),
      })),
    };
    try {
      const res = isEditing
        ? await api.put(`/puzzles/${puzzleId}`, payload)
        : await api.post('/puzzles', payload);
      navigate(`/play/${res.data.id}`);
    } catch (err) {
      const message = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(' ')
        : 'Could not save the puzzle. Check your grid coordinates for overlaps.';
      setError(message);
    } finally {
      setSaving(false);
    }
  };

  if (loading) return <p>Loading puzzle…</p>;

  return (
    <form className="puzzle-create" onSubmit={submit}>
      <h1>{isEditing ? 'Edit Puzzle' : 'New Puzzle'}</h1>

      <div className="field-row">
        <label>
          Title
          <input value={title} onChange={(e) => setTitle(e.target.value)} required />
        </label>
        <label>
          Category
          <input value={category} onChange={(e) => setCategory(e.target.value)} placeholder="Payroll" />
        </label>
        <label>
          Rows
          <input type="number" min="2" max="40" value={rows} onChange={(e) => setRows(e.target.value)} />
        </label>
        <label>
          Cols
          <input type="number" min="2" max="40" value={cols} onChange={(e) => setCols(e.target.value)} />
        </label>
      </div>

      <h2>Clues</h2>
            <p className="hint">
        Row/col are 0-indexed. Give each word its starting cell — the app auto-numbers the grid
        and checks that overlapping words share the same letter. Clue text can be a plain
        definition ("Hours worked beyond normal daily hours") or a fill-in-the-blank sentence
        with the blank spelled out as dots, the way your slide deck does it
        ("…………. means the number of hours of work carried out in excess of the normal
        hours of work per day.") — either style displays the same way to players.
      </p>
      <table className="clue-table">
                <colgroup>
          <col className="col-direction" />
          <col className="col-coord" />
          <col className="col-coord" />
          <col className="col-answer" />
          <col className="col-clue" />
          <col className="col-delete" />
        </colgroup>
        <thead>
          <tr>
            <th>Direction</th><th>Row</th><th>Col</th><th>Answer</th><th>Clue text</th><th />
          </tr>
        </thead>
        <tbody>
          {clues.map((c, i) => (
            <tr key={i}>
              <td>
                <div className="direction-toggle">
                  <button
                    type="button"
                    className={`across-btn ${c.direction === 'across' ? 'active' : ''}`}
                    onClick={() => updateClue(i, 'direction', 'across')}
                  >
                    Across
                  </button>
                  <button
                    type="button"
                    className={`down-btn ${c.direction === 'down' ? 'active' : ''}`}
                    onClick={() => updateClue(i, 'direction', 'down')}
                  >
                    Down
                  </button>
                </div>
              </td>
              <td><input type="number" min="0" value={c.start_row} onChange={(e) => updateClue(i, 'start_row', e.target.value)} /></td>
              <td><input type="number" min="0" value={c.start_col} onChange={(e) => updateClue(i, 'start_col', e.target.value)} /></td>
              <td><input value={c.answer} onChange={(e) => updateClue(i, 'answer', e.target.value)} placeholder="MATERNITY" /></td>
                            <td>
                <div className="clue-text-cell">
                  <textarea
                    ref={(el) => (clueTextRefs.current[i] = el)}
                    className="clue-text-input"
                    rows={2}
                    value={c.clue_text}
                    onChange={(e) => updateClue(i, 'clue_text', e.target.value)}
                    placeholder="Clue wording…"
                  />
                  <button type="button" className="insert-blank-btn" onClick={() => insertBlank(i)}>
                    + Insert blank
                  </button>
                </div>
              </td>
              <td><button type="button" onClick={() => removeClue(i)}>✕</button></td>
            </tr>
          ))}
        </tbody>
      </table>
      <button type="button" className="btn-pill blue" onClick={addClue}>+ Add clue</button>

      {error && <p className="error">{error}</p>}
      <button className="btn-pill save-button" type="submit" disabled={saving}>
        {saving ? 'Saving…' : isEditing ? 'Save changes' : 'Save puzzle'}
      </button>
    </form>
  );
}
