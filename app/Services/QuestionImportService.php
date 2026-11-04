<?php

namespace App\Services;

use RuntimeException;
use Throwable;
use ZipArchive;
use PharData;

class QuestionImportService
{
    public function parse(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'docx' => $this->parseStructuredText($this->extractDocxText($path)),
            'txt' => $this->parseStructuredText((string) file_get_contents($path)),
            'csv' => $this->parseCsv($path),
            default => throw new RuntimeException('Format file tidak didukung. Gunakan DOCX, TXT, atau CSV.'),
        };
    }

    private function extractDocxText(string $path): string
    {
        $xml = false;

        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
            }
        }

        // Fallback untuk instalasi PHP yang belum mengaktifkan ext-zip.
        if ($xml === false && class_exists(PharData::class)) {
            try {
                $archive = new PharData($path);
                if (isset($archive['word/document.xml'])) {
                    $xml = $archive['word/document.xml']->getContent();
                }
            } catch (Throwable $e) {
                $xml = false;
            }
        }

        if ($xml === false) {
            throw new RuntimeException('File DOCX tidak dapat dibaca. Pastikan file Word tidak rusak; jika perlu aktifkan ekstensi PHP ZIP.');
        }

        preg_match_all('/<w:p\b[^>]*>(.*?)<\/w:p>/s', $xml, $paragraphs);
        $lines = [];
        foreach ($paragraphs[1] as $paragraph) {
            $paragraph = preg_replace('/<w:tab\s*\/>/i', "\t", $paragraph);
            $paragraph = preg_replace('/<w:br\b[^>]*\/>/i', "\n", $paragraph);
            preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/s', $paragraph, $texts);
            $line = html_entity_decode(implode('', $texts[1] ?? []), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $lines[] = trim($line);
        }

        return implode("\n", $lines);
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new RuntimeException('File CSV kosong.');
        }

        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        $delimiter = $semicolonCount > $commaCount ? ';' : ',';
        rewind($handle);

        $rows = [];
        $header = null;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null] || count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            if ($header === null) {
                $header = array_map(function ($v) {
                    $v = preg_replace('/^\xEF\xBB\xBF/', '', (string) $v);
                    return strtolower(trim($v));
                }, $data);
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = trim((string) ($data[$i] ?? ''));
            }

            $rows[] = [
                'text' => $row['soal'] ?? $row['question'] ?? $row['pertanyaan'] ?? '',
                'options' => [
                    'A' => $row['a'] ?? '',
                    'B' => $row['b'] ?? '',
                    'C' => $row['c'] ?? '',
                    'D' => $row['d'] ?? '',
                    'E' => $row['e'] ?? '',
                ],
                'correct' => strtoupper($row['jawaban'] ?? $row['correct'] ?? $row['kunci'] ?? ''),
                'explanation' => $row['pembahasan'] ?? $row['explanation'] ?? '',
                'difficulty' => $this->normalizeDifficulty($row['kesulitan'] ?? $row['difficulty'] ?? 'medium'),
            ];
        }
        fclose($handle);

        return $this->validateParsedQuestions($rows);
    }

    private function parseStructuredText(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = preg_split('/\n/u', $text) ?: [];
        $questions = [];
        $current = null;
        $mode = null;

        $flush = function () use (&$questions, &$current): void {
            if ($current !== null && trim($current['text'] ?? '') !== '') {
                $questions[] = $current;
            }
            $current = null;
        };

        foreach ($lines as $rawLine) {
            $line = trim(preg_replace('/\x{00A0}/u', ' ', (string) $rawLine));
            // Toleransi jika tanda * tersalin sebagai \* dari chat/Markdown.
            $line = preg_replace('/^\\\\\*/u', '*', $line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\s*\d+\s*[\.\)]\s*(.+)$/u', $line, $m) || preg_match('/^\s*(?:soal|pertanyaan)\s*[:\-]\s*(.+)$/iu', $line, $m)) {
                $flush();
                $current = $this->emptyQuestion(trim($m[1]));
                $mode = 'question';
                continue;
            }

            if ($current === null) {
                $current = $this->emptyQuestion($line);
                $mode = 'question';
                continue;
            }

            // Format sederhana: beri tanda * pada opsi yang benar, contoh: *A. I10
            // Format lama tanpa * + baris Jawaban: A tetap didukung untuk kompatibilitas.
            if (preg_match('/^\s*(\*)?\s*([A-E])\s*[\.\)]\s*(.+)$/iu', $line, $m)) {
                $isCorrect = ($m[1] ?? '') === '*';
                $label = strtoupper($m[2]);
                $current['options'][$label] = trim($m[3]);
                if ($isCorrect) {
                    if (trim((string) ($current['correct'] ?? '')) !== '' && $current['correct'] !== $label) {
                        $current['multiple_correct'] = true;
                    }
                    $current['correct'] = $label;
                }
                $mode = 'option_'.$label;
                continue;
            }

            if (preg_match('/^\s*(?:jawaban|kunci(?:\s+jawaban)?)\s*[:\-]\s*([A-E])\b/iu', $line, $m)) {
                $current['correct'] = strtoupper($m[1]);
                $mode = 'answer';
                continue;
            }

            if (preg_match('/^\s*(?:pembahasan|penjelasan)\s*[:\-]\s*(.*)$/iu', $line, $m)) {
                $current['explanation'] = trim($m[1]);
                $mode = 'explanation';
                continue;
            }

            if (preg_match('/^\s*(?:kesulitan|difficulty)\s*[:\-]\s*(.+)$/iu', $line, $m)) {
                $current['difficulty'] = $this->normalizeDifficulty($m[1]);
                $mode = 'difficulty';
                continue;
            }

            // Word kadang mengubah nomor soal menjadi automatic numbering sehingga angka "1." tidak ikut
            // terbaca dari DOCX. Jika satu soal sudah memiliki A-E lalu muncul teks biasa, anggap itu soal baru.
            $hasAllOptions = count(array_filter(
                $current['options'],
                fn ($value) => trim((string) $value) !== ''
            )) === 5;
            if ($hasAllOptions && !str_starts_with((string) $mode, 'explanation')) {
                $flush();
                $current = $this->emptyQuestion($line);
                $mode = 'question';
                continue;
            }

            if ($mode === 'question') {
                $current['text'] .= ' '.$line;
            } elseif ($mode === 'explanation') {
                $current['explanation'] = trim($current['explanation'].' '.$line);
            } elseif (str_starts_with((string) $mode, 'option_')) {
                $label = substr((string) $mode, -1);
                $current['options'][$label] = trim($current['options'][$label].' '.$line);
            }
        }

        $flush();

        return $this->validateParsedQuestions($questions);
    }

    private function emptyQuestion(string $text): array
    {
        return [
            'text' => $text,
            'options' => ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''],
            'correct' => '',
            'multiple_correct' => false,
            'explanation' => '',
            'difficulty' => 'medium',
        ];
    }

    private function normalizeDifficulty(string $value): string
    {
        $value = strtolower(trim($value));
        return match ($value) {
            'easy', 'mudah' => 'easy',
            'hard', 'sulit', 'sukar' => 'hard',
            default => 'medium',
        };
    }

    private function validateParsedQuestions(array $questions): array
    {
        if (count($questions) === 0) {
            throw new RuntimeException('Tidak ada soal yang berhasil dibaca dari file. Periksa format penulisan.');
        }

        $errors = [];
        foreach ($questions as $index => $q) {
            $number = $index + 1;
            if (trim((string) ($q['text'] ?? '')) === '') {
                $errors[] = "Soal {$number}: teks soal kosong.";
            }
            foreach (['A', 'B', 'C', 'D', 'E'] as $label) {
                if (trim((string) ($q['options'][$label] ?? '')) === '') {
                    $errors[] = "Soal {$number}: pilihan {$label} belum diisi.";
                }
            }
            if (($q['multiple_correct'] ?? false) === true) {
                $errors[] = "Soal {$number}: ada lebih dari satu pilihan bertanda *. Gunakan hanya satu tanda * untuk jawaban benar.";
            } elseif (!in_array(strtoupper((string) ($q['correct'] ?? '')), ['A', 'B', 'C', 'D', 'E'], true)) {
                $errors[] = "Soal {$number}: belum ada jawaban benar. Beri tanda * pada salah satu opsi, misalnya *A. Jawaban.";
            }
        }

        if ($errors) {
            $message = "Import dibatalkan karena format berikut belum benar:\n- ".implode("\n- ", array_slice($errors, 0, 20));
            if (count($errors) > 20) {
                $message .= "\n- ... dan ".(count($errors) - 20).' kesalahan lainnya.';
            }
            throw new RuntimeException($message);
        }

        return $questions;
    }
}
