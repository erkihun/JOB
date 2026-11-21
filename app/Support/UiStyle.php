<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * Site-wide interface style (Settings › Appearance › Interface style).
 *
 * Each style is a set of CSS overrides keyed on <html data-ui-style="…"> (resources/css/app.css,
 * "Interface styles"). Styles change shape, depth, type scale and contrast only; colours stay
 * with the admin's colour theme, so any style works with any theme. Based on the UI/UX Pro Max
 * style catalogue (Soft UI Evolution, Minimalism & Swiss, Bento Box Grid, Accessible & Ethical).
 */
final class UiStyle
{
    public const SETTING = 'appearance.ui_style';

    public const DEFAULT = 'classic';

    /** Order = order shown in Settings. */
    public const STYLES = ['classic', 'soft', 'swiss', 'bento', 'accessible'];

    /** The saved style, or the default when unset or no longer valid. */
    public static function current(): string
    {
        return self::sanitize(Setting::get(self::SETTING, self::DEFAULT));
    }

    public static function sanitize(mixed $value): string
    {
        return in_array($value, self::STYLES, true) ? $value : self::DEFAULT;
    }
}
