<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\News;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

final class NewsController extends AbstractController
{
    #[Route('/api/news', name: 'news_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Получаем данные формы
        $title = $request->request->get('title');
        $author = $request->request->get('author');
        $content = $request->request->get('content');
        $photo = $request->files->get('photo');

        // Валидация входных данных
        if (!$title || !$author || !$content) {
            $logger->error('Missing required fields in request data', [
                'data' => [
                    'title' => $title,
                    'author' => $author,
                    'content' => $content,
                ],
                'request' => $request,
            ]);
            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $news = new News();
        $news->setTitle($title);
        $news->setAuthor($author);
        $news->setContent($content);

        if ($photo && $photo instanceof UploadedFile) {
            $allowedMimeTypes = $this->getParameter('app.allowed_mime_types');
            if (!in_array($photo->getClientMimeType(), $allowedMimeTypes)) {
                return new JsonResponse(['error' => 'Invalid file type. Only JPEG and PNG are allowed.'], Response::HTTP_BAD_REQUEST);
            }

            $photoPath = 'uploads/' . uniqid() . '.' . $photo->getClientOriginalExtension();

            $photo->move($this->getParameter('app.upload_directory'), $photoPath);

            $news->setPhoto($photoPath);
        }

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
