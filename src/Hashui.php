<?php

namespace AppBundle\Services;

/**
 *
 * Enregistrement de cles -> valeurs volumineuses.
 * Systeme de hash de fichiers de taille moyennes.
 *
 * @Author : CK
 *
 * Ex (if you store a key): 
 * $contents = @file_get_contents("/etc/hosts");
 * $hui = new Hashui("/path/to/rootStorageDir");
 * $key = $hui->set($contents);
 * print($hui->get($key));


 * Ex2 (if you don't want to store keys) : 
 * $contents = @file_get_contents("/etc/hosts");
 * $userId = 198212;
 * $hui = new Hashui("/path/to/rootStorageDir");
 * $key = "user:$userId";
 * $hui->set($contents, $key);
 * print($hui->get($key));
 *
 *
 */

class Hashui
{
    private $_generalPath;
    private $_isInitialised;

    /**
     * Hashui constructor.
     * @param string $generalPath
     */
    public function __construct($generalPath)
    {
        if (empty($generalPath))
            throw new \Exception("Hashui : Cannot find parameter \"hashuipath\". Please set this parameter.");
        $this->_generalPath = $generalPath;
        $this->_isInitialised = false;
    }

    /**
     * Record a key / value
     * @param string $value
     * @param string (optionnel) $key.
     *
     * @return string $key
     */
    public function set($value, $key=null)
    {
        // generation d'une cle si besoin
        while (is_null($key)) {
            $keyTmp = '' . rand(1, 2147483647);
            if (!$this->hasKey($keyTmp))
                $key = $keyTmp;
            unset($keyTmp);
        }

        // initialisation si besoin
        if (!$this->_isInitialised)
            $this->init();
        $path = $this->getPathFromKey($key);

        // ecriture
        @file_put_contents($path, $value);
        @chmod($path, 0755);

        // retour
        unset($path);
        return ($key);
    }

    /**
     * get a key
     * @param string $key
     * @return string Value
     */
    public function get($key)
    {
        if (!$this->_isInitialised)
            $this->init();

        $path = $this->getPathFromKey($key);
        $res = @file_get_contents($path);

        // retour
        unset($path);
        return ($res);
    }

    /**
     * drop a key
     * @param string $key
     */
    public function drop($key)
    {
        if (!$this->_isInitialised)
            $this->init();

        $path = $this->getPathFromKey($key);
        if (is_file($path))
            @unlink($path);

        // retour
        unset($path);
    }
    /**
     * @param string $key
     * @return bool
     */
    public function hasKey($key)
    {
        if (!$this->_isInitialised)
            $this->init();

        $path = $this->getPathFromKey($key);
        $res = is_file($path) ? true : false;

        // retour
        unset($path);
        return ($res);
    }

    /**
     * @param   string  $key
     * @return  string  Path
     */
    private function getPathFromKey($key) {
        // determination de chiffrage de la $key
        {
            $somme = 0;
            $len = strlen($key);
            for ($cpt = 0; $cpt < $len; $cpt++) {
                $char = $key[$cpt];
                $somme += ord($char);
            }
        }
        // determination du path
        {
            $tmp = floor(($somme % 1225) / 35);
            $tmp2 = ($somme % 1225) - ($tmp * 35);
            $res = $this->_generalPath.'/'.$this->getMiniPathNameFromDigit($tmp).'/'.$this->getMiniPathNameFromDigit($tmp2) .'/'.md5($key);
        }

        // retour
        unset($len, $cpt, $char, $tmp, $tmp2, $somme);
        return ($res);
    }

    /**
     * Initialise all directories
     * @param string $key
     * @param string $value
     *
     * @return null
     * @throws \Exception If cannot initialise directories
     */
    public function init()
    {
        // verifs
        if ($this->_isInitialised)
            return;

        // verif general directory path
        $generalPathLen = strlen($this->_generalPath);
        if ($this->_generalPath[$generalPathLen - 1] == '/')
            $this->_generalPath = substr($this->_generalPath, 0, $generalPathLen - 1);

        // verif existence of general directory
        {
            $firstRepositoryPath = $this->_generalPath . '/A';
            if ( is_dir($this->_generalPath) && 16895 == fileperms($this->_generalPath) &&
                 is_dir($firstRepositoryPath) && 16895 == fileperms($firstRepositoryPath))
            {
                unset($generalPathLen, $firstRepositoryPath);
                $this->_isInitialised = true;
                return;
            }
        }
        // creation (si besoin du repertoire general)
        $this->createDir($this->_generalPath);

        // creation (si besoin) des repertoires
        for ($i = 0; $i <= 35; $i++) {
            $dir1 = $this->_generalPath . '/' . $this->getMiniPathNameFromDigit($i);
            $this->createDir($dir1);
            for ($j = 0; $j <= 35; $j++) {
                $dir2 = $dir1 . '/' . $this->getMiniPathNameFromDigit($j);
                $this->createDir($dir2);
            }
        }

        // retour
        unset($generalPathLen, $firstRepositoryPath, $i, $j);
        $this->_isInitialised = true;
    }

    /**
     * @param int $digit 0-35
     * @return string a-z0-9
     */
    private function getMiniPathNameFromDigit($digit) {
        if ($digit >= 0 && $digit <= 25)
            return (chr(  65 + $digit));
        elseif ($digit > 25 && $digit <= 35)
            return (chr( 48 + $digit - 26 ));
        return null;
    }

    /**
     * @param string $path Cree un repertoire physique
     * @throws \Exception Si la creation du repertoire n'a pas marche.
     */
    private function createDir($path) {
        if (!is_dir($path)) {
            if (!mkdir($path))
                throw new \Exception("Hashui : Cannot create directories '$path' .");
        }
        if (16895 != fileperms ($path)) {
            if (!chmod($path, 0777))
                throw new \Exception("Hashui : Cannot set 777 rights to directory '$path' .");
        }
    }
}
