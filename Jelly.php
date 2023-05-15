<?php

namespace GeneMarks;

class Jelly {
    private $CONFIG;
    private $HOST;
    private $API_KEY;
    private $USER_ID;
    private $ITEM_LIMIT;
    private $CACHE_FILE;
    private $CACHE_EXP;
    private $LOG_FILE;
    private $LOG_FILES;
    private $IMGS_DIR;
    private $IMGS;

    function __construct() {
        $this->CONFIG     = parse_ini_file(__DIR__ . '/jfserver.ini');
        $this->HOST       = 'https://' . $this->CONFIG['host'];
        $this->API_KEY    = $this->CONFIG['apikey'];
        $this->USER_ID    = $this->CONFIG['userid'];
        $this->ITEM_LIMIT = $this->CONFIG['itemlimit'];
        $this->CACHE_FILE = __DIR__ . '/jfcache.json';
        $this->CACHE_EXP  = $this->CONFIG['cachetime'];
        $this->LOG_FILE   = __DIR__ . '/logs//' . date('Y-m-d') . '.txt';
        $this->LOG_FILES  = glob(__DIR__ . '/logs/*.txt');
        $this->IMGS_DIR   = $this->CONFIG['imagesdir'];
        $this->IMGS       = glob($this->IMGS_DIR . '/*.webp');
    }

    function writeJellyCache($content) {
        file_put_contents($this->CACHE_FILE, $content);
    }

    function jellyfinRequest() {
        $headers = [
            'Accept: application/json',
            'Authorization: Mediabrowser Token="' . $this->API_KEY . '"'
        ];
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->HOST . '/Users/' . $this->USER_ID. '/Items/Latest?includeItemTypes=Episode&includeItemTypes=Movie&isPlayed=true&limit=999999&groupItems=false');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($_SERVER['HTTP_HOST'] === 'localhost') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $data = curl_exec($ch);
        $err = curl_error($ch) ?? '';
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($err !== '')
            throw new \Exception($err);
        else if ($status !== 200)
            throw new \Exception("cURL request returned an HTTP error. Please check if your jellyfin server is up.");

        curl_close($ch);

        return $data;
    }

    function DlJellyImgs($data) {
        foreach ($data as $d) {
            $img_type = 'Backdrop';
            if (array_key_exists('SeriesName', $d) && $d['SeriesName'] !== '')
                $img_type = 'Primary';

            $img_name = str_replace(':', '', str_replace('.', '', $d['LastPlayedDate']));
            $img = $this->IMGS_DIR . '/' . $img_name . '.webp';
            $url = $this->HOST . '/Items//' . $d['Id'] . '/Images/' . $img_type . '?maxWidth=480&quality=96';

            if (!file_exists($img)) {
                try {
                    file_put_contents($img, file_get_contents($url));
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
    }

    function JellyCleanup($files, $amt_to_keep, $compared_to = false) {
        if (count($files) < $amt_to_keep) return;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $all_imgs = true;
        foreach ($files as $file) {
            $mime_type = finfo_file($finfo, $file);
            if (substr($mime_type, 0, 6) !== 'image/') {
                $all_imgs = false;
                break;
            }
        }
        finfo_close($finfo);

        if ($all_imgs) {
            foreach ($files as $file) {
                $img_filename = pathinfo($file, PATHINFO_FILENAME);
                if (!in_array($img_filename, $compared_to))
                    unlink($file);
            }
        } else {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $to_delete = array_slice($files, $amt_to_keep);
            array_map('unlink', $to_delete);
        }

    }

    function getWatchedItems() {
        if (!file_exists($this->CACHE_FILE) || (time() - filemtime($this->CACHE_FILE) > $this->CACHE_EXP)) {
            try {
                $jelly_data = $this->jellyfinRequest();
                $jelly_data = json_decode($jelly_data, true);

                $jelly_data = array_filter($jelly_data, function ($item) {
                    return isset($item['UserData']['LastPlayedDate']);
                });

                usort($jelly_data, function ($a, $b) {
                    return strcmp($b['UserData']['LastPlayedDate'], $a['UserData']['LastPlayedDate']);
                });

                $jelly_data = array_slice($jelly_data, 0, $this->ITEM_LIMIT);

                $jelly_data_sens = [];
                foreach ($jelly_data as $d) {
                    $jelly_data_sens[] = [
                        'Name'           => $d['Name'] ?? '',
                        'Id'             => $d['Id'] ?? '', // REMOVE LATER! SENSITIVE INFO!
                        'SeriesName'     => $d['SeriesName'] ?? '',
                        'ProductionYear' => $d['ProductionYear'] ?? '',
                        'PlayCount'      => $d['UserData']['PlayCount'] ?? '',
                        'IsFavorite'     => $d['UserData']['IsFavorite'] ?? '',
                        'LastPlayedDate' => $d['UserData']['LastPlayedDate'] ?? '',
                    ];
                }

                $this->DlJellyImgs($jelly_data_sens);

                $dates = array_column($jelly_data_sens, 'LastPlayedDate');
                $dates = array_filter($dates, function ($value) {
                    return $value !== '';
                });
                $dates = array_map(function($value) {
                    return str_replace(':', '', str_replace('.', '', $value));
                }, $dates);
                $this->JellyCleanup($this->IMGS, $this->ITEM_LIMIT, $dates);

                $jelly_data = array_map(function ($item) {
                    unset($item['Id']);
                    return $item;
                }, $jelly_data_sens);

                $jelly_data = json_encode($jelly_data);

                $this->writeJellyCache($jelly_data);

            } catch (\Exception $e) {
                file_put_contents($this->LOG_FILE, date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
                $jelly_data = file_exists($this->CACHE_FILE) ? file_get_contents($this->CACHE_FILE) : '';
            }

            $this->JellyCleanup($this->LOG_FILES, 10);

        } else {
            $jelly_data = file_get_contents($this->CACHE_FILE);
        }

        return $jelly_data;
    }
}