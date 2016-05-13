<?php

namespace Skysoul;

use Imagick;
use ImagickPixel;
use ZipArchive;

/**
 * Class Atlas
 *
 * @package Skysoul
 */
class Atlas
{

    /**
     * @var int size of the document
     * white = 1024x600
     * yellow = 1024x422
     */
    public $docSizeX = 1024;
    public $docSizeY = 422;

    public $file;

    public $canvas;

    /**
     * Create a new job instance.
     *
     * @param array $fields
     * @param array $file
     */
    public function __construct(array $fields, array $file)
    {

        $this->docSizeX = isset($fields['x']) ? $fields['x'] : '1024';
        $this->docSizeY = isset($fields['y']) ? $fields['y'] : '1024';
        $this->file = $file;
        $this->zip = new ZipArchive;

    }

    /**
     * Get All Unique Codes
     */
    public function generate()
    {

        $directory = __DIR__ . "/extract";
        $folderName = "Folder";
        $spriteDirectory = $directory . DIRECTORY_SEPARATOR . $folderName;

        array_map('unlink', glob("extract/*.*"));
die();
        /**
         * Extract Folder
         */
        if ($this->zip->open($this->file['tmp_name']) === true) {
            $this->zip->extractTo($directory);
            $this->zip->close();
        }

        $sprites = [];

        $characters = $this->parseTextFile();

        foreach ($characters as $index => $character) {

            $realPath = realpath($spriteDirectory . DIRECTORY_SEPARATOR . $index . ".psd");

            $imagick = new Imagick($realPath);

            $imagick->setFormat("png");
            $imagick->setIteratorIndex(0);
            $imagick->trimImage(1);

            $sprite = new \stdClass();
            $sprite->id = $index;
            $sprite->image = $imagick;
            $sprite->width = $imagick->getImageWidth();
            $sprite->height = $imagick->getImageHeight();
            $sprite->path = $realPath;

            $sprite->character = $character;
            $sprite->decimal = $this->getDecimal($character);

            $sprites[$index] = $sprite;

        }

        /**
         * Generate X and Y
         * Generate Char
         */
        $y = 0;
        $x = 0;
        $numberOfRows = 0;
        $maxHeight = 0;
        $heightArray = [];
        $current = 0;

        foreach ($sprites as $sprite) {

            $maxHeight = $sprite->height > $maxHeight ? $sprite->height : $maxHeight;

            if ($sprite->id != 0) {

                $previous = $sprites[$current];

                $x = $previous->width + $previous->x;

                if ($x + $sprite->width > $this->docSizeX) {
                    $x = 0;
                    $numberOfRows++;
                    array_push($heightArray, $maxHeight);
                }

                if ($numberOfRows > 0) {
                    $y = array_sum($heightArray);
                }

            }

            $sprite->x = $x;
            $sprite->y = $y;

            $sprite->char = "\nchar id=$sprite->decimal x=$sprite->x y=$sprite->y width=$sprite->width height=$sprite->height xoffset=0 yoffset=0 xadvance=$sprite->width page=0 chnl=0";

            $current = $sprite->id;

        }

        /** @var Imagick $canvas */
        $canvas = new Imagick();
        $canvas->newImage($this->docSizeX, $this->docSizeY, new ImagickPixel('transparent'));

        foreach ($sprites as $sprite) {
            $canvas->compositeImage($sprite->image, Imagick::ALPHACHANNEL_SET, $sprite->x, $sprite->y);
        }

        $canvas->writeImage(__DIR__ . DIRECTORY_SEPARATOR . "exported" . DIRECTORY_SEPARATOR . "sprite.png");

        die();

    }

    /**
     * @return array
     */
    public function parseTextFile()
    {

        $lines = file(__DIR__ . DIRECTORY_SEPARATOR . "extract" . DIRECTORY_SEPARATOR . "photoshop-data-set.txt");

        $final = [];

        foreach ($lines as $line) {
            list($id, $character) = explode(',', trim($line));
            $final[$id] = $character;
        }

        array_shift($final);

        return $final;

    }

    /**
     * Split the string to characters
     *
     * @param $string
     * @return mixed
     */
    public function split($string)
    {
        if (empty($string))
            return [];

        if (is_array($string))
            $string = implode('', $string);

        return preg_split('/(?<!^)(?!$)/u', $string);

    }

    /**
     * Execute the job.
     */
    public function handle()
    {
//        $this->zip->open()
    }

    /**
     * Get The Decimal of a Number
     *
     * @param $char
     * @return number
     */
    public function getDecimal($char)
    {
        return hexdec(mb_encode_numericentity($char, array(0x0, 0xffff, 0, 0xffff), 'UTF-8', true));
    }

}