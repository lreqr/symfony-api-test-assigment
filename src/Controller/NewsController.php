<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\News;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NewsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/news', name: 'news_create', methods: ['POST'])]
    public function create(Request $request, LoggerInterface $logger, ValidatorInterface $validator): JsonResponse
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

        $errors = $validator->validate($news);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $allowedMimeTypes = $this->getParameter('app.allowed_mime_types');
            $uploadDir = $this->getParameter('app.upload_directory');
            if (!in_array($data['photo']->getClientMimeType(), $allowedMimeTypes)) {
                return new JsonResponse(['error' => 'Invalid file type. Only JPEG and PNG are allowed.'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $photoPath = 'uploads/' . uniqid() . '.' . $data['photo']->getClientOriginalExtension();
                $data['photo']->move($uploadDir, $photoPath);
                $news->setPhoto($photoPath);
            } catch (FileException $e) {
                $logger->error('File upload failed', ['exception' => $e]);
                return new JsonResponse(['error' => 'Failed to upload file'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // Сохраняем новость в базе данных
        try {
            $this->entityManager->persist($news);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $logger->error('Database error', ['exception' => $e]);
            return new JsonResponse(['error' => 'Failed to save news'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'id' => $news->getId(),
            'title' => $news->getTitle(),
            'author' => $news->getAuthor(),
            'content' => $news->getContent(),
            'photo' => $news->getPhoto(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/news/{id}', name: 'news_delete', methods: ['DELETE'])]
    public function delete(string $id, NewsRepository $newsRepository, LoggerInterface $logger): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $news = $newsRepository->find($id);

            if (!$news) {
                $logger->warning("Attempted to delete non-existent news item", ['id' => $id]);
                return new JsonResponse(['error' => 'News not found'], Response::HTTP_NOT_FOUND);
            }
            try {
                $this->entityManager->remove($news);
                $this->entityManager->flush();
                $logger->info("News item deleted successfully", ['id' => $id]);
            } catch (\Exception $e) {
                $logger->error('Database error', ['exception' => $e]);
                return new JsonResponse(['error' => 'Failed to save news'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return new JsonResponse(['status' => 'News deleted', 'id' => $id], Response::HTTP_OK);
        } catch (\Exception $e) {
            $logger->error("Error while deleting news item", [
                'exception' => $e,
                'id' => $id,
            ]);
            return new JsonResponse(['error' => 'An error occurred while deleting the news item'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/news', name: 'news_list', methods: ['GET'])]
    public function list(Request $request, NewsRepository $newsRepository, LoggerInterface $logger): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $page = max((int)$request->query->get('page', 1), 1); // Минимум 1
            $limit = min(max((int)$request->query->get('limit', 10), 1), 100); // От 1 до 100
            $filters = [
                'author' => $request->query->get('author'),
                'title' => $request->query->get('title'),
            ];

            [$newsList, $totalItems] = $newsRepository->findByFilters($filters, $page, $limit);

            $pagination = [
                'page' => $page,
                'limit' => $limit,
                'total_items' => $totalItems,
                'total_pages' => (int)ceil($totalItems / $limit),
            ];

            $data = array_map(static function ($news) {
                return [
                    'id' => $news->getId(),
                    'title' => $news->getTitle(),
                    'author' => $news->getAuthor(),
                    'content' => $news->getContent(),
                    'photo' => $news->getPhoto(),
                ];
            }, $newsList);
        } catch (\Exception $e){
            $logger->error("Error while getting news items", [
                'exception' => $e,
            ]);
            return new JsonResponse(['error' => 'Failed to find news'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'filters' => $filters,
            'pagination' => $pagination,
            'data' => $data,
        ], Response::HTTP_OK);
    }


}
