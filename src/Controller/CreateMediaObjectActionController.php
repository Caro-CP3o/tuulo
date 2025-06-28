<?php

namespace App\Controller;

use App\Entity\MediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Response;

final class CreateMediaObjectActionController extends AbstractController
{
    /**
     * Handles the creation of a new MediaObject from an uploaded file.
     *
     * This method is called automatically by Symfony when this controller
     * is used as an invokable action (e.g., via API Platform).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @return MediaObject
     */
    public function __invoke(Request $request): MediaObject
    {
        // ---------------------------
        // Retrieve the uploaded file from the request under the key 'file'
        // ---------------------------
        $uploadedFile = $request->files->get('file');
        // ---------------------------
        // If no file was uploaded, throw a 400 Bad Request error
        // ---------------------------
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }
        // ---------------------------
        // Get the MediaObject instance from the request attributes
        // ---------------------------
        $mediaObject = $request->attributes->get('data');
        // ---------------------------
        // If the data attribute is not already a MediaObject instance (e.g., on POST), create a new one
        // ---------------------------
        if (!$mediaObject instanceof MediaObject) {
            $mediaObject = new MediaObject();
        }
        // ---------------------------
        // Set the uploaded file on the MediaObject entity and the currently logged-in user as the owner of this MediaObject
        // ---------------------------
        $mediaObject->setFile($uploadedFile);
        $mediaObject->setUser($this->getUser());
        // ---------------------------
        // Return the prepared MediaObject entity
        // ---------------------------
        return $mediaObject;
    }
}
