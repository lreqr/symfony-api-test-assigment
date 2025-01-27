<?php

namespace App\Controller;

use App\Repository\NewsRepository;
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
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/news', name: 'news_create', methods: ['POST'])]
    public function create(Request $request, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $isJsonRequest = $request->headers->get('Content-Type') === 'application/json';

        if ($isJsonRequest) {
            $data = json_decode($request->getContent(), true);
        } else {
            $data = [
                'title' => $request->request->get('title'),
                'author' => $request->request->get('author'),
                'content' => $request->request->get('content'),
                'photo' => $request->files->get('photo'),
            ];
        }

        // Получаем данные формы/json
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

        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $allowedMimeTypes = $this->getParameter('app.allowed_mime_types');
            if (!in_array($data['photo']->getClientMimeType(), $allowedMimeTypes)) {
                return new JsonResponse(['error' => 'Invalid file type. Only JPEG and PNG are allowed.'], Response::HTTP_BAD_REQUEST);
            }

            $photoPath = 'uploads/' . uniqid() . '.' . $data['photo']->getClientOriginalExtension();

            $data['photo']->move($this->getParameter('app.upload_directory'), $photoPath);

            $news->setPhoto($photoPath);
        }

        // Сохраняем новость в базе данных
        $this->entityManager->persist($news);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $news->getId(),
            'title' => $news->getTitle(),
            'author' => $news->getAuthor(),
            'content' => $news->getContent(),
            'photo' => $news->getPhoto(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/news/{id}', name: 'news_delete', methods: ['DELETE'])]
    public function delete(string $id, NewsRepository $newsRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $news = $newsRepository->find($id);

        if (!$news) {
            return new JsonResponse(['error' => 'News not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($news);
        $entityManager->flush();

        return new JsonResponse(['status' => 'News deleted', 'id' => $id], Response::HTTP_OK);
    }

}
