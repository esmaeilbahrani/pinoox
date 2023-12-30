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

use Pinoox\Portal\View;
use function Pinoox\Router\{action};
use App\com_pinoox_welcome\Controller\MainController;
use Pinoox\Component\Helpers\HelperHeader;

action('welcome', MainController::class);
action('pinooxjs', function () {
    HelperHeader::contentType('application/javascript', 'UTF-8');
    return View::render('pinoox');
});