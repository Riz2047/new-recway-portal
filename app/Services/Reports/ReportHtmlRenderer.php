<?php

declare(strict_types=1);

namespace App\Services\Reports;

class ReportHtmlRenderer
{
    /**
     * Render JSON template sections into a complete, print-ready HTML document.
     *
     * @param  array{version:int,sections:array<int,array<string,mixed>>}  $template
     * @param  array<string,string>  $substitutions  e.g. ['{can_name}' => 'John Doe']
     */
    public function render(array $template, array $substitutions = [], bool $standalone = true): string
    {
        $sectionsHtml = '';

        foreach ($template['sections'] ?? [] as $section) {
            $sectionsHtml .= $this->renderSection($section, $substitutions);
        }

        if (! $standalone) {
            return $sectionsHtml;
        }

        return $this->wrapInDocument($sectionsHtml);
    }

    // -------------------------------------------------------------------------

    private function renderSection(array $section, array $subs): string
    {
        $type = (string) ($section['type'] ?? 'text');

        return match ($type) {
            'table' => $this->renderTable($section, $subs),
            'page_break' => '<div class="page-break"></div>',
            default => $this->renderText($section, $subs),
        };
    }

    private function renderText(array $section, array $subs): string
    {
        $heading = $this->substitute((string) ($section['heading'] ?? ''), $subs);
        $content = $this->substitute((string) ($section['content'] ?? ''), $subs);

        $html = '<div class="section text-section">';

        if ($heading !== '') {
            $html .= '<h2>' . e($heading) . '</h2>';
        }

        if ($content !== '') {
            $paragraphs = preg_split('/\r\n|\r|\n/', $content) ?: [$content];
            foreach ($paragraphs as $para) {
                $para = trim($para);
                if ($para !== '') {
                    $html .= '<p>' . e($para) . '</p>';
                }
            }
        }

        $html .= '</div>';

        return $html;
    }

    private function renderTable(array $section, array $subs): string
    {
        $caption = $this->substitute((string) ($section['caption'] ?? ''), $subs);
        $columns = max(1, (int) ($section['columns'] ?? 3));
        $headers = (array) ($section['headers'] ?? []);
        $rows = (array) ($section['rows'] ?? []);

        $html = '<div class="section table-section">';

        if ($caption !== '') {
            $html .= '<h2>' . e($caption) . '</h2>';
        }

        $html .= '<table><thead><tr>';
        for ($i = 0; $i < $columns; $i++) {
            $header = $this->substitute((string) ($headers[$i] ?? ''), $subs);
            $html .= '<th>' . e($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $html .= '<tr>';
            for ($i = 1; $i <= $columns; $i++) {
                $cell = $this->substitute((string) ($row['c' . $i] ?? ''), $subs);
                $html .= '<td>' . e($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function substitute(string $text, array $subs): string
    {
        return str_replace(array_keys($subs), array_values($subs), $text);
    }

    private function wrapInDocument(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Background Check Report</title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body {
    font-family: 'Arial', 'Helvetica Neue', sans-serif;
    font-size: 11pt;
    color: #1a1a1a;
    background: #fff;
    margin: 0;
    padding: 0;
  }
  .page {
    max-width: 210mm;
    margin: 0 auto;
    padding: 20mm 18mm;
  }
  .report-header {
    border-bottom: 2px solid #1e3a5f;
    padding-bottom: 12px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
  }
  .report-header h1 {
    font-size: 18pt;
    font-weight: 700;
    color: #1e3a5f;
    margin: 0;
  }
  .report-header .meta {
    font-size: 9pt;
    color: #666;
    text-align: right;
  }
  .section {
    margin-bottom: 18px;
  }
  .section h2 {
    font-size: 11pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #1e3a5f;
    border-bottom: 1px solid #d1d5db;
    padding-bottom: 4px;
    margin: 0 0 8px 0;
  }
  .section p {
    margin: 0 0 6px 0;
    line-height: 1.6;
    text-align: justify;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
    margin-top: 6px;
  }
  thead th {
    background-color: #1e3a5f;
    color: #fff;
    font-weight: 600;
    padding: 6px 10px;
    text-align: left;
    font-size: 9pt;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }
  tbody tr:nth-child(even) { background-color: #f8fafc; }
  tbody td {
    padding: 5px 10px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
  }
  .page-break {
    page-break-after: always;
    break-after: page;
    height: 0;
    margin: 16px 0;
    border-top: 1px dashed #ccc;
  }
  @media print {
    body { background: #fff; }
    .page { padding: 15mm 12mm; }
    .page-break {
      page-break-after: always;
      break-after: page;
      border: none;
      height: 0;
      margin: 0;
    }
    .no-print { display: none !important; }
  }
</style>
</head>
<body>
<div class="page">
{$body}
</div>
</body>
</html>
HTML;
    }
}
