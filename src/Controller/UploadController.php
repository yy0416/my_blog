<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadController extends AbstractController
{
    #[Route('/upload/image', name: 'app_upload_image')]
    public function uploadImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $uploadedFile = $request->files->get('upload');

        if (!$uploadedFile) {
            return new JsonResponse([
                'uploaded' => false,
                'error' => [
                    'message' => 'No file uploaded'
                ]
            ]);
        }

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

        try {
            $uploadedFile->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );

            return new JsonResponse([
                'uploaded' => true,
                'url' => $this->getParameter('uploads_url') . '/' . $newFilename
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'uploaded' => false,
                'error' => [
                    'message' => $e->getMessage()
                ]
            ]);
        }
    }
}
