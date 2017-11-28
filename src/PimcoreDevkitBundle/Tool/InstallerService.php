<?php
/**
 * @date        02/11/2017
 * @author      Korneliusz Kirsz <kkirsz@divante.pl>
 * @copyright   Copyright (c) 2017 DIVANTE (http://divante.pl)
 */

declare(strict_types=1);

namespace PimcoreDevkitBundle\Tool;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject;
use Pimcore\Model\WebsiteSetting;

/**
 * Class InstallerService
 *
 * @package PimcoreDevkitBundle\Tool
 */
class InstallerService
{
    /**
     * @param string $wsName
     * @param int $parentId
     * @param string $key
     * @return DataObject\Folder
     */
    public function createDataObjectFolderAndWebsiteSettings(string $wsName, int $parentId, string $key)
    {
        $setting = WebsiteSetting::getByName($wsName);

        if ($setting instanceof WebsiteSetting && $setting->getData()) {
            $folder = DataObject\Folder::getById($setting->getData());
            if ($folder instanceof DataObject\Folder) {
                return $folder;
            }
        }

        $folder = $this->getOrCreateObjectFolder($parentId, $key);
        $this->setWebsiteSetting(
            [
                'name' => $wsName,
                'type' => 'object',
                'data' => $folder->getId(),
            ]
        );

        return $folder;
    }

    /**
     * @param string $name
     * @param string $jsonFilePath
     */
    public function createClassDefinition(string $name, string $jsonFilePath)
    {
        $class = ClassDefinition::getByName($name);
        if (!$class instanceof ClassDefinition) {
            $class = ClassDefinition::create(['name' => $name, 'userOwner' => 0]);
            $json = $this->jsonGetContents($jsonFilePath);
            ClassDefinition\Service::importClassDefinitionFromJson($class, $json, true);
        }
    }

    /**
     * Creates or updates WebsiteSettings.
     *
     * @param array $params
     * @return WebsiteSetting
     */
    public function setWebsiteSetting(array $params)
    {
        $setting = WebsiteSetting::getByName($params['name']);

        if (!$setting instanceof WebsiteSetting) {
            $setting = new WebsiteSetting();
        }

        $setting->setValues($params);
        $setting->save();

        return $setting;
    }

    /**
     * Returns Object folder. If not existent, creates it.
     *
     * @param int $parentId
     * @param string $key
     * @return DataObject\Folder
     * @internal param string $name
     */
    public function getOrCreateObjectFolder($parentId, $key)
    {
        $parent = DataObject::getById($parentId);
        $key    = DataObject\Service::getValidKey($key, 'object');
        $path   = $parent->getRealFullPath() . '/' . $key;

        $folder = DataObject\Folder::getByPath($path);
        if (!$folder instanceof DataObject\Folder) {
            $folder = DataObject\Folder::create([
                'o_parentId'         => $parentId,
                'o_creationDate'     => time(),
                'o_userOwner'        => 0,
                'o_userModification' => 0,
                'o_key'              => $key,
                'o_published'        => true,
                'o_locked'           => true,
            ]);
        }

        return $folder;
    }

    /**
     * @param string $permission
     */
    public function createPermission(string $permission)
    {
        \Pimcore\Model\User\Permission\Definition::create($permission);
    }

    /**
     * @param string $jsonFilePath
     * @return string
     * @throws \UnexpectedValueException
     */
    protected function jsonGetContents(string $jsonFilePath) : string
    {
        $contents = file_get_contents($jsonFilePath);

        if (!is_string($contents)) {
            throw new \UnexpectedValueException();
        }

        return $contents;
    }
}
