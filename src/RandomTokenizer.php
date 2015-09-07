<?php

namespace JLaso\ToolsLib;

class RandomTokenizer
{
    /** @var array  */
    protected $nouns;
    /** @var array  */
    protected $adjectives;
    /** @var array  */
    protected $attributes;
    /** @var array  */
    protected $attributeUnions;

    /**
     * @param null $file
     */
    public function __construct($file = null)
    {
        if (!$file){
            $file = __DIR__.'/words/words.txt';
        }

        $section = '';
        $words = array();
        foreach (file($file) as $row){
            $row = trim($row);
            if (preg_match('/\[([^\]]*)\]/i', $row, $matches)){
                $section = $matches[1];
                continue;
            }
            if ($section && $row){
                $words[$section][] = $row;
            }
        }
        $this->nouns = isset($words['nouns']) ? $words['nouns'] : array();
        $this->adjectives = isset($words['adjectives']) ? $words['adjectives'] : array();
        $this->attributes = isset($words['attributes']) ? $words['attributes'] : array();
        $this->attributeUnions = isset($words['attribute-unions']) ? $words['attribute-unions'] : array();
    }


    /**
     * @return string
     */
    public function getPhrase()
    {
        $mainNoun = $this->getOne($this->nouns);
        $mainAdjective = $this->getOne($this->adjectives);
        $phrase =  $mainAdjective . ' ' . $mainNoun;
        if (rand(0,10) > 4){
            $phrase .= ' ' . $this->getOne($this->attributeUnions) . ' ' .
                        $this->getOne($this->adjectives, $mainAdjective) . ' ' . $this->getOne($this->nouns, $mainNoun);
        }

        return $phrase;
    }

    /**
     * @param $words
     * @param null $except
     * @return mixed
     */
    protected function getOne($words, $except = null)
    {
        do {
            $word = $words[rand(0,count($words)-1)];
        }while ($except && $except == $word);

        return $word;
    }

}