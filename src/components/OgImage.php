<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\components;

use skeeks\cms\components\og\OgImageRenderer;
use yii\base\Component;
use yii\helpers\FileHelper;
use yii\web\View;

/**
 * Link preview card for pages without their own picture.
 *
 * Messengers and social networks show og:image of a link. Pages of tree sections and content
 * elements give the picture of their record; every other page (and a record without a picture)
 * gets a generated 1200x630 card with the page title, a label and the site logo instead of
 * the bare site logo the messengers fall back to.
 *
 * The card is drawn once with GD and saved as a static file named by the hash of its content and
 * of the template version, so it is served by the web server and a new design never collides with
 * old files. The folder may be deleted at any time: the cards are drawn again on the next views.
 *
 * Flow: controllers call {@see setPage()} with the record title and its section; the view calls
 * {@see apply()} at the end of the page. When the page already has og:image, only
 * twitter:card is added.
 *
 * A site sets its own style in the `ogImage` component config, for example:
 * ```php
 * 'ogImage' => [
 *     'glows'       => [['color' => '#a6de58', 'x' => 0.05, 'y' => 0.0, 'radius' => 0.55, 'opacity' => 0.45]],
 *     'accentColor' => '#a6de58',
 *     'logo'        => '@vendor/skeeks/skeeks-brand/brand/logos/skeeks/logo-on-dark-128h.png',
 *     'logoPlate'   => false,
 * ],
 * ```
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class OgImage extends Component
{
    /**
     * Change it together with any change of {@see OgImageRenderer} drawing: old cards are not reused.
     */
    public const TEMPLATE_VERSION = 1;

    /**
     * @var bool false switches the generated cards off (record pictures are still given by controllers)
     */
    public $enabled = true;

    /**
     * @var string where the cards are saved; must be served as static files at {@see $url}
     */
    public $path = '@webroot/uploads/og';

    /**
     * @var string public URL of {@see $path}
     */
    public $url = '@web/uploads/og';

    public $background = '#0d1117';
    /**
     * @var array soft round glows: color, x and y (0..1 of the card), radius (0..1 of the width), opacity (0..1)
     */
    public $glows = [
        ['color' => '#3b82f6', 'x' => 0.02, 'y' => 0.0, 'radius' => 0.55, 'opacity' => 0.40],
        ['color' => '#8b5cf6', 'x' => 0.98, 'y' => 0.15, 'radius' => 0.45, 'opacity' => 0.30],
        ['color' => '#14b8a6', 'x' => 0.85, 'y' => 1.0, 'radius' => 0.50, 'opacity' => 0.30],
    ];
    public $grid = true;
    public $gridColor = '#ffffff';
    public $gridOpacity = 0.10;

    public $textColor = '#ffffff';
    public $mutedColor = '#9aa4b2';
    public $accentColor = '#60a5fa';

    /**
     * @var string|null logo file (path or alias). By default the logo of the current CMS site.
     */
    public $logo;
    /**
     * @var bool draw the logo on a white plate: any logo stays readable on the dark card.
     * Switch it off for a logo made for a dark background.
     */
    public $logoPlate = true;
    public $logoHeight = 56;

    /**
     * @var string|null text in the bottom left corner; by default the domain of the site
     */
    public $siteName;

    public $fontBold = '@skeeks/cms/components/og/fonts/Nunito-ExtraBold.ttf';
    public $fontRegular = '@skeeks/cms/components/og/fonts/Nunito-SemiBold.ttf';

    public $quality = 88;

    /**
     * @var string|null page title for the card; set by a controller, otherwise taken from the view title
     */
    public $title;
    /**
     * @var string|null small line above the title: section, record type
     */
    public $label;

    /**
     * Title and label of the current page for the card.
     */
    public function setPage(?string $title, ?string $label = null)
    {
        $this->title = $title;
        $this->label = $label;
        return $this;
    }

    /**
     * Adds og:image (with its size) and twitter:card to the page. Called by the view at the end
     * of a page: every controller and template has had a chance to give its own picture.
     */
    public function apply(View $view)
    {
        if (!$this->isApplicable()) {
            return;
        }

        if (!isset($view->metaTags['twitter:card'])) {
            $view->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image'], 'twitter:card');
        }

        if (!$this->enabled || $this->hasOwnImage($view)) {
            return;
        }

        $title = $this->title ?: $this->titleFromView((string)$view->title);
        if ($title === '') {
            return;
        }

        try {
            //A page with a query string may get a card only if it has been drawn already:
            //titles of searches and filters must not fill the disk
            $create = \Yii::$app->request->queryString === '';
            $url = $this->cardUrl($title, $this->label, $create);
        } catch (\Throwable $e) {
            \Yii::error('og:image card: '.$e->getMessage(), static::class);
            return;
        }

        if (!$url) {
            return;
        }

        $view->registerMetaTag(['property' => 'og:image', 'content' => $url], 'og:image');
        $view->registerMetaTag(['property' => 'og:image:width', 'content' => OgImageRenderer::WIDTH], 'og:image:width');
        $view->registerMetaTag(['property' => 'og:image:height', 'content' => OgImageRenderer::HEIGHT], 'og:image:height');
        $view->registerMetaTag(['property' => 'og:image:type', 'content' => 'image/jpeg'], 'og:image:type');
    }

    /**
     * Absolute URL of the card; draws it when it does not exist yet.
     *
     * @param bool $create false: only an already drawn card
     * @return string|null
     */
    public function cardUrl(string $title, ?string $label = null, bool $create = true)
    {
        $title = $this->clean($title);
        $label = $label !== null ? $this->clean($label) : null;
        $style = $this->style();
        $logoFile = $this->logoFile();

        $hash = md5(json_encode([
            self::TEMPLATE_VERSION,
            $style,
            $logoFile ? [$logoFile, @filemtime($logoFile)] : null,
            $title,
            $label,
        ], JSON_UNESCAPED_UNICODE));

        $relative = substr($hash, 0, 2).'/'.$hash.'.jpg';
        $file = \Yii::getAlias($this->path).'/'.$relative;

        if (!is_file($file)) {
            if (!$create || !function_exists('imagettftext')) {
                return null;
            }

            $im = (new OgImageRenderer($style))->render($title, $label ?: null, $logoFile);
            FileHelper::createDirectory(dirname($file));
            $tmp = $file.'.'.uniqid('', true).'.tmp';
            imageinterlace($im, true);
            $saved = imagejpeg($im, $tmp, (int)$this->quality);
            imagedestroy($im);
            if (!$saved || !@rename($tmp, $file)) {
                @unlink($tmp);
                return null;
            }
        }

        return \Yii::$app->urlManager->hostInfo.\Yii::getAlias($this->url).'/'.$relative;
    }

    /**
     * Everything the drawing depends on; part of the file hash.
     * @return array
     */
    public function style()
    {
        return [
            'background'  => $this->background,
            'glows'       => array_values((array)$this->glows),
            'grid'        => (bool)$this->grid,
            'gridColor'   => $this->gridColor,
            'gridOpacity' => $this->gridOpacity,
            'textColor'   => $this->textColor,
            'mutedColor'  => $this->mutedColor,
            'accentColor' => $this->accentColor,
            'logoPlate'   => (bool)$this->logoPlate,
            'logoHeight'  => (int)$this->logoHeight,
            'siteName'    => $this->siteName !== null ? (string)$this->siteName : $this->defaultSiteName(),
            'fontBold'    => \Yii::getAlias($this->fontBold),
            'fontRegular' => \Yii::getAlias($this->fontRegular),
        ];
    }

    /**
     * Only ordinary HTML pages of the site: not the administration and cabinets, not AJAX/PJAX
     * fragments, not error pages and not console requests.
     */
    protected function isApplicable()
    {
        $app = \Yii::$app;
        if (!$app instanceof \yii\web\Application) {
            return false;
        }
        if (!$app->request->isGet || $app->request->isAjax || $app->request->isPjax) {
            return false;
        }
        if ($app->response->statusCode != 200 || $app->response->format !== \yii\web\Response::FORMAT_HTML) {
            return false;
        }
        if ($app->controller && is_a($app->controller, 'skeeks\cms\backend\BackendController')) {
            return false;
        }
        return true;
    }

    /**
     * og:image given by a controller or a template, also registered without the 'og:image' key.
     */
    protected function hasOwnImage(View $view)
    {
        if (isset($view->metaTags['og:image'])) {
            return true;
        }
        foreach ((array)$view->metaTags as $tag) {
            if (is_string($tag) && strpos($tag, 'property="og:image"') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * "Title | Site" -> "Title"
     */
    protected function titleFromView(string $title)
    {
        $title = $this->clean($title);
        if (($pos = mb_strrpos($title, ' | ')) !== false && $pos > 0) {
            $title = mb_substr($title, 0, $pos);
        }
        return $title;
    }

    protected function clean(string $text)
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim(mb_substr($text, 0, 300));
    }

    /**
     * @return string|null
     */
    protected function logoFile()
    {
        if ($this->logo) {
            $file = \Yii::getAlias($this->logo);
            return is_file($file) ? $file : null;
        }

        $site = \Yii::$app->skeeks->site ?? null;
        if ($site && $site->image) {
            $file = $site->image->rootSrc;
            return $file && is_file($file) ? $file : null;
        }

        return null;
    }

    protected function defaultSiteName()
    {
        $site = \Yii::$app->skeeks->site ?? null;
        $url = $site ? $site->url : \Yii::$app->urlManager->hostInfo;
        $host = parse_url((string)$url, PHP_URL_HOST);
        return $host ?: ($site ? (string)$site->name : '');
    }
}
