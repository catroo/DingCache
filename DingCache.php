<?php

/**
 * DingCache.php
 *
 * Developed by dinghui <dinghui@plu.cn>
 * Copyright (c) 2016 https://github.com/catroo
 *
 * Changelog:
 * 2016-09-08 - created
 *
 */
namespace Ding;

class Cache
{
        private $cacheDir;

        private $prefix;

        const NOT_FOUND_SHM = 'The directory /dev/shm/ cannot be found.';

        private $keysData = [];


        /*
         * Constructor of Ding Cache, you can specify a prefix which 
         * will used to prepend to any keys when doing set/get/delete
         *
         * @param string $prefix
         *
         */
        public function __construct($prefix = 'dc')
        {
                $this->cacheDir = '/dev/shm/';
                $this->prefix = $prefix;
                if (! is_dir($this->cacheDir) ) {
                        throw new Exception(NOT_FOUND_SHM);
                }
                $this->cacheDir .= 'dingcache/';
		@chmod($this->cacheDir, 0777);
        }

        /**
         * Store a value into Ding cache, keys are cache-unique, 
         * so storing a second value with the same key will overwrite the original value.
         *
         * @param string  $key
         * @param mix     $value
         * @param int     $ttl
         */
        public function set($key, $value, $ttl = 0)
        {
                $file = $this->makeFile($key);
                @file_put_contents($file, serialize($value), LOCK_EX);
		@chmod($file, 0777);
                if( $ttl <= 0 ) {
                        $ttl = 31536000; // 1 year
                }
                $ttl += time();
                @touch($file, $ttl);
                unset($value);
        }

        /**
         * Fetches a stored variable from the cache. If an array is passed then each element is fetched and returned.
         * @param string|array  $key
         * @return mix
         *
         */
        public function get($keys)
        {
                if ( is_string($keys) ) {
                        $file = $this->makeFile($keys);
                        if ( file_exists($file) ) {
                                $time = @filemtime($file);
                                if ( $time > time() ) {
                                        return unserialize(@file_get_contents($file));
                                } else {
                                        @unlink($cacheFile);
                                }
                        }
                } else if( is_array($keys) ) {
                        foreach ($keys as $val) {
                                $this->keysData[] = $this->get($val);
                        }
                        return $this->keysData;
                }
                return null;
        }

        /**
         * Removes a stored variable from the cache.
         *
         * @param string|array $removeDir
         */
        public function delete($keys)
        {
                if ( is_string($keys) ) {
                        $file = $this->makeFile($keys);
                        if ( file_exists($file) ) {
                                unlink($file);
                        }
                } else if ( is_array($keys) ) {
                        foreach ($keys as $val) {
                                $this->delete($val);
                        }
                }
        }

        /**
         * Immediately invalidates all existing items
         *
         * @param string $removeDir
         */
        public function flush($removeDir = '')
        {
                $dirname = $this->cacheDir . $removeDir;
                $handle = opendir($dirname);
                while(($file = readdir($handle)) !== false) {
                    if($file != '.' && $file != '..') {
                        $dir = $dirname . $file;
                        is_dir($dir) ? $this->flush($dir) : unlink($dir);
                    }
                }
                
                closedir($handle);
                
                if ( $removeDir != '' ) {
                        rmdir($dirName);
                }
        }

        public function info()
        {
                //@todu
        }

        private function makeFile($key)
        {
                $key = $this->prefix  . '_' . str_replace('..', '', $key);
                $file = $this->cacheDir . '/' . $key . '.dat';
		$dir = dirname($file);
		@mkdir($dir, 0777, true);
                return $file;
        }

        public function __destruct()
        {
                unset($this->keysData);
        }
}
