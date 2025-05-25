<?php

namespace App\Controller;

use App\Entity\MediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Response;

final class CreateMediaObjectActionController extends AbstractController
{
    public function __invoke(Request $request): MediaObject
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $mediaObject = $request->attributes->get('data');

        if (!$mediaObject instanceof MediaObject) {
            $mediaObject = new MediaObject(); // For POST
        }
        // $mediaObject = new MediaObject();
        $mediaObject->setFile($uploadedFile);

        return $mediaObject;
    }
}
