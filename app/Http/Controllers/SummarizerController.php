<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SummarizerController extends Controller
{
    /**
     * Available providers and their best models.
     */
    public static array $providers = [
        'groq' => [
            'label'    => 'Groq',
            'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
            'env_key'  => 'GROQ_API_KEY',
            'models'   => [
                'qwen/qwen3.8-27b'  => 'Qwen 3.8 27B (Recommended)',
                'qwen/qwen3.6-27b'  => 'Qwen 3.6 27B',
                'groq/compound'     => 'Groq Compound',
                'groq/compound-mini'=> 'Groq Compound Mini',
                'allam-2-7b'        => 'Allam 2 7B',
            ],
        ],
        'openrouter' => [
            'label'    => 'OpenRouter',
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'env_key'  => 'OPENROUTER_API_KEY',
            'models'   => [
                'nvidia/nemotron-3-ultra-550b-a55b:free'             => 'Nemotron Ultra 550B (Recommended)',
                'nvidia/nemotron-3-super-120b-a12b:free'             => 'Nemotron Super 120B',
                'google/gemma-4-31b-it:free'                         => 'Gemma 4 31B',
                'google/gemma-4-26b-a4b-it:free'                     => 'Gemma 4 26B',
                'thinkingmachines/inkling:free'                      => 'Inkling (1M ctx)',
                'nvidia/nemotron-3.5-lightning:free'                 => 'Nemotron 3.5 Lightning',
            ],
        ],
    ];

    /**
     * Show the summarizer form view.
     */
    public function index()
    {
        return view('summarizer', ['providers' => self::$providers]);
    }

    /**
     * Fetch a webpage, extract its text, and summarize via the chosen provider & model.
     */
    public function summarize(Request $request)
    {
        $validModels = collect(self::$providers)->flatMap(fn($p) => array_keys($p['models']))->toArray();

        $request->validate([
            'url'      => ['required', 'url'],
            'language' => ['nullable', 'in:english,bengali'],
            'provider' => ['required', 'in:groq,openrouter'],
            'model'    => ['required', 'in:' . implode(',', $validModels)],
        ]);

        $url      = $request->input('url');
        $language = $request->input('language', 'english');
        $provider = $request->input('provider', 'groq');
        $model    = $request->input('model');

        $providerConfig = self::$providers[$provider];

        // ── 1. Fetch the webpage ──────────────────────────────────────────────
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; SummarizerBot/1.0)',
            ])->timeout(15)->get($url);

            if ($response->failed()) {
                return back()
                    ->withInput()
                    ->with('error', "Could not fetch the page. HTTP status: {$response->status()}");
            }

            $html = $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->withInput()->with('error', 'Failed to reach the URL: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Unexpected error while fetching URL: ' . $e->getMessage());
        }

        // ── 2. Clean HTML → plain text ────────────────────────────────────────
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = mb_substr($text, 0, 4000);

        // ── 3. Build language-aware prompts ───────────────────────────────────
        if ($language === 'bengali') {
            $systemPrompt = 'আপনি একজন সহায়ক AI সহকারী। নিচের ওয়েবপেজের বিষয়বস্তু বাংলায় স্পষ্ট ও সংক্ষিপ্তভাবে কয়েকটি ছোট অনুচ্ছেদে সারসংক্ষেপ করুন। সারসংক্ষেপটি অবশ্যই বাংলায় লিখতে হবে।';
            $userPrompt   = "নিচের ওয়েবপেজের বিষয়বস্তু বাংলায় সারসংক্ষেপ করুন:\n\n{$text}";
        } else {
            $systemPrompt = 'You are a helpful assistant that summarizes web page content clearly and concisely in a few short paragraphs in English.';
            $userPrompt   = "Please summarize the following web page content in English:\n\n{$text}";
        }

        // ── 4. Build request headers per provider ─────────────────────────────
        $headers = [
            'Authorization' => 'Bearer ' . env($providerConfig['env_key']),
            'Content-Type'  => 'application/json',
        ];

        // OpenRouter requires these extra headers
        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = env('APP_URL', 'http://localhost');
            $headers['X-Title']      = 'Website Summarizer';
        }

        // ── 5. Call the API ───────────────────────────────────────────────────
        try {
            $apiResponse = Http::withHeaders($headers)
                ->timeout(60)
                ->post($providerConfig['endpoint'], [
                    'model'    => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                    'temperature' => 0.5,
                    'max_tokens'  => 700,
                ]);

            if ($apiResponse->failed()) {
                $errorBody = $apiResponse->json('error.message', 'Unknown API error.');
                return back()->withInput()->with('error', "{$providerConfig['label']} API error: {$errorBody}");
            }

            $summary = $apiResponse->json('choices.0.message.content', 'No summary returned.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->withInput()->with('error', "Failed to reach {$providerConfig['label']} API: " . $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Unexpected error while calling API: ' . $e->getMessage());
        }

        // ── 6. Return results ─────────────────────────────────────────────────
        return view('summarizer', [
            'providers' => self::$providers,
            'summary'   => $summary,
            'url'       => $url,
            'language'  => $language,
            'provider'  => $provider,
            'model'     => $model,
        ]);
    }
}
