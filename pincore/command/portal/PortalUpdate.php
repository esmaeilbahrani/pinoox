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


namespace Pinoox\Command\Portal;


use Pinoox\Component\Console;
use Pinoox\Component\Helpers\HelperString;
use Pinoox\Component\Helpers\PhpFile\PortalFile;
use Pinoox\Component\Interfaces\CommandInterface;

class PortalUpdate extends console implements CommandInterface
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "portal:update";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create a new Portal class";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['PortalName', true, 'Portal Name'],
    ];

    protected $options = [
        ['package', 'p', 'change package name for example:[--package=com_pinoox_welcome | --p=com_pinoox_welcome]', 'default'],
    ];

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->setPackageName();
        $portalName = HelperString::toCamelCase($this->argument('PortalName'));
        $this->registerPortal($portalName,$this->cli['package']);
    }


    private function setPackageName()
    {
        $packageName = $this->option('package');
        $packageName = empty($packageName) || $packageName == 'default'? null : $packageName;
       // $this->chooseApp($packageName);
        $this->chooseApp('pincore');

    }

    private function registerPortal(string $portalName, string $packageName): void
    {
        $path = $this->getPath($portalName);
        PortalFile::updatePortal($path, $portalName,$packageName);
        $this->success(sprintf('Portal update in "%s".', str_replace('\\', '/', $path)));
        $this->newLine();
    }

    private function getPath(string $portalName): string
    {
        return $this->cli['path'] . '\\Portal\\' . ucfirst($portalName) . '.php';
    }
}