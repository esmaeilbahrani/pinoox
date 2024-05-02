<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pinoox\Component\Database\Model;
use Pinoox\Component\Date;
use Pinoox\Component\Token;
use Pinoox\Component\User;
use Pinoox\Model\Scope\AppScope;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\FileUploader;
use Pinoox\Portal\Url;


/**
 * @property mixed $file_id
 */
class FileModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pincore_file';
    protected $primaryKey = 'file_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'app',
        'file_group',
        'file_realname',
        'file_name',
        'file_ext',
        'file_path',
        'file_size',
        'file_access',
    ];

    protected $hidden = [
        'app'
    ];
    protected $appends = ['file_link', 'thumb_link'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function getFileLinkAttribute()
    {
        return Url::path($this->file_path . '/' . $this->file_name);
    }


    public function getThumbLinkAttribute()
    {
        if (in_array($this->file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return Url::path($this->file_path . '/thumbs/thumb_' . $this->file_name);
        }

        return null;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($file) {
            $file->app = $file->app ?? self::getPackage();
            $file->user_id = $file->user_id ?? User::get('user_id');
        });

        static::deleting(function ($file) {
            $path = path($file->file_path, $file->app);
            $originalFile = $path . '/' . $file->file_name;
            $thumbnailFile = $path . '/thumbs/thumb_' . $file->file_name;

            if (file_exists($originalFile)) unlink($originalFile);
            if (file_exists($thumbnailFile)) unlink($thumbnailFile);
        });
    }

    public static function setPackage(string $package): void
    {
        App::set('transport.file', $package)->save();
        self::addAppGlobalScope();
    }

    public static function getPackage(): string
    {
        $package = App::get('transport.file');
        return $package ?? App::package();
    }

    protected static function booted()
    {
        static::addGlobalScope('app', new AppScope(static::getPackage()));
    }

    private static function addAppGlobalScope(): void
    {
        static::addGlobalScope('app', new AppScope(static::getPackage()));
    }
}
