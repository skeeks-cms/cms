<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\components\og;

/**
 * Draws the fallback link-preview card (og:image) with GD: a dark background with soft glows,
 * an optional perspective grid, the site logo, a small label, the page title and the site name.
 *
 * Pure drawing: no Yii, no database. Every style value comes from the style array built by
 * {@see \skeeks\cms\components\OgImage::style()}.
 */
class OgImageRenderer
{
    public const WIDTH = 1200;
    public const HEIGHT = 630;

    private const PAD_X = 80;

    /** @var array */
    private $style;

    public function __construct(array $style)
    {
        $this->style = $style;
    }

    /**
     * @param string      $title page title (seo_h1 or name)
     * @param string|null $label small line above the title: a section or a record type
     * @param string|null $logoFile local path of a PNG/JPEG/WebP/GIF logo
     * @return \GdImage|resource
     */
    public function render(string $title, ?string $label, ?string $logoFile)
    {
        $W = self::WIDTH;
        $H = self::HEIGHT;

        $im = $this->background();
        imagealphablending($im, true);

        if ($this->style['grid']) {
            $this->grid($im);
        }

        //Logo or the site name in the top left corner
        $logoBottom = 64;
        if ($logoFile && ($logoBottom = $this->logo($im, $logoFile, self::PAD_X, 64)) === null) {
            $logoBottom = 64;
        }

        $textColor = $this->color($im, $this->style['textColor']);
        $mutedColor = $this->color($im, $this->style['mutedColor']);
        $accentColor = $this->color($im, $this->style['accentColor']);

        $maxWidth = $W - self::PAD_X * 2;

        //Title: the biggest size that fits into 3 lines
        $lines = [];
        $size = 0;
        foreach ([60, 56, 52, 48, 44, 40, 36] as $size) {
            $lines = $this->wrap($title, $this->style['fontBold'], $size, $maxWidth);
            if (count($lines) <= 3) {
                break;
            }
        }
        if (count($lines) > 3) {
            //The rest does not fit: the third line ends with "…" on a word boundary
            $last = rtrim($lines[2], " ,.;:—-");
            while (mb_strpos($last, ' ') !== false && $this->width($last.'…', $this->style['fontBold'], $size) > $maxWidth) {
                $last = rtrim(mb_substr($last, 0, mb_strrpos($last, ' ')), " ,.;:—-");
            }
            $lines = array_slice($lines, 0, 3);
            $lines[2] = $this->ellipsis($last.'…', $this->style['fontBold'], $size, $maxWidth);
        }

        $lineHeight = (int)round($size * 1.62);
        $ascent = $this->ascent($this->style['fontBold'], $size);

        $labelSize = 20;
        $labelBlock = $label ? 56 : 0;
        $titleBlock = $lineHeight * (count($lines) - 1) + $ascent + (int)round($size * 0.35);

        //The text block is centred between the logo and the footer line
        $areaTop = max($logoBottom + 40, 150);
        $areaBottom = $H - 120;
        $top = $areaTop + max(0, (int)(($areaBottom - $areaTop - $labelBlock - $titleBlock) / 2));

        if ($label) {
            $labelText = mb_strtoupper($label);
            $labelText = $this->ellipsis($labelText, $this->style['fontRegular'], $labelSize, $maxWidth - 64);
            $labelAscent = $this->ascent($this->style['fontRegular'], $labelSize);
            imagefilledrectangle($im, self::PAD_X, $top + 8, self::PAD_X + 40, $top + 13, $accentColor);
            imagettftext($im, $labelSize, 0, self::PAD_X + 56, $top + $labelAscent - 4, $accentColor, $this->style['fontRegular'], $labelText);
            $top += $labelBlock;
        }

        foreach ($lines as $i => $line) {
            imagettftext($im, $size, 0, self::PAD_X, $top + $ascent + $i * $lineHeight, $textColor, $this->style['fontBold'], $line);
        }

        //Footer: the site name and a stripe of the glow colours
        if ($this->style['siteName']) {
            imagettftext($im, 22, 0, self::PAD_X, $H - 64, $mutedColor, $this->style['fontRegular'], $this->style['siteName']);
        }
        $this->stripe($im);

        return $im;
    }

    /**
     * Glows are computed on a small canvas and scaled up: smooth gradients at a low cost.
     */
    private function background()
    {
        $w = 240;
        $h = 126;
        $small = imagecreatetruecolor($w, $h);
        [$br, $bg, $bb] = $this->rgb($this->style['background']);

        $glows = [];
        foreach ((array)$this->style['glows'] as $glow) {
            [$r, $g, $b] = $this->rgb($glow['color']);
            $glows[] = [$r, $g, $b, $glow['x'] * $w, $glow['y'] * $h, max(1, $glow['radius'] * $w), $glow['opacity']];
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $r = $br;
                $g = $bg;
                $b = $bb;
                foreach ($glows as [$gr, $gg, $gb, $gx, $gy, $radius, $opacity]) {
                    $d = sqrt(($x - $gx) ** 2 + ($y - $gy) ** 2) / $radius;
                    if ($d < 1) {
                        $k = (1 - $d) * (1 - $d) * $opacity;
                        $r += ($gr - $r) * $k;
                        $g += ($gg - $g) * $k;
                        $b += ($gb - $b) * $k;
                    }
                }
                imagesetpixel($small, $x, $y, ((int)$r << 16) | ((int)$g << 8) | (int)$b);
            }
        }

        $im = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled($im, $small, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, $w, $h);
        imagedestroy($small);

        return $im;
    }

    /**
     * Perspective floor grid in the lower part; lines fade towards the horizon.
     */
    private function grid($im)
    {
        $W = self::WIDTH;
        $H = self::HEIGHT;
        $horizon = (int)($H * 0.56);
        $vx = $W * 0.62;
        [$r, $g, $b] = $this->rgb($this->style['gridColor']);
        $maxOpacity = (float)$this->style['gridOpacity'];

        //Horizontal lines: perspective spacing, more transparent near the horizon
        for ($i = 1; $i <= 9; $i++) {
            $t = ($i / 9) ** 2;
            $y = (int)($horizon + ($H - $horizon) * $t);
            imageline($im, 0, $y, $W, $y, $this->alpha($im, $r, $g, $b, $maxOpacity * (0.25 + 0.75 * $t)));
        }

        //Lines to the vanishing point, drawn in segments to fade them out
        $steps = 10;
        for ($i = -12; $i <= 12; $i++) {
            $x0 = $vx + $i * 150;
            for ($s = 2; $s < $steps; $s++) {
                $t1 = $s / $steps;
                $t2 = ($s + 1) / $steps;
                $y1 = $horizon + ($H - $horizon) * $t1;
                $y2 = $horizon + ($H - $horizon) * $t2;
                $x1 = $vx + ($x0 - $vx) * $t1;
                $x2 = $vx + ($x0 - $vx) * $t2;
                imageline($im, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $this->alpha($im, $r, $g, $b, $maxOpacity * $t2));
            }
        }
    }

    /**
     * @return int|null the bottom edge of the logo, null when the file cannot be read
     */
    private function logo($im, string $file, int $x, int $y)
    {
        $data = @file_get_contents($file);
        $logo = $data ? @imagecreatefromstring($data) : false;
        if (!$logo) {
            return null;
        }

        $maxH = (int)$this->style['logoHeight'];
        $maxW = 420;
        $lw = imagesx($logo);
        $lh = imagesy($logo);
        $k = min($maxH / $lh, $maxW / $lw);
        $dw = (int)round($lw * $k);
        $dh = (int)round($lh * $k);

        if ($this->style['logoPlate']) {
            //A light plate keeps any logo readable on the dark card
            $pad = 14;
            $this->roundedRect($im, $x - $pad, $y - $pad, $x + $dw + $pad, $y + $dh + $pad, 14, $this->color($im, '#ffffff'));
        }

        imagecopyresampled($im, $logo, $x, $y, 0, 0, $dw, $dh, $lw, $lh);
        imagedestroy($logo);

        return $y + $dh + ($this->style['logoPlate'] ? 14 : 0);
    }

    private function stripe($im)
    {
        $colors = [];
        foreach ((array)$this->style['glows'] as $glow) {
            $colors[] = $glow['color'];
        }
        if (!$colors) {
            $colors[] = $this->style['accentColor'];
        }
        $W = self::WIDTH;
        $part = $W / count($colors);
        foreach ($colors as $i => $color) {
            imagefilledrectangle($im, (int)($i * $part), self::HEIGHT - 8, (int)(($i + 1) * $part), self::HEIGHT, $this->color($im, $color));
        }
    }

    /**
     * Word wrap by the real text width; a word longer than the line is left on its own line.
     * @return string[]
     */
    private function wrap(string $text, string $font, int $size, int $maxWidth)
    {
        //Short words (prepositions, conjunctions) stay with the next word
        $words = [];
        $carry = '';
        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            //A dash stays at the end of the previous line
            if (preg_match('/^[—–-]$/u', $word) && $words && $carry === '') {
                $words[count($words) - 1] .= ' '.$word;
                continue;
            }
            if (mb_strlen($word) <= 2 && !preg_match('/\d/u', $word)) {
                $carry .= $word.' ';
                continue;
            }
            $words[] = $carry.$word;
            $carry = '';
        }
        if ($carry !== '') {
            $words[] = rtrim($carry);
        }

        $lines = $this->greedy($words, $font, $size, $maxWidth);

        //Balanced lines: the narrowest width that keeps the same number of lines
        $count = count($lines);
        if ($count > 1) {
            $lo = (int)($maxWidth / 2);
            $hi = $maxWidth;
            while ($hi - $lo > 8) {
                $mid = (int)(($lo + $hi) / 2);
                if (count($this->greedy($words, $font, $size, $mid)) === $count) {
                    $hi = $mid;
                } else {
                    $lo = $mid;
                }
            }
            $lines = $this->greedy($words, $font, $size, $hi);
        }

        return $lines;
    }

    /**
     * @param string[] $words
     * @return string[]
     */
    private function greedy(array $words, string $font, int $size, int $maxWidth)
    {
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $try = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && $this->width($try, $font, $size) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        return $lines;
    }

    private function ellipsis(string $text, string $font, int $size, int $maxWidth)
    {
        if ($this->width($text, $font, $size) <= $maxWidth) {
            return $text;
        }
        while (mb_strlen($text) > 1 && $this->width($text.'…', $font, $size) > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }
        return rtrim($text, " ,.;:—-").'…';
    }

    private function width(string $text, string $font, int $size)
    {
        $box = imagettfbbox($size, 0, $font, $text);
        return $box[2] - $box[0];
    }

    private function ascent(string $font, int $size)
    {
        $box = imagettfbbox($size, 0, $font, 'ЁЙDH');
        return -$box[7];
    }

    private function roundedRect($im, int $x1, int $y1, int $x2, int $y2, int $r, int $color)
    {
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    private function color($im, string $hex)
    {
        [$r, $g, $b] = $this->rgb($hex);
        return imagecolorallocate($im, $r, $g, $b);
    }

    private function alpha($im, int $r, int $g, int $b, float $opacity)
    {
        $opacity = max(0, min(1, $opacity));
        return imagecolorallocatealpha($im, $r, $g, $b, (int)round(127 * (1 - $opacity)));
    }

    /**
     * @return int[] [r, g, b]
     */
    private function rgb(string $hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $n = hexdec(substr(str_pad($hex, 6, '0'), 0, 6));
        return [($n >> 16) & 255, ($n >> 8) & 255, $n & 255];
    }
}
