<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Compares a question and its answer options independently of editor markup,
 * whitespace, and option order. No stored question content is rewritten.
 */
class QuestionDuplicateChecker
{
    /**
     * @param  array<int, mixed>  $options
     */
    public static function signature(string $questionText, array $options): string
    {
        $normalizedOptions = array_map(
            fn ($option): string => self::normalizeContent(
                (string) $option,
                OptionContent::isHtml((string) $option)
            ),
            $options
        );

        // Options are a multiset: their order does not matter, but repeated
        // identical options still count separately.
        sort($normalizedOptions, SORT_STRING);

        return hash('sha256', serialize([
            self::normalizeContent($questionText, self::looksLikeHtml($questionText), true),
            $normalizedOptions,
        ]));
    }

    /**
     * Build one exam-scoped lookup for a single or bulk question request.
     *
     * @return array<string, true>
     */
    public static function existingSignatures(Exam $exam, array $candidateQuestionTexts, ?int $exceptQuestionId = null): array
    {
        $candidateKeys = [];
        foreach ($candidateQuestionTexts as $candidateQuestionText) {
            if (is_string($candidateQuestionText) && trim($candidateQuestionText) !== '') {
                $candidateKeys[self::normalizeQuestionText($candidateQuestionText)] = true;
            }
        }

        if ($candidateKeys === []) {
            return [];
        }

        $questions = Question::query()
            ->where('exam_id', $exam->id)
            ->when($exceptQuestionId !== null, fn ($query) => $query->whereKeyNot($exceptQuestionId))
            ->get(['id', 'question_text']);

        $matchingQuestions = $questions->filter(
            fn (Question $question): bool => isset($candidateKeys[self::normalizeQuestionText((string) $question->question_text)])
        )->values();

        if ($matchingQuestions->isEmpty()) {
            return [];
        }

        // Load potentially large option HTML/base64 images only for questions
        // whose normalized text can match this request.
        $optionsByQuestion = QuestionOption::query()
            ->whereIn('question_id', $matchingQuestions->pluck('id'))
            ->get(['question_id', 'option_text'])
            ->groupBy('question_id');

        $signatures = [];

        foreach ($matchingQuestions as $question) {
            $signatures[self::signature(
                (string) $question->question_text,
                $optionsByQuestion->get($question->id, collect())->pluck('option_text')->all()
            )] = true;
        }

        return $signatures;
    }

    private static function normalizeContent(string $content, bool $isHtml, bool $decodePlainEntities = false): string
    {
        if (! $isHtml) {
            if ($decodePlainEntities) {
                $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            return self::normalizeWhitespace($content);
        }

        $document = new DOMDocument;
        $previousErrorMode = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="__question_duplicate_root__">'.$content.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        $root = (new \DOMXPath($document))
            ->query('//*[@id="__question_duplicate_root__"]')
            ->item(0);

        if (! $root instanceof DOMElement) {
            return self::normalizeWhitespace(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $text = '';
        self::appendMeaningfulContent($root, $text);

        return self::normalizeWhitespace($text);
    }

    private static function normalizeQuestionText(string $questionText): string
    {
        return self::normalizeContent($questionText, self::looksLikeHtml($questionText), true);
    }

    private static function looksLikeHtml(string $content): bool
    {
        return preg_match(
            '/^\s*<(?:p|br|strong|b|em|i|u|s|a|ul|ol|li|blockquote|h[1-6]|figure|figcaption|img|table|thead|tbody|tr|td|th|span|code|pre)\b/i',
            $content
        ) === 1;
    }

    private static function appendMeaningfulContent(DOMNode $node, string &$text): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $text .= $child->nodeValue;

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if ($tag === 'script' || $tag === 'style') {
                continue;
            }

            if ($tag === 'img') {
                $source = trim($child->getAttribute('src'));
                $alt = trim($child->getAttribute('alt'));
                $text .= ' [image:'.hash('sha256', $source).' '.$alt.'] ';

                continue;
            }

            $isBlock = in_array($tag, [
                'p', 'div', 'li', 'ul', 'ol', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'table', 'thead', 'tbody', 'tr', 'td', 'th', 'figure', 'pre',
            ], true);

            if ($isBlock || $tag === 'br') {
                $text .= ' ';
            }

            self::appendMeaningfulContent($child, $text);

            if ($isBlock || $tag === 'br') {
                $text .= ' ';
            }
        }
    }

    private static function normalizeWhitespace(string $content): string
    {
        $content = preg_replace('/[\p{Z}\s]+/u', ' ', $content) ?? $content;

        // Keep letter case and punctuation intact: they may be meaningful in
        // names, abbreviations, or mathematical expressions.
        return trim($content);
    }
}
