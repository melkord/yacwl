<?php

namespace Yacwl;

use Exception;
use Symfony\Component\DomCrawler\Crawler;


class Yacwl
{
    public $file_list_download;
    public $parallelDownloadNumber = 1;
    protected $finder;
    private $s = array();
    private $yc = array();
    private $sc = array();
    private $page_numbers;

    public function __construct(Crawler $finder)
    {
        $this->finder = $finder;
        $this->s['page_number_selector'] = 'body > .pagination a:last-child';
        $this->s['page_content_box_selector'] = '#main-content .main-content-inner article .entry-title a';
        $this->s['single_page_download_link'] = '#page #main-content .download-links a';
        $this->s['single_page_book_info'] = '#page .book-detail';

        $this->sc['url_pagination_structure'] = "http://www.allitebooks.com/page/%s/";
        $this->sc['base_url'] = 'http://www.allitebooks.com/';

        $this->yc['base_path'] = '/tmp/';
        $this->yc['folder_name'] = 'yacwl';
        $this->yc['complete_path'] = $this->yc['base_path'] . $this->yc['folder_name'];
        $this->yc['current_page'] = 1;
        $this->yc['file_list_download'] = array();
        $this->yc['fail_list_download'] = array();
    }

    public function execute()
    {
        $this->setup();
        $this->get_current_pagination_page();

        $this->set_site_page_number();

        while ($this->yc['current_page'] < $this->page_numbers) {
            $this->manage_single_page();
            $this->yc['current_page']++;
            $this->get_current_pagination_page();
        }

        $this->manage_single_page();

        $this->end();
    }

    public function setup()
    {
        try {
            if (is_dir($this->yc['complete_path'])) {
                //@todo cambiare la cancellazione della cartella nel caso esista
                system("rm -rf " . escapeshellarg($this->yc['complete_path']));
            }
            mkdir($this->yc['complete_path']);
        } catch (Exception $e) {
            throw new \RuntimeException('Exception during the Yacwl setup' . $e->getMessage());
        }
    }

    private function get_current_pagination_page()
    {
        $html_page = $this->getSinglePage(sprintf($this->sc['url_pagination_structure'], $this->yc['current_page']));
        $this->finder->clear();
        $this->finder->add($html_page);
    }

    public function getSinglePage($url)
    {
        try {
            $html_page = file_get_contents($url);
        } catch (Exception $e) {
            throw new \RuntimeException('Exception while downloading this url: ' . (string)$url . ' Exception: ' . $e->getMessage());
        }
        return $html_page;

    }

    private function set_site_page_number()
    {
        $this->page_numbers = (int)$this->finder->filter($this->s['page_number_selector'])->text();
        if ($this->page_numbers <= 0) {
            throw new \RuntimeException('Not able to get page numbers');
        }
    }

    private function manage_single_page()
    {
        echo "pagina " . $this->yc['current_page'] . "\n";

        $link_single_pages = $this->finder->filter($this->s['page_content_box_selector']);
        foreach ($link_single_pages as $link_single_page) {
            $url = $link_single_page->getAttribute('href');
            $single_finder = new Crawler($this->getSinglePage($url));
            $url_pdf = $single_finder->filter($this->s['single_page_download_link'])->first()->attr('href');
            $detail_pdf = $single_finder->filter($this->s['single_page_book_info'])->first()->html();
            array_push($this->yc['file_list_download'], array('url' => $url_pdf, 'detail' => $detail_pdf));
            echo ".";

        }
    }

    private function end()
    {
        file_put_contents($this->yc['complete_path'] . '/result.json', json_encode($this->yc['file_list_download']));
    }

    public function download()
    {

        $curl_arr = array();
        $master = curl_multi_init();
        for ($i = 0; $i < count($this->yc['file_list_download']); $i = $i + $this->parallelDownloadNumber) {
            $array_chunk = array_slice($this->yc['file_list_download'], $i, $this->parallelDownloadNumber);
            for ($k = 0; $k < $this->parallelDownloadNumber; $k++) {
                $curl_arr[$k] = curl_init($array_chunk[$k]['url']);
                curl_setopt($curl_arr[$k], CURLOPT_RETURNTRANSFER, true);
                curl_multi_add_handle($master, $curl_arr[$k]);
                echo "add url to download " . $array_chunk[$k]['url'];
                echo "\n";
            }
            do {
                curl_multi_exec($master, $running);
            } while ($running > 0);
            for ($k = 0; $k < $this->parallelDownloadNumber; $k++) {
                $path_to_save = $this->yc['complete_path'] . '/' . basename($array_chunk[$k]['url']);
                file_put_contents($path_to_save, curl_multi_getcontent($curl_arr[$k]));
                echo "saved " . $path_to_save;
                echo "\n";
            }
        }
    }

    public function setFileListDownload($json)
    {
        if ($this->isGoodJson($json)) {
            $this->yc['file_list_download'] = json_decode($json, true);
        } else {
            throw new \RuntimeException('The JSON parsed is not valid for Yacwl');
        }
    }

    public function isGoodJson($json)
    {
        return is_string($json) && is_array(json_decode($json)) && (json_last_error() == JSON_ERROR_NONE);
    }
}