<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */


namespace pinoox\component\http;

use pinoox\component\helpers\Str;
use pinoox\component\Url;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectResponseSymfony;

class RedirectResponse extends RedirectResponseSymfony
{
    public function setTargetUrl(string $url): static
    {
        if (!empty($url)) {
            $base = Url::link('^');
            if (Str::firstHas($url, $base))
                $url = Str::firstDelete($url, $base);
            $url = Url::link($url);
        }
        return parent::setTargetUrl($url);
    }
}