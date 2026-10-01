<?php

namespace App\Services\Publisher;

use App\Models\BlogPost;
use App\Models\Website;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

class StaticPublisher implements PublisherInterface
{
    public function __construct(protected Website $website) {}

    public function publish(BlogPost $post): array
    {
        try {
            $html = $this->generateHtml($post);
            $filename = "blog/{$post->slug}.html";

            // Upload via FTP
            $this->uploadToFtp($filename, $html);

            // Update sitemap (optional)
            $this->updateSitemap($post);

            return [
                'success'    => true,
                'remote_id'  => $post->slug,
                'remote_url' => rtrim($this->website->url, '/') . "/{$filename}",
            ];

        } catch (\Exception $e) {
            Log::error('Static publish failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function generateHtml(BlogPost $post): string
    {
        $template = $this->getTemplate();

        return str_replace(
            ['{{TITLE}}', '{{CONTENT}}', '{{META_DESC}}', '{{KEYWORD}}', '{{DATE}}', '{{SITE_NAME}}'],
            [
                e($post->title),
                $post->content_html,
                e($post->meta_description),
                e($post->focus_keyword),
                now()->format('F j, Y'),
                e($this->website->name),
            ],
            $template
        );
    }

    protected function getTemplate(): string
    {
        // Agar website ka custom template hai to use karo
        $customTemplate = $this->website->getSetting('html_template');
        if ($customTemplate) {
            return $customTemplate;
        }

        return $this->defaultTemplate();
    }

    protected function defaultTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}} | {{SITE_NAME}}</title>
    <meta name="description" content="{{META_DESC}}">
    <meta name="keywords" content="{{KEYWORD}}">

    <meta property="og:title" content="{{TITLE}}">
    <meta property="og:description" content="{{META_DESC}}">
    <meta property="og:type" content="article">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BlogPosting",
      "headline": "{{TITLE}}",
      "datePublished": "{{DATE}}",
      "description": "{{META_DESC}}"
    }
    </script>

    <style>
        body { font-family: -apple-system, system-ui, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; line-height: 1.6; color: #333; }
        h1 { color: #1a1a1a; font-size: 2.2em; margin-bottom: 0.5em; }
        h2 { color: #2a2a2a; margin-top: 1.5em; }
        h3 { color: #3a3a3a; }
        a { color: #0066cc; }
        blockquote { border-left: 4px solid #0066cc; padding-left: 1em; color: #555; font-style: italic; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        time { color: #888; }
    </style>
</head>
<body>
    <header>
        <a href="/" style="text-decoration:none; color:#000; font-weight:bold;">{{SITE_NAME}}</a>
    </header>
    <main>
        <article>
            {{CONTENT}}
            <hr>
            <time>{{DATE}}</time>
        </article>
    </main>
    <footer style="margin-top: 4em; padding-top: 2em; border-top: 1px solid #eee; color: #888; font-size: 0.9em;">
        &copy; {{SITE_NAME}}
    </footer>
</body>
</html>
HTML;
    }

    protected function uploadToFtp(string $path, string $content): void
    {
        $adapter = new FtpAdapter(FtpConnectionOptions::fromArray([
            'host'     => $this->website->ftp_host,
            'username' => $this->website->ftp_username,
            'password' => $this->website->ftp_password,
            'port'     => 21,
            'root'     => $this->website->ftp_path,
            'ssl'      => false,
            'timeout'  => 30,
            'passive'  => true,
        ]));

        $filesystem = new Filesystem($adapter);
        $filesystem->write($path, $content);
    }

    protected function updateSitemap(BlogPost $post): void
    {
        // Optional: sitemap.xml me new URL add karo
        // Currently skip - baad me implement karenge
    }

    public function testConnection(): bool
    {
        try {
            $adapter = new FtpAdapter(FtpConnectionOptions::fromArray([
                'host'     => $this->website->ftp_host,
                'username' => $this->website->ftp_username,
                'password' => $this->website->ftp_password,
                'port'     => 21,
                'timeout'  => 10,
            ]));

            $filesystem = new Filesystem($adapter);
            $filesystem->listContents('/')->toArray();

            return true;
        } catch (\Exception $e) {
            Log::error('FTP test failed: ' . $e->getMessage());
            return false;
        }
    }
}