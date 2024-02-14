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

namespace App\com_pinoox_manager\Model;

use Pinoox\Model\PinooxDatabase;

class LangModel extends PinooxDatabase
{

    public static function fetch_all()
    {
        return [
            'manager' => t('manager'),
            'user' => t('user'),
            'setting' => [
                'account' => t('setting>account'),
                'dashboard' => t('setting>dashboard'),
                'market' => t('setting>market'),
                'router' => t('setting>router'),
                'appManager' => t('setting>appManager'),
            ],
            'widget' => [
                'clock' => t('widget>clock'),
                'storage' => t('widget>storage'),
            ],
        ];
    }
}