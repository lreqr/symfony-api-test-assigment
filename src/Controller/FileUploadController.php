<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class FileUploadController extends AbstractController
{
    #[Route('/file/upload', name: 'app_file_upload')]
    public function upload(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $maxFileSize = $this->getParameter('app.max_file_size');
            $contentLength = $request->headers->get('Content-Length');

            if ($contentLength > $maxFileSize) {
                return new JsonResponse(
                    ['error' => 'Uploaded file exceeds the allowed size of ' . ($maxFileSize / 1024 / 1024) . ' MB.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $file = $request->files->get('file');
            if (!$file instanceof UploadedFile) {
                return new JsonResponse(
                    ['error' => 'No file provided.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $allowedMimeTypes = $this->getParameter('app.allowed_mime_types');
            if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
                return new JsonResponse(
                    ['error' => 'Invalid file type.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $uploadDirectory = $this->getParameter('app.upload_directory');
            if (!is_dir($uploadDirectory)) {
                return new JsonResponse(
                    ['error' => 'Upload directory cannot be created.'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );}

            $filename = sprintf('%s.%s', uniqid('', true), $file->guessExtension());

            try {
                $file->move($uploadDirectory, $filename);
            } catch (\Exception $e) {
                $logger->error('File upload failed: ' . $e->getMessage());
                return new JsonResponse(
                    ['error' => 'File upload failed. Please try again later.'],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            $fileUrl = $this->getParameter('app.base_upload_url') . '/' . $filename;

            return new JsonResponse(['url' => $fileUrl], Response::HTTP_CREATED);
        } catch (\Exception $e){
            $logger->error('File upload failed: ' . $e->getMessage());
            return new JsonResponse(
                ['error' => 'An unexpected error occurred. Please try again later.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
