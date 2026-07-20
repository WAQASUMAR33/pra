import '@/app/globals.css';
import Link from 'next/link';

export const metadata = {
  title: 'PRA e-IMS Invoice Integration Dashboard',
  description: 'Upload sales invoices to Punjab Revenue Authority Electronic Invoice Monitoring System',
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>
        <div className="app-container">
          <aside className="sidebar">
            <div className="logo-section">
              <div className="logo-icon">PR</div>
              <div className="logo-text">PRA Portal</div>
            </div>
            
            <nav style={{ flex: 1 }}>
              <ul className="nav-links">
                <li className="nav-item">
                  <Link href="/">
                    📊 Dashboard
                  </Link>
                </li>
                <li className="nav-item">
                  <Link href="/invoices/new">
                    📝 Create Invoice
                  </Link>
                </li>
                <li className="nav-item">
                  <Link href="/settings">
                    ⚙️ POS Settings
                  </Link>
                </li>
              </ul>
            </nav>
            
            <div style={{ color: 'var(--text-muted)', fontSize: '0.8rem', textAlign: 'center', borderTop: '1px solid var(--panel-border)', paddingTop: '1rem' }}>
              v1.0.0 &bull; PRAL e-IMS
            </div>
          </aside>
          
          <div className="main-wrapper">
            {children}
          </div>
        </div>
      </body>
    </html>
  );
}
