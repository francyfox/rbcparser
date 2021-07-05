<?php


namespace App\Services;
use App\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;

interface Log
{
    public function setInfoFromCurl($curl, $body): Log;
    public function add(): Log;
}

class LogType implements Log
{
    private $em;
    private $datetime;
    private $method;
    private $url;
    private $status;
    private $body;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function setInfoFromCurl($curl, $body): Log
    {
        $this->datetime = new \DateTime('now');
        $this->method = 'HTTP';
        $this->url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->body = json_encode($body);
        return $this;
    }

    public function add(): Log
    {
        $log = new Logs();
        $log
            ->setDateTime($this->datetime)
            ->setMethod($this->method)
            ->setBody($this->body ?? null)
            ->setStatus($this->status)
            ->setUrl($this->url);
        $this->em->persist($log);
        $this->em->flush();
        return $this;
    }

}