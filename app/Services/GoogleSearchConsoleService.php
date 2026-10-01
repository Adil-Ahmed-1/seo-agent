<?php

namespace App\Services;

use Google\Client;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleSearchConsoleService
{
    protected function getClient()
    {
        $tokenPath = storage_path('app/private/google-search-console-token.json');

        if (!file_exists($tokenPath)) {
            throw new Exception('Google Search Console is not connected.');
        }

        $token = json_decode(file_get_contents($tokenPath), true);

        if (!$token) {
            throw new Exception('Google Search Console token is invalid.');
        }

        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $client->setAccessToken($token);

        /*
         * Refresh expired access token
         */
        if ($client->isAccessTokenExpired()) {

            if (!isset($token['refresh_token'])) {
                throw new Exception('Google refresh token is missing. Please reconnect Google Search Console.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken(
                $token['refresh_token']
            );

            if (isset($newToken['error'])) {
                throw new Exception(
                    $newToken['error_description'] ?? $newToken['error']
                );
            }

            $newToken['refresh_token'] = $token['refresh_token'];

            file_put_contents(
                $tokenPath,
                json_encode($newToken, JSON_PRETTY_PRINT)
            );

            $client->setAccessToken($newToken);
        }

        return $client;
    }

    /**
     * Get Google Search Console properties.
     */
    public function getSites()
    {
        $client = $this->getClient();

        $service = new SearchConsole($client);

        $response = $service->sites->listSites();

        $sites = [];

        foreach ($response->getSiteEntry() as $site) {
            $sites[] = [
                'site_url' => $site->getSiteUrl(),
                'permission' => $site->getPermissionLevel(),
            ];
        }

        return $sites;
    }


public function getQueries(string $siteUrl)
{
    $client = $this->getClient();

    $service = new SearchConsole($client);

    $endDate = Carbon::now()
        ->subDays(3)
        ->format('Y-m-d');

    $startDate = Carbon::now()
        ->subDays(30)
        ->format('Y-m-d');

    $request = new SearchAnalyticsQueryRequest();

    $request->setStartDate($startDate);
    $request->setEndDate($endDate);

    $request->setDimensions([
        'query'
    ]);

    $request->setRowLimit(100);

    $response = $service->searchanalytics->query(
        $siteUrl,
        $request
    );

    $results = [];

    foreach ($response->getRows() as $row) {

        $keys = $row->getKeys();

        $results[] = [
            'query' => $keys[0] ?? '',
            'clicks' => round($row->getClicks(), 2),
            'impressions' => round($row->getImpressions(), 2),
            'ctr' => round($row->getCtr() * 100, 2),
            'position' => round($row->getPosition(), 1),
        ];
    }

    return $results;
}

    /**
     * Get ranking for a keyword.
     */
    public function getKeywordPosition(
        string $siteUrl,
        string $keyword
    ) {
        $client = $this->getClient();

        $service = new SearchConsole($client);

        /*
         * Search Console data can have a delay,
         * so we use a recent completed period.
         */
        $endDate = Carbon::now()
            ->subDays(3)
            ->format('Y-m-d');

        $startDate = Carbon::now()
            ->subDays(30)
            ->format('Y-m-d');

        $request = new SearchAnalyticsQueryRequest();

        $request->setStartDate($startDate);
        $request->setEndDate($endDate);

        $request->setDimensions([
            'query'
        ]);

        $request->setDimensionFilterGroups([
            [
                'groupType' => 'and',
                'filters' => [
                    [
                        'dimension' => 'query',
                        'operator' => 'equals',
                        'expression' => $keyword,
                    ]
                ]
            ]
        ]);

        $request->setRowLimit(1);

        $response = $service->searchanalytics->query(
            $siteUrl,
            $request
        );

        $rows = $response->getRows();

        if (!$rows || count($rows) === 0) {
            return null;
        }

        return round(
            $rows[0]->getPosition(),
            1
        );
    }
}