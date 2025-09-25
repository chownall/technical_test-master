<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class RepositoryController extends Controller
{
    /**
     * @return array<array>
     */
    public function search()
    {
        if (!is_string(\Request::get('q'))) throw new Exception("The query parameter 'q' must be a string");
        if (strlen(request('q')) > 256) throw new Exception("The query parameter 'q' cannot be longer than 256 chars.", 422);

        $httpClient = new Client();

        try {
            $resp = $httpClient->get('https://api.github.com/search/repositories?per_page=5&q=' . request()->get('q'));
        } catch (GuzzleException $e) {
            // Maybe we should return empty array if server error ?
            throw new Exception("Unable to contact API server.");
        }

        $repositories = @json_decode($resp->getBody()->getContents(), true)['items'];
        if ($repositories == null) {
            // if ($resp->getStatusCode() != 400) {
            //     dd($resp->getBody());
            // }

            // Maybe we should return empty array if parsing error ?
            throw new Exception("Unable to parse JSON resp.");
        }

        $return = [];
        foreach ($repositories as $r) {
            $return[] = [
                'name' => $r['name'],
                'full_name' => $r['full_name'],
                'description' => $r['description'],
                'owner' => @$r['owner']['username'] ?: $r['owner']['login'],
            ];
        }

        return $return;
    }
}
