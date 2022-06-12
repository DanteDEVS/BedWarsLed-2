<?php



declare(strict_types=1);

namespace VietnamPMTeam\BedWars\math;

use VietnamPMTeam\BedWars\Game;

use pocketmine\world\World;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class MapReset {


    public $plugin;


    public function __construct(Game $plugin) {
        $this->plugin = $plugin;
    }

    public static function getWorldByNameNonNull(string $folderName): World
    {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($folderName);
        if ($world === null) {
            throw new AssumptionFailedError("Required world $folderName is null");
        }

        return $world;
    }

    /**
     * @param World $world
     */
    public function saveMap(World $world) {
        $world->save(true);

        $levelPath = $this->plugin->plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $world->getFolderName();
        $zipPath = $this->plugin->plugin->getDataFolder() . "saves" . DIRECTORY_SEPARATOR . $world->getFolderName() . ".zip";

        $zip = new ZipArchive();

        if(is_file($zipPath)) {
            unlink($zipPath);
        }

        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($levelPath)), RecursiveIteratorIterator::LEAVES_ONLY);


        foreach ($files as $file) {
            if($file->isFile()) {
                $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename();
                $localPath = substr($filePath, strlen($this->plugin->plugin->getServer()->getDataPath() . "worlds"));
                $zip->addFile($filePath, $localPath);
            }
        }

        $zip->close();
    }

    /**
     * @param string $folderName
     * @param bool $justSave
     *
     * @return World|null
     */
    public function loadMap(string $folderName, bool $justSave = false): ?World {
        if(!$this->plugin->plugin->getServer()->getWorldManager()->isWorldGenerated($folderName)) {
            return null;
        }

        if($this->plugin->plugin->getServer()->getWorldManager()->isWorldLoaded($folderName)) {
            $this->plugin->plugin->getServer()->getWorldManager()->unloadWorld(MapReset::getWorldByNameNonNull($folderName));
        }

        $zipPath = $this->plugin->plugin->getDataFolder() . "saves" . DIRECTORY_SEPARATOR . $folderName . ".zip";

        if(!file_exists($zipPath)) {
            $this->plugin->plugin->getServer()->getLogger()->error("Could not reload map ($folderName). File wasn't found, try save level in setup mode.");
            return null;
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($zipPath);
        $zipArchive->extractTo($this->plugin->plugin->getServer()->getDataPath() . "worlds");
        $zipArchive->close();

        if($justSave) {
            return null;
        }

        $this->plugin->plugin->getServer()->getWorldManager()->loadWorld($folderName);
        return $this->plugin->plugin->getServer()->getWorldManager()->getWorldByName($folderName);
    }
}