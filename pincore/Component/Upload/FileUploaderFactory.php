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

namespace Pinoox\Component\Upload;


use Pinoox\Component\Http\File\UploadedFile;
use Pinoox\Model\FileModel;

class FileUploaderFactory
{

    public function store($destination, UploadedFile $file, $access = 'public'): FileUploader
    {
        return new FileUploader(
            path(''),
            $destination,
            $file,
            $access
        );
    }

    public function delete(int $file_id): FileUploader|bool
    {
        $fileModel = FileModel::find($file_id);
        if (empty($fileModel)) return false;

        return (new FileUploader())->delete($fileModel);
    }

    public function addEvent(Event $type, \Closure $event): void
    {
        FileUploader::addEvent($type, $event);
    }

}