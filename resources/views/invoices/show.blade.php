@extends('layouts.app')

@section('title', 'Invoice Details - ' . $invoice->invoiceNumber)

@section('content')
@php
    $qrData = $invoice->praFiscalNumber 
        ? "https://e.pra.punjab.gov.pk/verify?fiscalNumber=" . urlencode($invoice->praFiscalNumber) . "&usin=" . urlencode($invoice->usin) . "&posid=" . urlencode($invoice->posId)
        : "Local USIN: " . $invoice->usin;
    
    $qrCodeImgUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrData);
@endphp

<div class="content-body animate-fade-in">
    {/* Action Header */}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;" class="no-print">
        <div>
            <h1 class="page-title">Invoice: {{ $invoice->invoiceNumber }}</h1>
            <p style="color: var(--text-muted); margin-top: 0.25rem;">USIN: {{ $invoice->usin }}</p>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button onclick="window.print()" class="btn btn-secondary">
                🖨️ Print Invoice Receipt
            </button>
            
            <button 
                id="delete-btn"
                onclick="deleteInvoice()" 
                class="btn btn-secondary" 
                style="color: var(--danger); border-color: rgba(239,68,68,0.2);"
            >
                🗑️ Delete Draft
            </button>

            @if($invoice->status === 'DRAFT' || $invoice->status === 'FAILED')
                <button 
                    id="upload-btn"
                    onclick="uploadToPRA()" 
                    class="btn btn-success"
                >
                    🚀 Upload to PRA
                </button>
            @endif
        </div>
    </div>

    <div id="error-alert" style="display: none; padding: 1rem; border-radius: 8px; background-color: var(--danger-glow); color: var(--danger); border: 1px solid var(--danger); margin-bottom: 1.5rem;" class="no-print">
    </div>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem; align-items: start;">
        
        {/* Visual Marriage Hall POS Receipt Card */}
        <div class="card" style="max-width: 480px; margin: 0 auto; background-color: #ffffff; color: #0f172a; font-family: monospace; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #cbd5e1; border-radius: 4px;">
            
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.3rem; font-weight: bold; text-transform: uppercase; margin-bottom: 0.25rem; letter-spacing: 0.5px;">
                    {{ $config->branchName ?: "Punjab Marriage Hall" }}
                </h2>
                <p style="font-size: 0.8rem; color: #475569;">
                    {{ $config->branchAddress ?: "Lahore, Punjab" }}
                </p>
                <p style="font-size: 0.85rem; font-weight: 600; margin-top: 0.5rem;">
                    POS ID: {{ $invoice->posId }}
                </p>
                
                <div style="margin: 1rem 0; border-top: 1px dashed #475569; border-bottom: 1px dashed #475569; padding: 0.25rem 0;">
                    <span style="font-size: 0.9rem; font-weight: bold; text-transform: uppercase;">Marriage Hall Booking Invoice</span>
                </div>
            </div>

            <div style="font-size: 0.85rem; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.35rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span>BILL NO:</span>
                    <span style="font-weight: bold;">{{ $invoice->invoiceNumber }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>DATE/TIME:</span>
                    <span>{{ $invoice->dateTime->format('d/m/Y h:i A') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>PAY MODE:</span>
                    <span>
                        @if($invoice->paymentMode === 1) Cash
                        @elseif($invoice->paymentMode === 2) Bank Cheque
                        @elseif($invoice->paymentMode === 3) Credit Card
                        @elseif($invoice->paymentMode === 4) Bank Transfer
                        @else Other
                        @endif
                    </span>
                </div>

                {/* Marriage Hall Event Specifications */}
                <div style="border-top: 1px dotted #94a3b8; border-bottom: 1px dotted #94a3b8; padding: 0.5rem 0; margin: 0.5rem 0; display: flex; flex-direction: column; gap: 0.25rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 600;">EVENT TYPE:</span>
                        <span style="font-weight: bold;">{{ $invoice->eventType ?: 'N/A' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 600;">EVENT DATE:</span>
                        <span>{{ $invoice->eventDate ? $invoice->eventDate->format('l, F d, Y') : 'N/A' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 600;">GUEST COUNT:</span>
                        <span>{{ $invoice->numberOfGuests ? $invoice->numberOfGuests . ' Guests' : 'N/A' }}</span>
                    </div>
                </div>

                @if($invoice->buyerName)
                    <div style="display: flex; justify-content: space-between;">
                        <span>HOST NAME:</span>
                        <span>{{ $invoice->buyerName }}</span>
                    </div>
                @endif
                @if($invoice->buyerPhone)
                    <div style="display: flex; justify-content: space-between;">
                        <span>HOST PHONE:</span>
                        <span>{{ $invoice->buyerPhone }}</span>
                    </div>
                @endif
                @if($invoice->buyerNtn)
                    <div style="display: flex; justify-content: space-between;">
                        <span>HOST NTN:</span>
                        <span>{{ $invoice->buyerNtn }}</span>
                    </div>
                @endif
            </div>

            {/* Items Section */}
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-bottom: 1rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #0f172a;">
                        <th style="text-align: left; padding-bottom: 0.5rem;">SERVICE</th>
                        <th style="text-align: center; padding-bottom: 0.5rem; width: 50px;">QTY</th>
                        <th style="text-align: right; padding-bottom: 0.5rem; width: 80px;">RATE</th>
                        <th style="text-align: right; padding-bottom: 0.5rem; width: 80px;">TAX</th>
                        <th style="text-align: right; padding-bottom: 0.5rem; width: 90px;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr style="border-bottom: 1px dotted #cbd5e1;">
                            <td style="padding: 0.5rem 0; vertical-align: top;">
                                {{ $item->itemName }}
                                <div style="font-size: 0.75rem; color: #64748b;">Code: {{ $item->itemCode }}</div>
                            </td>
                            <td style="text-align: center; padding: 0.5rem 0; vertical-align: top;">{{ $item->quantity }}</td>
                            <td style="text-align: right; padding: 0.5rem 0; vertical-align: top;">
                                {{ number_format($item->saleValue / $item->quantity, 0) }}
                            </td>
                            <td style="text-align: right; padding: 0.5rem 0; vertical-align: top;">
                                {{ number_format($item->salesTaxApplicable, 0) }}
                                <div style="font-size: 0.7rem; color: #64748b;">({{ (float)$item->taxRate }}%)</div>
                            </td>
                            <td style="text-align: right; padding: 0.5rem 0; vertical-align: top; font-weight: bold;">
                                {{ number_format($item->netAmount, 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {/* Totals Section */}
            <div style="border-top: 1px solid #0f172a; padding-top: 0.5rem; display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span>GROSS TOTAL:</span>
                    <span>PKR {{ number_format($invoice->totalSaleValue, 0) }}</span>
                </div>
                
                @if($invoice->totalDiscount > 0)
                    <div style="display: flex; justify-content: space-between;">
                        <span>DISCOUNT:</span>
                        <span>-PKR {{ number_format($invoice->totalDiscount, 0) }}</span>
                    </div>
                @endif
                
                <div style="display: flex; justify-content: space-between;">
                    <span>PUNJAB SALES TAX (PST):</span>
                    <span>PKR {{ number_format($invoice->totalTaxCharged, 0) }}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: bold; border-top: 1px dashed #0f172a; padding-top: 0.5rem; margin-top: 0.25rem;">
                    <span>NET BILL AMOUNT:</span>
                    <span>PKR {{ number_format($invoice->totalBillAmount, 0) }}</span>
                </div>
            </div>

            {/* Fiscal Stamp */}
            <div style="border-top: 1px solid #0f172a; margin-top: 1.5rem; padding-top: 1.25rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                @if($invoice->status === 'SUCCESS')
                    <p style="font-size: 0.85rem; font-weight: bold; letter-spacing: 0.5px; color: #047857;">
                        FISCALLY REGISTERED WITH PRA
                    </p>
                    <div style="padding: 0.5rem; border: 1px solid #cbd5e1; display: inline-block; background-color: white;">
                        <img src="{{ $qrCodeImgUrl }}" alt="PRA Verification QR Code" style="display: block; width: 110px; height: 110px;" />
                    </div>
                    <div style="font-size: 0.75rem; margin-top: 0.25rem;">
                        <span style="display: block; color: #475569;">PRA FISCAL NUMBER:</span>
                        <span style="font-weight: bold; word-break: break-all;">{{ $invoice->praFiscalNumber }}</span>
                    </div>
                @else
                    <p style="font-size: 0.8rem; font-weight: bold; color: #dc2626;">
                        NOT REGISTERED WITH PRA ({{ $invoice->status }})
                    </p>
                    <p style="font-size: 0.7rem; color: #64748b;">
                        Usin: {{ $invoice->usin }}
                    </p>
                @endif
                
                <p style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.5rem;">
                    Powered by PRAL e-IMS Connector
                </p>
            </div>
        </div>

        {/* Audit Logs & Metadata Side Panel */}
        <div style="display: flex; flex-direction: column; gap: 1.5rem;" class="no-print">
            
            <div class="card">
                <h3 style="margin-bottom: 1rem; font-size: 1rem; border-bottom: 1px solid var(--panel-border); padding-bottom: 0.5rem;">📋 Audit Details</h3>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.9rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--text-muted);">Status</span>
                        @php
                            $badgeClass = 'badge-draft';
                            if ($invoice->status === 'SUCCESS') $badgeClass = 'badge-success';
                            elseif ($invoice->status === 'PENDING') $badgeClass = 'badge-pending';
                            elseif ($invoice->status === 'FAILED') $badgeClass = 'badge-danger';
                        @endphp
                        <span id="badge-status" class="badge {{ $badgeClass }}">{{ $invoice->status }}</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Local Created</span>
                        <span>{{ $invoice->createdAt->format('d/m/Y') }}</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Response Code</span>
                        <span id="label-code" style="font-weight: 600;">{{ $invoice->praResponseCode ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="card logs-panel">
                <h3 style="margin-bottom: 1rem; font-size: 1rem; border-bottom: 1px solid var(--panel-border); padding-bottom: 0.5rem;">💻 Raw PRA Gateway Logs</h3>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    
                    <div>
                        <h4 style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.25rem;">Payload Structure:</h4>
                        <pre id="log-payload" style="background-color: #f8fafc; padding: 0.75rem; border-radius: 6px; font-size: 0.75rem; overflow-x: auto; border: 1px solid var(--panel-border); color: #097969; white-space: pre-wrap; word-break: break-all;">// Submit to see formatted PRAL payload</pre>
                    </div>

                    <div>
                        <h4 style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.25rem;">API Response Log:</h4>
                        <pre id="log-response" style="background-color: #f8fafc; padding: 0.75rem; border-radius: 6px; font-size: 0.75rem; overflow-x: auto; border: 1px solid var(--panel-border); color: {{ $invoice->status === 'SUCCESS' ? '#097969' : '#dc2626' }}; white-space: pre-wrap; word-break: break-all;">@if($invoice->praResponseMsg){{ $invoice->praResponseMsg }}@else// No response logs recorded@endif</pre>
                    </div>
                    
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    async function deleteInvoice() {
        if (!confirm('Are you sure you want to delete this invoice?')) return;
        
        const deleteBtn = document.getElementById('delete-btn');
        deleteBtn.innerText = 'Deleting...';
        deleteBtn.disabled = true;

        try {
            const response = await fetch("{{ route('invoices.destroy', $invoice->id) }}", {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (response.ok && data.success) {
                window.location.href = "{{ route('dashboard') }}";
            } else {
                alert(data.error || 'Failed to delete invoice.');
                deleteBtn.innerText = '🗑️ Delete Draft';
                deleteBtn.disabled = false;
            }
        } catch (err) {
            alert('Failed to delete invoice.');
            deleteBtn.innerText = '🗑️ Delete Draft';
            deleteBtn.disabled = false;
        }
    }

    async function uploadToPRA() {
        const uploadBtn = document.getElementById('upload-btn');
        const alertEl = document.getElementById('error-alert');
        const badgeEl = document.getElementById('badge-status');
        const codeEl = document.getElementById('label-code');
        const logPayloadEl = document.getElementById('log-payload');
        const logResponseEl = document.getElementById('log-response');

        if (!uploadBtn) return;
        uploadBtn.innerText = 'Submitting...';
        uploadBtn.disabled = true;
        alertEl.style.display = 'none';

        try {
            const response = await fetch("{{ route('invoices.upload', $invoice->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (response.ok && data.success) {
                // Success: Reload page to show QR Code
                window.location.reload();
            } else {
                // Failed or validation error
                alertEl.innerText = '⚠️ ' + (data.error || 'Failed to submit invoice to PRA.');
                alertEl.style.display = 'block';
                
                // Update stats fields dynamically
                if (data.invoice) {
                    badgeEl.innerText = data.invoice.status;
                    badgeEl.className = 'badge badge-danger';
                    codeEl.innerText = data.invoice.praResponseCode || 'N/A';
                }
                
                if (data.payload) {
                    logPayloadEl.innerText = JSON.stringify(data.payload, null, 2);
                }
                if (data.response) {
                    logResponseEl.innerText = JSON.stringify(data.response, null, 2);
                    logResponseEl.style.color = '#dc2626';
                } else if (data.error) {
                    logResponseEl.innerText = data.error;
                    logResponseEl.style.color = '#dc2626';
                }

                uploadBtn.innerText = '🚀 Upload to PRA';
                uploadBtn.disabled = false;
            }
        } catch (err) {
            alertEl.innerText = '⚠️ Network error. Unable to contact upload API.';
            alertEl.style.display = 'block';
            uploadBtn.innerText = '🚀 Upload to PRA';
            uploadBtn.disabled = false;
        }
    }
</script>
@endsection
