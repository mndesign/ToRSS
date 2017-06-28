<?php

namespace mndesign\ToRSS;

class RSSFeed
{
    private $http = null;
    private $data = null;

    public function createRSS($data)
    {
        $rssBody = '';

        $this->http = 'https://thepiratebay.org/search/' . $this->encodeSearch($data) . '/0/3/0';
        $this->data = $this->cUrl($this->http);

        $patternTorrentTable = "/(?i)(?:<table id=\"searchResult\">)(?<torrentTable>[\d\W\w\s ,.]*?)(?:<\/table>)/";  //[\d\W\w\s ,.]
        preg_match_all($patternTorrentTable, $this->data, $torrentTableDataArr);

        $torrentTableData = $torrentTableDataArr['torrentTable'][0];

        $patternSingleTorrent = "/(?i)(<tr>)(?<singleTorrent>[\d\W\w\s ,.]*?)(?:<\/tr>)/";
        preg_match_all($patternSingleTorrent, $torrentTableData, $torrentSingleData);

        $loops = count($torrentSingleData['singleTorrent']);
        for ($row = 0; $row < 4; $row++) {

            $patternPagination = "/(?i)td colspan=\"9\"/";
            if (preg_match_all($patternPagination, $torrentSingleData['singleTorrent'][$row], $pagination)) {
                continue;
            }

            $patternInfo = "/(?i)(a href=\"\/torrent\/)(?<torrentDescURL>[\d\W\w\s ,.]*?)(\" class=\"detLink\" title=\"Details for )(?<torrentTitle>[\d\W\w\s ,.]*?)(\")/";
            preg_match_all($patternInfo, $torrentSingleData['singleTorrent'][$row], $torrentInfo);

            $patternCategory = "/(?i)More from this category\">(?<torrentCategories>[\d\W\w\s ,.-]*?)<\/a>/";
            preg_match_all($patternCategory, $torrentSingleData['singleTorrent'][$row], $torrentCats);
            $torrentCategories = $torrentCats['torrentCategories'][1];

            $torrentTitle = $torrentInfo['torrentTitle'][0];
            $torrentDescUrl = "http://thepiratebay.se/torrent/" . $torrentInfo['torrentDescURL'][0];

            $patternInfo = "/(?i)(<a href=\"magnet:\?)(?<torrentMagnetURI>[\d\W\w\s ,.]*?)(\" title=\"Download this torrent using magnet)/";
            preg_match_all($patternInfo, $torrentSingleData['singleTorrent'][$row], $torrentInfo);

            $torrentMagnetURI = "?" . $torrentInfo['torrentMagnetURI'][0];
            $torrentMagnetEncodedURI = str_replace("&", "&amp;", $torrentMagnetURI);

            $patternInfo = "/(?i)(btih:)(?<torrentMagnetHash>[\d\W\w\s ,.]*?)(&dn)/";
            preg_match_all($patternInfo, $torrentMagnetURI, $torrentMagnet);

            $patternUploadTimeAndSize = "/(?i)Uploaded (?<torrentTime>[\d\W\w\s -:]*?), Size (?<torrentSize>[\d\W\w\s .]*?),/";
            preg_match($patternUploadTimeAndSize, $torrentSingleData['singleTorrent'][$row], $UploadTimeAndSize);

            $torrentUploadTime = strip_tags($UploadTimeAndSize['torrentTime']);

            $patterns = array(
                '/mins/',
                '/&nbsp;/',
                '/Y-day/',
                '/(\d{2})-(\d{2}) (\d{2}):(\d{2})/',
                '/(\d{2})-(\d{2}) (\d{4})/'
            );

            $replacements = array(
                'minutes',
                ' ',
                'yesterday',
                '\3:\4 ' . date("Y") . '-\1-\2',
                '\3-\1-\2'
            );

            $torrentUploadTime = gmdate('r', strtotime(preg_replace($patterns, $replacements, $torrentUploadTime)));

            $torrentMagnetHash = $torrentMagnet['torrentMagnetHash'][0];

            $rssBody .= "   <item>\n"
                . "      <title><![CDATA[$torrentTitle]]></title>\n"
                . "      <pubDate>$torrentUploadTime</pubDate>\n"
                . "      <guid isPermaLink=\"true\">$torrentDescUrl</guid>\n"
                . "      <link>magnet:$torrentMagnetEncodedURI</link>\n"
                . "      <category>$torrentCategories</category>\n"
                . "      <torrent xmlns=\"http://xmlns.ezrss.it/0.1/\">\n"
                . "      	<infoHash>$torrentMagnetHash</infoHash>\n"
                . "      	<magnetURI><![CDATA[magnet:$torrentMagnetURI]]></magnetURI>\n"
                . "      </torrent>\n"
                . "   </item>\n";

        }
        return $rssBody;
    }

    private function encodeSearch($data)
    {
        $result = str_replace(' ', '%20', $data['query']);
        if ($data['ignore'] != null) {
            $result .= '%20' . str_replace(' ', '%20', $data['ignore']);
        }
        return $result;
    }

    private function cUrl($http)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $http);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 13);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function createRSSHead()
    {
        $rssHead = "<?xml version='1.0' encoding='UTF-8' ?>\n"
            . "<!DOCTYPE torrent PUBLIC \"-//bitTorrent//DTD torrent 0.1//EN\" \"http://xmlns.ezrss.it/0.1/dtd/\">\n"
            . "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n"
            . "   <channel> \n"
            . "   <title>Torrent Rss Feed</title> \n"
            . "   <description>Torrent Rss Feed</description> \n"
            . "   <lastBuildDate>" . gmdate('r') . "</lastBuildDate> \n"
            . "   <generator>ToRss ver. 1.10</generator> \n"
            . "   <language>en</language> \n";

        return $rssHead;
    }

    public function createRSSFooter()
    {
        $rssFooter = "   </channel> \n"
            . "</rss> ";
        return $rssFooter;
    }
}
