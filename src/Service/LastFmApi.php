<?php 
namespace AdinanCenci\Player\Service;

use Psr\SimpleCache\CacheInterface;

class LastFmApi 
{
    protected string $apiKey;
    protected string $secret;
    protected CacheInterface $cache;

    public function __construct(string $apiKey, string $secret, CacheInterface $cache) 
    {
        $this->apiKey = $apiKey;
        $this->secret = $secret;
        $this->cache  = $cache;
    }

    public static function create() : LastFmApi
    {
        $manager = ServicesManager::singleton();

        return new self(
            $manager->get('config')->get('lastFmApiKey'), 
            $manager->get('config')->get('lastFmApiSecret'), 
            $manager->get('cache')
        );
    }

    public function searchForArtist(string $artist, int $page = 1, int $itensPerPage = 20) : \stdClass 
    {
        return $this->getJson('2.0/?method=artist.search&artist=' . urlencode($artist) . '&page=' . $page . '&limit=' . $itensPerPage);
    }

    public function getArtistAlbums(string $artist, int $page = 1, int $itensPerPage = 20) : \stdClass
    {
        return $this->getJson('2.0/?method=artist.gettopalbums&artist=' . urlencode($artist) . '&page=' . $page . '&limit=' . $itensPerPage);
    }

    public function getRelease(string $id) : \stdClass
    {
        return $this->getJson('2.0/?method=album.getinfo&mbid='. $id);
    }

    public function getReleaseByArtistAndTitle(string $artistName, string $title) : \stdClass
    {
        return $this->getJson('2.0/?method=album.getinfo&artist='. urlencode($artistName) . '&album=' . urlencode($title));
    }

    protected function getJson(string $url) : \stdClass
    {
        $cacheKey = md5($url);

        if (!$json = $this->cache->get($cacheKey, false)) {
            $url = 'http://ws.audioscrobbler.com/' . $url .'&api_key=' . $this->apiKey . '&format=json';
            $json = $this->request($url);
            $this->cache->set($cacheKey, $json, 24 * 60 * 60 * 7);
        }

        return json_decode($json);
    }

    protected function request(string $url) : string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json', 
                'Accept-Language: en-US,en;q=0.5', 
                'Cache-Control: no-cache', 
                'Host: ws.audioscrobbler.com', 
                'Pragma: no-cache', 
                'Sec-Gpc: 1', 
                'Upgrade-Insecure-Requests: 1', 
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36' 
            ]
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $body;
    }

}
