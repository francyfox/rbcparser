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

    function deleteOldXml (array $filenames, string $newFilename)
    {

    }

    function xmlToArray (array $filenames): array
    {
        $mergedArray = array();
        foreach ($filenames as $item) {
            $xml = simplexml_load_file($item, 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_decode(json_encode($xml), TRUE);
            $array = array_pop($json);
            array_push($mergedArray, $array);
        }
        if (count($mergedArray) == 2) {
            return array_diff_ukey($mergedArray[0], $mergedArray[1]);
        } elseif (count($mergedArray) == 1) {
            return $mergedArray[0];
        }
        return [];
    }

    function getLastTime (string $url): int
    {
        $date = new \DateTime('now');
        $date = $date->format("M d Y H:i:s");
        return strtotime($date);
    }

    function getXmlArray (string $url): array
    {
        if (!file_exists($this->home_path.'/public/rss')) {
            mkdir($this->home_path.'/public/rss', 0700);
        }

        $path = $this->home_path.'/public/rss/'.$this->getLastTime($url).'.rss';
        $fp = fopen($path, 'c+');
        if($fp === false){
            throw new \Exception('Could not open: ' . $this->home_path.'/public/rss/news.rss');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        if(!curl_exec($ch)){
            curl_close($ch);
            fclose($fp);
            return [];
        }
        fflush($fp);
        fclose($fp);
        curl_close($ch);

        $map = glob($this->home_path.'/public/rss/*.rss');
        return $this->xmlToArray($map);
    }

    function saveXmlArrayToDb(array $array)
    {
        if (!empty($array)) {
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
}