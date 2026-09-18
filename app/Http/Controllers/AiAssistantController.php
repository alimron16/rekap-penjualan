<?php

namespace App\Http\Controllers;

use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAssistantController extends Controller
{
    protected GeminiAiService $geminiService;

    public function __construct(GeminiAiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Handle AI Assistant question from Web interface
     */
    public function askWeb(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $user = Auth::user();
        $userContext = [
            'name' => $user?->name ?? 'Kasir',
            'role' => $user?->role ?? 'Kasir',
            'outlet' => $user?->outlet?->name ?? 'Toko Kasir',
        ];

        $history = $request->input('history', []);
        $response = $this->geminiService->ask($request->message, $history, $userContext);

        return response()->json($response);
    }
}
