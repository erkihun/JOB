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

        // Rich allowlist covering TinyMCE output — HTML 4.01 elements only
        // (HTMLPurifier throws on unknown HTML5 elements like figure/mark)
        $config->set('HTML.Allowed',
            'h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],' .
            'p[style],div[style],blockquote[style],pre,hr,' .
            'br,strong,b,em,i,u,s,del,ins,sub,sup,code,small,' .
            'span[style|class],' .
            'a[href|title|target|rel],' .
            'ul[style],ol[style|type|start],li[style],' .
            'table[style|border|width|cellpadding|cellspacing],' .
            'thead[style],tbody[style],tfoot[style],' .
            'tr[style],th[style|colspan|rowspan|scope],td[style|colspan|rowspan],' .
            'caption,' .
            'img[src|alt|width|height|style|class|title]'
        );

        // CSS properties TinyMCE commonly writes
        $config->set('CSS.AllowedProperties', [
            'color'            => true,
            'background-color' => true,
            'background'       => true,
            'text-align'       => true,
            'text-decoration'  => true,
            'text-transform'   => true,
            'text-indent'      => true,
            'font-size'        => true,
            'font-weight'      => true,
            'font-style'       => true,
            'font-family'      => true,
            'line-height'      => true,
            'letter-spacing'   => true,
            'width'            => true,
            'height'           => true,
            'max-width'        => true,
            'padding'          => true,
            'padding-top'      => true,
            'padding-right'    => true,
            'padding-bottom'   => true,
            'padding-left'     => true,
            'margin'           => true,
            'margin-top'       => true,
            'margin-right'     => true,
            'margin-bottom'    => true,
            'margin-left'      => true,
            'border'           => true,
            'border-collapse'  => true,
            'border-spacing'   => true,
            'border-color'     => true,
            'border-style'     => true,
            'border-width'     => true,
            'float'            => true,
            'clear'            => true,
            'vertical-align'   => true,
            'list-style-type'  => true,
        ]);

        $config->set('URI.AllowedSchemes', [
            'http'   => true,
            'https'  => true,
            'mailto' => true,
            'tel'    => true,
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
