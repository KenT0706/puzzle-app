import { useRef, useState } from 'react';
import { QRCodeCanvas } from 'qrcode.react';

export default function ShareModal({ puzzle, onClose }) {
  const canvasRef = useRef(null);
  const [copied, setCopied] = useState(false);
  // Uses the address you're browsing on, so open this from the live site, not localhost.
  const link = `${window.location.origin}/p/${puzzle.share_code}`;

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(link);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      /* clipboard not available */
    }
  };

  const download = () => {
    const a = document.createElement('a');
    a.href = canvasRef.current.toDataURL('image/png');
    a.download = `${puzzle.title.replace(/[^a-z0-9]+/gi, '-')}-qr.png`;
    a.click();
  };

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal-card" onClick={(e) => e.stopPropagation()}>
        <h2>{puzzle.title}</h2>
        {!puzzle.is_published && (
          <p className="error">This puzzle is closed. Players who scan this will see “not available” until you open it.</p>
        )}
        <QRCodeCanvas ref={canvasRef} value={link} size={240} marginSize={2} />
        <p className="share-link">{link}</p>
        <div className="modal-actions">
          <button className="btn-pill" onClick={copy}>{copied ? 'Copied!' : 'Copy link'}</button>
          <button className="btn-pill blue" onClick={download}>Download QR</button>
          <button className="btn-pill red" onClick={onClose}>Close</button>
        </div>
      </div>
    </div>
  );
}
