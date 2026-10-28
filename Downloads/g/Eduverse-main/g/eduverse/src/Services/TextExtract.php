<?php
declare(strict_types=1);

/** Extracts plain text from .txt and .docx uploads so plagiarism and AI feedback can use them. PDF is not supported. */
class TextExtract
{
    public const MAX_CHARS = 20000;

    public static function fromFile(string $rel, string $originalName): ?string
    {
        $path = ROOT . '/storage/uploads/' . $rel;
        if (!is_file($path)) return null;
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'txt') {
            $raw = (string)file_get_contents($path, false, null, 0, 200000);
            return mb_substr(trim(mb_convert_encoding($raw, 'UTF-8', 'UTF-8')), 0, self::MAX_CHARS) ?: null;
        }
        if ($ext === 'docx' && class_exists('ZipArchive')) {
            $z = new ZipArchive();
            if ($z->open($path) !== true) return null;
            $st = $z->statName('word/document.xml');
            $xml = ($st && $st['size'] <= 5000000) ? $z->getFromName('word/document.xml') : false;
            $z->close();
            if ($xml === false) return null;
            $xml = preg_replace('#</w:p>#', "\n", $xml);
            $text = html_entity_decode(strip_tags((string)$xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
            return mb_substr(trim($text), 0, self::MAX_CHARS) ?: null;
        }
        return null;
    }
}
