<?php

namespace App\Services\Content;

class SEOOptimizer
{
    /**
     * Analyze and score SEO for given content.
     */
    public function analyze(string $content, string $keyword, string $metaDescription, string $title): array
    {
        $analysis = [
            'word_count'         => $this->wordCount($content),
            'keyword_density'    => $this->keywordDensity($content, $keyword),
            'readability_score'  => $this->readabilityScore($content),
            'headings'           => $this->analyzeHeadings($content),
            'links'              => $this->analyzeLinks($content),
            'images'             => $this->analyzeImages($content),
            'meta_length_ok'     => strlen($metaDescription) <= 160 && strlen($metaDescription) >= 120,
            'title_length_ok'    => strlen($title) <= 60,
            'keyword_in_title'   => stripos($title, $keyword) !== false,
            'keyword_in_meta'    => stripos($metaDescription, $keyword) !== false,
            'keyword_in_first_p' => $this->keywordInFirstParagraph($content, $keyword),
        ];

        $analysis['seo_score'] = $this->calculateScore($analysis);
        $analysis['suggestions'] = $this->generateSuggestions($analysis, $keyword);

        return $analysis;
    }

    protected function wordCount(string $content): int
    {
        return str_word_count(strip_tags($content));
    }

    protected function keywordDensity(string $content, string $keyword): float
    {
        if (empty($keyword)) return 0;

        $text  = strtolower(strip_tags($content));
        $words = str_word_count($text);
        $count = substr_count($text, strtolower($keyword));

        return $words > 0 ? round(($count / $words) * 100, 2) : 0;
    }

    protected function readabilityScore(string $content): int
    {
        $text = strip_tags($content);

        $sentences = max(1, preg_match_all('/[.!?]+/', $text));
        $words     = max(1, str_word_count($text));
        $syllables = max(1, $this->countSyllables($text));

        // Flesch Reading Ease
        $score = 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syllables / $words);

        return (int) max(0, min(100, round($score)));
    }

    protected function countSyllables(string $text): int
    {
        $text = strtolower($text);
        $words = preg_split('/\s+/', $text);
        $count = 0;

        foreach ($words as $word) {
            $word = preg_replace('/[^a-z]/', '', $word);
            if (empty($word)) continue;

            preg_match_all('/[aeiouy]+/', $word, $matches);
            $count += max(1, count($matches[0]));
        }

        return $count;
    }

    protected function analyzeHeadings(string $content): array
    {
        return [
            'h1' => preg_match_all('/<h1[^>]*>/i', $content),
            'h2' => preg_match_all('/<h2[^>]*>/i', $content),
            'h3' => preg_match_all('/<h3[^>]*>/i', $content),
        ];
    }

    protected function analyzeLinks(string $content): array
    {
        preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\']/i', $content, $matches);

        $internal = 0;
        $external = 0;

        foreach ($matches[1] ?? [] as $url) {
            if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
                $internal++;
            } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
                $external++;
            }
        }

        return ['internal' => $internal, 'external' => $external, 'total' => $internal + $external];
    }

    protected function analyzeImages(string $content): array
    {
        preg_match_all('/<img\s+[^>]*>/i', $content, $matches);
        $total = count($matches[0] ?? []);

        $withAlt = 0;
        foreach ($matches[0] ?? [] as $img) {
            if (preg_match('/alt=["\'][^"\']+["\']/i', $img)) {
                $withAlt++;
            }
        }

        return ['total' => $total, 'with_alt' => $withAlt];
    }

    protected function keywordInFirstParagraph(string $content, string $keyword): bool
    {
        if (preg_match('/<p[^>]*>(.*?)<\/p>/i', $content, $matches)) {
            return stripos($matches[1], $keyword) !== false;
        }
        return false;
    }

    protected function calculateScore(array $analysis): int
    {
        $score = 0;

        // Word count (max 15 points)
        if ($analysis['word_count'] >= 1500) $score += 15;
        elseif ($analysis['word_count'] >= 1000) $score += 10;
        elseif ($analysis['word_count'] >= 500) $score += 5;

        // Keyword density (max 15 points)
        if ($analysis['keyword_density'] >= 0.5 && $analysis['keyword_density'] <= 2.5) {
            $score += 15;
        } elseif ($analysis['keyword_density'] > 0 && $analysis['keyword_density'] < 4) {
            $score += 8;
        }

        // Headings (max 15 points)
        if ($analysis['headings']['h2'] >= 4) $score += 15;
        elseif ($analysis['headings']['h2'] >= 2) $score += 8;

        // Meta (max 10 points)
        if ($analysis['meta_length_ok']) $score += 10;

        // Title (max 10 points)
        if ($analysis['title_length_ok']) $score += 5;
        if ($analysis['keyword_in_title']) $score += 5;

        // Keyword in meta (max 5 points)
        if ($analysis['keyword_in_meta']) $score += 5;

        // Keyword in first para (max 5 points)
        if ($analysis['keyword_in_first_p']) $score += 5;

        // Links (max 10 points)
        if ($analysis['links']['internal'] >= 3) $score += 5;
        if ($analysis['links']['external'] >= 1) $score += 5;

        // Readability (max 10 points)
        if ($analysis['readability_score'] >= 60) $score += 10;
        elseif ($analysis['readability_score'] >= 40) $score += 5;

        return min(100, $score);
    }

    protected function generateSuggestions(array $analysis, string $keyword): array
    {
        $suggestions = [];

        if ($analysis['word_count'] < 1500) {
            $suggestions[] = "Content short hai. Kam se kam 1500 words karo.";
        }

        if ($analysis['keyword_density'] < 0.5) {
            $suggestions[] = "'{$keyword}' keyword zyada use karo (aim: 1-2%).";
        } elseif ($analysis['keyword_density'] > 2.5) {
            $suggestions[] = "Keyword density zyada hai (over-optimization). Kam karo.";
        }

        if ($analysis['headings']['h2'] < 4) {
            $suggestions[] = "Kam se kam 4-5 H2 headings add karo.";
        }

        if (! $analysis['keyword_in_title']) {
            $suggestions[] = "Title me '{$keyword}' include karo.";
        }

        if (! $analysis['meta_length_ok']) {
            $suggestions[] = "Meta description 120-160 characters ka rakho.";
        }

        if ($analysis['links']['internal'] < 3) {
            $suggestions[] = "3-5 internal links add karo.";
        }

        return $suggestions;
    }
}