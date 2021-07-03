<?php


namespace App\Services;
use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

class xmlType
{
    private $em;
    private $serializer;
    private $home_path;

    public function __construct(SerializerInterface $serializer,
                                KernelInterface $kernel, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->home_path = $kernel->getProjectDir();
    }

    function checkRssFileSize (string $url)
    {
        $file = $this->home_path.'/public/rss/news.rss';
        if (!file_exists($file)) {
            return false;
        }
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);

        $data = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        curl_close($ch);
        return $size == filesize($this->home_path.'/public/rss/news.rss');

    }

    function getXmlArray (string $url): array
    {
        if (!file_exists($this->home_path.'/public/rss')) {
            mkdir($this->home_path.'/public/rss', 0700);
        }
        $fp = fopen($this->home_path.'/public/rss/news.rss', 'w+');
        if($fp === false){
            throw new \Exception('Could not open: ' . $this->home_path.'/public/rss/news.rss');
        }
        if ($this->checkRssFileSize($url)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            if(!curl_exec($ch)){
                curl_close($ch);
                fclose($fp);
                return false;
            }
            fflush($fp);
            fclose($fp);
            curl_close($ch);

            $xml = simplexml_load_file($this->home_path.'/public/rss/news.rss', 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_decode(json_encode($xml), TRUE);
            $array = array_pop($json);
            return $array["item"];
        }
        return false;
    }

    function saveXmlArrayToDb(array $array)
    {
        foreach ($array as $item) {
            $news = new News();
            $news
                ->setTitle($item["title"])
                ->setAnounce($item["description"])
                ->setLink($item["link"])
                ->setAuthor($item["author"] ?? null)
                ->setImgUrl($item["enclosure"]["@attributes"]["url"] ?? null);
            $this->em->persist($news);
            $this->em->flush();
        }
    }
}