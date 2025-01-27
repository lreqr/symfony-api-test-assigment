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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new BadRequestHttpException('No file provided.');
        }

        $allowedMimeTypes = $this->getParameter('app.allowed_mime_types');
        if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $uploadDirectory = $this->getParameter('app.upload_directory');
        if (!is_dir($uploadDirectory)) {
            throw new \RuntimeException('Upload directory does not exist.');
        }

        $filename = sprintf('%s.%s', uniqid('', true), $file->guessExtension());

        try {
            $file->move($uploadDirectory, $filename);
        } catch (\Exception $e) {
            $logger->error('File upload failed: ' . $e->getMessage());
            throw new \RuntimeException('File upload failed. Please try again later.');
        }

        $fileUrl = $this->getParameter('app.base_upload_url') . '/' . $filename;

        return new JsonResponse(['url' => $fileUrl], Response::HTTP_CREATED);
    }

}
