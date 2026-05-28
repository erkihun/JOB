<?php

declare(strict_types=1);

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class SanitizeHtml
{
    private readonly HTMLPurifier $purifier;

    public function __construct()
    {
        $cachePath = storage_path('framework/cache/htmlpurifier');

        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);

        // Full rich-text allowlist covering everything TinyMCE can produce
        $config->set('HTML.Allowed', implode(',', [
            // Headings
            'h1[style]', 'h2[style]', 'h3[style]', 'h4[style]', 'h5[style]', 'h6[style]',
            // Block
            'p[style]', 'div[style]', 'blockquote[style]', 'pre', 'hr',
            // Inline text
            'br', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ins',
            'sub', 'sup', 'code', 'mark', 'small',
            // Span with style (colours, font-size, etc.)
            'span[style|class]',
            // Links
            'a[href|title|target|rel]',
            // Lists
            'ul[style]', 'ol[style|type|start]', 'li[style]',
            // Tables
            'table[style|border|width|cellpadding|cellspacing|summary]',
            'thead[style]', 'tbody[style]', 'tfoot[style]',
            'tr[style]',
            'th[style|colspan|rowspan|scope|abbr]',
            'td[style|colspan|rowspan]',
            'caption', 'colgroup', 'col[style|span|width]',
            // Images
            'img[src|alt|width|height|style|class|title|loading]',
            // Figure
            'figure[style|class]', 'figcaption',
        ]));

        // Allow the CSS properties TinyMCE commonly writes
        $config->set('CSS.AllowedProperties', [
            'color', 'background-color', 'background',
            'text-align', 'text-decoration', 'text-transform', 'text-indent',
            'font-size', 'font-weight', 'font-style', 'font-family',
            'line-height', 'letter-spacing',
            'width', 'height', 'max-width', 'min-width',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
            'border-width', 'border-style', 'border-color', 'border-collapse', 'border-spacing',
            'float', 'clear', 'vertical-align', 'display',
            'list-style-type',
        ]);

        $config->set('URI.AllowedSchemes', [
            'http'   => true,
            'https'  => true,
            'mailto' => true,
            'tel'    => true,
            'data'   => true, // needed for embedded images pasted into TinyMCE
        ]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Core.Encoding', 'UTF-8');

        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(?string $html): string
    {
        return $this->purifier->purify($html ?? '');
    }
}
