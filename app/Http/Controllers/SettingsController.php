<?php

namespace App\Http\Controllers;

use App\Models\MerchantConfig;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $config = MerchantConfig::where('isActive', true)->first();

        if (!$config) {
            $config = new MerchantConfig([
                'posId' => '820816',
                'token' => '2D79A61F',
                'branchName' => 'Lahore Main Branch',
                'branchAddress' => 'Gulberg III, Lahore, Punjab',
                'apiUrl' => 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData',
                'isActive' => true
            ]);
        }

        return view('settings', compact('config'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if ($request->isJson() || $request->wantsJson()) {
            $data = json_decode($request->getContent(), true) ?: $request->all();
        }

        $posId = $data['posId'] ?? null;
        $token = $data['token'] ?? null;
        $branchName = $data['branchName'] ?? null;
        $branchAddress = $data['branchAddress'] ?? null;
        $apiUrl = $data['apiUrl'] ?? 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData';

        if (!$posId || !$token || !$branchName || !$branchAddress) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }
            return redirect()->back()->withErrors(['error' => 'Missing required fields'])->withInput();
        }

        // Deactivate all previous configurations
        MerchantConfig::query()->update(['isActive' => false]);

        // Create new config
        $config = MerchantConfig::create([
            'posId' => $posId,
            'token' => $token,
            'branchName' => $branchName,
            'branchAddress' => $branchAddress,
            'apiUrl' => $apiUrl,
            'isActive' => true
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'config' => $config]);
        }

        return redirect()->route('settings')->with('success', 'PRA configurations saved successfully!');
    }
}
