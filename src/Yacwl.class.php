<?php

namespace Yacwl;
use Exception;
use Symfony\Component\DomCrawler\Crawler;


class Yacwl
{
    protected $finder;

    private $s                           = array();
    private $yc                             = array();
    private $sc                        = array();

    public $file_list_download;

    public function __construct(Crawler $finder)
    {
        $this->finder                                           = $finder;
        $this->s['page_number_selector']                 = 'body > .pagination a:last-child';
        $this->s['page_content_box_selector']           = '#main-content .main-content-inner article .entry-title a';
        $this->s['single_page_download_link']           = '#page #main-content .download-links a';
        $this->s['single_page_book_info']           = '#page .book-detail';

        $this->sc['url_pagination_structure']          = "http://www.allitebooks.com/page/%s/";
        $this->sc['base_url']                          = 'http://www.allitebooks.com/';

        $this->yc['base_path']                        = '/tmp/';
        $this->yc['folder_name']                      = 'yacwl';
        $this->yc['complete_path']                    =  $this->yc['base_path'] . $this->yc['folder_name'];
        $this->yc['current_page']                     = 1;
        $this->yc['file_list_download']               = array();
        $this->yc['fail_list_download']               = array();
    }

    public function execute()
    {
        $this->setup();
        $this->get_current_pagination_page();

        $this->set_site_page_number();

        while( $this->yc['current_page'] < $this->page_numbers ){
            $this->manage_single_page();
            $this->yc['current_page']++;
            $this->get_current_pagination_page();
        }

        $this->manage_single_page();

        $this->end();
    }
    private function end(){
        file_put_contents($this->yc['complete_path'] . '/result.json', json_encode($this->yc['file_list_download']));
    }
    private function get_current_pagination_page(){
        $html_page = $this->getSinglePage(sprintf($this->sc['url_pagination_structure'],$this->yc['current_page']));
        $this->finder->clear();
        $this->finder->add($html_page);
    }
    private function manage_single_page(){
        echo "pagina " . $this->yc['current_page'] . "\n";

        $link_single_pages = $this->finder->filter($this->s['page_content_box_selector']);
        foreach($link_single_pages as $link_single_page){
            $url = $link_single_page->getAttribute('href');
            $single_finder = new Crawler( $this->getSinglePage($url));
            $url_pdf = $single_finder->filter($this->s['single_page_download_link'])->first()->attr('href');
            $detail_pdf = $single_finder->filter($this->s['single_page_book_info'])->first()->html();
            array_push($this->yc['file_list_download'],array('url'=>$url_pdf, 'detail'=>$detail_pdf));
            echo ".";

        }
    }

    private function set_site_page_number()
    {
        $this->page_numbers = (int) $this->finder->filter($this->s['page_number_selector'])->text();
        if($this->page_numbers <= 0 ){
            throw new \RuntimeException('Not able to get page numbers');
        }
    }

    private function setup()
    {
        try {
            if (is_dir($this->yc['complete_path'] )) {
                //@todo cambiare la cancellazione della cartella nel caso esista
                system("rm -rf " . escapeshellarg($this->yc['complete_path'] ));
            }
            mkdir($this->yc['complete_path'] );
        } catch (Exception $e) {
            throw new \RuntimeException('Exception during the Yacwl setup' . $e->getMessage());
        }
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
}