<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SummarizerController extends Controller
{
    /**
     * Show the summarizer form view.
     */
    public function index()
    {
        return view('summarizer');
    }

    /**
     * Fetch a webpage, extract its text content, and summarize it via OpenRouter API.
     */
    public function summarize(Request $request)
    {
        $request->validate([
            'url'      => ['required', 'url'],
            'language' => ['nullable', 'in:english,bengali'],
        ]);

        $url      = $request->input('url');
        $language = $request->input('language', 'english');

        // ── 1. Fetch the webpage ──────────────────────────────────────────────
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; SummarizerBot/1.0)',
            ])->timeout(15)->get($url);

            if ($response->failed()) {
                return back()->with('error', "Could not fetch the page. HTTP status: {$response->status()}");
            }

            $html = $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->with('error', 'Failed to reach the URL: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Unexpected error while fetching URL: ' . $e->getMessage());
        }

        // ── 2. Clean HTML → plain text ────────────────────────────────────────
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = mb_substr($text, 0, 4000);

        // ── 3. Build system prompt based on language ──────────────────────────
        if ($language === 'bengali') {
            $systemPrompt = 'আপনি একজন সহায়ক AI সহকারী। নিচের ওয়েবপেজের বিষয়বস্তু বাংলায় স্পষ্ট ও সংক্ষিপ্তভাবে কয়েকটি ছোট অনুচ্ছেদে সারসংক্ষেপ করুন। সারসংক্ষেপটি অবশ্যই বাংলায় লিখতে হবে।';
            $userPrompt   = "নিচের ওয়েবপেজের বিষয়বস্তু বাংলায় সারসংক্ষেপ করুন:\n\n{$text}";
        } else {
            $systemPrompt = 'You are a helpful assistant that summarizes web page content clearly and concisely in a few short paragraphs in English.';
            $userPrompt   = "Please summarize the following web page content in English:\n\n{$text}";
        }

        // ── 4. Send to OpenRouter API ─────────────────────────────────────────
        try {
            $apiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => env('APP_URL', 'http://localhost'),
                'X-Title'       => 'Website Summarizer',
            ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'    => 'nvidia/nemotron-3-super-120b-a12b:free',
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'temperature' => 0.5,
                'max_tokens'  => 700,
            ]);

            if ($apiResponse->failed()) {
                $errorBody = $apiResponse->json('error.message', 'Unknown API error.');
                return back()->with('error', "OpenRouter API error: {$errorBody}");
            }

            $summary = $apiResponse->json('choices.0.message.content', 'No summary returned.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->with('error', 'Failed to reach OpenRouter API: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Unexpected error while calling API: ' . $e->getMessage());
        }

        // ── 5. Return results to view ─────────────────────────────────────────
        return view('summarizer', compact('summary', 'url', 'language'));
    }
}
