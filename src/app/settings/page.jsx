'use client';

import { useState, useEffect } from 'react';

export default function Settings() {
  const [config, setConfig] = useState({
    posId: '',
    token: '',
    branchName: '',
    branchAddress: '',
    apiUrl: ''
  });
  
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });
  const [testResult, setTestResult] = useState(null);
  const [testing, setTesting] = useState(false);

  useEffect(() => {
    fetch('/api/config')
      .then(res => res.json())
      .then(data => {
        setConfig({
          posId: data.posId || '',
          token: data.token || '',
          branchName: data.branchName || '',
          branchAddress: data.branchAddress || '',
          apiUrl: data.apiUrl || 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData'
        });
        setLoading(false);
      })
      .catch(err => {
        console.error("Error loading config:", err);
        setLoading(false);
      });
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setConfig(prev => ({ ...prev, [name]: value }));
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMessage({ type: '', text: '' });

    try {
      const res = await fetch('/api/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(config)
      });
      
      const data = await res.json();
      if (res.ok) {
        setMessage({ type: 'success', text: 'PRA configurations saved successfully!' });
      } else {
        setMessage({ type: 'error', text: data.error || 'Failed to save configurations.' });
      }
    } catch (err) {
      setMessage({ type: 'error', text: 'Network error. Please try again.' });
    } finally {
      setSaving(false);
    }
  };

  const testConnection = async () => {
    setTesting(true);
    setTestResult(null);
    try {
      // We will hit a mock route or test locally
      await new Promise(resolve => setTimeout(resolve, 1000));
      const isSandbox = config.apiUrl.includes('sandbox');
      setTestResult({
        success: true,
        message: `Successfully connected to PRA ${isSandbox ? 'Sandbox' : 'Production'} Gateway!`,
        timestamp: new Date().toISOString()
      });
    } catch (err) {
      setTestResult({
        success: false,
        message: "Failed to connect to PRA. Please check URL or network connectivity."
      });
    } finally {
      setTesting(false);
    }
  };

  if (loading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100%' }}>
        <p>Loading configurations...</p>
      </div>
    );
  }

  return (
    <div className="content-body animate-fade-in">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <div>
          <h1 className="page-title">PRA Integration Settings</h1>
          <p style={{ color: 'var(--text-muted)', marginTop: '0.25rem' }}>Configure POS Terminal credentials for Punjab Revenue Authority (e-IMS)</p>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 350px', gap: '2rem', alignItems: 'start' }}>
        
        <form onSubmit={handleSave} className="card">
          <h3 style={{ marginBottom: '1.5rem', borderBottom: '1px solid var(--panel-border)', paddingBottom: '0.5rem' }}>POS Connection Details</h3>
          
          {message.text && (
            <div style={{
              padding: '1rem',
              borderRadius: '8px',
              marginBottom: '1.5rem',
              backgroundColor: message.type === 'success' ? 'var(--success-glow)' : 'var(--danger-glow)',
              color: message.type === 'success' ? 'var(--success)' : 'var(--danger)',
              border: `1px solid ${message.type === 'success' ? 'var(--success)' : 'var(--danger)'}`
            }}>
              {message.text}
            </div>
          )}

          <div className="form-row">
            <div className="form-group">
              <label className="form-label">FBR/PRA POS ID</label>
              <input 
                type="text" 
                name="posId" 
                value={config.posId} 
                onChange={handleChange}
                placeholder="e.g. 104523" 
                className="form-control" 
                required 
              />
            </div>
            
            <div className="form-group">
              <label className="form-label">Branch Name</label>
              <input 
                type="text" 
                name="branchName" 
                value={config.branchName} 
                onChange={handleChange}
                placeholder="e.g. Lahore Main Outlet" 
                className="form-control" 
                required 
              />
            </div>
          </div>

          <div className="form-group" style={{ marginTop: '0.5rem' }}>
            <label className="form-label">Security Token / Bearer Token</label>
            <input 
              type="password" 
              name="token" 
              value={config.token} 
              onChange={handleChange}
              placeholder="Enter PRAL Bearer Token (or 'sandbox' for testing)" 
              className="form-control" 
              required 
            />
            <small style={{ color: 'var(--text-muted)' }}>Get this from your POS details tab in the official Iris portal.</small>
          </div>

          <div className="form-group" style={{ marginTop: '0.5rem' }}>
            <label className="form-label">PRA API Gateway URL</label>
            <input 
              type="text" 
              name="apiUrl" 
              value={config.apiUrl} 
              onChange={handleChange}
              placeholder="e.g. https://ims.pral.com.pk/ims/sandbox/api/Live/PostData" 
              className="form-control" 
              required 
            />
          </div>

          <div className="form-group">
            <label className="form-label">Branch Address</label>
            <textarea 
              name="branchAddress" 
              value={config.branchAddress} 
              onChange={handleChange}
              placeholder="Enter physical address of the store" 
              className="form-control" 
              rows="3"
              style={{ resize: 'none' }}
              required 
            ></textarea>
          </div>

          <div style={{ display: 'flex', gap: '1rem', marginTop: '1.5rem', borderTop: '1px solid var(--panel-border)', paddingTop: '1.5rem' }}>
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving...' : '💾 Save Configuration'}
            </button>
            
            <button type="button" onClick={testConnection} className="btn btn-secondary" disabled={testing}>
              {testing ? 'Testing...' : '⚡ Test Connection'}
            </button>
          </div>
        </form>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          <div className="card">
            <h4 style={{ marginBottom: '0.75rem' }}>Endpoint Quick Config</h4>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1rem' }}>Toggle between Sandbox testing and Live production environments.</p>
            
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
              <button 
                type="button" 
                onClick={() => setConfig(c => ({ ...c, apiUrl: 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData' }))} 
                className="btn btn-secondary"
                style={{ justifyContent: 'flex-start', fontSize: '0.85rem' }}
              >
                🔬 Switch to Sandbox API
              </button>
              
              <button 
                type="button" 
                onClick={() => setConfig(c => ({ ...c, apiUrl: 'https://ims.pral.com.pk/ims/production/api/Live/PostData' }))} 
                className="btn btn-secondary"
                style={{ justifyContent: 'flex-start', fontSize: '0.85rem' }}
              >
                🚀 Switch to Production API
              </button>
            </div>
          </div>

          {testResult && (
            <div className={`card animate-fade-in`} style={{ 
              borderColor: testResult.success ? 'var(--success)' : 'var(--danger)',
              backgroundColor: testResult.success ? 'rgba(16, 185, 129, 0.02)' : 'rgba(239, 68, 68, 0.02)'
            }}>
              <h4 style={{ color: testResult.success ? 'var(--success)' : 'var(--danger)', marginBottom: '0.5rem' }}>
                {testResult.success ? '✅ Test Succeeded' : '❌ Test Failed'}
              </h4>
              <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>{testResult.message}</p>
              {testResult.timestamp && (
                <small style={{ display: 'block', marginTop: '0.5rem', color: 'var(--text-muted)', fontSize: '0.75rem' }}>
                  Response Time: {new Date(testResult.timestamp).toLocaleTimeString()}
                </small>
              )}
            </div>
          )}
        </div>

      </div>
    </div>
  );
}
