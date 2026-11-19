import { Link } from 'react-router-dom';

export default function Landing() {
  return (
    <div className="landing">
      <div className="landing-icon">📱</div>
      <h1>Ready to play?</h1>
      <p>Scan the QR code your host shared with you to open your puzzle.</p>
      <p className="landing-footer"><Link to="/login">Host login</Link></p>
    </div>
  );
}
