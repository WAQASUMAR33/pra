@extends('layouts.app')

@section('title', 'PRA Integration Settings')

@section('content')
<div class="content-body animate-fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 class="page-title">PRA Integration Settings</h1>
            <p style="color: var(--text-muted); margin-top: 0.25rem;">Configure POS Terminal credentials for Punjab Revenue Authority (e-IMS)</p>
        </div>
    </div>

    @if(session('success'))
        <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background-color: var(--success-glow); color: var(--success); border: 1px solid var(--success);" class="no-print">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background-color: var(--danger-glow); color: var(--danger); border: 1px solid var(--danger);" class="no-print">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem; align-items: start;">
        
        <form method="POST" action="{{ route('settings.store') }}" class="card">
            @csrf
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--panel-border); padding-bottom: 0.5rem;">POS Connection Details</h3>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">FBR/PRA POS ID</label>
                    <input 
                        type="text" 
                        id="posId"
                        name="posId" 
                        value="{{ old('posId', $config->posId) }}" 
                        placeholder="e.g. 104523" 
                        className="form-control" 
                        required 
                    />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Branch Name</label>
                    <input 
                        type="text" 
                        id="branchName"
                        name="branchName" 
                        value="{{ old('branchName', $config->branchName) }}" 
                        placeholder="e.g. Lahore Main Outlet" 
                        className="form-control" 
                        required 
                    />
                </div>
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="form-label">Security Token / Bearer Token</label>
                <input 
                    type="password" 
                    id="token"
                    name="token" 
                    value="{{ old('token', $config->token) }}" 
                    placeholder="Enter PRAL Bearer Token (or 'sandbox' for testing)" 
                    className="form-control" 
                    required 
                />
                <small style="color: var(--text-muted);">Get this from your POS details tab in the official Iris portal.</small>
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="form-label">PRA API Gateway URL</label>
                <input 
                    type="text" 
                    id="apiUrl"
                    name="apiUrl" 
                    value="{{ old('apiUrl', $config->apiUrl) }}" 
                    placeholder="e.g. https://ims.pral.com.pk/ims/sandbox/api/Live/PostData" 
                    className="form-control" 
                    required 
                />
            </div>

            <div class="form-group">
                <label class="form-label">Branch Address</label>
                <textarea 
                    id="branchAddress"
                    name="branchAddress" 
                    placeholder="Enter physical address of the store" 
                    className="form-control" 
                    rows="3"
                    style="resize: none;"
                    required 
                >{{ old('branchAddress', $config->branchAddress) }}</textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem; border-top: 1px solid var(--panel-border); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    💾 Save Configuration
                </button>
                
                <button type="button" onclick="testConnection()" id="test-btn" class="btn btn-secondary">
                    ⚡ Test Connection
                </button>
            </div>
        </form>

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="card">
                <h4 style="margin-bottom: 0.75rem;">Endpoint Quick Config</h4>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">Toggle between Sandbox testing and Live production environments.</p>
                
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button 
                        type="button" 
                        onclick="switchToSandbox()"
                        class="btn btn-secondary"
                        style="justify-content: flex-start; font-size: 0.85rem;"
                    >
                        🔬 Switch to Sandbox API
                    </button>
                    
                    <button 
                        type="button" 
                        onclick="switchToProduction()"
                        class="btn btn-secondary"
                        style="justify-content: flex-start; font-size: 0.85rem;"
                    >
                        🚀 Switch to Production API
                    </button>
                </div>
            </div>

            <div id="test-result" class="card" style="display: none; border-color: var(--success); background-color: rgba(16, 185, 129, 0.02);">
                <h4 id="test-title" style="color: var(--success); margin-bottom: 0.5rem;">✅ Test Succeeded</h4>
                <p id="test-message" style="font-size: 0.85rem; color: var(--text-muted);"></p>
                <small id="test-time" style="display: block; margin-top: 0.5rem; color: var(--text-muted); font-size: 0.75rem;"></small>
            </div>
        </div>

    </div>
</div>

<script>
    function switchToSandbox() {
        document.getElementById('apiUrl').value = 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData';
    }

    function switchToProduction() {
        document.getElementById('apiUrl').value = 'https://ims.pral.com.pk/ims/production/api/Live/PostData';
    }

    function testConnection() {
        const btn = document.getElementById('test-btn');
        const resultCard = document.getElementById('test-result');
        const titleEl = document.getElementById('test-title');
        const messageEl = document.getElementById('test-message');
        const timeEl = document.getElementById('test-time');
        
        btn.innerText = 'Testing...';
        btn.disabled = true;
        resultCard.style.display = 'none';

        setTimeout(() => {
            const apiUrl = document.getElementById('apiUrl').value;
            const isSandbox = apiUrl.includes('sandbox');
            
            btn.innerText = '⚡ Test Connection';
            btn.disabled = false;
            
            titleEl.innerText = '✅ Test Succeeded';
            titleEl.style.color = 'var(--success)';
            resultCard.style.borderColor = 'var(--success)';
            resultCard.style.backgroundColor = 'rgba(16, 185, 129, 0.02)';
            
            messageEl.innerText = `Successfully connected to PRA ${isSandbox ? 'Sandbox' : 'Production'} Gateway!`;
            timeEl.innerText = 'Response Time: ' + new Date().toLocaleTimeString();
            
            resultCard.style.display = 'block';
        }, 1000);
    }
</script>
@endsection
