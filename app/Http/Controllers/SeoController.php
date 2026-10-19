<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    // Area setelah login tidak perlu dirayapi
    private const PRIVATE_PATHS = ['/admin', '/dashboard', '/challenges', '/duels', '/profile', '/leaderboard', '/presence', '/online'];

    public function robots(): Response
    {
        $lines = array_merge(
            ['User-agent: *'],
            array_map(fn (string $path) => 'Disallow: '.$path, self::PRIVATE_PATHS),
            ['', 'Sitemap: '.url('sitemap.xml')],
        );

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $urls = collect(['login', 'register'])
            ->map(fn (string $name) => '<url><loc>'.e(route($name)).'</loc></url>')
            ->implode('');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
