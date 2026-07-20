'use client';

import { useState, useEffect } from 'react';
import { QRCodeSVG } from 'qrcode.react';

export default function InvoiceDetails({ params }) {
  const { id } = params;
  const [invoice, setInvoice] = useState(null);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');
  const [config, setConfig] = useState(null);
  
  // Debug logs state
  const [payloadLog, setPayloadLog] = useState(null);
  const [responseLog, setResponseLog] = useState(null);

  const fetchInvoice = () => {
    setLoading(true);
    fetch(`/api/invoices/${id}`)
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          setError(data.error);
        } else {
          setInvoice(data);
          if (data.praResponseMsg) {
            try {
              // Try parsing raw response logs if stored as JSON
              const parsed = JSON.parse(data.praResponseMsg);
              setResponseLog(parsed);
            } catch (e) {
              setResponseLog(data.praResponseMsg);
            }
          }
        }
        setLoading(false);
      })
      .catch(err => {
        setError("Failed to fetch invoice detail.");
        setLoading(false);
      });
  };

  useEffect(() => {
    fetchInvoice();
    // Load config for header details
    fetch('/api/config')
      .then(res => res.json())
      .then(data => setConfig(data))
      .catch(err => console.error("Error loading config:", err));
  }, [id]);

  const handleUpload = async () => {
    setUploading(true);
    setError('');
    try {
      const response = await fetch(`/api/invoices/${id}/upload`, {
        method: 'POST'
      });
      const data = await response.json();
      
      if (response.ok && data.success) {
        setInvoice(data.invoice);
        setPayloadLog(data.payload);
        setResponseLog(data.response);
      } else {
        setError(data.error || "Failed to submit invoice to PRA.");
        fetchInvoice(); // Reload to get updated error logs
      }
    } catch (err) {
      setError("Network error. Unable to contact upload API.");
    } finally {
      setUploading(false);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  if (loading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100%' }}>
        <p>Loading invoice details...</p>
      </div>
    );
  }

  if (error && !invoice) {
    return (
      <div className="content-body">
        <div style={{ padding: '1.5rem', backgroundColor: 'var(--danger-glow)', color: 'var(--danger)', borderRadius: '8px' }}>
          Error: {error}
        </div>
      </div>
    );
  }

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'SUCCESS': return 'badge-success';
      case 'PENDING': return 'badge-pending';
      case 'FAILED': return 'badge-danger';
      default: return 'badge-draft';
    }
  };

  const getPaymentModeLabel = (mode) => {
    switch (mode) {
      case 1: return 'Cash';
      case 2: return 'Bank Cheque';
      case 3: return 'Credit Card';
      case 4: return 'Bank Transfer';
      default: return 'Other';
    }
  };

  const qrUrl = invoice.praFiscalNumber 
    ? `https://e.pra.punjab.gov.pk/verify?fiscalNumber=${invoice.praFiscalNumber}&usin=${invoice.usin}&posid=${invoice.posId}`
    : `Local USIN: ${invoice.usin}`;

  return (
    <div className="content-body animate-fade-in">
      {/* Action Header */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }} className="no-print">
        <div>
          <h1 className="page-title">Invoice: {invoice.invoiceNumber}</h1>
          <p style={{ color: 'var(--text-muted)', marginTop: '0.25rem' }}>USIN: {invoice.usin}</p>
        </div>
        
        <div style={{ display: 'flex', gap: '1rem' }}>
          <button onClick={handlePrint} className="btn btn-secondary">
            🖨️ Print Invoice Receipt
          </button>
          
          {(invoice.status === 'DRAFT' || invoice.status === 'FAILED') && (
            <button 
              onClick={handleUpload} 
              className="btn btn-success" 
              disabled={uploading}
            >
              🚀 {uploading ? 'Submitting...' : 'Upload to PRA'}
            </button>
          )}
        </div>
      </div>

      {error && (
        <div style={{
          padding: '1rem',
          borderRadius: '8px',
          backgroundColor: 'var(--danger-glow)',
          color: 'var(--danger)',
          border: '1px solid var(--danger)',
          marginBottom: '1.5rem'
        }} className="no-print">
          ⚠️ {error}
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 400px', gap: '2rem', alignItems: 'start' }}>
        
        {/* Visual Marriage Hall POS Receipt Card */}
        <div className="card" style={{ maxWidth: '480px', margin: '0 auto', backgroundColor: '#ffffff', color: '#0f172a', fontFamily: 'monospace', padding: '2rem', boxShadow: '0 4px 20px rgba(0,0,0,0.06)', border: '1px solid #cbd5e1', borderRadius: '4px' }}>
          
          <div style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
            <h2 style={{ fontSize: '1.3rem', fontWeight: 'bold', textTransform: 'uppercase', marginBottom: '0.25rem', letterSpacing: '0.5px' }}>
              {config?.branchName || "Punjab Marriage Hall"}
            </h2>
            <p style={{ fontSize: '0.8rem', color: '#475569' }}>
              {config?.branchAddress || "Lahore, Punjab"}
            </p>
            <p style={{ fontSize: '0.85rem', fontWeight: '600', marginTop: '0.5rem' }}>
              POS ID: {invoice.posId}
            </p>
            
            <div style={{ margin: '1rem 0', borderTop: '1px dashed #475569', borderBottom: '1px dashed #475569', padding: '0.25rem 0' }}>
              <span style={{ fontSize: '0.9rem', fontWeight: 'bold', textTransform: 'uppercase' }}>Marriage Hall Booking Invoice</span>
            </div>
          </div>

          <div style={{ fontSize: '0.85rem', marginBottom: '1rem', display: 'flex', flexDirection: 'column', gap: '0.35rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>BILL NO:</span>
              <span style={{ fontWeight: 'bold' }}>{invoice.invoiceNumber}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>DATE/TIME:</span>
              <span>{new Date(invoice.dateTime).toLocaleString()}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>PAY MODE:</span>
              <span>{getPaymentModeLabel(invoice.paymentMode)}</span>
            </div>

            {/* Marriage Hall Event Specifications */}
            <div style={{ borderTop: '1px dotted #94a3b8', borderBottom: '1px dotted #94a3b8', padding: '0.5rem 0', margin: '0.5rem 0', display: 'flex', flexDirection: 'column', gap: '0.25rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ fontWeight: '600' }}>EVENT TYPE:</span>
                <span style={{ fontWeight: 'bold' }}>{invoice.eventType || 'N/A'}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ fontWeight: '600' }}>EVENT DATE:</span>
                <span>{invoice.eventDate ? new Date(invoice.eventDate).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ fontWeight: '600' }}>GUEST COUNT:</span>
                <span>{invoice.numberOfGuests ? `${invoice.numberOfGuests} Guests` : 'N/A'}</span>
              </div>
            </div>

            {invoice.buyerName && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>HOST NAME:</span>
                <span>{invoice.buyerName}</span>
              </div>
            )}
            {invoice.buyerPhone && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>HOST PHONE:</span>
                <span>{invoice.buyerPhone}</span>
              </div>
            )}
            {invoice.buyerNtn && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>HOST NTN:</span>
                <span>{invoice.buyerNtn}</span>
              </div>
            )}
          </div>

          {/* Items Section */}
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.85rem', marginBottom: '1rem' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid #0f172a' }}>
                <th style={{ textAlign: 'left', paddingBottom: '0.5rem' }}>SERVICE</th>
                <th style={{ textAlign: 'center', paddingBottom: '0.5rem', width: '50px' }}>QTY</th>
                <th style={{ textAlign: 'right', paddingBottom: '0.5rem', width: '80px' }}>RATE</th>
                <th style={{ textAlign: 'right', paddingBottom: '0.5rem', width: '80px' }}>TAX</th>
                <th style={{ textAlign: 'right', paddingBottom: '0.5rem', width: '90px' }}>TOTAL</th>
              </tr>
            </thead>
            <tbody>
              {invoice.items?.map((item, idx) => (
                <tr key={idx} style={{ borderBottom: '1px dotted #cbd5e1' }}>
                  <td style={{ padding: '0.5rem 0', verticalAlign: 'top' }}>
                    {item.itemName}
                    <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Code: {item.itemCode}</div>
                  </td>
                  <td style={{ textAlign: 'center', padding: '0.5rem 0', verticalAlign: 'top' }}>{item.quantity}</td>
                  <td style={{ textAlign: 'right', padding: '0.5rem 0', verticalAlign: 'top' }}>
                    {(parseFloat(item.saleValue) / item.quantity).toFixed(0)}
                  </td>
                  <td style={{ textAlign: 'right', padding: '0.5rem 0', verticalAlign: 'top' }}>
                    {parseFloat(item.salesTaxApplicable).toFixed(0)}
                    <div style={{ fontSize: '0.7rem', color: '#64748b' }}>({parseFloat(item.taxRate)}%)</div>
                  </td>
                  <td style={{ textAlign: 'right', padding: '0.5rem 0', verticalAlign: 'top', fontWeight: 'bold' }}>
                    {parseFloat(item.netAmount).toFixed(0)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {/* Totals Section */}
          <div style={{ borderTop: '1px solid #0f172a', paddingTop: '0.5rem', display: 'flex', flexDirection: 'column', gap: '0.25rem', fontSize: '0.85rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>GROSS TOTAL:</span>
              <span>PKR {parseFloat(invoice.totalSaleValue).toFixed(0)}</span>
            </div>
            
            {parseFloat(invoice.totalDiscount) > 0 && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>DISCOUNT:</span>
                <span>-PKR {parseFloat(invoice.totalDiscount).toFixed(0)}</span>
              </div>
            )}
            
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>PUNJAB SALES TAX (PST):</span>
              <span>PKR {parseFloat(invoice.totalTaxCharged).toFixed(0)}</span>
            </div>
            
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '1.05rem', fontWeight: 'bold', borderTop: '1px dashed #0f172a', paddingTop: '0.5rem', marginTop: '0.25rem' }}>
              <span>NET BILL AMOUNT:</span>
              <span>PKR {parseFloat(invoice.totalBillAmount).toFixed(0)}</span>
            </div>
          </div>

          {/* Fiscal Stamp */}
          <div style={{ borderTop: '1px solid #0f172a', marginTop: '1.5rem', paddingTop: '1.25rem', textAlign: 'center', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '0.5rem' }}>
            {invoice.status === 'SUCCESS' ? (
              <>
                <p style={{ fontSize: '0.85rem', fontWeight: 'bold', letterSpacing: '0.5px', color: '#047857' }}>
                  FISCALLY REGISTERED WITH PRA
                </p>
                <div style={{ padding: '0.5rem', border: '1px solid #cbd5e1', display: 'inline-block', backgroundColor: 'white' }}>
                  <QRCodeSVG value={qrUrl} size={110} />
                </div>
                <div style={{ fontSize: '0.75rem', marginTop: '0.25rem' }}>
                  <span style={{ display: 'block', color: '#475569' }}>PRA FISCAL NUMBER:</span>
                  <span style={{ fontWeight: 'bold', wordBreak: 'break-all' }}>{invoice.praFiscalNumber}</span>
                </div>
              </>
            ) : (
              <>
                <p style={{ fontSize: '0.8rem', fontWeight: 'bold', color: '#dc2626' }}>
                  NOT REGISTERED WITH PRA ({invoice.status})
                </p>
                <p style={{ fontSize: '0.7rem', color: '#64748b' }}>
                  Usin: {invoice.usin}
                </p>
              </>
            )}
            
            <p style={{ fontSize: '0.7rem', color: '#94a3b8', marginTop: '0.5rem' }}>
              Powered by PRAL e-IMS Connector
            </p>
          </div>
        </div>

        {/* Audit Logs & Metadata Side Panel */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }} className="no-print">
          
          <div className="card">
            <h3 style={{ marginBottom: '1rem', fontSize: '1rem', borderBottom: '1px solid var(--panel-border)', paddingBottom: '0.5rem' }}>📋 Audit Details</h3>
            
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', fontSize: '0.9rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <span style={{ color: 'var(--text-muted)' }}>Status</span>
                <span className={`badge ${getStatusBadgeClass(invoice.status)}`}>{invoice.status}</span>
              </div>
              
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: 'var(--text-muted)' }}>Local Created</span>
                <span>{new Date(invoice.createdAt).toLocaleDateString()}</span>
              </div>
              
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: 'var(--text-muted)' }}>Response Code</span>
                <span style={{ fontWeight: '600' }}>{invoice.praResponseCode || 'N/A'}</span>
              </div>
            </div>
          </div>

          <div className="card logs-panel">
            <h3 style={{ marginBottom: '1rem', fontSize: '1rem', borderBottom: '1px solid var(--panel-border)', paddingBottom: '0.5rem' }}>💻 Raw PRA Gateway Logs</h3>
            
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
              
              <div>
                <h4 style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.25rem' }}>Payload Structure:</h4>
                <pre style={{ 
                  backgroundColor: '#f8fafc', 
                  padding: '0.75rem', 
                  borderRadius: '6px', 
                  fontSize: '0.75rem', 
                  overflowX: 'auto',
                  border: '1px solid var(--panel-border)',
                  color: '#097969',
                  whiteSpace: 'pre-wrap',
                  wordBreak: 'break-all'
                }}>
                  {payloadLog ? JSON.stringify(payloadLog, null, 2) : '// Submit to see formatted PRAL payload'}
                </pre>
              </div>

              <div>
                <h4 style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.25rem' }}>API Response Log:</h4>
                <pre style={{ 
                  backgroundColor: '#f8fafc', 
                  padding: '0.75rem', 
                  borderRadius: '6px', 
                  fontSize: '0.75rem', 
                  overflowX: 'auto',
                  border: '1px solid var(--panel-border)',
                  color: invoice.status === 'SUCCESS' ? '#097969' : '#dc2626',
                  whiteSpace: 'pre-wrap',
                  wordBreak: 'break-all'
                }}>
                  {responseLog ? JSON.stringify(responseLog, null, 2) : '// No response logs recorded'}
                </pre>
              </div>
              
            </div>
          </div>

        </div>

      </div>
    </div>
  );
}
