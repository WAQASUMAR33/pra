'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';

export default function NewInvoice() {
  const router = useRouter();
  
  const [buyer, setBuyer] = useState({
    name: '',
    ntn: '',
    cnic: '',
    phone: ''
  });
  
  const [eventType, setEventType] = useState('Barat');
  const [eventDate, setEventDate] = useState(new Date().toISOString().slice(0, 10));
  const [numberOfGuests, setNumberOfGuests] = useState(250);
  const [invoiceType, setInvoiceType] = useState(1); // 1 = Sale, 2 = Credit Note (Sales Return)
  const [paymentMode, setPaymentMode] = useState(1); // 1 = Cash, 2 = Card
  const [totalDiscount, setTotalDiscount] = useState(0);
  
  // Prepopulate standard Marriage Hall billing items
  const [items, setItems] = useState([
    { itemCode: 'SRV-VEN', itemName: 'Banquet Hall Venue Booking Fee', quantity: 1, price: 120000, taxRate: 16, discount: 0 },
    { itemCode: 'SRV-CAT', itemName: 'Premium Catering Services (Per Head Menu)', quantity: 250, price: 1500, taxRate: 16, discount: 0 },
    { itemCode: 'SRV-DEC', itemName: 'Floral Stage & Venue Decoration Setup', quantity: 1, price: 50000, taxRate: 16, discount: 0 },
    { itemCode: 'SRV-AV', itemName: 'DJ Sound System & Ambient Stage Lighting', quantity: 1, price: 20000, taxRate: 16, discount: 0 }
  ]);
  
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const handleBuyerChange = (e) => {
    const { name, value } = e.target;
    setBuyer(prev => ({ ...prev, [name]: value }));
  };

  const handleGuestsChange = (e) => {
    const guests = parseInt(e.target.value) || 0;
    setNumberOfGuests(guests);
    
    // Auto-update the Catering item quantity if it exists in items list!
    setItems(prevItems => 
      prevItems.map(item => {
        if (item.itemCode === 'SRV-CAT') {
          return { ...item, quantity: guests };
        }
        return item;
      })
    );
  };

  const handleItemChange = (index, field, value) => {
    const updatedItems = [...items];
    updatedItems[index][field] = value;
    
    // If the catering item quantity was changed manually, update the guest count state
    if (field === 'quantity' && updatedItems[index].itemCode === 'SRV-CAT') {
      setNumberOfGuests(parseInt(value) || 0);
    }
    
    setItems(updatedItems);
  };

  const addItemRow = () => {
    setItems(prev => [
      ...prev,
      { itemCode: `SRV-ADD`, itemName: '', quantity: 1, price: 0, taxRate: 16, discount: 0 }
    ]);
  };

  const removeItemRow = (index) => {
    if (items.length === 1) return;
    setItems(prev => prev.filter((_, idx) => idx !== index));
  };

  // Calculations
  const calculateTotals = () => {
    let grossTotal = 0;
    let taxTotal = 0;
    let itemsQty = 0;
    
    items.forEach(item => {
      const qty = parseInt(item.quantity) || 0;
      const price = parseFloat(item.price) || 0;
      const rate = parseFloat(item.taxRate) || 0;
      const disc = parseFloat(item.discount) || 0;
      
      const itemSubtotal = qty * price;
      const taxable = itemSubtotal - disc;
      const tax = taxable * (rate / 100);
      
      grossTotal += itemSubtotal;
      taxTotal += tax;
      itemsQty += qty;
    });

    const overallDiscount = parseFloat(totalDiscount) || 0;
    const finalAmount = grossTotal - overallDiscount + taxTotal;

    return {
      grossTotal,
      taxTotal,
      itemsQty,
      finalAmount
    };
  };

  const totals = calculateTotals();

  const handleSubmit = async (shouldUpload = false) => {
    // Basic validations
    if (items.some(item => !item.itemName.trim() || item.price <= 0)) {
      setError("Please fill out all item names and enter valid prices > 0.");
      return;
    }

    setSaving(true);
    setError('');

    try {
      // 1. Create Invoice Draft on backend
      const response = await fetch('/api/invoices', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          buyerName: buyer.name,
          buyerNtn: buyer.ntn,
          buyerCnic: buyer.cnic,
          buyerPhone: buyer.phone,
          invoiceType: parseInt(invoiceType),
          paymentMode: parseInt(paymentMode),
          totalDiscount: parseFloat(totalDiscount) || 0,
          eventType,
          eventDate,
          numberOfGuests: parseInt(numberOfGuests),
          items: items.map(item => ({
            ...item,
            quantity: parseInt(item.quantity),
            price: parseFloat(item.price),
            taxRate: parseFloat(item.taxRate),
            discount: parseFloat(item.discount)
          }))
        })
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.error || "Failed to create invoice");
      }

      const invoiceId = data.invoice.id;

      // 2. If user requested upload to PRA immediately
      if (shouldUpload) {
        const uploadResponse = await fetch(`/api/invoices/${invoiceId}/upload`, {
          method: 'POST'
        });
        const uploadData = await uploadResponse.json();
        
        if (!uploadResponse.ok) {
          setError(`Invoice saved as Draft, but PRA integration failed: ${uploadData.error}`);
          setSaving(false);
          // Redirect to detail page so they can review the failure logs and retry
          router.push(`/invoices/${invoiceId}`);
          return;
        }
      }

      router.push(`/invoices/${invoiceId}`);
    } catch (err) {
      console.error(err);
      setError(err.message || "An error occurred while saving.");
      setSaving(false);
    }
  };

  return (
    <div className="content-body animate-fade-in">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <div>
          <h1 className="page-title">Create Marriage Hall Invoice</h1>
          <p style={{ color: 'var(--text-muted)', marginTop: '0.25rem' }}>Construct banquet hall bookings and register them under PRA regulatory services</p>
        </div>
      </div>

      {error && (
        <div style={{
          padding: '1rem',
          borderRadius: '8px',
          backgroundColor: 'rgba(239, 68, 68, 0.08)',
          color: 'var(--danger)',
          border: '1px solid var(--danger)',
          marginBottom: '1.5rem'
        }}>
          ⚠️ {error}
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 340px', gap: '2rem', alignItems: 'start' }}>
        
        <div style={{ display: 'flex', flexDirection: 'column', gap: '2rem' }}>
          
          {/* Marriage Hall Event Specifications */}
          <div className="card">
            <h3 style={{ marginBottom: '1.25rem', fontSize: '1.1rem', borderBottom: '1px solid var(--panel-border)', paddingBottom: '0.5rem', color: 'var(--primary)' }}>🎪 Event details</h3>
            
            <div className="form-row">
              <div className="form-group">
                <label className="form-label">Event Type</label>
                <select 
                  value={eventType} 
                  onChange={(e) => setEventType(e.target.value)}
                  className="form-control"
                >
                  <option value="Barat">Barat Reception</option>
                  <option value="Valima">Valima Reception</option>
                  <option value="Mehndi">Mehndi / Mayun Event</option>
                  <option value="Engagement">Engagement Ceremony</option>
                  <option value="Corporate">Corporate Event</option>
                  <option value="Other">Other Event</option>
                </select>
              </div>
              
              <div className="form-group">
                <label className="form-label">Event Date</label>
                <input 
                  type="date" 
                  value={eventDate} 
                  onChange={(e) => setEventDate(e.target.value)} 
                  className="form-control" 
                />
              </div>

              <div className="form-group">
                <label className="form-label">Number of Guests</label>
                <input 
                  type="number" 
                  min="1"
                  value={numberOfGuests} 
                  onChange={handleGuestsChange} 
                  className="form-control" 
                  placeholder="e.g. 250"
                />
              </div>
            </div>
          </div>

          {/* Buyer Panel */}
          <div className="card">
            <h3 style={{ marginBottom: '1.25rem', fontSize: '1.1rem', borderBottom: '1px solid var(--panel-border)', paddingBottom: '0.5rem' }}>👤 Customer / Host Details</h3>
            
            <div className="form-row">
              <div className="form-group">
                <label className="form-label">Host Name</label>
                <input 
                  type="text" 
                  name="name" 
                  value={buyer.name} 
                  onChange={handleBuyerChange} 
                  placeholder="e.g. Bilal Taylor" 
                  className="form-control" 
                  required
                />
              </div>
              <div className="form-group">
                <label className="form-label">Host Phone</label>
                <input 
                  type="text" 
                  name="phone" 
                  value={buyer.phone} 
                  onChange={handleBuyerChange} 
                  placeholder="e.g. 0300-1234567" 
                  className="form-control" 
                />
              </div>
            </div>
            
            <div className="form-row" style={{ marginTop: '0.5rem' }}>
              <div className="form-group">
                <label className="form-label">Host NTN (if tax filer)</label>
                <input 
                  type="text" 
                  name="ntn" 
                  value={buyer.ntn} 
                  onChange={handleBuyerChange} 
                  placeholder="e.g. 1234567-8" 
                  className="form-control" 
                />
              </div>
              <div className="form-group">
                <label className="form-label">Host CNIC</label>
                <input 
                  type="text" 
                  name="cnic" 
                  value={buyer.cnic} 
                  onChange={handleBuyerChange} 
                  placeholder="e.g. 35201-1234567-1" 
                  className="form-control" 
                />
              </div>
            </div>
          </div>

          {/* Line Items Panel */}
          <div className="card">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem', borderBottom: '1px solid var(--panel-border)', paddingBottom: '0.5rem' }}>
              <h3 style={{ fontSize: '1.1rem' }}>🛒 Services & Charges Breakdown</h3>
              <button type="button" onClick={addItemRow} className="btn btn-secondary" style={{ padding: '0.4rem 0.8rem', fontSize: '0.85rem' }}>
                ➕ Add Service Item
              </button>
            </div>

            <div className="table-container">
              <table className="table">
                <thead>
                  <tr>
                    <th style={{ width: '110px' }}>Code</th>
                    <th>Service Description</th>
                    <th style={{ width: '90px' }}>Qty / Heads</th>
                    <th style={{ width: '120px' }}>Rate (PKR)</th>
                    <th style={{ width: '110px' }}>PST Rate</th>
                    <th style={{ width: '100px' }}>Discount</th>
                    <th style={{ width: '110px' }}>Subtotal</th>
                    <th style={{ width: '50px' }}></th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((item, idx) => {
                    const rowSubtotal = (item.quantity * item.price) - (item.discount || 0);
                    return (
                      <tr key={idx}>
                        <td>
                          <input 
                            type="text" 
                            value={item.itemCode} 
                            onChange={(e) => handleItemChange(idx, 'itemCode', e.target.value)}
                            className="form-control" 
                            style={{ padding: '0.5rem' }}
                          />
                        </td>
                        <td>
                          <input 
                            type="text" 
                            placeholder="e.g. Stage Decoration / Catering Service" 
                            value={item.itemName} 
                            onChange={(e) => handleItemChange(idx, 'itemName', e.target.value)}
                            className="form-control" 
                            style={{ padding: '0.5rem' }}
                            required
                          />
                        </td>
                        <td>
                          <input 
                            type="number" 
                            min="1" 
                            value={item.quantity} 
                            onChange={(e) => handleItemChange(idx, 'quantity', parseInt(e.target.value) || 1)}
                            className="form-control" 
                            style={{ padding: '0.5rem' }}
                          />
                        </td>
                        <td>
                          <input 
                            type="number" 
                            min="0" 
                            value={item.price} 
                            onChange={(e) => handleItemChange(idx, 'price', parseFloat(e.target.value) || 0)}
                            className="form-control" 
                            style={{ padding: '0.5rem' }}
                          />
                        </td>
                        <td>
                          <select 
                            value={item.taxRate} 
                            onChange={(e) => handleItemChange(idx, 'taxRate', parseFloat(e.target.value) || 0)}
                            className="form-control" 
                            style={{ padding: '0.5rem' }}
                          >
                            <option value="16">16% (Standard)</option>
                            <option value="15">15%</option>
                            <option value="8">8% (Banquet Halls)</option>
                            <option value="5">5% (Catering)</option>
                            <option value="0">0% (Exempt)</option>
                          </select>
                        </td>
                        <td>
                          <input 
                            type="number" 
                            min="0" 
                            value={item.discount} 
                            onChange={(e) => handleItemChange(idx, 'discount', parseFloat(e.target.value) || 0)}
                            className="form-control" 
                            style={{ padding: '0.5rem' }}
                          />
                        </td>
                        <td style={{ fontWeight: '600', paddingLeft: '0.5rem', whiteSpace: 'nowrap' }}>
                          PKR {rowSubtotal.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}
                        </td>
                        <td>
                          <button 
                            type="button" 
                            onClick={() => removeItemRow(idx)} 
                            className="btn btn-secondary" 
                            style={{ padding: '0.3rem 0.6rem', color: 'var(--danger)', borderColor: 'rgba(239, 68, 68, 0.2)' }}
                            disabled={items.length === 1}
                          >
                            🗑️
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

        </div>

        {/* Side Panel: Metadata & Total Summary */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '2rem' }}>
          
          <div className="card">
            <h3 style={{ marginBottom: '1rem', fontSize: '1rem', borderBottom: '1px solid var(--panel-border)', paddingBottom: '0.5rem' }}>⚙️ Payment Info</h3>
            
            <div className="form-group">
              <label className="form-label">Payment Mode</label>
              <select 
                value={paymentMode} 
                onChange={(e) => setPaymentMode(e.target.value)}
                className="form-control"
              >
                <option value="1">Cash Payment</option>
                <option value="2">Bank Cheque / Pay Order</option>
                <option value="3">Credit Card swipe</option>
                <option value="4">Online Bank Transfer</option>
              </select>
            </div>

            <div className="form-group" style={{ marginTop: '0.5rem' }}>
              <label className="form-label">Invoice Discount (PKR)</label>
              <input 
                type="number" 
                min="0" 
                value={totalDiscount} 
                onChange={(e) => setTotalDiscount(parseFloat(e.target.value) || 0)} 
                className="form-control" 
                placeholder="Overall Discount" 
              />
            </div>
          </div>

          <div className="card glow-primary" style={{ border: '1px solid var(--primary)', backgroundColor: 'var(--primary-glow)' }}>
            <h3 style={{ marginBottom: '1.25rem', fontSize: '1rem', borderBottom: '1px solid rgba(37,99,235,0.2)', paddingBottom: '0.5rem', color: 'var(--primary)' }}>📈 Bill Summary</h3>
            
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', fontSize: '0.95rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: 'var(--text-muted)' }}>Estimated Guests</span>
                <span>{numberOfGuests}</span>
              </div>
              
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: 'var(--text-muted)' }}>Gross Services Total</span>
                <span>PKR {totals.grossTotal.toFixed(0)}</span>
              </div>
              
              {parseFloat(totalDiscount) > 0 && (
                <div style={{ display: 'flex', justifyContent: 'space-between', color: 'var(--danger)' }}>
                  <span>Discount</span>
                  <span>-PKR {parseFloat(totalDiscount).toFixed(0)}</span>
                </div>
              )}
              
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: 'var(--text-muted)' }}>Punjab Sales Tax (PST)</span>
                <span>PKR {totals.taxTotal.toFixed(0)}</span>
              </div>

              <div style={{ height: '1px', backgroundColor: 'rgba(37,99,235,0.2)', margin: '0.5rem 0' }}></div>
              
              <div style={{ display: 'flex', justifyContent: 'space-between', fontWeight: '700', fontSize: '1.2rem', color: 'var(--primary)' }}>
                <span>Total Net Bill</span>
                <span>PKR {totals.finalAmount.toFixed(0)}</span>
              </div>
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', marginTop: '1.5rem' }}>
              <button 
                type="button" 
                onClick={() => handleSubmit(true)} 
                className="btn btn-success" 
                disabled={saving}
              >
                🚀 {saving ? 'Processing...' : 'Save & Upload to PRA'}
              </button>
              
              <button 
                type="button" 
                onClick={() => handleSubmit(false)} 
                className="btn btn-primary" 
                disabled={saving}
              >
                💾 Save as Draft Only
              </button>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
}
