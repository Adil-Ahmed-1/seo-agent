<?php

namespace App\Services\AI;

class PromptBuilder
{
    public static function blogOutline(array $data): string
    {
        return <<<PROMPT
You are a senior SEO content strategist with 10+ years of experience.

BUSINESS CONTEXT:
- Name: {$data['business_name']}
- Niche: {$data['niche']}
- Description: {$data['description']}
- Target Audience: {$data['audience']}

KEYWORD STRATEGY:
- Primary Keyword: {$data['focus_keyword']}
- Secondary Keywords: {$data['secondary_keywords']}

TASK: Create a detailed blog post outline optimized for SEO.

REQUIREMENTS:
1. SEO title (max 60 chars, primary keyword in first 40 chars)
2. Meta description (150-155 chars, include primary keyword)
3. URL slug (lowercase, hyphens, no stop words)
4. 5-7 H2 sections with 2-3 H3 subsections each
5. FAQ section (5 questions from "People Also Ask")
6. 5 secondary keywords (LSI keywords)

Return ONLY valid JSON matching this exact schema:
{
  "title": "string",
  "slug": "string",
  "meta_description": "string",
  "outline": [
    {
      "h2": "string",
      "h3s": ["string"],
      "key_points": ["string"]
    }
  ],
  "faq": [
    {"question": "string", "answer_hint": "string"}
  ],
  "secondary_keywords": ["string"]
}
PROMPT;
    }

public static function fullBlog(array $data): string
{
    $outline  = json_encode($data['outline'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $target   = $data['word_count'];
    $minimum  = (int) ($target * 0.9);

    return <<<PROMPT
You are an expert SEO blog writer. Write a complete, publish-ready blog post.

BLOG OUTLINE:
{$outline}

STRICT REQUIREMENTS:
⚠️ MINIMUM WORD COUNT: {$minimum} words (target: {$target})
⚠️ If your output is less than {$minimum} words, you will FAIL the task.

CONTENT REQUIREMENTS:
- Expand each section thoroughly with examples, data, and actionable steps
- Every H2 section should be at least 200-300 words
- Include specific examples, statistics, or case studies
- Add numbered lists and bullet points
- Use short paragraphs (2-3 sentences max)

SEO REQUIREMENTS:
- Focus keyword in: H1 title, first paragraph, at least 2 H2s, conclusion
- Keyword density: 1-2% (natural placement)
- Active voice 80%+
- Grade 8 reading level

LINKS:
- Add 3-5 internal links: <a href="/relevant-page">anchor text</a>
- Add 2-3 external authority links: <a href="https://authority.com" target="_blank" rel="noopener">source</a>

TONE: {$data['tone']}

AVOID:
- AI clichés: "in today's world", "delve into", "navigate the landscape"
- Generic filler content
- Repetitive sentences

FORMAT:
- Return ONLY clean HTML (no markdown, no code blocks)
- Use: <h1>, <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <blockquote>, <a>
- Start with the H1

Write the complete blog post now. MINIMUM {$minimum} words.
PROMPT;
}

    public static function seoOptimize(string $content, string $keyword): string
    {
        return <<<PROMPT
Analyze this blog content for SEO and return improvements as JSON.

FOCUS KEYWORD: {$keyword}

CONTENT:
{$content}

Analyze and return ONLY valid JSON:
{
  "keyword_density": "float (percentage)",
  "readability_score": "integer (0-100)",
  "word_count": "integer",
  "suggestions": ["string"],
  "seo_score": "integer (0-100)"
}
PROMPT;
    }
}