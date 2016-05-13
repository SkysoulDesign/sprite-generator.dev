<?php

namespace Skysoul;

/**
 * Class GetUniqueCharacters
 *
 * @package DreamsArk\Jobs
 */
class Generator
{

    /**
     * @var array
     */
    public $words;

    /**
     * @var array
     */
    public $ignore;

    /**
     * @var string
     */
    public $inject;

    /**
     * @var int size of the document
     * white = 1024x600
     * yellow = 1024x422
     */
    public $docSizeX = 1024;
    public $docSizeY = 422;

    public $source = "MR/sprites/yellow-png-42x42";
    public $output = "MR/output/sprite.png";

    /**
     * Create a new job instance.
     *
     * @param array $fields
     */
    public function __construct(array $fields)
    {

        /**
         * List of all words
         */
        $this->words = isset($fields['text']) ? $fields['text'] : [];

        /**
         * Injected Words
         */
        $this->inject = isset($fields['inject']) ? $fields['inject'] : [];

        /**
         * Ignored Words
         */
        $this->ignore = isset($fields['exclude']) ? $fields['exclude'] : [];

    }

    /**
     * Get All Unique Codes
     */
    public function getDataSet()
    {

        /**
         * Word List
         */
        $uniques = $this->split($this->inject);
        $ignored = $this->split($this->ignore);

        $buggyChars = $this->split("\"");

        foreach ($this->split($this->words) as $index => $char) {

            /**
             * Remove White Spaces and convert to lower case
             */
            $char = trim(strtolower($char));
            if (!in_array($char, $uniques) && !in_array($char, $ignored) && !empty($char) && !in_array($char, $buggyChars))
                $uniques[] = $char;

        }

        /**
         * Sort Alphabetical
         */
        sort($uniques, SORT_NATURAL);

        foreach ($uniques as $index => &$value) {
            $value = "\n$index,$value";
        }

        array_unshift($uniques, 'id,word');

        header("Content-type: text/plain; charset=UTF-8");
        header('Content-Disposition: ' . sprintf('attachment; filename="%s"', 'photoshop-data-set.txt'));
        header("Content-Length: " . sizeof($uniques));

        return implode("", $uniques);

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

        ini_set('default_charset', 'UTF-8');

        /** @var SplFileInfo[] $files */
        $files = \File::allFiles(base_path($this->source));


        /**
         * Sort array numeric order
         */
        ksort($sprites);


//        dd(count($uniques));

        if (($exceeded = $y - $this->docSizeY) > 0) {
            dd("this document has exceeded: $exceeded pixels in height");
        }

        $count = count($sprites);
        $header = "info face='sprite.png' size=16 bold=0 italic=0 charset=\"\" unicode=1 stretchH=100 smooth=1 aa=1 padding=0,0,0,0 spacing=1,1 outline=0 common lineHeight=32 base=25 scaleW=$this->docSizeX scaleH=$this->docSizeY pages=1 packed=0 alphaChnl=1 redChnl=0 greenChnl=0 blueChnl=0 \npage id=0 file='sprite.png' \nchars count=$count";
        $rows = [$header];

        foreach ($sprites as $sprite) {

            $image = imagecreatefrompng($sprite->path);

            imagecopy($canvas, $image, $sprite->x, $sprite->y, 0, 0, $sprite->width, $sprite->height);

            /**
             * Free Memory
             */
            imagedestroy($image);

            array_push($rows, $sprite->char);

        }

        /**
         * Save To Disk
         */
        imagepng($canvas, base_path($this->output));


        $keywords = ['id,word'];
        foreach ($uniques as $index => $value) {
            $keywords[] = str_replace("array-", "", $index) . ",$value";
        }

//        dd($keywords);

//        dd($rows);
//
//        return response()->make(implode('', $uniques), 200, $headers);
        /**
         * Keywords for Photoshop
         */
//        return response()->make(implode("\n", $keywords), 200, $headers);

        /**
         * Coordinates Map
         */
        return response()->make(implode(" ", $rows), 200, $headers);

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