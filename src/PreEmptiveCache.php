<?php

namespace JLaso\ToolsLib;

class PreEmptiveCache
{
    const OLDEST_MODE = 1;      // remove from cache first the oldest item
    const LESS_MODE = 2;        // remove from cache first the less accessed item
    const LESS_OLDEST_MODE = 3; // remove from cache first the oldest item from the less accessed items

    /** @var array  */
    protected $items = array();
    /** @var array  */
    protected $metaInfo = array();
    /** @var  \Closure */
    protected $callable;
    /** @var  int */
    protected $mode;
    /** @var int  */
    protected $maxRecordsCached;
    /** @var  bool */
    protected $debug;
    /** @var int  */
    protected $baseTime = 0;

    /**
     * @param \Closure $callable
     * @param array $options
     */
    public function __construct($callable, $options = array())
    {
	    $this->callable = $callable;
        $options = array_merge(array(
            "mode" => self::OLDEST_MODE,
            "maxRecordsCached" => 500,
            "debug" => false,
        ), $options);
	    $this->debug = $options["debug"];
	    $this->mode = $options["mode"];
        $this->maxRecordsCached = isset($options["maxRecordsCached"]) ? $options["maxRecordsCached"] : -1;
        $this->baseTime = $this->getTimeStamp();
    }

    /**
     * @return int
     */
    protected function getTimeStamp()
    {
        return intval(1000000*microtime(true)) - $this->baseTime;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function fetch($id)
    {
        if (!isset($this->items[$id])) {
            if (count($this->items) >= $this->maxRecordsCached){
                if ($this->debug){
                    print (sprintf("max mem exceeded (%d bytes) with %d items!\n", $this->currentMemUsed, count($this->items)));
                }
                $this->findCandidateAndRemoveFromCache();
            }

            $this->items[$id] = call_user_func_array($this->callable, array($id));
            $this->metaInfo[$id]['id'] = $id;
            $this->metaInfo[$id]['inMem'] = true;
            $this->metaInfo[$id]['lastAccess'] = $this->getTimeStamp();
            $this->metaInfo[$id]['numAccess'] = isset($this->metaInfo[$id]['numAccess']) ? $this->metaInfo[$id]['numAccess']+1 : 1;
        }

        return $this->items[$id];
    }

    /**
     * @return bool
     */
    protected function findCandidateAndRemoveFromCache()
    {
        $candidate = null;

        switch ($this->mode){
            case self::LESS_MODE:
                $less = 9999999;
                foreach ($this->metaInfo as $metaInfo){
                    if (($metaInfo['numAccess'] < $less) && $metaInfo['inMem']){
                        $less = $metaInfo['numAccess'];
                        $candidate = $metaInfo;
                        if ($less == 1){
                            break;
                        }
                    }
                }
                break;

            case self::LESS_OLDEST_MODE:
                $less = 9999999;
                // calculate the min value for numAccess
                foreach ($this->metaInfo as $metaInfo){
                    if (($metaInfo['numAccess'] < $less) && $metaInfo['inMem']){
                        $less = $metaInfo['numAccess'];
                    }
                }
                $oldest = $this->getTimeStamp();
                foreach ($this->metaInfo as &$metaInfo){
                    if (($less == $metaInfo['numAccess']) && $metaInfo['inMem'] && ($metaInfo['lastAccess'] < $oldest)){
                        $oldest = $metaInfo['lastAccess'];
                        $candidate = $metaInfo;
                    }
                }
                break;

            case self::OLDEST_MODE:
                $oldest = date("U");
                foreach ($this->metaInfo as $metaInfo){
                    if (($metaInfo['lastAccess'] < $oldest) && $metaInfo['inMem']){
                        $oldest = $metaInfo['lastAccess'];
                        $candidate = $metaInfo;
                    }
                }
                break;
        }
        if ($this->debug){
            var_dump($candidate);
        }
        if ($candidate){
            if ($this->debug){
                $this->dumpMetaInfo();
                print sprintf("freeing item %d from memory\n", $candidate['id']);
            }
            $this->metaInfo[$candidate['id']]['inMem'] = false;
            unset($this->items[$candidate['id']]);
            if ($this->debug){
                $this->dumpMetaInfo();
            }
            return true;
        }
    }

    /**
     * Only with debug purposes
     */
    protected function dumpMetaInfo()
    {
        $text = "";
        $temp = $this->metaInfo;
        sort($temp);
        foreach($temp as $metaInfo){
            $text .= sprintf("%d[%d]%s,", $metaInfo['id'], $metaInfo['numAccess'], ($metaInfo['inMem']?'*':''));
        }
        print $text."\n";
    }

}
