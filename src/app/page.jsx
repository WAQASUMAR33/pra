'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';

export default function Dashboard() {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalSales: 0,
    totalTax: 0,
    successRate: 0,
    pendingUploads: 0
  });

  const loadInvoices = () => {
    setLoading(true);
    fetch('/api/invoices?limit=50')
      .then(res => res.json())
      .then(data => {
        if (Array.isArray(data)) {
          setInvoices(data);
          calculateStats(data);
        }
        setLoading(false);
      })
      .catch(err => {
        console.error("Error fetching invoices:", err);
        setLoading(false);
      });
  };

  useEffect(() => {
    loadInvoices();
  }, []);

  const calculateStats = (invoiceList) => {
    let sales = 0;
    let tax = 0;
    let successCount = 0;
    let pendingCount = 0;

    invoiceList.forEach(inv => {
      const net = parseFloat(inv.totalBillAmount) || 0;
      const t = parseFloat(inv.totalTaxCharged) || 0;
      
      sales += net;
      tax += t;
      
      if (inv.status === 'SUCCESS') {
        successCount++;
      } else if (inv.status === 'DRAFT' || inv.status === 'FAILED') {
        pendingCount++;
      }
    });

    const rate = invoiceList.length > 0 
      ? Math.round((successCount / invoiceList.length) * 100)
      : 0;

    setStats({
      totalSales: sales,
      totalTax: tax,
      successRate: rate,
      pendingUploads: pendingCount
    });
  };

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
      case 2: return 'Cheque';
      case 3: return 'Card';
      case 4: return 'Transfer';
      default: return 'Other';
    }
  };

  return (
    <div className="content-body animate-fade-in">
      {/* Header */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <div>
          <h1 className="page-title">Marriage Hall e-IMS Dashboard</h1>
          <p style={{ color: 'var(--text-muted)', marginTop: '0.25rem' }}>Management and PRA validation monitoring for Banquet events</p>
        </div>
        
        <Link href="/invoices/new" className="btn btn-primary">
          🎪 New Event Invoice
        </Link>
      </div>

      {/* Stats Cards Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '1.5rem', marginBottom: '2.5rem' }}>
        
        <div className="card">
          <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem', fontWeight: '500' }}>Banquet Revenue (Inc. Tax)</div>
          <div style={{ fontSize: '1.8rem', fontWeight: '700', marginTop: '0.5rem', color: 'var(--primary)' }}>
            PKR {stats.totalSales.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}
          </div>
          <small style={{ color: 'var(--text-muted)', display: 'block', marginTop: '0.5rem' }}>Total recorded bookings</small>
        </div>
        
        <div className="card">
          <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem', fontWeight: '500' }}>Punjab Sales Tax (PST)</div>
          <div style={{ fontSize: '1.8rem', fontWeight: '700', marginTop: '0.5rem', color: 'var(--success)' }}>
            PKR {stats.totalTax.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}
          </div>
          <small style={{ color: 'var(--text-muted)', display: 'block', marginTop: '0.5rem' }}>PST payable to PRA (16%)</small>
        </div>
        
        <div className="card">
          <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem', fontWeight: '500' }}>PRA Upload Success Rate</div>
          <div style={{ fontSize: '1.8rem', fontWeight: '700', marginTop: '0.5rem', color: stats.successRate > 75 ? 'var(--success)' : 'var(--warning)' }}>
            {stats.successRate}%
          </div>
          <div style={{ width: '100%', height: '4px', backgroundColor: 'var(--panel-border)', borderRadius: '2px', marginTop: '0.75rem', overflow: 'hidden' }}>
            <div style={{ width: `${stats.successRate}%`, height: '100%', backgroundColor: stats.successRate > 75 ? 'var(--success)' : 'var(--warning)' }}></div>
          </div>
        </div>
        
        <div className="card">
          <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem', fontWeight: '500' }}>Pending PRA Uploads</div>
          <div style={{ fontSize: '1.8rem', fontWeight: '700', marginTop: '0.5rem', color: stats.pendingUploads > 0 ? 'var(--warning)' : 'var(--text-muted)' }}>
            {stats.pendingUploads}
          </div>
          <small style={{ color: 'var(--text-muted)', display: 'block', marginTop: '0.5rem' }}>Awaiting e-IMS filing</small>
        </div>

      </div>

      {/* Invoices List Table */}
      <div className="card">
        <h3 style={{ marginBottom: '1.25rem', fontSize: '1.1rem' }}>📋 Recent Event Bookings</h3>
        
        {loading ? (
          <p>Loading invoices...</p>
        ) : invoices.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '3rem 1rem', color: 'var(--text-muted)' }}>
            <p style={{ fontSize: '1.1rem', marginBottom: '1rem' }}>No event bookings registered yet.</p>
            <Link href="/invoices/new" className="btn btn-primary">
              Register First Booking
            </Link>
          </div>
        ) : (
          <div className="table-container">
            <table className="table">
              <thead>
                <tr>
                  <th>Invoice No</th>
                  <th>Host / Hostess Name</th>
                  <th>Event Type</th>
                  <th>Event Date</th>
                  <th>Guests</th>
                  <th>Total Amount</th>
                  <th>PST Tax (16%)</th>
                  <th>PRA Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                {invoices.map((inv) => (
                  <tr key={inv.id}>
                    <td style={{ fontWeight: '600' }}>{inv.invoiceNumber}</td>
                    <td>{inv.buyerName || <em style={{ color: 'var(--text-muted)' }}>Anonymous</em>}</td>
                    <td style={{ fontWeight: '600', color: 'var(--primary)' }}>{inv.eventType || 'Sale'}</td>
                    <td>{inv.eventDate ? new Date(inv.eventDate).toLocaleDateString() : 'N/A'}</td>
                    <td>{inv.numberOfGuests ? `${inv.numberOfGuests} guests` : 'N/A'}</td>
                    <td style={{ fontWeight: '600' }}>PKR {parseFloat(inv.totalBillAmount).toLocaleString(undefined, { maximumFractionDigits: 0 })}</td>
                    <td style={{ color: 'var(--success)' }}>PKR {parseFloat(inv.totalTaxCharged).toLocaleString(undefined, { maximumFractionDigits: 0 })}</td>
                    <td>
                      <span className={`badge ${getStatusBadgeClass(inv.status)}`}>
                        {inv.status}
                      </span>
                    </td>
                    <td>
                      <Link href={`/invoices/${inv.id}`} className="btn btn-secondary" style={{ padding: '0.35rem 0.75rem', fontSize: '0.85rem' }}>
                        👁️ View / Print
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
