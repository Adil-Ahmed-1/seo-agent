<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\SearchConsole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoogleSearchConsoleController extends Controller
{
    public function connect()
    {
        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $client->addScope(
            'https://www.googleapis.com/auth/webmasters.readonly'
        );

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return response()->json([
                'success' => false,
                'message' => $request->get(
                    'error_description',
                    $request->get('error')
                ),
            ], 400);
        }

        if (!$request->has('code')) {
            return response()->json([
                'success' => false,
                'message' => 'Google authorization code missing.',
                'received_parameters' => $request->all(),
            ], 400);
        }

        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $token = $client->fetchAccessTokenWithAuthCode(
            $request->get('code')
        );

        if (isset($token['error'])) {
            return response()->json([
                'success' => false,
                'message' => $token['error_description'] ?? $token['error'],
            ], 400);
        }

        Storage::disk('local')->put(
            'google-search-console-token.json',
            json_encode($token)
        );

        return redirect('/admin/keywords')
            ->with(
                'success',
                'Google Search Console connected successfully.'
            );
    }
}