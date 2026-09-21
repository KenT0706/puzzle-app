// A small fixed palette (Kahoot-style: a handful of bold, distinct hues)
// deterministically assigned by name, so the same category or person
// always lands on the same color across the app.
const PALETTE = [
  { bg: '#6C4CF0', on: '#ffffff' }, // violet (brand)
  { bg: '#FF4356', on: '#ffffff' }, // red
  { bg: '#1FA7FF', on: '#ffffff' }, // blue
  { bg: '#FFC83D', on: '#241B4E' }, // yellow
  { bg: '#26D07C', on: '#ffffff' }, // green
  { bg: '#FF8A3D', on: '#ffffff' }, // orange
];

export function colorFor(name = '') {
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = (hash << 5) - hash + name.charCodeAt(i);
    hash |= 0;
  }
  return PALETTE[Math.abs(hash) % PALETTE.length];
}

const CATEGORY_ICONS = {
  payroll: '💰',
  'employment act': '📋',
  'termination of employment': '🚪',
  'industrial relations': '⚖️',
  ir: '⚖️',
  'talent management': '🌟',
};

export function iconFor(category = '') {
  return CATEGORY_ICONS[category.trim().toLowerCase()] || '🧩';
}
