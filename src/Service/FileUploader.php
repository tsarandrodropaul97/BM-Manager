<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private $targetDirectory;
    private $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file, string $subDirectory = ''): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $destination = $this->targetDirectory;
        if ($subDirectory !== '') {
            $destination .= '/' . $subDirectory;
        }

        try {
            $file->move($destination, $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
            throw new \Exception("Une erreur est survenue lors de l'upload du fichier.");
        }

        return ($subDirectory !== '' ? $subDirectory . '/' : '') . $fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}
