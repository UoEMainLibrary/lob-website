<?php

namespace Drupal\lob_skos\Services;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

class FileManager
{
  private FileSystemInterface $fileSystem;

  private StreamWrapperManager $streamWrapper;

  public function __construct(FileSystemInterface $fileSystem, StreamWrapperManager $streamWrapper)
  {
    $this->fileSystem = $fileSystem;
    $this->streamWrapper = $streamWrapper;
  }

  public function isValidExportDirectoryUrl($url): bool
    {
        return preg_match('#^public://.*#', $url);
    }

    public function getExportDirectoryUrl()
    {
        return 'public://';
    }

    public function getExportedFiles($directory)
    {
        if ($this->isValidExportDirectoryUrl($directory)) {
            return $this->fileSystem->scanDirectory($directory, '/.*(ttl|rdf)$/');
        } else {
            return [];
        }
    }

    public function isValidExportFileName($file): bool
    {
        return strlen($file) > 0;
    }

    public function getExportFileName($directory, $name, $extension = ".ttl"): string
    {
        if ($this->isValidExportDirectoryUrl($directory)) {
            $files = $this->fileSystem->scanDirectory($directory, '/' . $name . '.*$/');

            $n = sizeof($files);

            if ($n > 0) {
                $name = $name . '-' . $n;
            }

            return $name . $extension;
        } else {
            return '';
        }
    }

    public function getLocalFilePath($directory, $file = "")
    {
        if ($wrapper = $this->streamWrapper->getViaUri($directory)) {
            if (strlen($file) > 0) {
                return $wrapper->realpath() . DIRECTORY_SEPARATOR . $file;
            } else {
                return $wrapper->realpath();
            }
        } else {
            return '';
        }
    }

    public function getPublicFileUrl($directory, $file)
    {
        if ($wrapper = $this->streamWrapper->getViaUri($directory)) {
            return $wrapper->getExternalUrl() . $file;
        } else {
            return '';
        }
    }
}
