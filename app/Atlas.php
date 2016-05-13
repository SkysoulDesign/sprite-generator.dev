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
    public $docSizeX;
    public $docSizeY;

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

        $this->docSizeX = empty(isset($fields['x'])) ? $fields['x'] : 1024;
        $this->docSizeY = empty(isset($fields['y'])) ? $fields['y'] : 1024;
        $this->file = $file;
        $this->zip = new ZipArchive;

    }

    /**
     * Get All Unique Codes
     */
    public function generate()
    {

        $directory = __DIR__ . "/extract";
        $folderName = "sprites";
        $spriteDirectory = $directory . DIRECTORY_SEPARATOR . $folderName;

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

            $sprite->char = "\nchar id=$sprite->decimal x=$sprite->x y=$sprite->y width=$sprite->width height=$sprite->height xoffset=0 yoffset=0 xadvance=$sprite->width page=0 chnl=0 letter=$sprite->character";

            $current = $sprite->id;

        }

        /** @var Imagick $canvas */
        $canvas = new Imagick();
        $canvas->newImage($this->docSizeX, $this->docSizeY, new ImagickPixel('transparent'));

        foreach ($sprites as $sprite) {
            $canvas->compositeImage($sprite->image, Imagick::ALPHACHANNEL_SET, $sprite->x, $sprite->y);
        }

        $canvas->writeImage(__DIR__ . DIRECTORY_SEPARATOR . "exported" . DIRECTORY_SEPARATOR . "sprite.png");

        $filePath = $this->deliveryZip($this->generateCoordinates($sprites));

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="sprites.zip"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);

        /**
         * Clean Up Directory
         */
        $this->delete_files($directory);

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
     * Get The Decimal of a Number
     *
     * @param $char
     * @return number
     */
    public function getDecimal($char)
    {
        return hexdec(mb_encode_numericentity($char, array(0x0, 0xffff, 0, 0xffff), 'UTF-8', true));
    }

    public function deliveryZip($coordinates)
    {
        $zip = new ZipArchive();
        $filename = __DIR__ . DIRECTORY_SEPARATOR . "exported/sprites.zip";

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            exit("cannot open <$filename>\n");
        }

        $zip->addFromString("coordinates.txt", $coordinates);
        $zip->addFile(__DIR__ . DIRECTORY_SEPARATOR . "exported/sprite.png", "sprite.png");
        $zip->close();

        return $filename;

    }

    public function generateCoordinates(array $sprites)
    {
        $count = count($sprites);
        $header = "info face='sprite.png' size=16 bold=0 italic=0 charset=\"\" unicode=1 stretchH=100 smooth=1 aa=1 padding=0,0,0,0 spacing=1,1 outline=0 common lineHeight=32 base=25 scaleW=$this->docSizeX scaleH=$this->docSizeY pages=1 packed=0 alphaChnl=1 redChnl=0 greenChnl=0 blueChnl=0 \npage id=0 file='sprite.png' \nchars count=$count";
        $coordinates = [$header];

        foreach ($sprites as $sprite) {
            $coordinates[] = $sprite->char;
        }

        return implode('', $coordinates);
    }

    /**
     * php delete function that deals with directories recursively
     *
     * @param $target
     */
    function delete_files($target)
    {
        if (is_dir($target)) {
            $files = glob($target . DIRECTORY_SEPARATOR . '*', GLOB_MARK);

            foreach ($files as $file) {
                $this->delete_files($file);
            }

            rmdir($target);

        } elseif (is_file($target)) {
            unlink($target);
        }
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

}