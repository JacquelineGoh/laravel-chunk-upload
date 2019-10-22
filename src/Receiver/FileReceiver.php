<?php

namespace Kladislav\LaravelChunkUpload\Receiver;

use Illuminate\Http\Request;
use Kladislav\LaravelChunkUpload\Config\AbstractConfig;
use Kladislav\LaravelChunkUpload\Exceptions\UploadFailedException;
use Kladislav\LaravelChunkUpload\Handler\AbstractHandler;
use Kladislav\LaravelChunkUpload\Save\AbstractSave;
use Kladislav\LaravelChunkUpload\Save\ChunkSave;
use Kladislav\LaravelChunkUpload\Save\SingleSave;
use Illuminate\Http\UploadedFile;
use Kladislav\LaravelChunkUpload\Storage\ChunkStorage;

class FileReceiver
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UploadedFile|null
     */
    protected $file;

    /**
     * The handler that detects what upload proccess is beeing used.
     *
     * @var AbstractHandler
     */
    protected $handler = null;

    /**
     * The chunk storage.
     *
     * @var ChunkStorage
     */
    protected $chunkStorage;

    /**
     * The current config.
     *
     * @var AbstractConfig
     */
    protected $config;

    /**
     * The file receiver for the given file index.
     *
     * @param string|UploadedFile $fileIndexOrFile the desired file index to use in request or the final UploadedFile
     * @param Request             $request         the current request
     * @param string              $handlerClass    the handler class name for detecting the file upload
     * @param ChunkStorage|null   $chunkStorage    the chunk storage, on null will use the instance from app container
     * @param AbstractConfig|null $config          the config, on null will use the instance from app container
     *
     * @throws UploadFailedException
     */
    public function __construct($fileIndexOrFile, Request $request, $handlerClass, $chunkStorage = null, $config = null)
    {
        $this->request = $request;
        $this->file = is_object($fileIndexOrFile) ? $fileIndexOrFile : $request->file($fileIndexOrFile);
        $this->chunkStorage = is_null($chunkStorage) ? ChunkStorage::storage() : $chunkStorage;
        $this->config = is_null($config) ? AbstractConfig::config() : $config;

        if ($this->isUploaded()) {
            if (!$this->file->isValid()) {
                throw new UploadFailedException($this->file->getErrorMessage());
            }

            $this->handler = new $handlerClass($this->request, $this->file, $this->config);
        }
    }

    /**
     * Checks if the file was uploaded.
     *
     * @return bool
     */
    public function isUploaded()
    {
        return $this->file->isValid();
    }

    /**
     * Tries to handle the upload request. If the file is not uploaded, returns false. If the file
     * is present in the request, it will create the save object.
     *
     * If the file in the request is chunk, it will create the `ChunkSave` object, otherwise creates the `SingleSave`
     * which doesn't nothing at this moment.
     *
     * @return bool|AbstractSave
     */
    public function receive()
    {
        if (false === is_object($this->handler)) {
            return false;
        }

        return $this->handler->startSaving($this->chunkStorage, $this->config);
    }
}