<?php


namespace App\Services;
use App\Entity\News;
use App\Services\LogType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

class xmlType
{
    private $log;
    private $em;
    private $serializer;
    private $home_path;

    public function __construct(SerializerInterface $serializer,
                                KernelInterface $kernel, EntityManagerInterface $em,
                                LogType $log)
    {
        $this->log = $log;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->home_path = $kernel->getProjectDir();
    }

    function deleteOldXml (array $filenames, string $newFilename)
    {
        foreach ($filenames as $item) {
            if ($item !== $newFilename) {
                unlink($item);
            }
        }
    }

    function key_compare_func($key1, $key2): int
    {
        if ($key1 == $key2)
            return 0;
        else if ($key1 > $key2)
            return 1;
        else
            return -1;
    }

    function xmlToArray (array $filenames): array
    {
        $mergedArray = array();
        foreach ($filenames as $item) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_file($item, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml === false) {
                echo "Failed loading XML\n";
                foreach(libxml_get_errors() as $error) {
                    echo "\t", $error->message;
                }
            }
            $json = json_decode(json_encode($xml), TRUE);
            $array = array_pop($json);
            array_push($mergedArray, $array["item"]);
        }
        if (count($mergedArray) == 2) {
            return array_diff_ukey($mergedArray[0], $mergedArray[1], 'App\Services\xmlType::key_compare_func');
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

        $map = glob($this->home_path.'/public/rss/*.rss');
        $result = $this->xmlToArray($map);;
        $this->log
            ->setInfoFromCurl($ch, $result)
            ->add();

        fflush($fp);
        fclose($fp);
        curl_close($ch);
        $this->deleteOldXml($map, $path);
        return $result;
    }

    function saveXmlArrayToDb(array $array)
    {
        if (count($array) !== 0) {
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