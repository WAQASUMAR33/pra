@extends('layouts.app')

@section('title', '🎪 New Event Invoice')

@section('content')
<div class="content-body animate-fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 class="page-title">🎪 New Event Invoice</h1>
            <p style="color: var(--text-muted); margin-top: 0.25rem;">Register a banquet event booking draft and sync it with FBR/PRA e-IMS</p>
        </div>
    </div>

    <div id="error-alert" style="display: none; padding: 1rem; border-radius: 8px; background-color: var(--danger-glow); color: var(--danger); border: 1px solid var(--danger); margin-bottom: 1.5rem;">
    </div>

    <form id="invoice-form" onsubmit="saveInvoice(event)" style="display: flex; flex-direction: column; gap: 2rem;">
        
        {/* Row 1: Customer & Event details */}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            
            <div class="card">
                <h3 style="margin-bottom: 1.25rem; font-size: 1rem; border-bottom: 1px solid var(--panel-border); padding-bottom: 0.5rem;">👤 Host Details</h3>
                
                <div class="form-group">
                    <label class="form-label">Buyer/Host Name</label>
                    <input type="text" id="buyerName" placeholder="Enter name of host" class="form-control" required />
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">CNIC (Without Dashes)</label>
                        <input type="text" id="buyerCnic" placeholder="e.g. 3520101234567" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">NTN (Optional)</label>
                        <input type="text" id="buyerNtn" placeholder="e.g. 1234567-8" class="form-control" />
                    </div>
                </div>

                <div class="form-group" style="margin-top: 0.5rem;">
                    <label class="form-label">Contact Number</label>
                    <input type="text" id="buyerPhone" placeholder="e.g. 03001234567" class="form-control" />
                </div>
            </div>

            <div class="card">
                <h3 style="margin-bottom: 1.25rem; font-size: 1rem; border-bottom: 1px solid var(--panel-border); padding-bottom: 0.5rem;">📅 Event Specifications</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Event Type</label>
                        <select id="eventType" class="form-control" required>
                            <option value="Barat">Barat Reception</option>
                            <option value="Walima">Walima Reception</option>
                            <option value="Mehndi">Mehndi Event</option>
                            <option value="Engagement">Engagement Party</option>
                            <option value="Corporate">Corporate Conference</option>
                            <option value="Other">Other Event</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event Date</label>
                        <input type="date" id="eventDate" class="form-control" required />
                    </div>
                </div>

                <div class="form-row" style="margin-top: 0.5rem;">
                    <div class="form-group">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" id="numberOfGuests" placeholder="e.g. 250" class="form-control" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Mode</label>
                        <select id="paymentMode" class="form-control" required>
                            <option value="1">Cash</option>
                            <option value="2">Bank Cheque</option>
                            <option value="3">Credit Card</option>
                            <option value="4">Bank Transfer</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        {/* Row 2: Billing Services items */}
        <div class="card">
            <h3 style="margin-bottom: 1.25rem; font-size: 1rem; border-bottom: 1px solid var(--panel-border); padding-bottom: 0.5rem;">📋 Billing Services & Menu Packages</h3>
            
            <div class="table-container">
                <table class="table" id="items-table">
                    <thead>
                        <tr>
                            <th>Service Details</th>
                            <th style="width: 100px;">Qty</th>
                            <th style="width: 130px;">Unit Price (PKR)</th>
                            <th style="width: 110px;">Tax Rate</th>
                            <th style="width: 110px;">Discount</th>
                            <th style="width: 150px; text-align: right;">Net Total</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        {/* Will be populated dynamically via JavaScript */}
                    </tbody>
                </table>
            </div>

            <button type="button" onclick="addNewItem()" class="btn btn-secondary" style="margin-top: 1rem;">
                ➕ Add Custom Service Row
            </button>

            {/* Bottom summary fields */}
            <div style="margin-top: 2rem; border-top: 1px solid var(--panel-border); padding-top: 1.5rem; display: grid; grid-template-columns: 1fr 350px;">
                <div>
                    <label style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500; font-size: 0.95rem; color: var(--text-main);">
                        <input type="checkbox" id="autoUpload" style="width: 16px; height: 16px;" checked />
                        🚀 Fiscally register/upload to PRA automatically upon saving
                    </label>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.95rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Gross Bill Total:</span>
                        <span id="label-gross">PKR 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Total Discount:</span>
                        <span id="label-discount" style="color: var(--danger);">-PKR 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Punjab Sales Tax (PST):</span>
                        <span id="label-tax" style="color: var(--success);">PKR 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem; border-top: 1px dashed var(--panel-border); padding-top: 0.5rem; margin-top: 0.25rem;">
                        <span>Net Bill Amount:</span>
                        <span id="label-net">PKR 0</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem; border-top: 1px solid var(--panel-border); padding-top: 1.5rem; justify-content: flex-end;">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" id="submit-btn" class="btn btn-primary">
                    💾 Create and Save Invoice
                </button>
            </div>
        </div>

    </form>
</div>

<script>
    // Initial dynamic state
    let itemsList = [
        { itemCode: 'SRV-RENT', itemName: 'Marriage Hall Base Rent', quantity: 1, price: 150000, taxRate: 16, discount: 0 },
        { itemCode: 'SRV-CATER', itemName: 'Banquet Catering (Per Head Menu)', quantity: 200, price: 1800, taxRate: 16, discount: 0 }
    ];

    document.addEventListener("DOMContentLoaded", () => {
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('eventDate').value = today;
        
        renderItems();
    });

    function renderItems() {
        const tbody = document.getElementById('items-tbody');
        tbody.innerHTML = '';

        itemsList.forEach((item, idx) => {
            const tr = document.createElement('tr');
            
            // Calculate item net total
            const gross = item.quantity * item.price;
            const tax = (gross - item.discount) * (item.taxRate / 100);
            const net = gross - item.discount + tax;

            tr.innerHTML = `
                <td>
                    <input type="text" value="${item.itemName}" onchange="updateItemField(${idx}, 'itemName', this.value)" placeholder="e.g. Stage Decoration" class="form-control" required />
                    <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">Code: 
                        <input type="text" value="${item.itemCode}" onchange="updateItemField(${idx}, 'itemCode', this.value)" style="border: none; border-bottom: 1px dotted #94a3b8; outline: none; width: 80px; font-family: monospace; font-size: 0.75rem; background: transparent; padding: 0;" />
                    </small>
                </td>
                <td>
                    <input type="number" value="${item.quantity}" onchange="updateItemField(${idx}, 'quantity', parseInt(this.value) || 0)" class="form-control" required min="1" />
                </td>
                <td>
                    <input type="number" value="${item.price}" onchange="updateItemField(${idx}, 'price', parseFloat(this.value) || 0)" class="form-control" required min="0" />
                </td>
                <td>
                    <select onchange="updateItemField(${idx}, 'taxRate', parseFloat(this.value))" class="form-control">
                        <option value="16" ${item.taxRate === 16 ? 'selected' : ''}>16% PST</option>
                        <option value="5" ${item.taxRate === 5 ? 'selected' : ''}>5% Services</option>
                        <option value="0" ${item.taxRate === 0 ? 'selected' : ''}>0% Exempt</option>
                    </select>
                </td>
                <td>
                    <input type="number" value="${item.discount}" onchange="updateItemField(${idx}, 'discount', parseFloat(this.value) || 0)" class="form-control" min="0" />
                </td>
                <td style="text-align: right; font-weight: 600; vertical-align: middle;">
                    PKR ${net.toLocaleString(undefined, {maximumFractionDigits: 0})}
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <button type="button" onclick="removeItem(${idx})" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.1rem;">🗑️</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        calculateTotals();
    }

    function addNewItem() {
        itemsList.push({
            itemCode: 'SRV-CUSTOM',
            itemName: 'Stage Lighting & Sound FX',
            quantity: 1,
            price: 25000,
            taxRate: 16,
            discount: 0
        });
        renderItems();
    }

    function removeItem(idx) {
        if (itemsList.length <= 1) {
            alert('Invoice must contain at least one billing item.');
            return;
        }
        itemsList.splice(idx, 1);
        renderItems();
    }

    function updateItemField(idx, field, value) {
        itemsList[idx][field] = value;
        renderItems();
    }

    function calculateTotals() {
        let grossTotal = 0;
        let discountTotal = 0;
        let taxTotal = 0;

        itemsList.forEach(item => {
            const gross = item.quantity * item.price;
            grossTotal += gross;
            discountTotal += item.discount;
            taxTotal += (gross - item.discount) * (item.taxRate / 100);
        });

        const netBill = grossTotal - discountTotal + taxTotal;

        document.getElementById('label-gross').innerText = 'PKR ' + grossTotal.toLocaleString(undefined, {maximumFractionDigits: 0});
        document.getElementById('label-discount').innerText = '-PKR ' + discountTotal.toLocaleString(undefined, {maximumFractionDigits: 0});
        document.getElementById('label-tax').innerText = 'PKR ' + taxTotal.toLocaleString(undefined, {maximumFractionDigits: 0});
        document.getElementById('label-net').innerText = 'PKR ' + netBill.toLocaleString(undefined, {maximumFractionDigits: 0});
    }

    async function saveInvoice(event) {
        event.preventDefault();
        
        const submitBtn = document.getElementById('submit-btn');
        const alertEl = document.getElementById('error-alert');
        
        submitBtn.innerText = 'Saving...';
        submitBtn.disabled = true;
        alertEl.style.display = 'none';

        const payload = {
            buyerName: document.getElementById('buyerName').value,
            buyerCnic: document.getElementById('buyerCnic').value,
            buyerNtn: document.getElementById('buyerNtn').value,
            buyerPhone: document.getElementById('buyerPhone').value,
            eventType: document.getElementById('eventType').value,
            eventDate: document.getElementById('eventDate').value,
            numberOfGuests: document.getElementById('numberOfGuests').value,
            paymentMode: document.getElementById('paymentMode').value,
            items: itemsList
        };

        try {
            // Save invoice draft
            const response = await fetch("{{ route('invoices.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (response.ok && data.success) {
                const invoiceId = data.invoice.id;
                
                // If autoUpload is checked, trigger upload automatically
                const autoUpload = document.getElementById('autoUpload').checked;
                if (autoUpload) {
                    submitBtn.innerText = 'Submitting to PRA...';
                    
                    const uploadResponse = await fetch(`/invoices/${invoiceId}/upload`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                }
                
                window.location.href = `/invoices/${invoiceId}`;
            } else {
                alertEl.innerText = '⚠️ ' + (data.error || 'Failed to save invoice.');
                alertEl.style.display = 'block';
                submitBtn.innerText = '💾 Create and Save Invoice';
                submitBtn.disabled = false;
            }

        } catch (err) {
            alertEl.innerText = '⚠️ Connection error. Unable to contact billing server.';
            alertEl.style.display = 'block';
            submitBtn.innerText = '💾 Create and Save Invoice';
            submitBtn.disabled = false;
        }
    }
</script>
@endsection
