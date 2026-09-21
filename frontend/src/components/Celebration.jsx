import { useEffect, useRef } from 'react';
import confetti from 'canvas-confetti';

/**
 * Fires a confetti burst + a short synthesized success chime the moment
 * `trigger` flips to true. The chime is generated with the Web Audio API
 * (a quick ascending 3-note arpeggio) so there's no audio file to ship
 * and no autoplay-without-interaction issues.
 */
export default function Celebration({ trigger }) {
  const firedFor = useRef(null);

  useEffect(() => {
    if (!trigger || firedFor.current === trigger) return;
    firedFor.current = trigger;

    confetti({
      particleCount: 140,
      spread: 80,
      origin: { y: 0.6 },
    });
    setTimeout(() => confetti({ particleCount: 60, angle: 60, spread: 60, origin: { x: 0 } }), 150);
    setTimeout(() => confetti({ particleCount: 60, angle: 120, spread: 60, origin: { x: 1 } }), 150);

    playChime();
  }, [trigger]);

  return null;
}

function playChime() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const notes = [523.25, 659.25, 783.99]; // C5, E5, G5
    notes.forEach((freq, i) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = freq;
      const start = ctx.currentTime + i * 0.12;
      gain.gain.setValueAtTime(0, start);
      gain.gain.linearRampToValueAtTime(0.25, start + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.001, start + 0.35);
      osc.connect(gain).connect(ctx.destination);
      osc.start(start);
      osc.stop(start + 0.4);
    });
  } catch {
    // Audio isn't available (e.g. blocked autoplay) — confetti alone is fine.
  }
}
