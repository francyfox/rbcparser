<?php

namespace App\Controller;

use App\Services\xmlType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'test')]
    public function index(xmlType $xmlType, KernelInterface $kernel, Request $request): Response
    {
        $url = 'http://static.feed.rbc.ru/rbc/logical/footer/news.rss';
        $rss = $xmlType->getXmlArray($url);
//        $xmlType->saveXmlArrayToDb($rss);

        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }
}
