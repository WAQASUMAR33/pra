@extends('layouts.app')

@section('title', 'Marriage Hall e-IMS Dashboard')

@section('content')
<div class="content-body animate-fade-in">
    {/* Header */}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 class="page-title">Marriage Hall e-IMS Dashboard</h1>
            <p style="color: var(--text-muted); margin-top: 0.25rem;">Management and PRA validation monitoring for Banquet events</p>
        </div>
        
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            🎪 New Event Invoice
        </a>
    </div>

    {/* Stats Cards Grid */}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        
        <div class="card">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500;">Banquet Revenue (Inc. Tax)</div>
            <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.5rem; color: var(--primary);">
                PKR {{ number_format($stats['totalSales'], 0) }}
            </div>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">Total recorded bookings</small>
        </div>
        
        <div class="card">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500;">Punjab Sales Tax (PST)</div>
            <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.5rem; color: var(--success);">
                PKR {{ number_format($stats['totalTax'], 0) }}
            </div>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">PST payable to PRA (16%)</small>
        </div>
        
        <div class="card">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500;">PRA Upload Success Rate</div>
            <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.5rem; color: {{ $stats['successRate'] > 75 ? 'var(--success)' : 'var(--warning)' }};">
                {{ $stats['successRate'] }}%
            </div>
            <div style="width: 100%; height: 4px; background-color: var(--panel-border); border-radius: 2px; margin-top: 0.75rem; overflow: hidden;">
                <div style="width: {{ $stats['successRate'] }}%; height: 100%; background-color: {{ $stats['successRate'] > 75 ? 'var(--success)' : 'var(--warning)' }};"></div>
            </div>
        </div>
        
        <div class="card">
            <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500;">Pending PRA Uploads</div>
            <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.5rem; color: {{ $stats['pendingUploads'] > 0 ? 'var(--warning)' : 'var(--text-muted)' }};">
                {{ $stats['pendingUploads'] }}
            </div>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">Awaiting e-IMS filing</small>
        </div>

    </div>

    {/* Invoices List Table */}
    <div class="card">
        <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem;">📋 Recent Event Bookings</h3>
        
        @if ($invoices->isEmpty())
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">No event bookings registered yet.</p>
                <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                    Register First Booking
                </a>
            </div>
        @else
            <div class="table-container">
                <table class="table">
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
                        @foreach ($invoices as $inv)
                            <tr>
                                <td style="font-weight: 600;">{{ $inv->invoiceNumber }}</td>
                                <td>{{ $inv->buyerName ?: 'Anonymous' }}</td>
                                <td style="font-weight: 600; color: var(--primary);">{{ $inv->eventType ?: 'Sale' }}</td>
                                <td>{{ $inv->eventDate ? $inv->eventDate->format('d/m/Y') : 'N/A' }}</td>
                                <td>{{ $inv->numberOfGuests ? $inv->numberOfGuests . ' guests' : 'N/A' }}</td>
                                <td style="font-weight: 600;">PKR {{ number_format($inv->totalBillAmount, 0) }}</td>
                                <td style="color: var(--success);">PKR {{ number_format($inv->totalTaxCharged, 0) }}</td>
                                <td>
                                    @php
                                        $badgeClass = 'badge-draft';
                                        if ($inv->status === 'SUCCESS') $badgeClass = 'badge-success';
                                        elseif ($inv->status === 'PENDING') $badgeClass = 'badge-pending';
                                        elseif ($inv->status === 'FAILED') $badgeClass = 'badge-danger';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                                        👁️ View / Print
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
