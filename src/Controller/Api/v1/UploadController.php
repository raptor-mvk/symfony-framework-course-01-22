<?php

namespace App\Controller\Api\v1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    #[Route(path: 'api/v1/upload', methods: ['POST'])]
    public function saveUserAction(Request $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('image');
        $file->move('upload', sprintf('%s.%s', uniqid('image', true), $file->getClientOriginalExtension()));

        return new JsonResponse(['filename' => $file->getRealPath()]);
    }
}
