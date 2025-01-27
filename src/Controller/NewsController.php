<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\News;
use App\Repository\NewsRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;

final class NewsController extends AbstractController
{
    #[Route('/api/news', name: 'news_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);

        // Валидация входных данных
        if (!isset($data['title'], $data['author'], $data['content'])) {
            $logger->error('Missing required fields in request data', [
                'data' => $data,
                'request_content' => $request->getContent(),
                'request' => $request,
            ]);
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $news = new News();
        $news->setTitle($data['title']);
        $news->setAuthor($data['author']);
        $news->setContent($data['content']);
        $news->setPhoto($data['photo'] ?? null);

        // Сохраняем новость в базе данных
        $entityManager->persist($news);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $news->getId(),
            'title' => $news->getTitle(),
            'author' => $news->getAuthor(),
            'content' => $news->getContent(),
            'photo' => $news->getPhoto(),
        ], Response::HTTP_CREATED);
    }
}
